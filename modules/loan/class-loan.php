<?php
class Loan_Calculator extends Calculator_Module {
    public function __construct() {
        parent::__construct('loan', __('Loan Calculator', 'calculator-framework'));
    }

    public function render($atts) {
        ob_start();
        include CF_PLUGIN_DIR . 'modules/loan/frontend.php';
        return ob_get_clean();
    }

    public function calculate($data) {
        $loan_amount = isset($data['loan_amount']) ? floatval($data['loan_amount']) : 0;
        $rate = isset($data['rate']) ? floatval($data['rate']) : 0;
        $time = isset($data['time']) ? floatval($data['time']) : 0;

        if ($loan_amount <= 0 || $rate <= 0 || $time <= 0) {
            return false;
        }

        $rate = $rate / 100 / 12; // Monthly rate
        $total_periods = $time * 12;
        $monthly_payment = ($loan_amount * $rate * pow(1 + $rate, $total_periods)) / (pow(1 + $rate, $total_periods) - 1);
        $total_payment = $monthly_payment * $total_periods;
        $total_interest = $total_payment - $loan_amount;

        $chart_interval = $time > 20 ? 5 : 1;

        $result = array();
        $result['table'] = array();
        $result['chart'] = array(
            'labels' => array(),
            'principal_data' => array(),
            'interest_data' => array(),
            'total_data' => array(),
        );

        $remaining_balance = $loan_amount;
        $cumulative_interest = 0;

        for ($month = 1; $month <= $total_periods; $month++) {
            $interest_payment = $remaining_balance * $rate;
            $principal_payment = $monthly_payment - $interest_payment;
            $remaining_balance -= $principal_payment;
            $cumulative_interest += $interest_payment;

            if ($month % (12 * $chart_interval) === 0) {
                $year = $month / 12;
                $result['table'][] = array(
                    'period' => $year,
                    'principal' => round($loan_amount - $remaining_balance, 2),
                    'interest' => round($cumulative_interest, 2),
                    'total' => round($monthly_payment * $month, 2),
                );

                $result['chart']['labels'][] = sprintf(__('Year %d', 'calculator-framework'), $year);
                $result['chart']['principal_data'][] = round($loan_amount - $remaining_balance, 2);
                $result['chart']['interest_data'][] = round($cumulative_interest, 2);
                $result['chart']['total_data'][] = round($monthly_payment * $month, 2);
            }
        }

        $result['final_amount'] = round($total_payment, 2);
        return $result;
    }

    public function render_admin_settings() {
        include CF_PLUGIN_DIR . 'modules/loan/admin.php';
    }
}