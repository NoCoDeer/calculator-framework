<?php
class Compound_Interest_Calculator extends Calculator_Module {
    public function __construct() {
        parent::__construct('compound-interest', __('Compound Interest Calculator', 'calculator-framework'));
    }

    public function render($atts) {
        ob_start();
        include CF_PLUGIN_DIR . 'modules/compound-interest/frontend.php';
        return ob_get_clean();
    }

    public function calculate($data) {
        // Извлечение входных данных с установкой значений по умолчанию
        $principal = isset($data['principal']) ? floatval($data['principal']) : 0;
        $rate = isset($data['rate']) ? floatval($data['rate']) : 0;
        $time = isset($data['time']) ? floatval($data['time']) : 0;
        $frequency = isset($data['frequency']) ? sanitize_text_field($data['frequency']) : 'yearly';
        $replenishment_frequency = isset($data['replenishment_frequency']) ? sanitize_text_field($data['replenishment_frequency']) : 'none';
        $replenishment_amount = isset($data['replenishment_amount']) ? floatval($data['replenishment_amount']) : 0;

        // Валидация входных данных
        if ($principal <= 0 || $rate <= 0 || $time <= 0) {
            error_log("Calculation failed: Invalid input - Principal: $principal, Rate: $rate, Time: $time");
            return false;
        }
        if ($replenishment_amount < 0) {
            error_log("Calculation failed: Replenishment amount cannot be negative: $replenishment_amount");
            return false;
        }

        // Преобразование процентной ставки в доли
        $rate = $rate / 100;

        // Определение частоты начисления процентов
        switch ($frequency) {
            case 'monthly':
                $n = 12;
                break;
            case 'quarterly':
                $n = 4;
                break;
            case 'yearly':
            default:
                $n = 1;
                break;
        }

        // Определение частоты пополнения
        switch ($replenishment_frequency) {
            case 'monthly':
                $r = 12;
                break;
            case 'quarterly':
                $r = 4;
                break;
            case 'yearly':
                $r = 1;
                break;
            case 'none':
            default:
                $r = 0;
                $replenishment_amount = 0;
                break;
        }

        // Определение минимального интервала для расчета
        $smallest_interval = 1;
        if ($n > 1 || $r > 1) {
            $smallest_interval = max($n, $r);
        }

        $total_periods = $time * $smallest_interval;
        $compounding_per_period = $n / $smallest_interval;
        $replenishment_per_period = $r ? $r / $smallest_interval : 0;

        // Интервал для графика
        $chart_interval = $time > 20 ? 5 : 1;

        // Инициализация результата
        $result = array(
            'table' => array(),
            'chart' => array(
                'labels' => array(),
                'principal_data' => array(),
                'interest_data' => array(),
                'total_data' => array(),
            ),
            'final_amount' => 0,
        );

        $balance = $principal;
        $total_principal = $principal;
        $total_interest = 0;

        // Расчет по периодам
        for ($period = 1; $period <= $total_periods; $period++) {
            // Добавление пополнения
            if ($r && $period % (int)($smallest_interval / $r) === 0) {
                $balance += $replenishment_amount;
                $total_principal += $replenishment_amount;
                error_log("Period $period: Added replenishment $replenishment_amount, Balance: $balance, Total Principal: $total_principal");
            }

            // Начисление процентов
            if ($period % (int)($smallest_interval / $n) === 0) {
                $interest = $balance * (pow(1 + $rate / $n, 1) - 1);
                $balance += $interest;
                $total_interest += $interest;
                error_log("Period $period: Added interest $interest, Balance: $balance, Total Interest: $total_interest");
            }

            // Добавление данных в таблицу и график
            if ($period % (int)($smallest_interval * $chart_interval) === 0) {
                $year = $period / $smallest_interval;
                $result['table'][] = array(
                    'period' => $year,
                    'principal' => round($total_principal, 2),
                    'interest' => round($total_interest, 2),
                    'total' => round($balance, 2),
                );

                $result['chart']['labels'][] = sprintf(__('Year %d', 'calculator-framework'), $year);
                $result['chart']['principal_data'][] = round($total_principal, 2);
                $result['chart']['interest_data'][] = round($total_interest, 2);
                $result['chart']['total_data'][] = round($balance, 2);
            }
        }

        $result['final_amount'] = round($balance, 2);
        error_log("Final Amount: {$result['final_amount']}, Total Principal: $total_principal, Total Interest: $total_interest");

        return $result;
    }

    public function render_admin_settings() {
        include CF_PLUGIN_DIR . 'modules/compound-interest/admin.php';
    }
}