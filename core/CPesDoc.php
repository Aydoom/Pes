<?php
/**
 * Хранилище данных
 */
class CPesDoc{

	private $dom;
	private $url;
	private $fix;
	
	function setDom($dom){
		$this->dom = $dom;
	}
	
	function getDom(){
		return $this->dom;
	}

	function setUrl($url){
		$this->url = $url;
	}
	
	function getUrl(){
		return $this->url;
	}
	
	function fix($flag){
		$this->fix[$flag] = $this->dom;
	}
	
	function unset_fix($flag=false){
		if($flag)
			unset($this->fix[$flag]);
		else
			$this->fix = array();
	}

	function up($flag){
		$this->dom = $this->fix[$flag];
	}
}