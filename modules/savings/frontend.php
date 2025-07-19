<?php
$enabled = get_option('cf_savings_enabled', true);
if ($enabled) : ?>
    <div class="cf-calculator cf-calculator--savings" data-module="savings">
        <?php
        $hide_title = wp_cache_get('cf_savings_hide_title', 'cf_options');
        if ($hide_title === false) {
            $hide_title = get_option('cf_savings_hide_title');
            wp_cache_set('cf_savings_hide_title', $hide_title, 'cf_options', 3600);
        }
        if (!$hide_title) : ?>
            <h2 class="cf-title"><?php _e('Savings Calculator', 'calculator-framework'); ?></h2>
        <?php endif; ?>
        <div class="cf-calculator__container">
<?php else: ?>
    <p><?php _e('Savings Calculator is disabled.', 'calculator-framework'); ?></p>
<?php endif; ?>
        <div class="cf-calculator__input">
            <form class="cf-calculator-form" action="#" method="POST">
                <div class="cf-form-group">
                    <label for="target_amount"><?php _e('Target Amount', 'calculator-framework'); ?>: <span class="cf-required">*</span></label>
                    <input type="number" id="target_amount" name="target_amount" step="0.01" min="0.01" required>
                </div>
                <div class="cf-form-group">
                    <label for="calculation_unit"><?php _e('Calculate By', 'calculator-framework'); ?>:</label>
                    <select id="calculation_unit" name="calculation_unit">
                        <option value="month"><?php _e('Months', 'calculator-framework'); ?></option>
                        <option value="year"><?php _e('Years', 'calculator-framework'); ?></option>
                    </select>
                </div>
                <div class="cf-form-group">
                    <label for="period"><?php _e('Savings Period', 'calculator-framework'); ?>: <span class="cf-required">*</span></label>
                    <input type="number" id="period" name="period" min="1" required>
                </div>
                <div class="cf-form-group">
                    <label for="current_amount"><?php _e('Current Amount Saved', 'calculator-framework'); ?>:</label>
                    <input type="number" id="current_amount" name="current_amount" step="0.01" min="0" value="0">
                </div>
                <div class="cf-form-group">
                    <label for="monthly_investment"><?php _e('Monthly Investment', 'calculator-framework'); ?>:</label>
                    <input type="number" id="monthly_investment" name="monthly_investment" step="0.01" min="0" value="0">
                </div>
                <div class="cf-form-group">
                    <label for="expected_return"><?php _e('Expected Return (%)', 'calculator-framework'); ?>:</label>
                    <input type="number" id="expected_return" name="expected_return" step="0.01" min="0" value="0">
                </div>
                <div class="cf-buttons">
                    <button type="button" class="cf-button cf-button--secondary cf-reset"><?php _e('Reset', 'calculator-framework'); ?></button>
                    <button type="submit" class="cf-button cf-button--primary"><?php _e('Calculate', 'calculator-framework'); ?></button>
                </div>
            </form>
        </div>
        <div class="cf-calculator__output">
            <div class="cf-results" style="display: none;">
                <h3 class="cf-subtitle"><?php _e('Savings Summary', 'calculator-framework'); ?></h3>
                <p class="cf-final-amount"><?php _e('Final Amount', 'calculator-framework'); ?>: <span class="cf-final-amount-value"></span></p>
                <p class="cf-target-amount"><?php _e('Target Amount', 'calculator-framework'); ?>: <span class="cf-target-amount-value"></span></p>
                <h4 class="cf-subtitle"><?php _e('Savings Growth Chart', 'calculator-framework'); ?></h4>
                <canvas class="cf-chart"></canvas>
                <div class="cf-savings-messages">
                    <p class="cf-savings-current-message"></p>
                    <p class="cf-savings-required-message"></p>
                </div>
                <h4 class="cf-subtitle"><?php _e('Savings Table', 'calculator-framework'); ?></h4>
                <table class="cf-table">
                    <thead>
                        <tr>
                            <th><?php _e('Year', 'calculator-framework'); ?></th>
                            <th><?php _e('Your Investment', 'calculator-framework'); ?></th>
                            <th><?php _e('Total Interest', 'calculator-framework'); ?></th>
                            <th><?php _e('Total Income', 'calculator-framework'); ?></th>
                            <th><?php _e('Final Balance', 'calculator-framework'); ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>