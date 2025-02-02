<?php

namespace App\Models;

class Cache
{
    private $REDIS_CLIENT;

    // 建構函式，初始化 Redis 客戶端
    public function __construct($REDIS_CLIENT = null)
    {
        $this->REDIS_CLIENT = $REDIS_CLIENT;
    }

    // 取得快取資料
    public function get($page)
    {
        $page_key = md5($page); // 生成頁面鍵值

        // 若 Redis 已連線，從 Redis 取得資料
        if ($this->REDIS_CLIENT->isConnected()) {
            return $this->REDIS_CLIENT->get(1, $page_key);
        };

        // 設定快取檔案路徑
        $folder = get_path("/storage/caches");
        $file   = $folder . "/" . $page_key . ".json";

        // 資料夾不存在且無法創建時，返回 null
        if (!file_exists($folder) && !mkdir($folder, 0777, true)) {
            return null;
        };

        // 資料夾不可寫入時，返回 null
        if (!is_writable($folder)) {
            return null;
        };

        // 快取檔案不存在時，返回 null
        if (!file_exists($file)) {
            return null;
        };

        $content = file_get_contents($file);    // 讀取快取檔案內容
        $data    = json_decode($content, true); // 解碼 JSON 快取內容

        // 快取過期，刪除並返回 null
        if (isset($data["expire"]) && $data["expire"] < time()) {
            unlink($file);
            return null;
        };

        return $data["content"]; // 返回快取內容
    }

    // 設定快取資料
    public function set($page, $content, $expire)
    {
        $page_key = md5($page);                              // 生成頁面鍵值
        $content = preg_replace("/\n[ ]*/", "", $content);   // 移除多餘空白與換行
        $content = preg_replace("/>[ ]*</", "><", $content); // 移除標籤間空白

        // 若 Redis 已連線，將資料存入 Redis
        if ($this->REDIS_CLIENT->isConnected()) {
            $this->REDIS_CLIENT->set(1, $page_key, $content, $expire);
            return $content;
        };

        // 設定快取檔案路徑
        $folder = get_path("/storage/caches");
        $file = $folder . "/" . $page_key . ".json";
        $data = [
            "content" => $content,
            "expire" => time() + $expire
        ]; // 設定快取資料

        // 資料夾不存在且無法創建時，返回 null
        if (!file_exists($folder) && !mkdir($folder, 0777, true)) {
            return null;
        }

        // 資料夾不可寫入時，返回 null
        if (!is_writable($folder)) {
            return null;
        };

        // 將快取資料寫入檔案
        file_put_contents($file, json_encode($data, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $content; // 返回快取內容
    }

    // 清理過期的快取資料
    public function clean()
    {
        $folder = get_path("/storage/caches");

        // 若資料夾不存在，直接返回
        if (!file_exists($folder)) {
            return;
        };

        $files = glob($folder . "/*.json"); // 獲取所有快取檔案

        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            };

            $content = file_get_contents($file);    // 讀取快取檔案內容
            $data = json_decode($content, true); // 解碼 JSON 快取內容

            // 若快取已過期，刪除快取檔案
            if (isset($data["expire"]) && $data["expire"] < time()) {
                unlink($file);
            };
        };
    }
}
