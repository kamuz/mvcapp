<?php

class Pages extends Controller{
    public function __construct(){
        echo "Posts loaded";
    }

    public function index(){
        $data = [
            'title' => 'Welcome'
        ];
        $this->view("pages/index", $data);
    }
}