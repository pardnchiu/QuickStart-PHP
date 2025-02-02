<?php

namespace App\Controllers;

class Controller
{
    protected array $post_data = [];
    protected array $params = [];

    public function __construct($params)
    {
        $this->params = $params ?? [];
        $this->post_data = $this->get_post_data();
    }

    protected function get_post_data(): array
    {
        $input = file_get_contents("php://input");
        $json = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        };

        return $json;
    }
}
