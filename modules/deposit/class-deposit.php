<?php
class Deposit_Calculator extends Calculator_Module {
    public function __construct() {
        parent::__construct('deposit', __('Deposit Calculator', 'calculator-framework'));
    }

    public function render($atts) {
        ob_start();
        include CF_PLUGIN_DIR . 'modules/deposit/frontend.php';
        return ob_get_clean();
    }

    public function calculate($data) {
        // Извлечение входных данных
        $principal = isset($data['principal']) ? floatval($data['principal']) : 0;
        $term = isset($data['term']) ? floatval($data['term']) : 0;
        $term_unit = isset($data['term_unit']) ? sanitize_text_field($data['term_unit']) : 'month';
        $start_date = isset($data['start_date']) ? sanitize_text_field($data['start_date']) : date('Y-m-d');
        $rate = isset($data['rate']) ? floatval($data['rate']) : 0;
        $capitalize = isset($data['capitalize']) && $data['capitalize'] === '1' ? true : false;
        $capitalization_frequency = isset($data['capitalization_frequency']) ? sanitize_text_field($data['capitalization_frequency']) : 'end';
        $replenishments = isset($data['replenishments']) ? $data['replenishments'] : array();
        $withdrawals = isset($data['withdrawals']) ? $data['withdrawals'] : array();

        // Валидация входных данных
        if ($principal <= 0 || $term <= 0 || $rate <= 0) {
            return false;
        }

        // Процентная ставка в долях
        $annual_rate = $rate / 100;
        $daily_rate = $annual_rate / 365;

        // Определение срока в днях
        $start = new DateTime($start_date);
        $end = clone $start;
        $term_in_months = $term_unit === 'year' ? $term * 12 : $term;
        $end->modify("+{$term_in_months} months");
        $total_days = $start->diff($end)->days;

        // Логирование для отладки
        error_log("Start Date: {$start->format('Y-m-d')}, End Date: {$end->format('Y-m-d')}, Total Days: {$total_days}");
        error_log("Initial Principal: {$principal}, Rate: {$rate}, Capitalize: " . ($capitalize ? 'Yes' : 'No'));

        // Преобразование пополнений и снятий в массив событий
        $events = array();
        foreach ($replenishments as $rep) {
            if (!isset($rep['amount']) || !isset($rep['date']) || !isset($rep['frequency'])) continue;
            $amount = floatval($rep['amount']);
            if ($amount <= 0) continue;
            $date = new DateTime($rep['date']);
            if ($date < $start || $date > $end) continue;

            $frequency = $rep['frequency'];
            $frequency_days = $this->get_frequency_days($frequency);
            // Добавляем первую транзакцию
            $events[] = array(
                'type' => 'replenishment',
                'amount' => $amount,
                'date' => $date,
                'frequency' => $frequency_days,
            );
            error_log("Replenishment: Amount {$amount} on {$rep['date']}, Frequency: {$frequency}");

            // Если частота не 'once', генерируем повторяющиеся транзакции
            if ($frequency !== 'once') {
                $current_date = clone $date;
                $iteration = 0;
                $original_day = $date->format('d'); // Сохраняем исходный день

                while (true) {
                    $iteration++;
                    switch ($frequency) {
                        case 'monthly':
                            $current_date->modify('first day of next month');
                            $days_in_month = (int)$current_date->format('t');
                            $day_to_set = min($original_day, $days_in_month);
                            $current_date->setDate($current_date->format('Y'), $current_date->format('m'), $day_to_set);
                            break;
                        case 'bimonthly':
                            $current_date->modify('first day of +2 months');
                            $days_in_month = (int)$current_date->format('t');
                            $day_to_set = min($original_day, $days_in_month);
                            $current_date->setDate($current_date->format('Y'), $current_date->format('m'), $day_to_set);
                            break;
                        case 'quarterly':
                            $current_date->modify('first day of +3 months');
                            $days_in_month = (int)$current_date->format('t');
                            $day_to_set = min($original_day, $days_in_month);
                            $current_date->setDate($current_date->format('Y'), $current_date->format('m'), $day_to_set);
                            break;
                        case 'semiannually':
                            $current_date->modify('first day of +6 months');
                            $days_in_month = (int)$current_date->format('t');
                            $day_to_set = min($original_day, $days_in_month);
                            $current_date->setDate($current_date->format('Y'), $current_date->format('m'), $day_to_set);
                            break;
                        case 'yearly':
                            $current_date->modify('first day of +1 year');
                            $days_in_month = (int)$current_date->format('t');
                            $day_to_set = min($original_day, $days_in_month);
                            $current_date->setDate($current_date->format('Y'), $current_date->format('m'), $day_to_set);
                            break;
                        default:
                            error_log("Unknown frequency: {$frequency}, skipping repeated replenishments");
                            break 2;
                    }
                    if ($current_date > $end) {
                        error_log("Stopping replenishment generation: Current Date {$current_date->format('Y-m-d')} exceeds End Date {$end->format('Y-m-d')}");
                        break;
                    }
                    $events[] = array(
                        'type' => 'replenishment',
                        'amount' => $amount,
                        'date' => clone $current_date,
                        'frequency' => $frequency_days,
                    );
                    error_log("Repeated Replenishment #$iteration: Amount {$amount} on {$current_date->format('Y-m-d')}, Frequency: {$frequency}");
                }
            }
        }

        foreach ($withdrawals as $with) {
            if (!isset($with['amount']) || !isset($with['date']) || !isset($with['frequency'])) continue;
            $amount = floatval($with['amount']);
            if ($amount <= 0) continue;
            $date = new DateTime($with['date']);
            if ($date < $start || $date > $end) continue;

            $frequency = $with['frequency'];
            $frequency_days = $this->get_frequency_days($frequency);
            // Добавляем первую транзакцию
            $events[] = array(
                'type' => 'withdrawal',
                'amount' => -$amount,
                'date' => $date,
                'frequency' => $frequency_days,
            );
            error_log("Withdrawal: Amount {$amount} on {$with['date']}, Frequency: {$frequency}");

            // Если частота не 'once', генерируем повторяющиеся транзакции
            if ($frequency !== 'once') {
                $current_date = clone $date;
                $iteration = 0;
                $original_day = $date->format('d'); // Сохраняем исходный день

                while (true) {
                    $iteration++;
                    switch ($frequency) {
                        case 'monthly':
                            $current_date->modify('first day of next month');
                            $days_in_month = (int)$current_date->format('t');
                            $day_to_set = min($original_day, $days_in_month);
                            $current_date->setDate($current_date->format('Y'), $current_date->format('m'), $day_to_set);
                            break;
                        case 'bimonthly':
                            $current_date->modify('first day of +2 months');
                            $days_in_month = (int)$current_date->format('t');
                            $day_to_set = min($original_day, $days_in_month);
                            $current_date->setDate($current_date->format('Y'), $current_date->format('m'), $day_to_set);
                            break;
                        case 'quarterly':
                            $current_date->modify('first day of +3 months');
                            $days_in_month = (int)$current_date->format('t');
                            $day_to_set = min($original_day, $days_in_month);
                            $current_date->setDate($current_date->format('Y'), $current_date->format('m'), $day_to_set);
                            break;
                        case 'semiannually':
                            $current_date->modify('first day of +6 months');
                            $days_in_month = (int)$current_date->format('t');
                            $day_to_set = min($original_day, $days_in_month);
                            $current_date->setDate($current_date->format('Y'), $current_date->format('m'), $day_to_set);
                            break;
                        case 'yearly':
                            $current_date->modify('first day of +1 year');
                            $days_in_month = (int)$current_date->format('t');
                            $day_to_set = min($original_day, $days_in_month);
                            $current_date->setDate($current_date->format('Y'), $current_date->format('m'), $day_to_set);
                            break;
                        default:
                            error_log("Unknown frequency: {$frequency}, skipping repeated withdrawals");
                            break 2;
                    }
                    if ($current_date > $end) {
                        error_log("Stopping withdrawal generation: Current Date {$current_date->format('Y-m-d')} exceeds End Date {$end->format('Y-m-d')}");
                        break;
                    }
                    $events[] = array(
                        'type' => 'withdrawal',
                        'amount' => -$amount,
                        'date' => clone $current_date,
                        'frequency' => $frequency_days,
                    );
                    error_log("Repeated Withdrawal #$iteration: Amount {$amount} on {$current_date->format('Y-m-d')}, Frequency: {$frequency}");
                }
            }
        }

        // Сортировка событий по дате
        usort($events, function($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        // Логируем все события для отладки
        foreach ($events as $index => $event) {
            error_log("Event #$index: Type {$event['type']}, Amount {$event['amount']}, Date {$event['date']->format('Y-m-d')}");
        }

        // Инициализация переменных
        $balance = $principal;
        $total_principal = $principal;
        $total_interest = 0;
        $table = array();
        $current_date = clone $start;

        if ($capitalization_frequency === 'end' || !$capitalize) {
            // Простые проценты (без капитализации или в конце срока)
            $current_balance = $principal;
            $segment_start = clone $start;
            $total_segment_interest = 0;

            for ($i = 0; $i <= count($events); $i++) {
                $segment_end = $i < count($events) ? clone $events[$i]['date'] : clone $end;
                if ($segment_end < $segment_start) {
                    error_log("Segment end ({$segment_end->format('Y-m-d')}) is before segment start ({$segment_start->format('Y-m-d')}), skipping");
                    continue;
                }
                if ($segment_end > $end) {
                    $segment_end = clone $end;
                }

                $days = $segment_start->diff($segment_end)->days;
                error_log("Segment from {$segment_start->format('Y-m-d')} to {$segment_end->format('Y-m-d')}, Days: {$days}");

                if ($days > 0) {
                    $segment_interest = $current_balance * $annual_rate * ($days / 365);
                    $total_segment_interest += $segment_interest;
                    error_log("Segment Interest: {$segment_interest}, Total Interest: {$total_segment_interest}");

                    // Добавление в таблицу по месяцам и в дни событий
                    $temp_date = clone $segment_start;
                    $last_added_date = null;
                    while ($temp_date <= $segment_end) {
                        $is_same_day = $temp_date->format('d') === $start->format('d');
                        $is_last_date = $temp_date->format('Y-m-d') === $segment_end->format('Y-m-d');
                        $is_event_date = false;
                        $daily_replenishment = 0;
                        $daily_withdrawal = 0;

                        // Проверяем, есть ли событие на текущую дату
                        for ($j = $i; $j < count($events) && $events[$j]['date']->format('Y-m-d') === $temp_date->format('Y-m-d'); $j++) {
                            $event = $events[$j];
                            if ($event['type'] === 'replenishment') {
                                $daily_replenishment += $event['amount'];
                            } else {
                                $daily_withdrawal += abs($event['amount']);
                            }
                            $is_event_date = true;
                        }

                        if ($is_same_day || $is_last_date || $is_event_date) {
                            $current_period = $temp_date->format('d.m.Y');
                            if ($last_added_date !== $current_period) {
                                $table[] = array(
                                    'period' => $current_period,
                                    'interest' => round($total_segment_interest, 2),
                                    'total' => round($current_balance + $total_segment_interest, 2),
                                    'replenishment' => $daily_replenishment,
                                    'withdrawal' => $daily_withdrawal,
                                );
                                $last_added_date = $current_period;
                                error_log("Table Row Added: Period {$current_period}, Interest {$total_segment_interest}, Total {$current_balance}, Replenishment {$daily_replenishment}, Withdrawal {$daily_withdrawal}");
                            }
                        }
                        $temp_date->modify('+1 day');
                    }
                }

                if ($i < count($events)) {
                    $event = $events[$i];
                    if ($event['type'] === 'replenishment') {
                        $current_balance += $event['amount'];
                        $total_principal += $event['amount'];
                    } else {
                        $current_balance += $event['amount'];
                        $total_principal += $event['amount'];
                        if ($current_balance < 0) {
                            $current_balance = 0;
                        }
                    }
                    error_log("After event {$event['type']} on {$event['date']->format('Y-m-d')}, Balance: {$current_balance}, Total Principal: {$total_principal}");
                }

                $segment_start = clone $segment_end;
                $segment_start->modify('+1 day');
            }

            $balance = $current_balance + $total_segment_interest;
            $total_interest = $total_segment_interest;
            error_log("Final Balance (Simple Interest): {$balance}, Total Interest: {$total_interest}");
        } else {
            // С капитализацией
            $capitalization_days = 1; // По умолчанию ежедневная
            if ($capitalization_frequency === 'monthly') {
                $capitalization_days = 30;
            } elseif ($capitalization_frequency === 'quarterly') {
                $capitalization_days = 90;
            } elseif ($capitalization_frequency === 'yearly') {
                $capitalization_days = 365;
            }

            $day_count = 0;
            $capitalization_counter = 0;
            $accumulated_interest = 0;
            $last_added_date = null;

            while ($current_date <= $end) {
                $day_count++;
                $capitalization_counter++;

                // Применение событий
                $daily_replenishment = 0;
                $daily_withdrawal = 0;
                // Проверяем все события, которые совпадают с текущей датой
                for ($i = 0; $i < count($events); $i++) {
                    if ($current_date->format('Y-m-d') === $events[$i]['date']->format('Y-m-d')) {
                        $event = $events[$i];
                        $balance += $event['amount'];
                        if ($event['type'] === 'replenishment') {
                            $total_principal += $event['amount'];
                            $daily_replenishment += $event['amount'];
                        } else {
                            $total_principal += $event['amount'];
                            $daily_withdrawal += abs($event['amount']);
                            if ($balance < 0) {
                                $balance = 0;
                            }
                        }
                        error_log("After event {$event['type']} on {$event['date']->format('Y-m-d')}, Balance: {$balance}, Total Principal: {$total_principal}");
                    }
                }

                // Начисление процентов
                $daily_interest = $balance * $daily_rate;
                $accumulated_interest += $daily_interest;

                // Капитализация процентов
                if ($capitalization_counter % $capitalization_days === 0 || $current_date == $end) {
                    $balance += $accumulated_interest;
                    $total_interest += $accumulated_interest;
                    $accumulated_interest = 0;
                }

                // Добавление в таблицу: в дни событий, в дни совпадения с начальным днем или в последний день
                $is_event_date = ($daily_replenishment > 0 || $daily_withdrawal > 0);
                $is_same_day = $current_date->format('d') === $start->format('d');
                $is_last_date = $current_date == $end;
                $current_period = $current_date->format('d.m.Y');

                if ($is_event_date || $is_same_day || $is_last_date) {
                    if ($last_added_date !== $current_period) {
                        $table[] = array(
                            'period' => $current_period,
                            'interest' => round($total_interest, 2),
                            'total' => round($balance + $accumulated_interest, 2),
                            'replenishment' => $daily_replenishment,
                            'withdrawal' => $daily_withdrawal,
                        );
                        $last_added_date = $current_period;
                        error_log("Table Row Added (Compound): Period {$current_period}, Interest {$total_interest}, Total {$balance}, Replenishment {$daily_replenishment}, Withdrawal {$daily_withdrawal}");
                    }
                }

                $current_date->modify('+1 day');
            }

            // Добавление оставшихся накопленных процентов
            if ($accumulated_interest > 0) {
                $balance += $accumulated_interest;
                $total_interest += $accumulated_interest;
            }
            error_log("Final Balance (Compound Interest): {$balance}, Total Interest: {$total_interest}");
        }

        $result = array(
            'total_principal' => round($total_principal, 2),
            'total_interest' => round($total_interest, 2),
            'final_amount' => round($balance, 2),
            'table' => $table,
        );

        return $result;
    }

    private function get_frequency_days($frequency) {
        switch ($frequency) {
            case 'once':
                return 0;
            case 'monthly':
                return 30; // Используется только для логирования
            case 'bimonthly':
                return 60;
            case 'quarterly':
                return 90;
            case 'semiannually':
                return 180;
            case 'yearly':
                return 365;
            default:
                return 0;
        }
    }

    public function render_admin_settings() {
        include CF_PLUGIN_DIR . 'modules/deposit/admin.php';
    }
}
