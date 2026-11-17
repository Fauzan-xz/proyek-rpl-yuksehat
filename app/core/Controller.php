<?php

class Controller {
    public function view($view, $data = []) {
        $path1 = '../app/views/' . $view . '.php';
        $path2 = __DIR__ . '/../views/' . $view . '.php';
        if (file_exists($path1)) {
            require $path1;
        } elseif (file_exists($path2)) {
            require $path2;
        } else {
            die("View does not exist.");
        }
    }

    public function model($model) {
        $path1 = '../app/models/' . $model . '.php';
        $path2 = __DIR__ . '/../models/' . $model . '.php';
        if (file_exists($path1)) {
            require_once $path1;
            return new $model;
        } elseif (file_exists($path2)) {
            require_once $path2;
            return new $model;
        } else {
            die("Model does not exist.");
        }
    }
}
