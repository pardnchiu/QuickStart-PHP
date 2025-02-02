<?php

function decode_save_base64($base64)
{
    try {
        if (!is_string($base64)) {
            throw new \InvalidArgumentException("解碼 Base64 必須是字串");
        };

        $norm_base64 = str_replace(["-", "_"], ["+", "/"], trim($base64));
        $padding = strlen($norm_base64) % 4;

        if ($padding) {
            $norm_base64 .= str_repeat("=", 4 - $padding);
        };

        $text = base64_decode($norm_base64, true);

        if ($text === false) {
            throw new \InvalidArgumentException("Base64 解碼失敗");
        }

        return $text;
    } catch (\Exception $e) {
        PrintError($e->getMessage());
        return null;
    };
}

function get_json_from_save_base64($base64)
{
    try {
        $text = decode_save_base64($base64);

        if (preg_match("/^\"(.*)\"$/", $text, $matches)) {
            $text = stripslashes($matches[1]);
        };

        $json = json_decode($text, true);


        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \JsonException("JSON 解碼失敗：" . json_last_error_msg());
        };

        if (!is_array($json)) {
            throw new \UnexpectedValueException("非陣列: " . gettype($json));
        };

        return $json;
    } catch (\Exception $e) {
        PrintError($e->getMessage());
        return null;
    };
}
