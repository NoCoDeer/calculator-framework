<?php
class CF_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Calculator Framework Settings', 'calculator-framework'),
            __('Calculators', 'calculator-framework'),
            'manage_options',
            'calculator-framework',
            array($this, 'render_admin_page'),
            'dashicons-calculator',
            80
        );

        add_submenu_page(
            'calculator-framework',
            __('Shortcode Guide', 'calculator-framework'),
            __('Shortcode Guide', 'calculator-framework'),
            'manage_options',
            'calculator-framework-shortcodes',
            array($this, 'render_shortcode_guide')
        );
    }

public function register_settings() {
    $framework = new Calculator_Framework();
    foreach ($framework->get_modules() as $module) {
        register_setting('cf_settings_group', 'cf_' . $module->get_slug() . '_enabled');
        register_setting('cf_settings_group', 'cf_' . $module->get_slug() . '_title'); // Добавляем регистрацию заголовка
        register_setting('cf_settings_group', 'cf_' . $module->get_slug() . '_hide_title');
    }

    // Register chart color settings
    register_setting('cf_settings_group', 'cf_chart_principal_color');
    register_setting('cf_settings_group', 'cf_chart_interest_color');
    register_setting('cf_settings_group', 'cf_chart_total_color');

    // Custom CSS and preloader icon settings
    register_setting('cf_settings_group', 'cf_custom_css');
    register_setting('cf_settings_group', 'cf_preloader_icon');
}

    public function render_admin_page() {
        $framework = new Calculator_Framework();
        if (empty($framework->get_modules())) {
            echo '<div class="wrap"><h1>' . __('Calculator Framework Settings', 'calculator-framework') . '</h1><p>' . __('No calculator modules are available.', 'calculator-framework') . '</p></div>';
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Calculator Framework Settings', 'calculator-framework'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('cf_settings_group');
                do_settings_sections('cf_settings_group');
                ?>

                <h2><?php _e('Chart Colors', 'calculator-framework'); ?></h2>
                <p>
                    <label for="cf_chart_principal_color"><?php _e('Principal Color', 'calculator-framework'); ?>:</label>
                    <input type="color" name="cf_chart_principal_color" id="cf_chart_principal_color" value="<?php echo esc_attr(get_option('cf_chart_principal_color', '#3C57EA')); ?>">
                </p>
                <p>
                    <label for="cf_chart_interest_color"><?php _e('Interest Color', 'calculator-framework'); ?>:</label>
                    <input type="color" name="cf_chart_interest_color" id="cf_chart_interest_color" value="<?php echo esc_attr(get_option('cf_chart_interest_color', '#34C759')); ?>">
                </p>
                <p>
                    <label for="cf_chart_total_color"><?php _e('Total Color', 'calculator-framework'); ?>:</label>
                    <input type="color" name="cf_chart_total_color" id="cf_chart_total_color" value="<?php echo esc_attr(get_option('cf_chart_total_color', '#00C4B4')); ?>">
                </p>

                <h2><?php _e('Custom CSS', 'calculator-framework'); ?></h2>
                <p>
                    <textarea name="cf_custom_css" id="cf_custom_css" rows="5" class="large-text"><?php echo esc_textarea(get_option('cf_custom_css')); ?></textarea>
                </p>

                <h2><?php _e('Preloader Icon', 'calculator-framework'); ?></h2>
                <p>
                    <input type="text" name="cf_preloader_icon" id="cf_preloader_icon" value="<?php echo esc_attr(get_option('cf_preloader_icon')); ?>" class="regular-text">
                    <button type="button" class="button" id="cf_preloader_icon_button"><?php _e('Select or Upload Image', 'calculator-framework'); ?></button>
                </p>

                <?php
                foreach ($framework->get_modules() as $module) {
                    echo '<h2>' . esc_html($module->get_name()) . '</h2>';
                    ?>
                    <p>
                        <label for="cf_<?php echo esc_attr($module->get_slug()); ?>_enabled">
                            <?php _e('Enable Calculator', 'calculator-framework'); ?>:
                        </label>
                        <input type="checkbox" name="cf_<?php echo esc_attr($module->get_slug()); ?>_enabled" id="cf_<?php echo esc_attr($module->get_slug()); ?>_enabled" value="1" <?php checked(1, get_option('cf_' . $module->get_slug() . '_enabled', true), true); ?>>
                    </p>
                    <?php
                    $module->render_admin_settings();
                }

                submit_button();
                ?>
            </form>
            <?php wp_enqueue_media(); ?>
            <script type="text/javascript">
            jQuery(document).ready(function($){
                $('#cf_preloader_icon_button').on('click', function(e){
                    e.preventDefault();
                    const frame = wp.media({
                        title: '<?php echo esc_js(__('Select or Upload Preloader Icon', 'calculator-framework')); ?>',
                        button: { text: '<?php echo esc_js(__('Use this image', 'calculator-framework')); ?>' },
                        multiple: false
                    });
                    frame.on('select', function(){
                        const attachment = frame.state().get('selection').first().toJSON();
                        $('#cf_preloader_icon').val(attachment.url);
                    });
                    frame.open();
                });
            });
            </script>
        </div>
        <?php
    }

public function render_shortcode_guide() {
    ?>
    <div class="wrap">
        <h1><?php _e('Shortcode Guide', 'calculator-framework'); ?></h1>
        <p><?php _e('Below is a list of available shortcodes for the Calculator Framework plugin, along with their descriptions and usage instructions.', 'calculator-framework'); ?></p>

        <h2><?php _e('Available Shortcodes', 'calculator-framework'); ?></h2>
        <ul>
            <li>
                <strong>[cf_compound-interest]</strong><br>
                <?php _e('Displays the Compound Interest Calculator. Use this shortcode to allow users to calculate compound interest with options for initial investment, interest rate, time, compounding frequency, and replenishment.', 'calculator-framework'); ?><br>
                <em><?php _e('Example:', 'calculator-framework'); ?> [cf_compound-interest]</em>
            </li>
            <li>
                <strong>[cf_savings]</strong><br>
                <?php _e('Displays the Savings Calculator. Use this shortcode to allow users to calculate savings growth with options for initial investment, monthly deposits, interest rate, and time.', 'calculator-framework'); ?><br>
                <em><?php _e('Example:', 'calculator-framework'); ?> [cf_savings]</em>
            </li>
            <li>
                <strong>[cf_deposit]</strong><br>
                <?php _e('Displays the Deposit Calculator. Use this shortcode to allow users to calculate deposit growth with options for deposit amount, term, interest rate, capitalization, replenishments, and withdrawals.', 'calculator-framework'); ?><br>
                <em><?php _e('Example:', 'calculator-framework'); ?> [cf_deposit]</em>
            </li>
            <li>
                <strong>[cf_goals]</strong><br>
                <?php _e('Displays the Goals Calculator. Use this shortcode to allow users to calculate the amount needed to achieve financial goals, either through savings or passive income, considering inflation, return rate, and initial capital.', 'calculator-framework'); ?><br>
                <em><?php _e('Example:', 'calculator-framework'); ?> [cf_goals]</em>
            </li>
        </ul>

        <h2><?php _e('Usage Instructions', 'calculator-framework'); ?></h2>
        <p><?php _e('1. Insert the desired shortcode into any page or post where you want the calculator to appear.', 'calculator-framework'); ?></p>
        <p><?php _e('2. Ensure the calculator is enabled in the Calculator Framework Settings.', 'calculator-framework'); ?></p>
        <p><?php _e('3. Customize the appearance and behavior of the calculators using the settings on the main settings page.', 'calculator-framework'); ?></p>
    </div>
    <?php
}
}
