<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Pes\Core;

/**
 * Description of CPesSelector
 *
 * @author aydoom
 */
class CPesSelector {
    
    public $selectors = [];
    
    
    
    public function __construct($selector) {
        
        $this->pars($selector);
        
    }
    
    
    public function pars($selector) {
        
        $output = preg_match("/#([^\.>:]+)*/", $selector);
        
        $this->selectors['id'] = $output[1];
        
    }
    
}
