<?php

namespace Models;

class Cache
{
    private $REDIS_CLIENT;

    public function __construct($REDIS_CLIENT = null)
    {
        $this->REDIS_CLIENT = $REDIS_CLIENT;
    }

    public function get($page)
    {
        $page_key = md5($page);

        if ($this->REDIS_CLIENT->isConnected()) {
            return $this->REDIS_CLIENT->get(1, $page_key);
        };

        $folder = PATH("/storage/caches");
        $file   = $folder . "/" . $page_key . ".json";

        if (!file_exists($folder) && !mkdir($folder, 0777, true)) {
            return null;
        };

        if (!is_writable($folder)) {
            return null;
        };

        if (!file_exists($file)) {
            return null;
        };

        $content = file_get_contents($file);
        $data    = json_decode($content, true);

        if (isset($data["expire"]) && $data["expire"] < time()) {
            unlink($file);
            return null;
        };

        return $data["content"];
    }

    public function set($page, $content, $expire)
    {
        $page_key = md5($page);
        $content = preg_replace("/\n[ ]*/", "", $content);
        $content = preg_replace("/>[ ]*</", "><", $content);

        if ($this->REDIS_CLIENT->isConnected()) {
            $this->REDIS_CLIENT->set(1, $page_key, $content, $expire);
            return $content;
        };

        $folder = PATH("/storage/caches");
        $file = $folder . "/" . $page_key . ".json";
        $data = [
            "content" => $content,
            "expire" => time() + $expire
        ];

        if (!file_exists($folder) && !mkdir($folder, 0777, true)) {
            return null;
        };

        if (!is_writable($folder)) {
            return null;
        };

        file_put_contents($file, json_encode($data, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $content;
    }

    public function clean()
    {
        $folder = PATH("/storage/caches");

        if (!file_exists($folder)) {
            return;
        };

        $files = glob($folder . "/*.json");

        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            };

            $content = file_get_contents($file);
            $data = json_decode($content, true);

            if (isset($data["expire"]) && $data["expire"] < time()) {
                unlink($file);
            };
        };
    }
}
