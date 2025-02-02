<?php
require_once "../vendor/autoload.php";

foreach (glob("../configs/*.php") as $file) {
    require_once $file;
};

foreach (glob("../functions/*.php") as $file) {
    require_once $file;
};

ini_set("zlib.output_compression", "On");
ini_set("zlib.output_compression_level", "6");

if (file_exists(get_path("/.env"))) {
    $dotenv = Dotenv\Dotenv::createImmutable($ROOT);
    $dotenv->load();

    $ENV = $_ENV["ENV"];
};

$REDIS_CLIENT   = new PD\Redis(0);
$CACHE_CLIENT   = new PD\Cache($REDIS_CLIENT);
$SESSION_CLIENT = new PD\Session($REDIS_CLIENT);

if ($SESSION_CLIENT->get("session_id") === null) {
    $SESSION_CLIENT->set("created_time", date("Y-m-d H:i:s"));
    $SESSION_CLIENT->set("session_id", $SESSION_CLIENT->getId());
};

$Router = new App\Router();
$Router->init();
