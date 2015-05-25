<?php
/**
 * Author: Zumochi <zumpyzum@gmail.com>
 *
 * By default this script outputs a dump of last month's 5 best scanners (by systems added).
 *
 * You can also specify start and end dates through GET (e.g. ScannerTally.php?start=2015-01-01&end=2015-05-01
 * You can also send the output to Slack instead of the browser, simply specify slack=true or slack=1
 */

require __DIR__ . '/Config.php';


class Tally
{
    private $DBH;
    public $DateStart, $DateEnd, $Out, $Action, $LimitNumber;
    public $dateTimeFormat = 'Y-m-d';

    function __construct()
    {
        try {
            $this->DBH = new PDO(DB_DSN, DB_USER, DB_PASS);
            $this->DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            exit("Error connecting to database: " . "<pre>" . $e->getMessage() . "</pre>");
        }
    }

    function Execute()
    {
        $this->SetDates();
        $this->SetAction();
        $this->SetLimit();
        $this->SetOutputMethod();

        return $this->ExecuteQuery();
    }

    function SetDates()
    {
        $inputStart = filter_input(INPUT_GET, 'start', FILTER_SANITIZE_STRING);
        $inputEnd = filter_input(INPUT_GET, 'end', FILTER_SANITIZE_STRING);

        if (empty($inputStart) && empty($inputEnd)) {
            $this->DateEnd = date($this->dateTimeFormat);
            $last = strtotime('-1 month');
            $this->DateStart = date($this->dateTimeFormat, $last);

            return;
        }

        try {
            $dateStart = new DateTime($inputStart);
            $dateEnd = new DateTime($inputEnd);
        } catch (Exception $e) {
            echo "Error validating date input.<br>";
            echo "Make sure you use YYYY-MM-DD for the date format.";
            exit;
        }

        $this->DateStart = $dateStart->format($this->dateTimeFormat);
        $this->DateEnd = $dateEnd->format($this->dateTimeFormat);
    }

    function SetAction()
    {
        $events = array(
            'updated_signatures' => 'Updated signature',
            'added_signature'    => 'Created signature',
            'added_system'       => 'Added system',
            'edited_system'      => 'Edited System',
        );

        if (empty($_GET['action'])) {
            $this->Action = $events['added_system'];

            return;
        }

        $input = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);

        if (!array_key_exists($input, $events)) {
            echo "Error validating event type.<br>";
            echo "Please use one of the following:<br>";
            echo "<pre>";
            foreach ($events as $key => $event) echo $key . "\n";
            echo "</pre>";
            exit;
        }

        $this->Action = $events[$input];
    }

    function SetLimit()
    {
        if (empty($_GET['limit'])) {
            $this->LimitNumber = 5;

            return;
        }

        $input = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
        $this->LimitNumber = intval($input);
    }

    function SetOutputMethod()
    {
        $methods = array(
            'stdout',
            'slack',
        );

        if (!empty($_GET['out'])) {
            $outputMethod = filter_input(INPUT_GET, 'out', FILTER_SANITIZE_STRING);
        } else {
            $outputMethod = "stdout";
        }

        if (array_search($outputMethod, $methods) === false) {
            echo "Error validating output method.<br>";
            echo "Please use one of the following:<br>";
            echo "<pre>";
            foreach ($methods as $method) echo $method . "\n";
            echo "</pre>";
            exit;
        } else {
            $this->Out = $outputMethod;
        }
    }

    function ExecuteQuery()
    {
        $LogAction = $this->Action . "%";

        $STH = $this->DBH->prepare(
            "SELECT user.username, COUNT(1) AS log_count FROM Map_maplog log
            JOIN account_ewsuser user ON user.id = log.user_id
            WHERE log.action LIKE :LogAction
            AND (log.timestamp >= :DateEnd AND log.timestamp <= :DateStart)
            GROUP BY user.id ORDER BY log_count DESC
            LIMIT :LimitNumber"
        );
        $STH->bindParam(':LogAction', $LogAction);
        $STH->bindParam(':DateStart', $this->DateEnd);
        $STH->bindParam(':DateEnd', $this->DateStart);
        $STH->bindParam(':LimitNumber', $this->LimitNumber, PDO::PARAM_INT);
        $STH->setFetchMode(PDO::FETCH_ASSOC);

        if ($STH->execute()) {
            return $STH->fetchAll();
        } else {
            exit("Error executing query.");
        }
    }

    function SendToSlack($data)
    {
        $slackURL = SLACK_URL;
        if (empty($slackURL)) exit("Slack URL empty.");
        $slackFormat = array();

        foreach ($data as $key => $person) {
            $slackFormat[$key] = array(
                "title" => $person['username'],
                "value" => $person['log_count'] . " systems added",
            );
        }

        $encode = array(
            "username"    => "Scanning Tally Bot",
            "attachments" => array(array(
                "fallback" => "This month's top scanner: " .
                    $data[0]['username'] . " with " . $data[0]['log_count'] . " systems added!",
                "pretext"  => "Top Scanners for period: \n" . $this->DateStart . "  to  " . $this->DateEnd,
                "color"    => $this->GetRandomColor($data[0]['username']),
                "fields"   => $slackFormat,
            )),
        );

        $encode = json_encode($encode, JSON_PRETTY_PRINT);

        $CH = curl_init($slackURL);
        curl_setopt($CH, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($CH, CURLOPT_POST, true);
        curl_setopt($CH, CURLOPT_POSTFIELDS, $encode);
        curl_setopt($CH, CURLOPT_RETURNTRANSFER, true);
        curl_exec($CH);
        curl_close($CH);
    }

    function GetRandomColor($name)
    {
        return "#" . substr(md5($name), 0, 6);
    }
}


$Tally = new Tally();
$data = $Tally->Execute();

switch ($Tally->Out) {
    case 'slack':
        $Tally->SendToSlack($data);
        break;
    case 'stdout':
    default:
        var_dump($data);
        break;
}
