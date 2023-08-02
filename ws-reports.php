<?php

if (file_exists("settings.json")) {
    $settings = json_decode(file_get_contents("settings.json"), true);
} else {
    exit;
}


if ($report["report"]["visitors"] != NULL && is_array($report["report"]["visitors"])) {
    foreach ($report["report"]["visitors"] as &$oneVisitor) {
        $logDescription = [];
        $logData["state"] = "true";
        // Форматирование query
        if ($oneVisitor["utm"] != "") {
            $utms = explode("&", $oneVisitor["utm"]);
            foreach ($utms as $oneUtm) {
                $utmData = explode("=", $oneUtm);
                $oneVisitor[urldecode($utmData[0])] = urldecode($utmData[1]);
            }
        }
        // Добавление базовых параметров вебинара
        $oneVisitor["webinarId"] = $report["report"]["id"];
        $oneVisitor["webinarName"] = $report["report"]["webinar_name"];
        $oneVisitor["webinarStart"] = $report["report"]["date_start"];
        $oneVisitor["webinarEnd"] = $report["report"]["date_end"];
        $oneVisitor["webinarDuration"] = $report["report"]["duration"];
        $oneVisitor["duration"] = (strtotime($oneVisitor["date_end"]) - strtotime($oneVisitor["date_start"])) / 60;
        settype($oneVisitor["duration"], "int");
        $oneVisitor["formatDate"] = date("d-m-y", strtotime($oneVisitor["webinarStart"]));
        $oneVisitor["formatTime"] = date("H:i", strtotime($oneVisitor["webinarStart"]));
        $oneVisitor["buttons"] = $oneVisitor["buttons"][0];
        if (count($oneVisitor["comments"]) >= 1) {
            foreach ($oneVisitor["comments"] as $oneComment) {
                $comments[] = $oneComment["created_at"]."\r\n".$oneComment["text"];
                
            }
            $oneVisitor["messages"] = "Сообщения пользователя в чате:\r\n\r\n".implode("\r\n", $comments);
        } else {
            $oneVisitor["messages"] = "Нет истории сообщений";
        }
        // Подготовка масивов переменных
        unset($search); unset($replace);
        foreach ($oneVisitor as $key => $value) {
            $search[] = "{{ ".$key." }}";
            $replace[] = $value;
        }
        // Подготовка данных к отправке
        foreach ($settings as $fieldKey => $fieldValue) {
            if ($fieldValue == NULL || $fieldValue == "") {
                continue;
            }
            if ($fieldKey == "contactTag" && $fieldValue != NULL) {
                $tagId = $fieldValue;
            }
            
            if ($fieldKey == "contactFunnel" && $fieldValue != NULL) {
                $funnelId = $fieldValue;
            }
            $fieldsData = explode("-", $fieldKey, 2);
            if ($fieldsData[0] == "contactFields") {
                if (stripos($fieldValue, "text%") === 0) {
                    $contact["values"][$fieldsData[1]] = str_ireplace($search, $replace, explode("%", $fieldValue)[1]);
                } else if (stripos($fieldValue, "date%") === 0) {
                    $temp = explode("%", $fieldValue);
                    $timestamp = strtotime($temp[2]);
                    $contact["values"][$fieldsData[1]] = date($temp[1], $timestamp);
                } else if ($oneVisitor[$fieldValue] != NULL) {
                    $contact["values"][$fieldsData[1]] = $oneVisitor[$fieldValue];
                }
            }
        }
        // Поиск контакта
        if ($oneVisitor["ssId"] != NULL) {
            $contactId = $oneVisitor["ssId"];
        }
        if ($contact["values"]["phone"] != NULL) {
            $phone = str_ireplace([" ", "(", ")", "-", "+", "'"], "", $contact["values"]["phone"]);
            if ($contactId == NULL) {
                $search = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/search?page=1&limitation=1&term=".urlencode($phone), $ssKey), true);
                if ($search["collection"][0]["id"] != NULL) {
                    $contactId = $search["collection"][0]["id"];
                }
            }
        }
        if ($contact["values"]["email"] != NULL) {
            $email = strtolower($contact["values"]["email"]);
            if ($contactId == NULL) {
                $search = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/search?page=1&limitation=1&term=".urlencode($email), $ssKey), true);
                if ($search["collection"][0]["id"] != NULL) {
                    $contactId = $search["collection"][0]["id"];
                }
            }
        }
        // Обновление контакта
        if ($contactId != NULL) {
            if ($contact["values"] != NULL) {
                $log["contact"] = [
                    "id" => $contactId,
                    "updates" => [
                        "send" => $contact,
                        "result" => json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$contactId, $ssKey, "PUT", $contact), true),
                    ],
                ];
            }
            if ($tagId != NULL) {
                $log["contact"]["tag"] = [
                    "id" => $tagId,
                    "result" => json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$contactId."/tags/".$tagId), true),
                ];
            }
            if ($funnelId != NULL) {
                $log["contact"]["funnel"] = [
                    "id" => $funnelId,
                    "result" => json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$contactId."/funnels/".$funnelId), true),
                ];
            }
        }
        
        
        $log["viewersData"] = $oneVisitor;
        $logData["description"] = implode("<br>", $logDescription);
        send_forward(json_encode($log), $logUrl."_report?".http_build_query($logData));
        unset($log); unset($logData); unset($logDescription); unset($contact); unset($deal); unset($comment1); unset($comment2); unset($task); unset($contactId); unset($dealId); unset($oneVisitor); unset($comments); unset($phones); unset($emails); unset($addedEmail); unset($addedPhone); unset($email); unset($phone); unset($ownerId);
    }
}
        
        
        
        
        
        