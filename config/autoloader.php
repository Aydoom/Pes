<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class PesClassAutoloader
{
    
    
    
    public function __construct() {
        spl_autoload_register([$this, "load"]);
    }
    
    
    
    private function load($className){
        
        $path = explode("\\", $className);
        
        if ($path[0] === "Pes") {
            
            $fileNames = [
                PESDIR . "core" . DS . $path[1] . ".php",
                PESDIR . "src" . DS . $path[1] . ".php",
                PESDIR . "test" . DS . $path[1] . ".php",
            ];
            
            foreach ($fileNames as $file) {
                
                if (file_exists($file)) {
                    
                    include_once $file;
                    
                }
                
            }
            
        }
        
    }
            
}

$autoloader = new PesClassAutoloader();