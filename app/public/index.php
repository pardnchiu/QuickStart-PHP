<?php
$ROOT = $_SERVER["DOCUMENT_ROOT"];

function get_path($path)
{
    global $ROOT;
    return $ROOT . $path;
};

function get_view($path)
{
    return  get_path("/resources/Views/" . $path . ".php");
};

require_once get_path("/src/Init.php");
