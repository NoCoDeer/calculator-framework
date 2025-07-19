<?php
class Goals_Calculator extends Calculator_Module {
    public function __construct() {
        parent::__construct('goals', __('Goals Calculator', 'calculator-framework'));
    }

    public function render($atts) {
        ob_start();
        include CF_PLUGIN_DIR . 'modules/goals/frontend.php';
        return ob_get_clean();
    }

    public function calculate($data) {
        // Извлечение общих данных
        $mode = isset($data['mode']) ? sanitize_text_field($data['mode']) : 'savings';
        $years = isset($data['years']) ? floatval($data['years']) : 0;
        $inflation_rate = isset($data['inflation_rate']) ? floatval($data['inflation_rate']) : 0;
        $return_rate = isset($data['return_rate']) ? floatval($data['return_rate']) : 0;
        $initial_capital = isset($data['initial_capital']) ? floatval($data['initial_capital']) : 0;

        // Валидация общих данных
        if ($years <= 0 || $inflation_rate < 0 || $return_rate < 0 || $initial_capital < 0) {
            error_log("Invalid common inputs: Years: $years, Inflation: $inflation_rate, Return: $return_rate, Initial: $initial_capital");
            return false;
        }

        // Преобразование ставок в доли
        $inflation = $inflation_rate / 100;
        $annual_rate = $return_rate / 100;
        $monthly_rate = $annual_rate / 12;
        $total_months = $years * 12;

        $result = array(
            'mode' => $mode,
            'years' => $years,
            'return_rate' => $return_rate,
            'target_with_inflation' => 0,
            'target_today' => 0,
            'monthly_contribution' => 0,
            'yearly_contribution' => 0,
            'lump_sum' => 0,
        );

        if ($mode === 'savings') {
            // Накопления
            $target_amount = isset($data['target_amount']) ? floatval($data['target_amount']) : 0;
            if ($target_amount <= 0) {
                error_log("Invalid target_amount for savings: $target_amount");
                return false;
            }

            // Учет инфляции: будущая стоимость цели
            $target_with_inflation = $target_amount * pow(1 + $inflation, $years);
            $target_today = $target_amount; // Эквивалент на сегодня — исходная сумма

            // Будущая стоимость начального капитала
            $future_initial = $initial_capital * pow(1 + $annual_rate, $years);

            // Оставшаяся сумма для накопления
            $remaining = $target_with_inflation - $future_initial;

            // Заполняем target_with_inflation и target_today
            $result['target_with_inflation'] = $target_with_inflation;
            $result['target_today'] = $target_today;

            // Расчет взносов
            if ($remaining > 0) {
                if ($annual_rate > 0) {
                    $growth_factor = pow(1 + $monthly_rate, $total_months);
                    $annuity_factor = ($growth_factor - 1) / $monthly_rate;
                    $result['monthly_contribution'] = $remaining / $annuity_factor;

                    $yearly_growth_factor = pow(1 + $annual_rate, $years);
                    $yearly_annuity_factor = ($yearly_growth_factor - 1) / $annual_rate;
                    $result['yearly_contribution'] = $remaining / $yearly_annuity_factor;
                } else {
                    $result['monthly_contribution'] = $remaining / $total_months;
                    $result['yearly_contribution'] = $remaining / $years;
                }
                $result['lump_sum'] = $remaining; // Единоразово — это оставшаяся сумма без учета роста
            } else {
                $result['monthly_contribution'] = 0;
                $result['yearly_contribution'] = 0;
                $result['lump_sum'] = 0;
            }

        } else {
            // Пассивный доход
            $monthly_income = isset($data['monthly_income']) ? floatval($data['monthly_income']) : 0;
            if ($monthly_income <= 0) {
                error_log("Invalid monthly_income for passive income: $monthly_income");
                return false;
            }

            // Годовой доход с учетом инфляции через N лет
            $future_annual_income = $monthly_income * 12 * pow(1 + $inflation, $years);

            // Необходимый капитал для пассивного дохода (предполагаем, что доходность = снятие процентов)
            $target_with_inflation = $future_annual_income / ($annual_rate > 0 ? $annual_rate : 0.01); // Избегаем деления на 0
            $target_today = $target_with_inflation / pow(1 + $inflation, $years);

            // Будущая стоимость начального капитала
            $future_initial = $initial_capital * pow(1 + $annual_rate, $years);

            // Оставшаяся сумма для накопления
            $remaining = $target_with_inflation - $future_initial;

            // Заполняем target_with_inflation и target_today
            $result['target_with_inflation'] = $target_with_inflation;
            $result['target_today'] = $target_today;

            // Расчет взносов
            if ($remaining > 0) {
                if ($annual_rate > 0) {
                    $growth_factor = pow(1 + $monthly_rate, $total_months);
                    $annuity_factor = ($growth_factor - 1) / $monthly_rate;
                    $result['monthly_contribution'] = $remaining / $annuity_factor;

                    $yearly_growth_factor = pow(1 + $annual_rate, $years);
                    $yearly_annuity_factor = ($yearly_growth_factor - 1) / $annual_rate;
                    $result['yearly_contribution'] = $remaining / $yearly_annuity_factor;
                } else {
                    $result['monthly_contribution'] = $remaining / $total_months;
                    $result['yearly_contribution'] = $remaining / $years;
                }
                $result['lump_sum'] = $remaining;
            } else {
                $result['monthly_contribution'] = 0;
                $result['yearly_contribution'] = 0;
                $result['lump_sum'] = 0;
            }
        }

        // Округление результатов
        $result['target_with_inflation'] = round($result['target_with_inflation'], 2);
        $result['target_today'] = round($result['target_today'], 2);
        $result['monthly_contribution'] = round($result['monthly_contribution'], 2);
        $result['yearly_contribution'] = round($result['yearly_contribution'], 2);
        $result['lump_sum'] = round($result['lump_sum'], 2);

        error_log("Goals calculation: Mode: $mode, Target with Inflation: {$result['target_with_inflation']}, Monthly: {$result['monthly_contribution']}");
        return $result;
    }

    public function render_admin_settings() {
        include CF_PLUGIN_DIR . 'modules/goals/admin.php';
    }
}
