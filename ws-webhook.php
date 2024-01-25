<?php

include('config.php');

// Получение вебхука из webinar-stars

if ($_POST["action"] == "webinar_end") {
    $data = $_POST["data"];
} else {
    echo '{"state":false,"error":{"message":"not data"}}';
    exit;
}
$log["input"] = [
    "get" => $_GET,
    "post" => $_POST,
    "json" => json_decode(file_get_contents("php://input"), true),
];
$report = json_decode(send_request("https://efir.webinar-stars.com/api/get_report?token=".$wsKey."&report=".$data["id"]), true);
$log["report"]["original"] = $report;
if ($report["visitors"] != NULL) {
    foreach ($report["visitors"] as $oneVisitor) {
        if ($oneVisitor["email"] != NULL && $oneVisitor["phone"] != NULL) {
            if ($visitors[$oneVisitor["email"]."--".$oneVisitor["phone"]] != NULL) {
                $oldTimeStart = strtotime($visitors[$oneVisitor["email"]."--".$oneVisitor["phone"]]["date_start"]);
                $newTimeStart = strtotime($oneVisitor["date_start"]);
                $oldTimeEnd = strtotime($visitors[$oneVisitor["email"]."--".$oneVisitor["phone"]]["date_end"]);
                $newTimeEnd = strtotime($oneVisitor["date_end"]);
                if ($newTimeStart < $oldTimeStart) {
                    $visitors[$oneVisitor["email"]."--".$oneVisitor["phone"]]["date_start"] == $oneVisitor["date_start"];
                }
                if ($newTimeEnd > $oldTimeEnd) {
                    $visitors[$oneVisitor["email"]."--".$oneVisitor["phone"]]["date_end"] == $oneVisitor["date_end"];
                }
                if ($visitors[$oneVisitor["email"]."--".$oneVisitor["phone"]]["buttons"] == NULL) {
                    $visitors[$oneVisitor["email"]."--".$oneVisitor["phone"]]["buttons"] = $oneVisitor["buttons"];
                } else  if ($oneVisitor["buttons"] != NULL) {
                    $visitors[$oneVisitor["email"]."--".$oneVisitor["phone"]]["buttons"] = array_merge($visitors[$oneVisitor["email"]."--".$oneVisitor["phone"]]["buttons"], $oneVisitor["buttons"]);
                }
                if ($visitors[$oneVisitor["email"]."--".$oneVisitor["phone"]]["comments"] == NULL) {
                    $visitors[$oneVisitor["email"]."--".$oneVisitor["phone"]]["comments"] = $oneVisitor["comments"];
                } else  if ($oneVisitor["comments"] != NULL) {
                    $visitors[$oneVisitor["email"]."--".$oneVisitor["phone"]]["comments"] = array_merge($visitors[$oneVisitor["email"]."--".$oneVisitor["phone"]]["comments"], $oneVisitor["comments"]);
                }
            } else {
                $visitors[$oneVisitor["email"]."--".$oneVisitor["phone"]] = $oneVisitor;
            }
        } else {
            $visitors[] = $oneVisitor;
        }
    }
    $report["visitors"] = array_values($visitors);
}
$log["report"]["modified"] = $report;

if (count($report["visitors"]) <= 10) {
    // Отправка сразу на скрипт
    include("ws-reports.php");
    echo json_encode($result);
} else {
    // Сохранения для планировщика
    if (file_exists("webinars") != true) {
        mkdir("webinars");
    }
    $log["report"]["write"] = file_put_contents("webinars/webinar-".$data["id"].".json", json_encode($report));
    echo "from cron";
}

send_forward(json_encode($log), $logUrl."_webhook");
