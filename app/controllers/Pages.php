<?php

class Pages extends Controller{
    public function __construct(){

    }

    public function index(){
        $this->view("pages");
    }

    public function about($id){
        echo "This is about function<br>";
        echo "This is ID - {$id}";
    }
}