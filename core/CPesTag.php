<?php
/**
 * Основной класс библиотеки, создание объекта тега
 */

namespace Pes\Core;

class CPesTag
{
	
	public $id;
	
	public $name;
	public $type = "open";
	
	public $attrs = [];
	
	public $position = [];
	
	public $html;
	
	
	public function __construct($html, $id)
	{
		
		$this->id = $id;
		
		$this->html = str_replace(["<", ">"], ["&#60;","&#62;"], $html);
		
		$this->setTag($html);
		
		$this->setType();
		
	}
			
	
	
	public function addAttr($name, $val)
	{
		
		if ($name == "class") {
			
			$vals = explode(" ", $val);
			
			$this->attrs[$name] = $vals;
			
		} else {
			
			$this->attrs[$name] = $val;
			
		}
		
	}
			
	
	
	public function changeAttr($name, $val)
	{
		
		unset($this->attrs[$name]);
		
		$this->addAttr($name, $val);
		
	}
			
	
	
	public function getAttr($name)
	{
		
		return $this->attrs[$name];
		
	}
	
	
	
	public function getEnd()
	{
		
		if (empty($this->position['end'])) {
		
			return $this->id;
		
		} else {
		
			return $this->position['end'];
		
		}
		
	}
	
	
	
	public function getHtml() {
		
		$attrs = [];
		
		foreach($this->attrs as $attrName => $value) {
		
			if (is_array($value)) {
			
				$attrs[] = $attrName . '="' . implode(" ", $value) . '"';
			
			} else {
			
				$attrs[] = $attrName . '="' . $value . '"';
			
			}
		
		}
		
		return '<' . $this->name . ' ' . implode(' ', $attrs) . '>';
	
	}
	
	
	
	public function isType($types) {
		
		if (!is_array($types)) {
			
			$types = array($types);
			
		}
		
		$output = false;
		
		foreach($types as $type) {
		
			if ($this->type == $type) {
			
				$output = true;
				break;
			
			}
		
		}
		
		
		return $output;
		
	}
			
	
	
	public function hasAttr($name)
	{
		
		return (in_array($name, $this->attrs));
		
	}
	
	

	public function setTag($html)
	{
		
		$html = str_replace(["<", ">", " =", " = ", "= ", '"'], ["", "", "=", "=", "=", '"'], $html);
		
		$els = explode(" ", $html);
		
		$this->name = strtolower(array_shift($els));
		
		preg_match_all('/\s([a-zA-Z]*)="([^"]*)/', $html, $attrs);
		
		if (!empty($attrs)) {
			
			foreach($attrs[1] as $i => $attr) {
			
				$this->addAttr($attr, $attrs[2][$i]);
			
			}
			
		}
		
		
	}
	
	
	
	public function setType()
	{
		
		$types = [
			'close' => ["meta", "link", "img", "br"],
			'mixed' => ["li", "p"]
		];
		
		foreach ($types as $type => $tags) {
			
			if (in_array($this->name, $tags)) {
			
				$this->type = $type;
			
			} 	
		
		}
		
	}
	
	
	public function setEnd($id) 
	{
		
		$this->position['end'] = $id;
		
	}

}
