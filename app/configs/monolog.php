<?php

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FilterHandler;
use Monolog\Formatter\LineFormatter;

// 建立日誌紀錄器
$log = new Logger("app");

// 設定日誌記錄的時間為台灣時區
$log->pushProcessor(function ($record) {
    $date = new DateTime("now", new DateTimeZone("Asia/Taipei"));
    $time = $date->format("Y-m-d H:i:s");
    $record["extra"]["datetime"] = $time;
    return $record;
});

// 自定義日誌格式
$output = "%extra.datetime%: %message%\n";
$formatter = new LineFormatter($output);

// 設置處理器並應用格式化器
$infoStream = new StreamHandler($_SERVER["DOCUMENT_ROOT"] . "/storage/logs/info.log", Level::Info);
$infoStream->setFormatter($formatter);
$debugStream = new StreamHandler($_SERVER["DOCUMENT_ROOT"] . "/storage/logs/debug.log", Level::Debug);
$debugStream->setFormatter($formatter);
$errorStream = new StreamHandler($_SERVER["DOCUMENT_ROOT"] . "/storage/logs/error.log", Level::Error);
$errorStream->setFormatter($formatter);

// 設置過濾處理器
$infoHandler = new FilterHandler($infoStream, Level::Info, Level::Info);
$debugHandler = new FilterHandler($debugStream, Level::Debug, Level::Debug);
$errorHandler = new FilterHandler($errorStream, Level::Error, Level::Error);

// 將處理器推送到日誌紀錄器中
$log->pushHandler($infoHandler);
$log->pushHandler($debugHandler);
$log->pushHandler($errorHandler);

// 輸出帶有原始型別的日誌
function logMessage($level, $str)
{
    global $log;

    // 檢查是否為陣列，若是則轉換為 JSON 字串，並加上原始型別
    if (is_array($str)) {
        $str = "Array: " . json_encode($str);
    } elseif (is_object($str)) {
        $str = "Object: " . json_encode($str);
    };

    if ($level === "debug") {
        $log->debug($str);
    } else if ($level === "error") {
        $log->error($str);
    } else {
        $log->info($str);
    };
}

function PrintInfo($str)
{
    logMessage("info", $str);
}

function PrintDebug($str)
{
    logMessage("debug", $str);
}

function PrintError($str)
{
    logMessage("error", $str);
}
