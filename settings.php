<?php

include("config.php");

$log = [];

if (count($_POST) > 1) {
    send_forward(json_encode($_POST), $logUrl."_setting-data");
    file_put_contents("settings.json", json_encode($_POST));
//echo json_encode($_POST);
}

if (file_exists("settings.json")) {
    $settings = json_decode(file_get_contents("settings.json"), true);
}

// Чтение данных SmartSender
// Переменные
$contactTable = [];
$ps=1;
for($p=1;$p<=$ps;$p++) {
    $listVar = json_decode(send_bearer("https://api.smartsender.com/v1/variables?limitation=20&page=".$p, $ssKey), true);
    if ($listVar["collection"] != NULL) {
        $ps = $listVar["cursor"]["pages"];
        foreach($listVar["collection"] as $oneVar) {
            $log["vars"][] = $oneVar;
            $contactTable[] = '<tr><td>'.$oneVar["name"].'</td><td><input name="contactFields-'.$oneVar["name"].'" list="webinar-data" value="'.$settings["contactFields-".$oneVar["name"]].'"></td></tr>';
        }
    }
}
// Теги
$tagOption = [];
$ps=1;
for($p=1; $p<=$ps; $p++) {
    $listTag = json_decode(send_bearer("https://api.smartsender.com/v1/tags?limitation=20&page=".$p, $ssKey), true);
    if ($listTag["collection"] != NULL) {
        $ps = $listTag["cursor"]["pages"];
        foreach ($listTag["collection"] as $oneTag) {
            $log["tags"][] = $oneTag;
            if ($settings["contactTag"] == $oneTag["id"]) {
                $tagOption[] = '<option selected value="'.$oneTag["id"].'">'.$oneTag["name"].'</option>';
            } else {
                $tagOption[] = '<option value="'.$oneTag["id"].'">'.$oneTag["name"].'</option>';
            }
        }
    }
}
$tagList = '<option value="">Не добавлять</option>'.implode("", $tagOption);

// Воронки
$funnelOption = [];
$ps=1;
for($p=1; $p<=$ps; $p++) {
    $listFunnel = json_decode(send_bearer("https://api.smartsender.com/v1/funnels?limitation=20&page=".$p, $ssKey), true);
    if ($listFunnel["collection"] != NULL) {
        $ps = $listFunnel["cursor"]["pages"];
        foreach ($listFunnel["collection"] as $oneFunnel) {
            $log["funnels"][] = $oneFunnel;
            if ($settings["contactFunnel"] == $oneFunnel["serviceKey"]) {
                $funnelOption[] = '<option selected value="'.$oneFunnel["serviceKey"].'">'.$oneFunnel["name"].'</option>';
            } else {
                $funnelOption[] = '<option value="'.$oneFunnel["serviceKey"].'">'.$oneFunnel["name"].'</option>';
            }
        }
    }
}
$funnelList = '<option value="">Не подписывать</option>'.implode("", $funnelOption);


$webinarFields = ["name","email","phone","city","date_start","date_end","buttons","comments","webinarId","webinarName","webinarStart","webinarEnd","webinarDuration","duration","messages", "text%", "date%[format]%[date/offset]"];

$datalist = '<datalist id="webinar-data"><option>'.implode("</option><option>", $webinarFields).'</option></datalist>';




?>
<html>
    <head>
        <title>Настройки сопоставления</title>
        <meta charset="utf-8">
        <style type="text/css">
            .header {
                text-align: center;
            }
            .bodys {
                text-align: center;
            }
            .bodys form {
                display: flex;
                justify-content: space-around;
            }
            .bodys form thead {
                text-align: center;
            }
            .bodys form h3 {
                text-align: center;
            }
            .bodys form input, .bodys form textarea, .bodys form select {
                width: 250px;
            }
            .bodys form textarea {
                height: 100px;
            }
            .bodys form input[type="number"] {
                width: 100px;
            }
            .bodys pre {
                display: inline;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h2>Настройки интеграции webinar-stars и Smart Sender</h2>
            <p>Настройте сопоставление всех необходимых полей<br>все урл-параметры и utm-метки можно указать вручную</p>
            <div class="datalist">
                <?php echo $datalist ?>
            </div>
            <div class="log">
                <script>
                    console.log(JSON.parse('<?php echo json_encode($log) ?>'));
                </script>
            </div>
        </div>
        <div class="bodys">
            <form id="settings" method="post">
            <div class="contactsField">
                <h3>Поля контакта</h3>
                <table>
                    <thead>
                        <tr>
                            <td>Переменные пользователя</td>
                            <td>Данные отчета</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Телефон</td>
                            <td><input name="contactFields-phone" list="webinar-data" value="<?php echo $settings["contactFields-phone"] ?>"></td>
                        </tr>
                        <tr>
                            <td>Почта</td>
                            <td><input name="contactFields-email" list="webinar-data" value="<?php echo $settings["contactFields-email"] ?>"></td>
                        </tr>
                        <?php echo implode("", $contactTable) ?>
                    </tbody>
                </table>
            </div>
            <div class="contactsActions">
                <h3>Дополнительные действия</h3>
                <table>
                    <thead>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Добавить тег:</td>
                            <td>
                                <select name="contactTag">
                                    <?php echo $tagList ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Подписать на воронку:<pre>    </pre></td>
                            <td>
                                <select name="contactFunnel">
                                    <?php echo $funnelList ?>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </form>
            <input type="submit" form="settings">
        </div>
        <div class="footers">
            
        </div>
    </body>
</html>

