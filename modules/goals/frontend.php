<?php
$enabled = get_option('cf_goals_enabled', true);
if ($enabled) : ?>
    <div class="cf-calculator cf-calculator--goals" data-module="goals">
        <?php
        $hide_title = wp_cache_get('cf_goals_hide_title', 'cf_options');
        if ($hide_title === false) {
            $hide_title = get_option('cf_goals_hide_title');
            wp_cache_set('cf_goals_hide_title', $hide_title, 'cf_options', 3600);
        }
        if (!$hide_title) : ?>
            <h2 class="cf-calculator__title"><?php _e('Goals Calculator', 'calculator-framework'); ?></h2>
        <?php endif; ?>
        <div class="cf-calculator__container">
<?php else: ?>
    <p><?php _e('Goals Calculator is disabled.', 'calculator-framework'); ?></p>
<?php endif; ?>
        <div class="cf-calculator__input">
            <form class="cf-calculator-form" action="#" method="POST">
                <div class="cf-form-group cf-mode-toggle-buttons">
                    <button type="button" class="cf-mode-button cf-mode-button--active" data-mode="savings"><?php _e('Savings', 'calculator-framework'); ?></button>
                    <button type="button" class="cf-mode-button" data-mode="passive"><?php _e('Passive Income', 'calculator-framework'); ?></button>
                    <input type="hidden" id="mode" name="mode" value="savings">
                </div>

                <!-- Поля для Накоплений -->
                <div class="cf-savings-fields" style="display: block;">
                    <div class="cf-form-group">
                        <label for="target_amount"><?php _e('Target Amount', 'calculator-framework'); ?>: <span class="cf-required">*</span></label>
                        <input type="number" id="target_amount" name="target_amount" step="0.01" min="0.01" required>
                    </div>
                </div>

                <!-- Поля для Пассивного дохода -->
                <div class="cf-passive-fields" style="display: none;">
                    <div class="cf-form-group">
                        <label for="monthly_income"><?php _e('Desired Monthly Passive Income', 'calculator-framework'); ?>: <span class="cf-required">*</span></label>
                        <input type="number" id="monthly_income" name="monthly_income" step="0.01" min="0.01">
                    </div>
                </div>

                <!-- Общие поля -->
                <div class="cf-common-fields">
                    <div class="cf-form-group">
                        <label for="years"><?php _e('Number of Years', 'calculator-framework'); ?>: <span class="cf-required">*</span></label>
                        <input type="number" id="years" name="years" min="1" required>
                    </div>
                    <div class="cf-form-group">
                        <label for="inflation_rate"><?php _e('Average Annual Inflation Rate (%)', 'calculator-framework'); ?>:</label>
                        <input type="number" id="inflation_rate" name="inflation_rate" step="0.01" min="0" value="0">
                    </div>
                    <div class="cf-form-group">
                        <label for="return_rate"><?php _e('Average Annual Return Rate (%)', 'calculator-framework'); ?>:</label>
                        <input type="number" id="return_rate" name="return_rate" step="0.01" min="0" value="0">
                    </div>
                    <div class="cf-form-group">
                        <label for="initial_capital"><?php _e('Initial Capital', 'calculator-framework'); ?>:</label>
                        <input type="number" id="initial_capital" name="initial_capital" step="0.01" min="0" value="0">
                    </div>
                </div>

                <div class="cf-buttons">
                    <button type="button" class="cf-button cf-button--secondary cf-reset"><?php _e('Reset', 'calculator-framework'); ?></button>
                    <button type="submit" class="cf-button cf-button--primary"><?php _e('Calculate', 'calculator-framework'); ?></button>
                </div>
            </form>
        </div>
<div class="cf-calculator__output">
    <div class="cf-results" style="display: none;">
        <h3 class="cf-results__title"></h3>
        <div class="cf-goal-summary">
            <div class="cf-result-cards">
                <div class="cf-result-card cf-result-card--primary">
                    <p class="cf-result-card__label"><?php _e('Target with Inflation', 'calculator-framework'); ?></p>
                    <p class="cf-result-card__value" id="target-with-inflation"></p>
                </div>
                <div class="cf-result-card cf-result-card--secondary">
                    <p class="cf-result-card__label"><?php _e('Equivalent Today', 'calculator-framework'); ?></p>
                    <p class="cf-result-card__value" id="target-today"></p>
                </div>
            </div>
        </div>
        <div class="cf-goal-contributions">
            <h4 class="cf-contributions__title"><?php _e('To Achieve Your Goal, You Need to Contribute:', 'calculator-framework'); ?></h4>
            <div class="cf-result-cards">
                <div class="cf-result-card cf-result-card--primary">
                    <p class="cf-result-card__label"><?php _e('Monthly', 'calculator-framework'); ?></p>
                    <p class="cf-result-card__value" id="monthly-contribution"></p>
                </div>
                <div class="cf-result-card cf-result-card--secondary">
                    <p class="cf-result-card__label"><?php _e('Yearly', 'calculator-framework'); ?></p>
                    <p class="cf-result-card__value" id="yearly-contribution"></p>
                </div>
                <div class="cf-result-card cf-result-card--tertiary">
                    <p class="cf-result-card__label"><?php _e('Lump Sum', 'calculator-framework'); ?></p>
                    <p class="cf-result-card__value" id="lump-sum"></p>
                </div>
            </div>
        </div>
        <p class="cf-results__description">
            <?php _e('With this calculator, you can calculate how much money you need to invest or save to achieve any financial goal by a specific date.', 'calculator-framework'); ?>
        </p>
    </div>
    <div class="cf-preloader"></div>
</div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.cf-mode-button').on('click', function() {
        const mode = $(this).data('mode');
        $('.cf-mode-button').removeClass('cf-mode-button--active');
        $(this).addClass('cf-mode-button--active');
        $('#mode').val(mode);

        if (mode === 'savings') {
            $('.cf-savings-fields').show();
            $('.cf-passive-fields').hide();
            $('#target_amount').prop('required', true);
            $('#monthly_income').prop('required', false);
        } else {
            $('.cf-savings-fields').hide();
            $('.cf-passive-fields').show();
            $('#target_amount').prop('required', false);
            $('#monthly_income').prop('required', true);
        }
    });
});
</script>
