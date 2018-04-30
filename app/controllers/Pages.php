<?php

class Pages{
    public function __construct(){

    }

    public function index(){
        echo "Index function";
    }

    public function about($id){
        echo "This is about function<br>";
        echo "This is ID - {$id}";
    }
}