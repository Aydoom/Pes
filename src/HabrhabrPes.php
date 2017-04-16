<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of HabrhabrPes
 *
 * @author Aydoom
 */

namespace Pes\Src;

class HabrhabrPes extends \Pes\Core\Pes {
    //put your code here
    
    public function __construct(){
        
        parent::__construct("https://habrahabr.ru/");
        
    }
            
}
