<?php

namespace App;

class Router
{
    private $GET_ROUTES = [];
    private $POST_ROUTES = [];
    private $PATCH_ROUTES = [];
    private $PUT_ROUTES = [];
    private $DELETE_ROUTES = [];

    public function __construct()
    {
        $this->GET_ROUTES = include PATH("/src/Routes/GET.php");
        $this->POST_ROUTES = include PATH("/src/Routes/POST.php");
        $this->PATCH_ROUTES = include PATH("/src/Routes/PATCH.php");
        $this->PUT_ROUTES = include PATH("/src/Routes/PUT.php");
        $this->DELETE_ROUTES = include PATH("/src/Routes/DELETE.php");
    }

    public function init()
    {
        global $ENV;

        $uri = $_SERVER["REQUEST_URI"];
        $path = parse_url($uri, PHP_URL_PATH);

        try {
            switch ($_SERVER["REQUEST_METHOD"]) {
                case "HEAD":
                case "GET":
                    $this->handle($this->GET_ROUTES, $path);
                    break;
                case "POST":
                    $this->handle($this->POST_ROUTES, $path);
                    break;
                case "PATCH":
                    $this->handle($this->PATCH_ROUTES, $path);
                    break;
                case "PUT":
                    $this->handle($this->PUT_ROUTES, $path);
                    break;
                case "DELETE":
                    $this->handle($this->DELETE_ROUTES, $path);
                    break;
                default:
                    http_response_code(405);
                    throw new \Exception('405: 方法不存在.');
            };
        } catch (\Exception $err) {
            if ($ENV === "develop") {
                PrintError($err->getMessage());
            };
        };
    }

    private function handle($routes, $path)
    {
        foreach ($routes as $route => $controller) {
            $pattern = preg_replace("/:([\w\-\_\.]+)/", "(.+)", $route);
            $pattern = str_replace("/", "\/", $pattern);

            if (preg_match("/^" . $pattern . "$/", $path, $matches)) {
                array_shift($matches);

                preg_match_all("/:([\w\-\_\.]+)/", $route, $keys);
                $params = array_combine($keys[1], $matches);

                $this->get($controller, $params);
                return;
            };
        };

        http_response_code(404);
        throw new \Exception($path . " 路由不存在");
    }

    private function get($controller, $params = [])
    {
        if (!class_exists($controller)) {
            http_response_code(500);
            throw new \Exception(" 尚未設定控制器");
        };

        $instance = new $controller($params);

        if (!method_exists($instance, "init")) {
            http_response_code(500);
            throw new \Exception(" init() 不存在");
        };

        $instance->init();
    }
}
