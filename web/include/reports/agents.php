<?php

use \common\helpers\DocumentHelper;
class Report_Agents extends BaseReport {
    public $mode = 'agents';
    function getName($short = 0) {
        if ($short) {
            return "По реализациям агентов ответственного";
        } else {
            return "Отчёт реализациям агентов ответственного";
        }
    }

    /// Форма для формирования отчёта
    function Form() {
        global $tmpl, $db;
        $date_start = date("Y-01-01");
        $date_end = date("Y-m-d");
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>".
                          $this->formBegin()."
        Начальная дата:<br>
        <input type='text' name='date_f' id='datepicker_f' value='$date_start'><br>
        Конечная дата:<br>
        <input type='text' name='date_t' id='datepicker_t' value='$date_end'><br>
        Ответсвенный:<br>
        <select name='user_id'>");
        $res = $db->query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
        while ($line = $res->fetch_assoc()) {
            $tmpl->addContent("<option value='{$line['user_id']}'>{$line['worker_real_name']}</option>");
        }
        $tmpl->addContent("</select><br>
        Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
            <button type='submit'>Создать отчет</button></form>
            <script type='text/javascript'>
        initCalendar('datepicker_f',false);
        initCalendar('datepicker_t',false);

            </script>");
    }
    public function includeJSAndCSS()
    {
        return <<<EOD
<script src='/css/jquery/jquery.js' type='text/javascript'></script>
<script src='/css/jquery/jquery.alerts.js' type='text/javascript'></script>
<link href='/css/jquery/jquery.alerts.css' rel='stylesheet' type='text/css' media='screen'>
<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
EOD;

    }
    public function formBegin($opt = 'make')
    {
        return "<form action='' method='post'>". $this->includeJSAndCSS()."
                    <input type='hidden' name='mode' value='{$this->mode}'>
                    <input type='hidden' name='opt' value='$opt'>";

    }

    function Make($engine) {
        global $db;
        $user_id = rcvint('user_id', false);
        $dt_f = rcvdate('date_f', false);
        $dt_t = rcvdate('date_t', false);

        $dayStart = strtotime("$dt_f 00:00:00");
        $dayEnd = strtotime("$dt_t 23:59:59");
        if($dt_f == false || $dt_t == false || $dayEnd == -1 || $dayStart == -1) {
            throw new ErrorException("Что-то не так с датами");
        }
        $sql = "SELECT `name`, `id` FROM `doc_agent` WHERE `responsible`=$user_id";
        $res = $db->query($sql);
        if (!$res->num_rows) {
            throw new Exception("Агенты, за которых отвечает выбранный сотрудник, не найдены.");
        }
        $this->loadEngine($engine);
        $this->header($this->getName() . " N".$user_id);
        $header = [
            'Id' => 7,
            'Док.' => 8,
            'Дата' => 10,
            'Пров.' => 6,
            'Агент' => 30,
            'Сумма' => 10,
            'Оплачено связанным' => 12,
            'Оплачено суммарно' => 10,
        ];
        $this->tableBegin(array_values($header));
        $this->tableHeader(array_keys($header));
        $agents = array();
        $agents_str = '';
        while($agentRow = $res->fetch_assoc()) {
            $agents[$agentRow['id']] = $agentRow['name'];
            if($agents_str) {
                $agents_str .= ',';
            }
            $agents_str .= $agentRow['id'];
        }

        $resource = $db->query("
SELECT `id`, `altnum`, `subtype`, `date`, `sum`, `p_doc`, `ok`, `agent`
FROM `doc_list`
WHERE
`agent` IN ($agents_str) AND
`date` >= $dayStart AND
`date` <= $dayEnd AND
`type` = 2 AND
`mark_del`= 0
ORDER BY `date`");
        while($documentRow = $resource->fetch_assoc()) {
            $sumFromChildren = DocumentHelper::getCalculatedPaySum($documentRow['id']);
            $paysum = DocumentHelper::getSavedPaySum($documentRow['id']);
            $this->tableRow([
                    $documentRow['id'],
                    $documentRow['altnum'] . $documentRow['subtype'],
                    date('Y-m-d',$documentRow['date']),
                    $documentRow['ok']?'Да':'Нет',
                    $agents[$documentRow['agent']],
                    $documentRow['sum'],
                    sprintf("%0.2f", $sumFromChildren),
                    sprintf("%0.2f", $paysum),
                ]);
        }        
        $this->tableEnd();
        $this->output();
        exit(0);
    }
}
