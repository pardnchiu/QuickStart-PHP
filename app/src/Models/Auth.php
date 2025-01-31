<?php

namespace Models;

use \Firebase\JWT\JWT;

class Auth
{
    private static $secret_key  = "your_secret_key";
    private static $token_name  = "auth_admin_token";
    private static $data_name   = "auth_admin";
    private static $expire_sec  = 3600;

    public static function create_jwt($auth_data)
    {
        $iat = time();
        $exp = $iat + self::$expire_sec;

        $payload = array(
            "iat" => $iat,
            "exp" => $exp,
            "user" => [
                "sn" => $auth_data["sn"],
                "email" => $auth_data["email"],
                "name" => $auth_data["name"]
            ]
        );

        return JWT::encode($payload, self::$secret_key, "HS256");
    }

    public static function add_jwt($auth_data)
    {
        $jwt = self::create_jwt($auth_data);
        $expired = time() + self::$expire_sec;

        setcookie(
            self::$token_name,
            $jwt,
            $expired,
            "/",
            "",
            false,
            true
        );
        setcookie(
            self::$data_name,
            json_encode($auth_data),
            $expired,
            "/",
            "",
            false,
            false
        );
    }

    public static function check_jwt()
    {
        $jwt = self::get_jwt();

        if ($jwt == null) {
            return;
        };

        try {
            $data = (object) JWT::decode(
                $jwt,
                new \Firebase\JWT\Key(self::$secret_key, "HS256")
            );

            if ($data->iat + 600 < time()) {
                self::add_jwt((array) $data->user);
            }
            return $data;
        } catch (\Exception $err) {
            PrintError($err->getMessage());
        };
    }

    public static function get_jwt()
    {
        if (isset($_SERVER["HTTP_AUTHORIZATION"])) {
            return str_replace("Bearer ", "", $_SERVER["HTTP_AUTHORIZATION"]);
        } else if (isset($_COOKIE[self::$token_name])) {
            return $_COOKIE[self::$token_name];
        };
    }

    // 用於登出時清除所有 cookie
    public static function remove_jwt()
    {
        setcookie(self::$token_name, "", 0, "/", "", false, true);
        setcookie(self::$data_name, "", 0, "/", "", false, false);
    }
}
