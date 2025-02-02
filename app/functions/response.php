<?php

function Response($data, int $statusCode = 200)
{
    header("Content-Type: application/json");
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function Success($data = null, $code = null)
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

    Response($response, $code);
}

function Error($message, $code)
{
    Response(["message" => $message], $code);
}
