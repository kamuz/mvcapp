<?php

// Debug
function dd($var) {
    echo '<pre>';
    print_r($var);
    echo '</pre>';
}

// Load config
require_once "config/config.php";

// Autoload Core Libraries
spl_autoload_register(function($className){
    require_once 'libraries/' . $className . '.php';
});