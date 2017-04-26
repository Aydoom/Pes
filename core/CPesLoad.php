<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Pes\Core;

/**
 * Description of CPesLoad
 *
 * @author Aydoom
 */
class CPesLoad {

    public $encode;
    public $redirect;

    
    public function __construct($html, $encode = false, $redirect = true) {
        $this->encode = $encode;
        $this->redirect = $redirect;
        
        if ($this->isUrl($html)) {
            $url_string = trim($html);
            $url = parse_url($url_string);
            
            $path_array = explode(".", $url['path']);
            $url['type_file'] = end($path_array);
            $url['url'] = $url_string;
            // Проверяется на повторный запрос к хосту
            CPesControl::sleep($url);
            // Считываем страницу
            $code = CPesCurl::load($url['url'],$encode,$redirect);
        } else if ($this->isFile($html)) {
            
        } else if ($this->isHtml($html)){
            
        }
    }
    
    
    public function isUrl($html) {
        $url = parse_url(trim($html));
        return (isset($url['scheme']));
    }
            
}
