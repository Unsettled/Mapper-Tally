<?php
/**
 * I'm so meta even this acronym.
 */

require __DIR__ . '/Config.php';


class Tally
{
    private $DBH;
    private $DateStart, $DateEnd;

    function __construct()
    {
        try {
            $this->DBH = new PDO(DB_DSN, DB_USER, DB_PASS);
            $this->DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            exit("Error connecting to database: " . "<pre>" . $e->getMessage() . "</pre>");
        }
    }

    function SetDates()
    {
        if (!empty($_GET['start'])) {
            $input = filter_input(INPUT_GET, 'start', FILTER_SANITIZE_NUMBER_INT);
            $year = substr($input, 0, 4);
            $month = substr($input, 5, 2);
            $day = substr($input, 8, 2);

            if (checkdate($month, $day, $year)) {
                $this->DateStart = $input;
            } else {
                echo "<pre>Error validating start date input.<br>";
                echo "Make sure you use YYYY-MM-DD for the date format.";
                exit;
            };

            if (!empty($_GET['end'])) {
                $input = filter_input(INPUT_GET, 'end', FILTER_SANITIZE_NUMBER_INT);
                $year = substr($input, 0, 4);
                $month = substr($input, 5, 2);
                $day = substr($input, 8, 2);

                if (checkdate($month, $day, $year)) {
                    $this->DateEnd = $input;
                } else {
                    echo "<pre>Error validating end date input.<br>";
                    echo "Make sure you use YYYY-MM-DD for the date format.";
                    exit;
                }
            } else {
                exit("You need to provide an end date if you specify a start date!");
            }
        } else {
            $this->DateEnd = date('Y-m-d');
            $last = strtotime('-1 month');
            $this->DateStart = date('Y-m-d', $last);
        }
    }

    function ExecuteQuery()
    {
        $STH = $this->DBH->prepare(
            "SELECT user.username, COUNT(1) AS log_count FROM Map_maplog log
            JOIN account_ewsuser user ON user.id = log.user_id
            WHERE log.action LIKE 'Added system%'
            AND (log.timestamp >= :DateEnd AND log.timestamp <= :DateStart)
            GROUP BY user.id ORDER BY log_count DESC
            LIMIT 5"
        );
        $STH->bindParam(':DateStart', $this->DateEnd);
        $STH->bindParam(':DateEnd', $this->DateStart);
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
                "fallback" => "This month's top scanner: " . $data[0]['username'] . " with " . $data[0]['log_count'] . " systems added!",
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

    function GetRandomColor($name) {
        return "#" . substr(md5($name), 0, 6);
    }
}


$Tally = new Tally();
$Tally->SetDates();
$out = $Tally->ExecuteQuery();

if (!empty($_GET['slack'])) {
    $slack = filter_input(INPUT_GET, 'slack', FILTER_VALIDATE_BOOLEAN);

    if ($slack) {
        $Tally->SendToSlack($out);
    } else var_dump($out);
} else var_dump($out);
