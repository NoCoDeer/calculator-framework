<?php
class Calculator_Framework {
    private $modules = array();

    public function __construct() {
        // Enqueue shared assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // Register AJAX handler
        add_action('wp_ajax_cf_calculate', array($this, 'handle_ajax'));
        add_action('wp_ajax_nopriv_cf_calculate', array($this, 'handle_ajax'));

        // Load modules
        $this->load_modules();
    }

    public function enqueue_assets() {
        global $post;
        $load_assets = false;

        // Check if any calculator shortcode is present
        foreach ($this->modules as $module) {
            $shortcode = $module->get_shortcode();
            if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, $shortcode)) {
                $load_assets = true;
                error_log("Shortcode found: $shortcode");
                break;
            } else {
                error_log("Shortcode $shortcode not found in post content");
            }
        }

        if ($load_assets) {
            error_log('Enqueuing assets for calculator');
            wp_enqueue_script('jquery');
            wp_enqueue_script('chart-js', CF_PLUGIN_URL . 'vendor/chart.js', array(), '4.4.0', true);
            wp_enqueue_script(
                'html2canvas',
                'https://unpkg.com/html2canvas@1.4.1/dist/html2canvas.min.js',
                array(),
                '1.4.1',
                true
            );
            wp_enqueue_script(
                'jspdf',
                'https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js',
                array(),
                '2.5.1',
                true
            );
            wp_enqueue_script('cf-framework-js', CF_PLUGIN_URL . 'assets/js/framework.js', array('jquery', 'chart-js', 'html2canvas', 'jspdf'), '1.0.0', true);
            wp_localize_script('cf-framework-js', 'cfAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cf_calculate_nonce'),
                'translations' => array(
                    'replenishment_label' => __('Replenishment', 'calculator-framework'),
                    'withdrawal_label' => __('Withdrawal', 'calculator-framework'),
                    'invalid_input' => __('Please fill in all required fields with valid values.', 'calculator-framework'),
                    'error' => __('Error', 'calculator-framework'),
                    'calculation_failed' => __('Calculation failed.', 'calculator-framework'),
                    'ajax_error' => __('An error occurred while processing your request.', 'calculator-framework'),
                    'principal_label' => __('Investment', 'calculator-framework'), // Обновлено для savings
                    'interest_label' => __('Interest', 'calculator-framework'),
                    'total_label' => __('Total', 'calculator-framework'),
                    'required_principal_label' => __('Required Investment', 'calculator-framework'),
                    'required_interest_label' => __('Required Interest', 'calculator-framework'),
                    'required_total_label' => __('Required Total', 'calculator-framework'),
                    'savings_current_message' => __('Investing %s per month is %s to reach the target amount.', 'calculator-framework'),
                    'savings_required_message' => __('Investing %s per month is %s to reach the target amount.', 'calculator-framework'),
                    'insufficient' => __('insufficient', 'calculator-framework'),
                    'sufficient' => __('sufficient', 'calculator-framework'),
                    'years_label' => __('Years', 'calculator-framework'),
                    'amount_label' => __('Amount', 'calculator-framework'),
                ),
                'chart_colors' => array(
                    'principal' => get_option('cf_chart_principal_color', '#3C57EA'),
                    'interest' => get_option('cf_chart_interest_color', '#34C759'),
                    'total' => get_option('cf_chart_total_color', '#00C4B4'),
                ),
                'preloader_icon' => esc_url(get_option('cf_preloader_icon')),
            ));
            wp_enqueue_style('cf-framework-css', CF_PLUGIN_URL . 'assets/css/framework.css', array(), '1.0.0');

            $custom_css = trim(get_option('cf_custom_css'));
            if ($custom_css !== '') {
                wp_add_inline_style('cf-framework-css', $custom_css);
            }
        } else {
            error_log('Assets not enqueued: No calculator shortcode found');
        }
    }

    public function register_module($module) {
        if ($module instanceof Calculator_Module) {
            $this->modules[$module->get_slug()] = $module;
            error_log('Module registered: ' . $module->get_slug());
        } else {
            error_log('Failed to register module: Invalid instance for ' . (is_object($module) ? get_class($module) : $module));
        }
    }

    public function get_modules() {
        return $this->modules;
    }

private function load_modules() {
    $modules_to_load = [
        'compound-interest' => CF_PLUGIN_DIR . 'modules/compound-interest/class-compound-interest.php',
        'savings' => CF_PLUGIN_DIR . 'modules/savings/class-savings.php',
        'deposit' => CF_PLUGIN_DIR . 'modules/deposit/class-deposit.php',
        'goals' => CF_PLUGIN_DIR . 'modules/goals/class-goals.php',
    ];

    foreach ($modules_to_load as $slug => $path) {
        if (file_exists($path)) {
            require_once $path;
            $class_name_parts = array_map('ucfirst', explode('-', $slug));
            $class_name = implode('_', $class_name_parts) . '_Calculator';
            if (class_exists($class_name)) {
                $module = new $class_name();
                $this->register_module($module);
            } else {
                error_log("Class $class_name not found in $path");
            }
        } else {
            error_log("Module file $path does not exist");
        }
    }

    do_action('cf_register_modules', $this);
}

    public function handle_ajax() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cf_calculate_nonce')) {
            wp_send_json_error(array('message' => __('Nonce verification failed', 'calculator-framework')));
            return;
        }

        $module_slug = isset($_POST['module']) ? sanitize_text_field($_POST['module']) : '';
        if (!isset($this->modules[$module_slug])) {
            wp_send_json_error(array('message' => __('Invalid calculator module', 'calculator-framework')));
            return;
        }

        // Create a cache key based on inputs
        $cache_key = 'cf_calc_' . md5(serialize($_POST));
        $result = get_transient($cache_key);
        if ($result !== false) {
            wp_send_json_success($result);
            return;
        }

        $module = $this->modules[$module_slug];
        $result = $module->calculate($_POST);

        if ($result === false) {
            wp_send_json_error(array('message' => __('Calculation failed', 'calculator-framework')));
        } else {
            set_transient($cache_key, $result, HOUR_IN_SECONDS);
            wp_send_json_success($result);
        }
    }
}