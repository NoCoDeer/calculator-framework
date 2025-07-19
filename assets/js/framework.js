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
        performCalculation(container);
    }

    function downloadPDF(container) {
        const results = container.find('.cf-results')[0];
        if (!results) {
            return;
        }
        const opt = {
            margin: 10,
            filename: 'calculator-results.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(results).save();
    }

    function renderBarChart(chartCanvas, result) {
        const ctx = chartCanvas.getContext('2d');
        const datasets = [
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
        ];

        if (result.chart.required_principal_data && result.chart.required_interest_data) {
            datasets.push({
                label: cfAjax.translations.required_principal_label,
                data: result.chart.required_principal_data,
                backgroundColor: 'rgba(54, 162, 235, 0.3)',
            });
            datasets.push({
                label: cfAjax.translations.required_interest_label,
                data: result.chart.required_interest_data,
                backgroundColor: 'rgba(153, 102, 255, 0.3)',
            });
            datasets.push({
                label: cfAjax.translations.required_total_label,
                data: result.chart.required_total_data,
                backgroundColor: 'rgba(0, 0, 0, 0.1)',
                hidden: true
            });
        }

        chartCanvas.chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: result.chart.labels,
                datasets: datasets
            },
            options: {
                locale: 'ru-RU',
                scales: {
                    x: { 
                        stacked: true,
                        title: { display: true, text: cfAjax.translations.years_label }
                    },
                    y: { 
                        stacked: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                        },
                        title: { display: true, text: cfAjax.translations.amount_label }
                    }
                },
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    function renderDoughnutChart(chartCanvas, result) {
        const ctx = chartCanvas.getContext('2d');
        const principal = result.total_principal || 0;
        const interest = result.total_interest || 0;

        chartCanvas.chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [cfAjax.translations.principal_label, cfAjax.translations.interest_label],
                datasets: [{
                    data: [principal, interest],
                    backgroundColor: [cfAjax.chart_colors.principal, cfAjax.chart_colors.interest],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '70%',
                plugins: {
                    legend: { display: false },
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

        formData.forEach(item => {
            if (module === 'deposit' && (item.name.startsWith('replenishment_') || item.name.startsWith('withdrawal_'))) {
                // Skip deposit transaction fields, they are processed separately
                return;
            }
            data[item.name] = item.value;
        });

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
                data.replenishments.push({ frequency: frequency, date: date, amount: amount });
            } else if (type === 'withdrawal') {
                data.withdrawals.push({ frequency: frequency, date: date, amount: amount });
            }
        });

        // Валидация для goals
        if (module === 'goals') {
            const years = parseFloat(data.years || 0);
            const inflationRate = parseFloat(data.inflation_rate || 0);
            const returnRate = parseFloat(data.return_rate || 0);
            const initialCapital = parseFloat(data.initial_capital || 0);

            if (years <= 0 || inflationRate < 0 || returnRate < 0 || initialCapital < 0) {
                console.log('Validation failed for goals:', { years, inflationRate, returnRate, initialCapital });
                alert(cfAjax.translations.invalid_input);
                return;
            }

            if (data.mode === 'savings') {
                const targetAmount = parseFloat(data.target_amount || 0);
                if (targetAmount <= 0) {
                    console.log('Invalid target_amount:', targetAmount);
                    alert(cfAjax.translations.invalid_input);
                    return;
                }
            } else {
                const monthlyIncome = parseFloat(data.monthly_income || 0);
                if (monthlyIncome <= 0) {
                    console.log('Invalid monthly_income:', monthlyIncome);
                    alert(cfAjax.translations.invalid_input);
                    return;
                }
            }
        } else if (module === 'compound-interest') {
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
        } else if (module === 'deposit') {
            const principal = parseFloat(data.principal || 0);
            const rate = parseFloat(data.rate || 0);
            const term = parseFloat(data.term || 0);
            if (isNaN(principal) || isNaN(rate) || isNaN(term) || principal <= 0 || rate <= 0 || term <= 0) {
                console.log('Validation failed for deposit:', { principal, rate, term });
                alert(cfAjax.translations.invalid_input);
                return;
            }
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
    if (container.hasClass('cf-calculator--goals')) {
        const resultsTitle = container.find('.cf-results__title');

        // Fallback для переводов
        const translations = cfAjax.translations || {};
        const savingsSummary = translations.savings_summary || 'Чтобы через %years% лет накопить %target_amount%, вам нужно вносить:';
        const passiveSummary = translations.passive_summary || 'Чтобы через %years% лет ежемесячно получать пассивный доход %monthly_income%, вам нужно накопить:';
        const targetWithInflation = translations.target_with_inflation || 'Сумма цели с учетом инфляции';
        const targetToday = translations.target_today || 'Эквивалент на сегодняшний день';
        const contributionsTitle = translations.contributions_title || 'Чтобы достигнуть цели, вам нужно вносить:';
        const monthly = translations.monthly || 'Ежемесячно';
        const yearly = translations.yearly || 'Ежегодно';
        const lumpSum = translations.lump_sum || 'Единоразово';

        // Обновляем заголовок
        if (result.mode === 'savings') {
            const targetAmount = parseFloat(container.find('input[name="target_amount"]').val()) || 0;
            resultsTitle.text(
                savingsSummary
                    .replace('%years%', result.years)
                    .replace('%target_amount%', targetAmount.toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }))
            );
        } else {
            const monthlyIncome = parseFloat(container.find('input[name="monthly_income"]').val()) || 0;
            resultsTitle.text(
                passiveSummary
                    .replace('%years%', result.years)
                    .replace('%monthly_income%', monthlyIncome.toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }))
            );
        }

        // Заполняем карточки с проверкой на наличие данных
        container.find('#target-with-inflation').text(
            (result.target_with_inflation || 0).toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        );
        container.find('#target-today').text(
            (result.target_today || 0).toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        );
        container.find('#monthly-contribution').text(
            (result.monthly_contribution || 0).toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        );
        container.find('#yearly-contribution').text(
            (result.yearly_contribution || 0).toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        );
        container.find('#lump-sum').text(
            (result.lump_sum || 0).toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        );

        container.find('.cf-results').show();
    } else if (container.hasClass('cf-calculator--deposit')) {
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

        if (container.hasClass('cf-calculator--savings')) {
            container.find('.cf-target-amount-value').text(result.target_amount.toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

            const monthlyInvestment = parseFloat(container.find('input[name="monthly_investment"]').val()) || 0;
            const $currentMessage = container.find('.cf-savings-current-message');
            const $requiredMessage = container.find('.cf-savings-required-message');
            $currentMessage.text(
                cfAjax.translations.savings_current_message
                    .replace('%s', monthlyInvestment.toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }))
                    .replace('%s', result.final_amount < result.target_amount ? cfAjax.translations.insufficient : cfAjax.translations.sufficient)
            );
            $requiredMessage.text(
                cfAjax.translations.savings_required_message
                    .replace('%s', result.required_monthly_investment.toLocaleString('be-BY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }))
                    .replace('%s', cfAjax.translations.sufficient)
            );
        }

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
    // Инициализация калькуляторов
    $('.cf-calculator').each(function() {
        const container = $(this);
        console.log('Initializing calculator:', container.data('module'));

        if (container.find('.cf-replenishment-frequency').length) {
            toggleReplenishmentAmount(container);
            container.find('.cf-replenishment-frequency').on('change', function() {
                console.log('Replenishment frequency changed');
                toggleReplenishmentAmount(container);
            });
        }

        if (container.find('#capitalize').length) {
            toggleCapitalizationFrequency(container);
            container.find('#capitalize').on('change', function() {
                toggleCapitalizationFrequency(container);
            });
        }

        container.find('.cf-toggle-replenishment').on('click', function() {
            const form = container.find('.cf-replenishment-form');
            form.slideToggle(300);
            container.find('.cf-withdrawal-form').slideUp(300);
        });

        container.find('.cf-cancel-replenishment').on('click', function() {
            container.find('.cf-replenishment-form').slideUp(300);
        });

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

        container.find('.cf-toggle-withdrawal').on('click', function() {
            const form = container.find('.cf-withdrawal-form');
            form.slideToggle(300);
            container.find('.cf-replenishment-form').slideUp(300);
        });

        container.find('.cf-cancel-withdrawal').on('click', function() {
            container.find('.cf-withdrawal-form').slideUp(300);
        });

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

        container.on('click', '.cf-remove-transaction', function() {
            $(this).closest('.cf-transaction-item').remove();
            performCalculation(container);
        });

        const debouncedPerformCalculation = debounce(() => performCalculation(container), 300);

        container.find('.cf-calculator-form').on('submit', function(e) {
            e.preventDefault();
            console.log('Form submit event triggered for:', container.data('module'));
            debouncedPerformCalculation();
        });

        container.find('.cf-button[type="submit"]').on('click', function(e) {
            e.preventDefault();
            console.log('Calculate button clicked for:', container.data('module'));
            debouncedPerformCalculation();
        });

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

        container.find('.cf-download-pdf').on('click', function(e) {
            e.preventDefault();
            downloadPDF(container);
        });
    });
});