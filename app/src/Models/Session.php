<?php

namespace App\Models;

class Session
{
    // 建構函式，根據 Redis 連線情況設定 Session 儲存方式
    public function __construct($REDIS_CLIENT = null)
    {
        // 顯示所有錯誤，並設定 Session 時間與垃圾回收時間
        ini_set("display_errors", 1);
        ini_set("display_startup_errors", 1);
        ini_set("session.cookie_lifetime", 86400 * 7);  // 7天有效
        ini_set("session.gc_maxlifetime", 86400 * 7);   // 7天過期
        error_reporting(E_ALL);

        // 如果 Redis 連線可用，則使用 Redis 儲存 Session
        if ($REDIS_CLIENT->isConnected()) {
            $host = (string) $_ENV["REDIS_HOST"] ?? "localhost";
            $port = (int) $_ENV["REDIS_PORT"] ?? 6379;
            $password = (string) $_ENV["REDIS_PASSWORD"] ?? '';
            $uri = "tcp://" . $host . ":" . $port . "?auth=" . $password . "&database=0&persistent=1"; // Redis 連線 URI

            // 設定 Session 儲存方式為 Redis
            ini_set("session.save_handler", "redis");
            ini_set("session.save_path", $uri);
        } else {
            // 如果 Redis 不可用，使用本地檔案儲存 Session
            $folder = get_path("/storage/sessions");

            // 若資料夾不存在且無法創建，則返回
            if (!is_dir($folder) && !mkdir($folder, 0777, true)) {
                return;
            };

            // 如果資料夾不可寫入，則返回
            if (!is_writable($folder)) {
                return;
            };

            // 設定 Session 儲存方式為檔案
            ini_set("session.save_handler", "files");
            ini_set("session.save_path", $folder);
        };

        // 開始 Session（若未啟動）
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        };
    }

    // 設定 Session 值
    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    // 取得 Session 值
    public function get($key)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    // 刪除 Session 值
    public function delete($key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        };
    }

    // 銷毀整個 Session
    public function destroy()
    {
        session_destroy();
    }

    // 重新生成 Session ID
    public function regenerateId()
    {
        session_regenerate_id(true);
    }

    // 取得當前 Session 的 ID
    public function getId()
    {
        return session_id();
    }

    // 取得 Session 創建時間
    public function getCreatedTime()
    {
        if (!isset($_SESSION["created_time"])) {
            $_SESSION["created_time"] = time();
        };

        return $_SESSION["created_time"];
    }
}
