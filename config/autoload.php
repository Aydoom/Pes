<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once PESDIR . "core" . DS . "CAutoloader.php";

$autoload = new Pes\Core\CAutoloader();

spl_autoload_register([$autoload, "load"]);


