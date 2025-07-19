<?php
$enabled = get_option('cf_compound_interest_enabled', true);
if ($enabled) : ?>
    <div class="cf-calculator cf-calculator--compound-interest" data-module="compound-interest">
        <?php
        $hide_title = wp_cache_get('cf_compound_interest_hide_title', 'cf_options');
        if ($hide_title === false) {
            $hide_title = get_option('cf_compound_interest_hide_title');
            wp_cache_set('cf_compound_interest_hide_title', $hide_title, 'cf_options', 3600);
        }
        if (!$hide_title) : ?>
            <h2 class="cf-calculator__title"><?php _e('Compound Interest Calculator', 'calculator-framework'); ?></h2>
        <?php endif; ?>
        <div class="cf-calculator__container">
<?php else: ?>
    <p><?php _e('Compound Interest Calculator is disabled.', 'calculator-framework'); ?></p>
<?php endif; ?>
        <div class="cf-calculator__input">
            <form class="cf-calculator-form" action="#" method="POST">
                <div class="cf-form-group">
                    <label for="principal"><?php _e('Initial Investment', 'calculator-framework'); ?>: <span class="cf-required">*</span></label>
                    <input type="number" id="principal" name="principal" step="0.01" min="0.01" required>
                </div>
                <div class="cf-form-group">
                    <label for="rate"><?php _e('Annual Interest Rate (%)', 'calculator-framework'); ?>: <span class="cf-required">*</span></label>
                    <input type="number" id="rate" name="rate" step="0.01" min="0.01" required>
                </div>
                <div class="cf-form-group">
                    <label for="time"><?php _e('Time (Years)', 'calculator-framework'); ?>: <span class="cf-required">*</span></label>
                    <input type="number" id="time" name="time" min="1" required>
                </div>
                <div class="cf-form-group">
                    <label for="frequency"><?php _e('Compounding Frequency', 'calculator-framework'); ?>:</label>
                    <select id="frequency" name="frequency">
                        <option value="yearly"><?php _e('Yearly', 'calculator-framework'); ?></option>
                        <option value="quarterly"><?php _e('Quarterly', 'calculator-framework'); ?></option>
                        <option value="monthly"><?php _e('Monthly', 'calculator-framework'); ?></option>
                    </select>
                </div>
                <div class="cf-form-group">
                    <label for="replenishment_frequency"><?php _e('Replenishment Frequency', 'calculator-framework'); ?>:</label>
                    <select id="replenishment_frequency" name="replenishment_frequency" class="cf-replenishment-frequency">
                        <option value="none"><?php _e('No Replenishment', 'calculator-framework'); ?></option>
                        <option value="yearly"><?php _e('Yearly', 'calculator-framework'); ?></option>
                        <option value="quarterly"><?php _e('Quarterly', 'calculator-framework'); ?></option>
                        <option value="monthly"><?php _e('Monthly', 'calculator-framework'); ?></option>
                    </select>
                </div>
                <div class="cf-form-group cf-replenishment-amount" style="display: none;">
                    <label for="replenishment_amount"><?php _e('Replenishment Amount', 'calculator-framework'); ?>:</label>
                    <input type="number" id="replenishment_amount" name="replenishment_amount" step="0.01" min="0">
                </div>
                <div class="cf-buttons">
                    <button type="button" class="cf-button cf-button--secondary cf-reset"><?php _e('Reset', 'calculator-framework'); ?></button>
                    <button type="submit" class="cf-button cf-button--primary"><?php _e('Calculate', 'calculator-framework'); ?></button>
                </div>
            </form>
        </div>
        <div class="cf-calculator__output">
            <div class="cf-results" style="display: none;">
                <h3><?php _e('Amount and Term', 'calculator-framework'); ?></h3>
                <p class="cf-final-amount"><?php _e('Final Amount', 'calculator-framework'); ?>: <span class="cf-final-amount-value"></span></p>
                <h4><?php _e('Capital Growth Chart', 'calculator-framework'); ?></h4>
                <canvas class="cf-chart"></canvas>
                <h4><?php _e('Calculation Table', 'calculator-framework'); ?></h4>
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
                <button type="button" class="cf-button cf-download-pdf"><?php _e('Download PDF', 'calculator-framework'); ?></button>
            </div>
        </div>
    </div>
</div>