<?php

include("config.php");

$webinars = scandir(dirname($_SERVER["SCRIPT_FILENAME"])."/webinars");

file_put_contents(dirname($_SERVER["SCRIPT_FILENAME"])."/cron", "OK");

$log["files"] = $webinars;

foreach ($webinars as $oneWebinar) {
    if (stripos($oneWebinar, "webinar-") === 0) {
        $report = json_decode(file_get_contents(dirname($_SERVER["SCRIPT_FILENAME"])."/webinars/".$oneWebinar), true);
        $file = $oneWebinar;
        break;
    }
}

if ($report["report"]["visitors"] != NULL && is_array($report["report"]["visitors"])) {
    foreach ($report["report"]["visitors"] as $key => $value) {
        if ($key > 20) {
            $saveFile = true;
            break;
        }
        $curentVisitors[] = $value;
        unset($report["report"]["visitors"][$key]);
    }
    
    $report["report"]["visitors"] = array_values($report["report"]["visitors"]);
    file_put_contents(dirname($_SERVER["SCRIPT_FILENAME"])."/webinars/".$file, json_encode($report));
    $report["report"]["visitors"] = $curentVisitors;
    
    $log["report"] = $report;
    send_forward(json_encode($log), $logUrl."_cron");
    include("ws-reports.php");
    
    if ($saveFile !== true) {
        // удаление файла отчета
        unlink(dirname($_SERVER["SCRIPT_FILENAME"])."/webinars/".$file);
    }
} else {
    unlink(dirname($_SERVER["SCRIPT_FILENAME"])."/webinars/".$file);
}

echo json_encode($result);