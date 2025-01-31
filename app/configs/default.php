<?php

$IS_HTTPS = (isset($_SERVER["REDIRECT_HTTPS"]) && $_SERVER["REDIRECT_HTTPS"] === "on") || (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https");
$LOCATION_HREF = ($IS_HTTPS ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

function GET_VIEW($path)
{
    return  PATH("/resources/Views/" . $path . ".php");
};

function RESPONSE($data, int $statusCode = 200)
{
    header("Content-Type: application/json");
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function SUCCESS($data = null, $code = null)
{
    if ($code === null && $data === null) {
        $code = 200;
    };

    if (is_numeric($data)) {
        $code = $data;
        $data = null;
    };

    $response = ["message" => "成功"];

    if ($data !== null) {
        $response["data"] = $data;
    };

    RESPONSE($response, $code);
}

function ERROR($message, $code)
{
    RESPONSE(["message" => $message], $code);
}

function GET_TEXT($filepath)
{
    if (!file_exists($filepath)) {
        return;
    };

    $content = file_get_contents($filepath);

    return $content ?? "";
}

function GET_JSON($filepath)
{
    $content = GET_TEXT($filepath);
    $data = json_decode($content, true);

    if (!is_array($data)) {
        return;
    };

    return $data;
}

function CREATE_JSON($filepath, $data)
{
    file_put_contents($filepath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function GET_FRMO_BASE64($base64)
{
    try {
        $$base64 = str_replace(["-", "_"], ["+", "/"], trim($base64));
        $padding = strlen($$base64) % 4;

        if ($padding) {
            $$base64 .= str_repeat("=", 4 - $padding);
        };

        $base64_decode = base64_decode($$base64, true);

        if ($base64_decode === false) {
            throw new \Exception("Failed: Base decode.");
        };

        if (preg_match('/^"(.*)"$/', $base64_decode, $matches)) {
            $base64_decode = stripslashes($matches[1]);
        };

        $json_decode = json_decode($base64_decode, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(json_last_error_msg());
        };

        if ($json_decode === null) {
            throw new \Exception("");
        };

        if (!is_array($json_decode)) {
            throw new \Exception("Type error: " . gettype($json_decode));
        };

        return $json_decode;
    } catch (\Exception $e) {
        throw $e;
    };
}
