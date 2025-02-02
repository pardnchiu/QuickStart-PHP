<?php

namespace App\Models;

class Redis
{
    private $client;

    // 建構函式，初始化 Redis 連線
    public function __construct()
    {
        $this->getConnection(); // 確保連線初始化
    }

    // 檢查 Redis 是否已連線
    public function isConnected()
    {
        return $this->client !== null;
    }

    // 連接 Redis 伺服器
    private function getConnection()
    {
        // 如果已經連線，則不再進行連線
        if ($this->client !== null) {
            return;
        };

        try {
            // 從環境變數中讀取 Redis 配置，若無則使用預設值
            $host = (string) $_ENV["REDIS_HOST"] ?? "localhost";
            $port = (int) $_ENV["REDIS_PORT"] ?? 6379;
            $password = (string) $_ENV["REDIS_PASSWORD"] ?? '';
            $options  = [
                "host" => $host,
                "port" => $port,
                "persistent" => true
            ];

            // 如果有密碼則添加至選項中
            if (!empty($password)) {
                $options["password"] = $password;
            };

            // 使用 Predis 來建立 Redis 連線
            $this->client = new \Predis\Client($options);
            $this->client->select(0); // 選擇資料庫 0
            $this->client->connect(); // 建立連線
        } catch (\Exception $err) {
            // 若無法連線，輸出錯誤並設為 null
            PrintError("Redis 無法連線: " . $err->getMessage());
            http_response_code(500);
            $this->client = null;
        };
    }

    // 從 Redis 中取得資料
    public function get($db, $key)
    {
        $this->getConnection(); // 確保連線有效

        // 若 Redis 已連線，則從指定的資料庫取得指定鍵值的資料
        if ($this->client !== null) {
            $this->client->select($db);
            $result = $this->client->get($key);
            return $result;
        };

        return null;
    }

    // 設定資料到 Redis 中
    public function set($db, $key, $content, $expire)
    {
        $this->getConnection(); // 確保連線有效

        // 若 Redis 已連線，則將資料寫入指定的資料庫，並設置過期時間
        if ($this->client !== null) {
            $this->client->select($db);
            $this->client->set($key, $content);
            $this->client->expire($key, $expire);
        };
    }

    // 在物件銷毀時，斷開 Redis 連線
    public function __destruct()
    {
        if ($this->client !== null && $this->client->ping()) {
            $this->client->disconnect(); // 斷開連線
            $this->client = null;
        };
    }
}
