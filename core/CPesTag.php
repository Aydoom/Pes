<?php
/**
 * Основной класс библиотеки, создание объекта тега
 */

namespace Pes\Core;

class CPesTag
{
	
	public $id;
	public $parentID;
	
	public $name;
	public $type;
	
	public $selector = [];
	
	public $position = [];
	
	public $content;
	
	public $html;
	
	
	public function __construct($html, $id, $parentID)
	{
		
		$this->id = $id;
		$this->parentID = $parentID;
		
		$this->setTag($html);
		
	}
	
	
	public function setTag($html)
	{
		
		foreach($html as $row => $tag) {
		
			
		
		}
		
	}
	
}
