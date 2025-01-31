<?php

namespace GET;

use Controllers\Controller;

class IndexController extends Controller
{
    private string $view = "Index";

    public function __construct($params)
    {
        parent::__construct($params);
    }

    public function init()
    {
        $this->render();
    }

    protected function render($data = [])
    {
        extract($data);

        ob_start();

        include GET_VIEW($this->view);

        echo ob_get_clean();
    }
};
