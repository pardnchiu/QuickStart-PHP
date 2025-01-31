<?php
$ROOT = $_SERVER["DOCUMENT_ROOT"];

function PATH($path) {
    global $ROOT;
    return $ROOT . $path;
};

require_once PATH("/src/Init.php");
