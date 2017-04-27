<?php
/**
 * Основной класс библиотеки, создание объекта тега
 */

namespace Pes\Core;

class CPesTag
{
	
	public $id;
	
	public $name;
	public $type;
	
	public $attrs = [];
	
	public $position = [];
	
	public $html;
	
	
	public function __construct($html, $id)
	{
		
		$this->id = $id;
		
		$this->html = $html;
		
		$this->setTag($html);
		
	}
			
	
	
	public function addAttr($el)
	{
		
		$parts = explode("=", $el);
		
		$typeSel = $parts[0];
		
		$this->attrs[$typeSel] = explode(" ", $parts[1]);
		
	}



	public function setTag($html)
	{
		
		$html = str_replace(["<", ">", " =", " = ", "= ", '"'], ["", "", "=", "=", "=", "'"], $html);
		
		$els = explode(" ", $html);
		
		$this->name = array_shift($els);
		
		foreach($els as $el) {
		
			$this->addAttr($el);	
		
		}
		
	}
	
	
	public function setEnd($id) 
	{
		
		$this->position['end'] = $id;
		
	}

}
