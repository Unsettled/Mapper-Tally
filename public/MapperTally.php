<?php
/**
 * I'm so meta even this acronym.
 */

require __DIR__ . '/../Config.php';


class Tally
{
    private $DBH;
    private $DateNow, $DateLast;

    function __construct()
    {
        try {
            $this->DBH = new PDO(DB_DSN, DB_USER, DB_PASS);
            $this->DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            exit("Error connecting to database: " . $e->getMessage());
        }
    }

    function SetDates()
    {
        $this->DateNow = date('Y-m-d');
        $last = strtotime('-1 month');
        $this->DateLast = date('Y-m-d', $last);
    }

    function ExecuteQuery()
    {
        $STH = $this->DBH->prepare(
            "SELECT user.username, COUNT(1) AS log_count FROM Map_maplog log
            JOIN account_ewsuser user ON user.id = log.user_id
            WHERE log.action LIKE 'Added system%'
            AND (log.timestamp >= :DateLast AND log.timestamp <= :DateNow)
            GROUP BY user.id ORDER BY log_count
            DESC LIMIT 10"
        );
        $STH->bindParam(':DateLast', $this->DateLast);
        $STH->bindParam(':DateNow', $this->DateNow);
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
                "value" => $person['log_count'],
            );
        }

        $encode = array(
            "username"    => "Scanning Tally Bot",
            "attachments" => array(array(
                "fallback" => "This month's top scanner: " . $data[0]['username'] . " with " . $data[0]['log_count'] . " systems added!",
                "pretext"  => "Top Scanners for period " . $this->DateLast . " - " . $this->DateNow . ".",
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
}

$Tally = new Tally();
$Tally->SetDates();
$out = $Tally->ExecuteQuery();

$Tally->SendToSlack($out);
