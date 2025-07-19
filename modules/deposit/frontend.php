<?php
$enabled = get_option('cf_deposit_enabled', true);
if ($enabled) : ?>
    <div class="cf-calculator cf-calculator--deposit" data-module="deposit">
        <?php
        $hide_title = wp_cache_get('cf_deposit_hide_title', 'cf_options');
        if ($hide_title === false) {
            $hide_title = get_option('cf_deposit_hide_title');
            wp_cache_set('cf_deposit_hide_title', $hide_title, 'cf_options', 3600);
        }
        if (!$hide_title) : ?>
            <h2 class="cf-calculator__title"><?php _e('Deposit Calculator', 'calculator-framework'); ?></h2>
        <?php endif; ?>
        <div class="cf-calculator__container">
<?php else: ?>
    <p><?php _e('Deposit Calculator is disabled.', 'calculator-framework'); ?></p>
<?php endif; ?>
        <div class="cf-calculator__input">
            <form class="cf-calculator-form" action="#" method="POST">
                <div class="cf-section">
                    <div class="cf-form-group">
                        <label for="principal"><?php _e('Deposit Amount', 'calculator-framework'); ?>: <span class="cf-required">*</span></label>
                        <input type="number" id="principal" name="principal" step="0.01" required>
                    </div>
                    <div class="cf-form-group">
                        <label for="term"><?php _e('Term', 'calculator-framework'); ?>: <span class="cf-required">*</span></label>
                        <input type="number" id="term" name="term" required>
                    </div>
                    <div class="cf-form-group">
                        <label for="term_unit"><?php _e('Term Unit', 'calculator-framework'); ?>:</label>
                        <select id="term_unit" name="term_unit">
                            <option value="month"><?php _e('Month', 'calculator-framework'); ?></option>
                            <option value="year"><?php _e('Year', 'calculator-framework'); ?></option>
                        </select>
                    </div>
                    <div class="cf-form-group">
                        <label for="start_date"><?php _e('Start Date', 'calculator-framework'); ?>: <span class="cf-required">*</span></label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="cf-form-group">
                        <label for="rate"><?php _e('Interest Rate (%)', 'calculator-framework'); ?>: <span class="cf-required">*</span></label>
                        <input type="number" id="rate" name="rate" step="0.01" required>
                    </div>
                    <div class="cf-form-group">
                        <label for="capitalize">
                            <input type="checkbox" id="capitalize" name="capitalize" value="1" checked>
                            <?php _e('Capitalize Interest', 'calculator-framework'); ?>
                            <span class="cf-tooltip">
                                <span class="dashicons dashicons-info"></span>
                                <span class="cf-tooltip-text"><?php _e('Capitalization of interest is a form of interest payment on a deposit where the accrued interest is added to the principal amount of the deposit, meaning the initial deposit is regularly increased by the amount of accrued interest, and subsequent interest is calculated on the increased amount.', 'calculator-framework'); ?></span>
                            </span>
                        </label>
                    </div>
                    <div class="cf-form-group cf-capitalization-frequency">
                        <label for="capitalization_frequency"><?php _e('Capitalization Frequency', 'calculator-framework'); ?>:</label>
                        <select id="capitalization_frequency" name="capitalization_frequency">
                            <option value="daily"><?php _e('Daily', 'calculator-framework'); ?></option>
                            <option value="monthly"><?php _e('Monthly', 'calculator-framework'); ?></option>
                            <option value="quarterly"><?php _e('Quarterly', 'calculator-framework'); ?></option>
                            <option value="yearly"><?php _e('Yearly', 'calculator-framework'); ?></option>
                            <option value="end"><?php _e('At the End of Term', 'calculator-framework'); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Кнопки для пополнений и снятий -->
                <div class="cf-section cf-actions">
                    <h3 class="cf-section__title"><?php _e('Replenishments and Withdrawals', 'calculator-framework'); ?></h3>
                    <div class="cf-action-buttons">
                        <button type="button" class="cf-toggle-replenishment cf-button cf-button--secondary"><?php _e('Add Replenishment', 'calculator-framework'); ?></button>
                        <button type="button" class="cf-toggle-withdrawal cf-button cf-button--secondary"><?php _e('Add Withdrawal', 'calculator-framework'); ?></button>
                    </div>

<!-- Форма для пополнения -->
<div class="cf-replenishment-form" style="display: none;">
    <h4><?php _e('Add Replenishment', 'calculator-framework'); ?></h4>
    <div class="cf-form-group">
        <label><?php _e('Replenishment Frequency', 'calculator-framework'); ?>:</label>
        <select name="replenishment_frequency" class="cf-replenishment-frequency">
            <option value="once"><?php _e('One-Time', 'calculator-framework'); ?></option>
            <option value="monthly"><?php _e('Monthly', 'calculator-framework'); ?></option>
            <option value="bimonthly"><?php _e('Every 2 Months', 'calculator-framework'); ?></option>
            <option value="quarterly"><?php _e('Quarterly', 'calculator-framework'); ?></option>
            <option value="semiannually"><?php _e('Semiannually', 'calculator-framework'); ?></option>
            <option value="yearly"><?php _e('Yearly', 'calculator-framework'); ?></option>
        </select>
    </div>
    <div class="cf-form-group">
        <label><?php _e('Replenishment Date', 'calculator-framework'); ?>:</label>
        <input type="date" name="replenishment_date" class="cf-replenishment-date" required>
    </div>
    <div class="cf-form-group">
        <label><?php _e('Replenishment Amount', 'calculator-framework'); ?>:</label>
        <input type="number" name="replenishment_amount" step="0.01" required>
    </div>
    <div class="cf-form-buttons">
        <button type="button" class="cf-cancel-replenishment cf-button cf-button--secondary"><?php _e('Cancel', 'calculator-framework'); ?></button>
        <button type="button" class="cf-add-replenishment cf-button cf-button--primary"><?php _e('Add', 'calculator-framework'); ?></button>
    </div>
</div>

<!-- Форма для снятия -->
<div class="cf-withdrawal-form" style="display: none;">
    <h4><?php _e('Add Withdrawal', 'calculator-framework'); ?></h4>
    <div class="cf-form-group">
        <label><?php _e('Withdrawal Frequency', 'calculator-framework'); ?>:</label>
        <select name="withdrawal_frequency" class="cf-withdrawal-frequency">
            <option value="once"><?php _e('One-Time', 'calculator-framework'); ?></option>
            <option value="monthly"><?php _e('Monthly', 'calculator-framework'); ?></option>
            <option value="bimonthly"><?php _e('Every 2 Months', 'calculator-framework'); ?></option>
            <option value="quarterly"><?php _e('Quarterly', 'calculator-framework'); ?></option>
            <option value="semiannually"><?php _e('Semiannually', 'calculator-framework'); ?></option>
            <option value="yearly"><?php _e('Yearly', 'calculator-framework'); ?></option>
        </select>
    </div>
    <div class="cf-form-group">
        <label><?php _e('Withdrawal Date', 'calculator-framework'); ?>:</label>
        <input type="date" name="withdrawal_date" class="cf-withdrawal-date" required>
    </div>
    <div class="cf-form-group">
        <label><?php _e('Withdrawal Amount', 'calculator-framework'); ?>:</label>
        <input type="number" name="withdrawal_amount" step="0.01" required>
    </div>
    <div class="cf-form-buttons">
        <button type="button" class="cf-cancel-withdrawal cf-button cf-button--secondary"><?php _e('Cancel', 'calculator-framework'); ?></button>
        <button type="button" class="cf-add-withdrawal cf-button cf-button--primary"><?php _e('Add', 'calculator-framework'); ?></button>
    </div>
</div>

                    <!-- Список для отображения пополнений и снятий -->
                    <div class="cf-transaction-list"></div>
                </div>

                <div class="cf-buttons">
                    <button type="button" class="cf-button cf-button--secondary cf-reset"><?php _e('Reset', 'calculator-framework'); ?></button>
                    <button type="submit" class="cf-button cf-button--primary"><?php _e('Calculate', 'calculator-framework'); ?></button>
                </div>
            </form>
        </div>
        <div class="cf-calculator__output">
            <div class="cf-results" style="display: none;">
                <h3><?php _e('Deposit Summary', 'calculator-framework'); ?></h3>
                <div class="cf-summary">
                    <div class="cf-summary__item">
                        <p class="cf-summary__label"><?php _e('Deposit Amount', 'calculator-framework'); ?></p>
                        <p class="cf-summary__value cf-deposit-amount-value"></p>
                    </div>
                    <div class="cf-summary__item">
                        <p class="cf-summary__label"><?php _e('Final Amount', 'calculator-framework'); ?></p>
                        <p class="cf-summary__value cf-final-amount-value"></p>
                    </div>
                    <div class="cf-summary__item">
                        <p class="cf-summary__label"><?php _e('Accrued Interest', 'calculator-framework'); ?></p>
                        <p class="cf-summary__value cf-total-interest-value"></p>
                    </div>
                    <div class="cf-summary__item">
                        <p class="cf-summary__label"><?php _e('Capital Growth', 'calculator-framework'); ?></p>
                        <p class="cf-summary__value cf-capital-growth-value"></p>
                    </div>
                </div>
                <div class="cf-doughnut-chart">
                    <canvas class="cf-doughnut"></canvas>
                    <div class="cf-doughnut-legend">
                        <div class="cf-legend-item">
                            <span class="cf-legend-color cf-legend-color--principal"></span>
                            <span class="cf-legend-label"><?php _e('Deposit Amount', 'calculator-framework'); ?></span>
                        </div>
                        <div class="cf-legend-item">
                            <span class="cf-legend-color cf-legend-color--interest"></span>
                            <span class="cf-legend-label"><?php _e('Accrued Interest', 'calculator-framework'); ?></span>
                        </div>
                    </div>
                </div>
                <h4><?php _e('Interest Accrual Table', 'calculator-framework'); ?></h4>
                <table class="cf-table">
                    <thead>
                        <tr>
                            <th><?php _e('No.', 'calculator-framework'); ?></th>
                            <th><?php _e('Date', 'calculator-framework'); ?></th>
                            <th><?php _e('Replenishment', 'calculator-framework'); ?></th>
                            <th><?php _e('Withdrawal', 'calculator-framework'); ?></th>
                            <th><?php _e('Accrued Interest', 'calculator-framework'); ?></th>
                            <th><?php _e('Balance', 'calculator-framework'); ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>