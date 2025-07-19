jQuery(document).ready(function($) {
    console.log('Calculator Framework JS Loaded');

    const ajaxCache = {};

    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    function toggleReplenishmentAmount(container) {
        const replenishmentFrequency = container.find('.cf-replenishment-frequency').val();
        console.log('Replenishment Frequency:', replenishmentFrequency);

        if (replenishmentFrequency === 'none') {
            console.log('Hiding replenishment amount field');
            container.find('.cf-replenishment-amount').hide();
            container.find('.cf-replenishment-amount input').val(0);
        } else {
            console.log('Showing replenishment amount field');
            container.find('.cf-replenishment-amount').show();
        }
    }

    function toggleCapitalizationFrequency(container) {
        const capitalize = container.find('#capitalize').is(':checked');
        if (capitalize) {
            container.find('.cf-capitalization-frequency').show();
        } else {
            container.find('.cf-capitalization-frequency').hide();
        }
    }

    function addTransaction(container, type, frequency, date, amount) {
        const list = container.find('.cf-transaction-list');
        const index = list.find('.cf-transaction-item').length;
        const frequencyText = container.find(`select[name="${type}_frequency"] option[value="${frequency}"]`).text();
        const formattedAmount = parseFloat(amount).toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const label = type === 'replenishment' 
            ? (cfAjax.translations.replenishment_label || 'Пополнение') 
            : (cfAjax.translations.withdrawal_label || 'Снятие');
        const item = `
            <div class="cf-transaction-item" role="button" data-index="${index}" data-type="${type}" data-frequency="${frequency}">
                <div class="cf-transaction-content">
                    <p class="cf-transaction-label">${label}</p>
                    <div class="cf-transaction-details">
                        <p class="cf-transaction-detail">${frequencyText}</p>
                        <p class="cf-transaction-detail">${formattedAmount}</p>
                        <p class="cf-transaction-detail">${date}</p>
                    </div>
                </div>
                <div class="cf-transaction-actions">
                    <button type="button" class="cf-remove-transaction cf-button cf-button--icon">
                        <span class="cf-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 4L4 20M4 4l16 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </span>
                    </button>
                </div>
            </div>
        `;
        list.append(item);
        performCalculation(container); // Пересчитываем сразу после добавления
    }

    function renderBarChart(chartCanvas, result) {
        const ctx = chartCanvas.getContext('2d');
        chartCanvas.chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: result.chart.labels,
                datasets: [
                    {
                        label: cfAjax.translations.principal_label,
                        data: result.chart.principal_data,
                        backgroundColor: cfAjax.chart_colors.principal,
                    },
                    {
                        label: cfAjax.translations.interest_label,
                        data: result.chart.interest_data,
                        backgroundColor: cfAjax.chart_colors.interest,
                    },
                    {
                        label: cfAjax.translations.total_label,
                        data: result.chart.total_data,
                        backgroundColor: cfAjax.chart_colors.total,
                        hidden: true
                    }
                ]
            },
            options: {
                locale: 'ru-RU',
                scales: {
                    x: { 
                        stacked: true,
                        title: {
                            display: true,
                            text: cfAjax.translations.years_label
                        }
                    },
                    y: { 
                        stacked: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                        },
                        title: {
                            display: true,
                            text: cfAjax.translations.amount_label
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    function renderDoughnutChart(chartCanvas, result) {
        const ctx = chartCanvas.getContext('2d');
        const principal = result.total_principal || 0;
        const interest = result.total_interest || 0;
        const total = principal + interest;
        const principalPercent = total > 0 ? (principal / total * 100).toFixed(1) : 0;
        const interestPercent = total > 0 ? (interest / total * 100).toFixed(1) : 0;

        chartCanvas.chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [
                    cfAjax.translations.principal_label,
                    cfAjax.translations.interest_label
                ],
                datasets: [{
                    data: [principal, interest],
                    backgroundColor: [
                        cfAjax.chart_colors.principal,
                        cfAjax.chart_colors.interest
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                return `${label}: ${value.toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                            }
                        }
                    }
                }
            }
        });
    }

function performCalculation(container) {
    console.log('Performing calculation for module:', container.data('module'));

    const module = container.data('module');
    const formData = container.find('.cf-calculator-form').serializeArray();
    const data = { module: module, replenishments: [], withdrawals: [] };

    // Собираем данные из формы
    formData.forEach(item => {
        if (!item.name.startsWith('replenishment_') && !item.name.startsWith('withdrawal_')) {
            data[item.name] = item.value;
        }
    });

    // Собираем данные из списка транзакций (для deposit)
    container.find('.cf-transaction-list .cf-transaction-item').each(function(index) {
        const item = $(this);
        const type = item.data('type');
        const frequency = item.data('frequency');
        const amountText = item.find('.cf-transaction-detail').eq(1).text().replace(/\s/g, '').replace(',', '.');
        const amount = parseFloat(amountText);
        const date = item.find('.cf-transaction-detail').eq(2).text();

        if (isNaN(amount) || amount <= 0) {
            console.error('Invalid amount for transaction:', amountText);
            return;
        }

        if (type === 'replenishment') {
            data.replenishments.push({
                frequency: frequency,
                date: date,
                amount: amount
            });
        } else if (type === 'withdrawal') {
            data.withdrawals.push({
                frequency: frequency,
                date: date,
                amount: amount
            });
        }
    });

    // Валидация в зависимости от модуля
    if (module === 'compound-interest') {
        const principal = parseFloat(data.principal || 0);
        const rate = parseFloat(data.rate || 0);
        const time = parseFloat(data.time || 0);
        const replenishmentAmount = parseFloat(data.replenishment_amount || 0);

        if (isNaN(principal) || isNaN(rate) || isNaN(time) || principal <= 0 || rate <= 0 || time <= 0) {
            console.log('Validation failed for compound-interest:', { principal, rate, time });
            alert(cfAjax.translations.invalid_input);
            return;
        }
        if (replenishmentAmount < 0) {
            console.log('Invalid replenishment_amount:', replenishmentAmount);
            alert(cfAjax.translations.invalid_input);
            return;
        }

        data.frequency = data.frequency || 'yearly';
        data.replenishment_frequency = data.replenishment_frequency || 'none';
    } else if (module === 'savings') {
        const targetAmount = parseFloat(data.target_amount || 0);
        const period = parseFloat(data.period || 0);
        const currentAmount = parseFloat(data.current_amount || 0);
        const monthlyInvestment = parseFloat(data.monthly_investment || 0);
        const expectedReturn = parseFloat(data.expected_return || 0);

        if (isNaN(targetAmount) || isNaN(period) || targetAmount <= 0 || period <= 0) {
            console.log('Validation failed for savings:', { targetAmount, period });
            alert(cfAjax.translations.invalid_input);
            return;
        }
        if (currentAmount < 0 || monthlyInvestment < 0 || expectedReturn < 0) {
            console.log('Invalid values for savings:', { currentAmount, monthlyInvestment, expectedReturn });
            alert(cfAjax.translations.invalid_input);
            return;
        }

        data.calculation_unit = data.calculation_unit || 'month';
    } else {
        // Для deposit и других модулей
        const principal = parseFloat(data.principal || 0);
        const rate = parseFloat(data.rate || 0);
        const term = parseFloat(data.term || 0);
        if (isNaN(principal) || isNaN(rate) || isNaN(term) || principal <= 0 || rate <= 0 || term <= 0) {
            console.log('Validation failed for module:', module, { principal, rate, term });
            alert(cfAjax.translations.invalid_input);
            return;
        }

        if (module === 'deposit') {
            const startDate = data.start_date || '';
            if (!startDate) {
                console.log('Missing start_date for deposit');
                alert(cfAjax.translations.invalid_input);
                return;
            }
            data.term_unit = data.term_unit || 'month';
            data.capitalize = data.capitalize || '1';
            data.capitalization_frequency = data.capitalization_frequency || 'end';
        }
    }

    // Логируем данные для отладки
    console.log('Data sent to server:', data);

    const cacheKey = JSON.stringify(data);

    if (ajaxCache[cacheKey]) {
        console.log('Using cached result');
        const result = ajaxCache[cacheKey];
        displayResults(container, result);
        return;
    }

    data.action = 'cf_calculate';
    data.nonce = cfAjax.nonce;

    $.ajax({
        url: cfAjax.ajaxurl,
        type: 'POST',
        data: data,
        success: function(response) {
            console.log('AJAX Success:', response);
            if (response.success) {
                const result = response.data;
                ajaxCache[cacheKey] = result;
                displayResults(container, result);
            } else {
                alert(cfAjax.translations.error + ': ' + (response.data.message || cfAjax.translations.calculation_failed));
            }
        },
        error: function(xhr, status, error) {
            console.log('AJAX Error:', status, error);
            alert(cfAjax.translations.ajax_error);
        }
    });
}

    function displayResults(container, result) {
        if (container.hasClass('cf-calculator--deposit')) {
            const principal = result.total_principal || 0;
            const interest = result.total_interest || 0;
            const finalAmount = result.final_amount || 0;
            const capitalGrowth = principal > 0 ? ((finalAmount - principal) / principal * 100).toFixed(1) : 0;

            container.find('.cf-deposit-amount-value').text(principal.toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            container.find('.cf-final-amount-value').text(finalAmount.toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            container.find('.cf-total-interest-value').text(interest.toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            container.find('.cf-capital-growth-value').text(capitalGrowth + '%');

            const tbody = container.find('.cf-table tbody');
            tbody.empty();
            const rows = result.table.map((row, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${row.period}</td>
                    <td>${row.replenishment.toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td>${row.withdrawal.toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td>${row.interest.toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td>${row.total.toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                </tr>
            `).join('');
            tbody.html(rows);

            const doughnutCanvas = container.find('.cf-doughnut')[0];
            if (doughnutCanvas.chart) {
                doughnutCanvas.chart.destroy();
            }

            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        renderDoughnutChart(doughnutCanvas, result);
                        observer.unobserve(doughnutCanvas);
                    }
                });
            }, { rootMargin: '0px 0px 100px 0px' });
            observer.observe(doughnutCanvas);
        } else {
            container.find('.cf-final-amount-value').text(result.final_amount.toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

            const tbody = container.find('.cf-table tbody');
            tbody.empty();
            const rows = result.table.map(row => `
                <tr>
                    <td>${row.period}</td>
                    <td>${row.principal.toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td>${row.interest.toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td>${(row.interest + row.principal).toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td>${row.total.toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                </tr>
            `).join('');
            tbody.html(rows);

            const chartCanvas = container.find('.cf-chart')[0];
            if (chartCanvas.chart) {
                chartCanvas.chart.destroy();
            }

            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        renderBarChart(chartCanvas, result);
                        observer.unobserve(chartCanvas);
                    }
                });
            }, { rootMargin: '0px 0px 100px 0px' });
            observer.observe(chartCanvas);
        }

        container.find('.cf-results').show();
    }

    // Initialize each calculator on the page
    $('.cf-calculator').each(function() {
        const container = $(this);
        console.log('Initializing calculator:', container.data('module'));

        // Toggle replenishment amount field if present
        if (container.find('.cf-replenishment-frequency').length) {
            toggleReplenishmentAmount(container);
            container.find('.cf-replenishment-frequency').on('change', function() {
                console.log('Replenishment frequency changed');
                toggleReplenishmentAmount(container);
            });
        }

        // Toggle capitalization frequency
        if (container.find('#capitalize').length) {
            toggleCapitalizationFrequency(container);
            container.find('#capitalize').on('change', function() {
                toggleCapitalizationFrequency(container);
            });
        }

        // Toggle replenishment form
        container.find('.cf-toggle-replenishment').on('click', function() {
            const form = container.find('.cf-replenishment-form');
            form.slideToggle(300);
            container.find('.cf-withdrawal-form').slideUp(300);
        });

        // Cancel replenishment form
        container.find('.cf-cancel-replenishment').on('click', function() {
            container.find('.cf-replenishment-form').slideUp(300);
        });

        // Add replenishment
        container.find('.cf-add-replenishment').on('click', function() {
            const frequency = container.find('select[name="replenishment_frequency"]').val();
            const date = container.find('input[name="replenishment_date"]').val();
            const amount = container.find('input[name="replenishment_amount"]').val();

            if (!date || !amount || amount <= 0) {
                alert(cfAjax.translations.invalid_input);
                return;
            }

            addTransaction(container, 'replenishment', frequency, date, amount);
            container.find('input[name="replenishment_date"]').val('');
            container.find('input[name="replenishment_amount"]').val('');
            container.find('.cf-replenishment-form').slideUp(300);
        });

        // Toggle withdrawal form
        container.find('.cf-toggle-withdrawal').on('click', function() {
            const form = container.find('.cf-withdrawal-form');
            form.slideToggle(300);
            container.find('.cf-replenishment-form').slideUp(300);
        });

        // Cancel withdrawal form
        container.find('.cf-cancel-withdrawal').on('click', function() {
            container.find('.cf-withdrawal-form').slideUp(300);
        });

        // Add withdrawal
        container.find('.cf-add-withdrawal').on('click', function() {
            const frequency = container.find('select[name="withdrawal_frequency"]').val();
            const date = container.find('input[name="withdrawal_date"]').val();
            const amount = container.find('input[name="withdrawal_amount"]').val();

            if (!date || !amount || amount <= 0) {
                alert(cfAjax.translations.invalid_input);
                return;
            }

            addTransaction(container, 'withdrawal', frequency, date, amount);
            container.find('input[name="withdrawal_date"]').val('');
            container.find('input[name="withdrawal_amount"]').val('');
            container.find('.cf-withdrawal-form').slideUp(300);
        });

        // Remove transaction
        container.on('click', '.cf-remove-transaction', function() {
            $(this).closest('.cf-transaction-item').remove();
            performCalculation(container);
        });

        // Debounced calculation
        const debouncedPerformCalculation = debounce(() => performCalculation(container), 300);

        // Handle form submission
        container.find('.cf-calculator-form').on('submit', function(e) {
            e.preventDefault();
            console.log('Form submit event triggered for:', container.data('module'));
            debouncedPerformCalculation();
        });

        // Fallback: Handle button click
        container.find('.cf-button[type="submit"]').on('click', function(e) {
            e.preventDefault();
            console.log('Calculate button clicked for:', container.data('module'));
            debouncedPerformCalculation();
        });

        // Reset form
        container.find('.cf-reset').on('click', function(e) {
            e.preventDefault();
            console.log('Reset button clicked for:', container.data('module'));

            container.find('.cf-calculator-form')[0].reset();
            container.find('.cf-results').hide();
            container.find('.cf-table tbody').empty();
            container.find('.cf-transaction-list').empty();

            const chartCanvas = container.find('.cf-chart')[0];
            if (chartCanvas && chartCanvas.chart) {
                chartCanvas.chart.destroy();
            }

            const doughnutCanvas = container.find('.cf-doughnut')[0];
            if (doughnutCanvas && doughnutCanvas.chart) {
                doughnutCanvas.chart.destroy();
            }

            if (container.find('.cf-replenishment-frequency').length) {
                toggleReplenishmentAmount(container);
            }
            if (container.find('#capitalize').length) {
                toggleCapitalizationFrequency(container);
            }
        });
    });
});