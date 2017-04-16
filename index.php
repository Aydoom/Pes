<?php

/* 
 * Pesphp - парсер html страниц
 */

if (!defined("DS")) {
    define("DS", DIRECTORY_SEPARATOR);
}


require 'config' . DS . 'bootstrap.php';


$habrhabr = new Pes\Src\HabrhabrPes();
