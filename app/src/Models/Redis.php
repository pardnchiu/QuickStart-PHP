<?php

namespace Models;

class Redis
{
    private $client;

    public function __construct()
    {
        $this->getConnection();
    }

    public function isConnected()
    {
        return $this->client !== null;
    }

    private function getConnection()
    {
        if ($this->client !== null) {
            return;
        };

        try {
            $host = (string) $_ENV["REDIS_HOST"] ?? "localhost";
            $port = (int) $_ENV["REDIS_PORT"] ?? 6379;
            $password = (string) $_ENV["REDIS_PASSWORD"] ?? '';
            $options  = [
                "host" => $host,
                "port" => $port,
                "persistent" => true
            ];

            if (!empty($password)) {
                $options["password"] = $password;
            };

            $this->client = new \Predis\Client($options);
            $this->client->select(0);
            $this->client->connect();
        } catch (\Exception $err) {
            PrintError("Redis 無法連線: " . $err->getMessage());
            http_response_code(500);
            $this->client = null;
        };
    }

    public function get($db, $key)
    {
        $this->getConnection();

        if ($this->client !== null) {
            $this->client->select($db);
            $result = $this->client->get($key);
            return $result;
        };

        return null;
    }

    public function set($db, $key, $content, $expire)
    {
        $this->getConnection();

        if ($this->client !== null) {
            $this->client->select($db);
            $this->client->set($key, $content);
            $this->client->expire($key, $expire);
        };
    }

    public function __destruct()
    {
        if ($this->client !== null && $this->client->ping()) {
            $this->client->disconnect();
            $this->client = null;
        };
    }
}
