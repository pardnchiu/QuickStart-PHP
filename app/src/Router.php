<?php

namespace App;

class Router
{
    // 儲存各 HTTP 方法的路由
    private $GET_ROUTES = [];
    private $POST_ROUTES = [];
    private $PATCH_ROUTES = [];
    private $PUT_ROUTES = [];
    private $DELETE_ROUTES = [];

    // 構造函數，載入各個 HTTP 方法對應的路由設定
    public function __construct()
    {
        $this->GET_ROUTES = include get_path("/src/Routes/GET.php");
        $this->POST_ROUTES = include get_path("/src/Routes/POST.php");
        $this->PATCH_ROUTES = include get_path("/src/Routes/PATCH.php");
        $this->PUT_ROUTES = include get_path("/src/Routes/PUT.php");
        $this->DELETE_ROUTES = include get_path("/src/Routes/DELETE.php");
    }

    // 初始化路由，根據請求方法選擇對應的路由處理
    public function init()
    {
        global $ENV;
        // 取得請求的 URI 路徑與方法
        $uri = $_SERVER["REQUEST_URI"];
        $path = parse_url($uri, PHP_URL_PATH);

        try {
            switch ($_SERVER["REQUEST_METHOD"]) {
                case "HEAD":
                case "GET":
                    // 處理 GET 請求
                    $this->handle($this->GET_ROUTES, $path);
                    break;
                case "POST":
                    // 處理 POST 請求
                    $this->handle($this->POST_ROUTES, $path);
                    break;
                case "PATCH":
                    // 處理 PATCH 請求
                    $this->handle($this->PATCH_ROUTES, $path);
                    break;
                case "PUT":
                    // 處理 PUT 請求
                    $this->handle($this->PUT_ROUTES, $path);
                    break;
                case "DELETE":
                    // 處理 DELETE 請求
                    $this->handle($this->DELETE_ROUTES, $path);
                    break;
                default:
                    // 其他 HTTP 方法不支援，回傳 405 錯誤
                    http_response_code(405);
                    throw new \Exception('405: 方法不存在.');
            };
        } catch (\Exception $err) {
            PrintError($err);
        };
    }

    // 處理路由匹配
    private function handle($routes, $path)
    {
        foreach ($routes as $route => $controller) {
            // 轉換路由模式為正規表達式
            $pattern = preg_replace("/:([\w\-\_\.]+)/", "(.+)", $route);
            $pattern = str_replace("/", "\/", $pattern);

            // 檢查路由是否匹配
            if (preg_match('/^' . $pattern . '$/', $path, $matches)) {
                array_shift($matches);  // 移除匹配的路由部分

                // 擷取路由中的參數名稱並對應參數值
                preg_match_all('/:([\w\-\_\.]+)/', $route, $keys);
                $params = array_combine($keys[1], $matches);

                // 執行對應的控制器
                $this->get($controller, $params);
                return;
            };
        };

        // 如果沒有匹配到路由，回傳 404 錯誤
        http_response_code(404);
        throw new \Exception($path . " 路由不存在");
    }

    // 執行控制器中的方法
    private function get($controller, $params = [])
    {
        // 檢查控制器類別是否存在
        if (!class_exists($controller)) {
            http_response_code(500);
            throw new \Exception(" 尚未設定控制器");
        };

        // 創建控制器實例並傳遞參數
        $instance = new $controller($params);

        // 檢查控制器中是否存在 init() 方法
        if (!method_exists($instance, "init")) {
            http_response_code(500);
            throw new \Exception(" init() 不存在");
        };

        // 執行控制器的 init() 方法
        $instance->init();
    }
}
