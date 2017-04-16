<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Pes\Config;

class PesClassAutoloader
{
    
    
    
    public function __construct() {
        spl_autoload_register([$this, "load"]);
    }
    
    
    
    private function load($className){
        
        $path = explode("\\", $className);
        
        $root = array_shift($path);
        $fileName = array_pop($path) . ".php";
        
        if ($root === "Pes") {
            
            $path = array_map('strtolower', $path);
            $fileUrl = PESDIR . implode(DS, $path) . DS . $fileName;
                
            if (file_exists($fileUrl)) {

                include_once $fileUrl;

            }
            
        }
        
    }
            
}

