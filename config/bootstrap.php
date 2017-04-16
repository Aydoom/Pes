<?php

/* 
 * 
 */

if (!defined("DS")) {
    define("DS", DIRECTORY_SEPARATOR);
}

define("PESDIR", __DIR__ . DS . ".." . DS);

require_once PESDIR . "config" . DS . "PesClassAutoloader.php";
$autoloader = new Pes\Config\PesClassAutoloader();