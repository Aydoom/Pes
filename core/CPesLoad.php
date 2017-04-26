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
	
	private $html;

    
    public function __construct($html, $encode = false, $redirect = true)
	{
        $this->encode = $encode;
        $this->redirect = $redirect;
        
		
        if ($this->isUrl($html)) {
			
			$this->loadUrl($html);
			
        } elseif (is_file($html) ) {
			
            $this->loadFile($html);
			
        } elseif (strlen($html) > 0){
			
            $this->loadHtml($html);
			
        }
    }


	public function getHtml()
	{
		return $this->html;
	}
    
    public function isUrl($html)
	{
        $url = parse_url(trim($html));
        return (isset($url['scheme']));
    }
	
	
	public function loadFile($fileName)
	{
		$this->html = file_get_contents($fileName);
	}
	
	
	public function loadHtml($html)
	{
		$this->html = $html;
	}
    
	
	public function loadUrl($url_string)
	{
		$url = parse_url(trim($url_string));
		
		$path_array = explode(".", $url['path']);
		
		$url['type_file'] = end($path_array);
		
		$url['url'] = $url_string;
		
		// Проверяется на повторный запрос к хосту
		CPesControl::sleep($url);
		
		// Считываем страницу
		$this->html = CPesCurl::load($url['url'], $this->encode, $this->redirect);
		
	}
}
