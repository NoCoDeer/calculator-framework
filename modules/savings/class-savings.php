<?php
class Savings_Calculator extends Calculator_Module {
    public function __construct() {
        parent::__construct('savings', __('Savings Calculator', 'calculator-framework'));
    }

    public function render($atts) {
        ob_start();
        include CF_PLUGIN_DIR . 'modules/savings/frontend.php';
        return ob_get_clean();
    }

    public function calculate($data) {
        // Извлечение входных данных
        $target_amount = isset($data['target_amount']) ? floatval($data['target_amount']) : 0;
        $calculation_unit = isset($data['calculation_unit']) ? sanitize_text_field($data['calculation_unit']) : 'month';
        $period = isset($data['period']) ? floatval($data['period']) : 0;
        $current_amount = isset($data['current_amount']) ? floatval($data['current_amount']) : 0;
        $monthly_investment = isset($data['monthly_investment']) ? floatval($data['monthly_investment']) : 0;
        $expected_return = isset($data['expected_return']) ? floatval($data['expected_return']) : 0;

        // Валидация
        if ($target_amount <= 0 || $period <= 0 || $current_amount < 0 || $monthly_investment < 0 || $expected_return < 0) {
            error_log("Calculation failed: Invalid input - Target: $target_amount, Period: $period, Current: $current_amount, Monthly: $monthly_investment, Return: $expected_return");
            return false;
        }

        // Переводим доходность в доли и месячную ставку
        $annual_rate = $expected_return / 100;
        $monthly_rate = $annual_rate / 12;

        // Переводим период в месяцы
        $total_months = $calculation_unit === 'year' ? $period * 12 : $period;

        // Инициализация для текущих инвестиций
        $balance = $current_amount;
        $total_invested = $current_amount;
        $total_interest = 0;

        // Результаты
        $result = array(
            'table' => array(),
            'chart' => array(
                'labels' => array(),
                'principal_data' => array(),
                'interest_data' => array(),
                'total_data' => array(), // Добавляем
                'required_principal_data' => array(),
                'required_interest_data' => array(),
                'required_total_data' => array(), // Добавляем
            ),
            'final_amount' => 0,
            'target_amount' => round($target_amount, 2),
            'required_monthly_investment' => 0,
        );

        // Интервал для графика
        $years = ceil($total_months / 12);
        $chart_interval = $years > 20 ? 5 : 1;

        // Расчет для текущих инвестиций
        for ($month = 1; $month <= $total_months; $month++) {
            $interest = $balance * $monthly_rate;
            $balance += $interest;
            $total_interest += $interest;

            $balance += $monthly_investment;
            $total_invested += $monthly_investment;

            if ($month % 12 === 0) {
                $year = $month / 12;
                if ($year % $chart_interval === 0) {
                    $result['table'][] = array(
                        'period' => $year,
                        'principal' => round($total_invested, 2),
                        'interest' => round($total_interest, 2),
                        'total' => round($balance, 2),
                    );

                    $result['chart']['labels'][] = sprintf(__('Year %d', 'calculator-framework'), $year);
                    $result['chart']['principal_data'][] = round($total_invested, 2);
                    $result['chart']['interest_data'][] = round($total_interest, 2);
                    $result['chart']['total_data'][] = round($balance, 2); // Добавляем
                }
            }
        }

        if ($total_months % 12 !== 0) {
            $final_year = $total_months / 12;
            $result['table'][] = array(
                'period' => $final_year,
                'principal' => round($total_invested, 2),
                'interest' => round($total_interest, 2),
                'total' => round($balance, 2),
            );

            if (ceil($final_year) % $chart_interval === 0) {
                $result['chart']['labels'][] = sprintf(__('Year %.1f', 'calculator-framework'), $final_year);
                $result['chart']['principal_data'][] = round($total_invested, 2);
                $result['chart']['interest_data'][] = round($total_interest, 2);
                $result['chart']['total_data'][] = round($balance, 2); // Добавляем
            }
        }

        $result['final_amount'] = round($balance, 2);

        // Расчет необходимой суммы ежемесячных инвестиций
        $required_monthly_investment = 0;
        if ($monthly_rate > 0) {
            $growth_factor = pow(1 + $monthly_rate, $total_months);
            $future_value_of_current = $current_amount * $growth_factor;
            $remaining_amount = $target_amount - $future_value_of_current;
            $annuity_factor = ($growth_factor - 1) / $monthly_rate;
            $required_monthly_investment = $remaining_amount / $annuity_factor;
        } else {
            $remaining_amount = $target_amount - $current_amount;
            $required_monthly_investment = $remaining_amount / $total_months;
        }

        $result['required_monthly_investment'] = round(max(0, $required_monthly_investment), 2);

        // Расчет для необходимых инвестиций (второй график)
        $balance = $current_amount;
        $total_invested = $current_amount;
        $total_interest = 0;

        for ($month = 1; $month <= $total_months; $month++) {
            $interest = $balance * $monthly_rate;
            $balance += $interest;
            $total_interest += $interest;

            $balance += $result['required_monthly_investment'];
            $total_invested += $result['required_monthly_investment'];

            if ($month % 12 === 0) {
                $year = $month / 12;
                if ($year % $chart_interval === 0) {
                    $result['chart']['required_principal_data'][] = round($total_invested, 2);
                    $result['chart']['required_interest_data'][] = round($total_interest, 2);
                    $result['chart']['required_total_data'][] = round($balance, 2); // Добавляем
                }
            }
        }

        if ($total_months % 12 !== 0 && ceil($final_year) % $chart_interval === 0) {
            $result['chart']['required_principal_data'][] = round($total_invested, 2);
            $result['chart']['required_interest_data'][] = round($total_interest, 2);
            $result['chart']['required_total_data'][] = round($balance, 2); // Добавляем
        }

        error_log("Savings calculation: Final Amount: {$result['final_amount']}, Target: {$result['target_amount']}, Required Monthly: {$result['required_monthly_investment']}");
        return $result;
    }

    public function render_admin_settings() {
        include CF_PLUGIN_DIR . 'modules/savings/admin.php';
    }
}
