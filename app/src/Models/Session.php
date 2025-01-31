<?php

namespace Models;

class Session
{
    public function __construct($REDIS_CLIENT = null)
    {
        ini_set("display_errors", 1);
        ini_set("display_startup_errors", 1);
        ini_set("session.cookie_lifetime", 86400 * 7);
        ini_set("session.gc_maxlifetime", 86400 * 7);
        error_reporting(E_ALL);

        if ($REDIS_CLIENT->isConnected()) {
            $host = (string) $_ENV["REDIS_HOST"] ?? "localhost";
            $port = (int) $_ENV["REDIS_PORT"] ?? 6379;
            $password = (string) $_ENV["REDIS_PASSWORD"] ?? '';
            $uri = "tcp://" . $host . ":" . $port . "?auth=" . $password . "&database=0&persistent=1";

            ini_set("session.save_handler", "redis");
            ini_set("session.save_path", $uri);
        } else {
            $folder = PATH("/storage/sessions");

            if (!is_dir($folder) && !mkdir($folder, 0777, true)) {
                return;
            };

            if (!is_writable($folder)) {
                return;
            };

            ini_set("session.save_handler", "files");
            ini_set("session.save_path", $folder);
        };

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        };
    }

    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function get($key)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    public function delete($key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        };
    }

    public function destroy()
    {
        session_destroy();
    }

    public function regenerateId()
    {
        session_regenerate_id(true);
    }

    public function getId()
    {
        return session_id();
    }

    public function getCreatedTime()
    {
        if (!isset($_SESSION["created_time"])) {
            $_SESSION["created_time"] = time();
        };

        return $_SESSION["created_time"];
    }
}
