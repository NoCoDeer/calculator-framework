<?php
$enabled = get_option('cf_loan_enabled', true);
if ($enabled) : ?>
    <div class="cf-calculator" data-module="loan">
        <?php
        $hide_title = wp_cache_get('cf_loan_hide_title', 'cf_options');
        if ($hide_title === false) {
            $hide_title = get_option('cf_loan_hide_title');
            wp_cache_set('cf_loan_hide_title', $hide_title, 'cf_options', 3600);
        }
        if (!$hide_title) : ?>
            <h2><?php _e('Loan Calculator', 'calculator-framework'); ?></h2>
        <?php endif; ?>
        <form class="cf-calculator-form" action="#" method="POST">
<?php else: ?>
    <p><?php _e('Loan Calculator is disabled.', 'calculator-framework'); ?></p>
<?php endif; ?>
        <div class="cf-form-group">
            <label for="loan_amount"><?php _e('Loan Amount', 'calculator-framework'); ?>:</label>
            <input type="number" id="loan_amount" name="loan_amount" step="0.01" required>
        </div>
        <div class="cf-form-group">
            <label for="rate"><?php _e('Annual Interest Rate (%)', 'calculator-framework'); ?>:</label>
            <input type="number" id="rate" name="rate" step="0.01" required>
        </div>
        <div class="cf-form-group">
            <label for="time"><?php _e('Loan Term (Years)', 'calculator-framework'); ?>:</label>
            <input type="number" id="time" name="time" required>
        </div>
        <div class="cf-buttons">
            <button type="submit" class="cf-button"><?php _e('Calculate', 'calculator-framework'); ?></button>
            <button type="reset" class="cf-button cf-reset"><?php _e('Reset', 'calculator-framework'); ?></button>
        </div>
    </form>
    
    <div class="cf-results" style="display: none;">
        <h3><?php _e('Loan Details', 'calculator-framework'); ?></h3>
        <p class="cf-final-amount">
            <?php _e('Total Payment', 'calculator-framework'); ?>: 
            <span class="cf-final-amount-value"></span>
        </p>
        <h4><?php _e('Repayment Chart', 'calculator-framework'); ?></h4>
        <canvas class="cf-chart"></canvas>
        <h4><?php _e('Repayment Table', 'calculator-framework'); ?></h4>
        <table class="cf-table">
            <thead>
                <tr>
                    <th><?php _e('Year', 'calculator-framework'); ?></th>
                    <th><?php _e('Principal Paid', 'calculator-framework'); ?></th>
                    <th><?php _e('Interest Paid', 'calculator-framework'); ?></th>
                    <th><?php _e('Total Paid', 'calculator-framework'); ?></th>
                    <th><?php _e('Remaining Balance', 'calculator-framework'); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>