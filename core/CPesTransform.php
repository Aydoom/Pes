<?php
/**
 * The main class of the library, it does parsing a html page
 * and create the array for further handling
 */

namespace Pes\Core;

class CPesTransform
{
	
	public $rows = [];
	
	
	
	public function __construct($html)
	{
		// Remove the excess symbols
		$html = $this->clear($html);
		
		// Remove the comments
		$html = CPesComment::remove($html);
		
		// Создаем из html массив для удобства дальнейшей обработки
		$html = $this->htmlToArray($html);
		
		// Запускаем парсер
		$this->parse($html);

	}
	
	
	
	// function getFirstRow()
	public function getFirstRow()
	{
		
		$rows = $this->rows;
		
		return array_shift($rows);
		
	}
	
	
	
	// function getHtmlArray()
	public function getHtmlArray()
	{
		
		$html = [];
		
		foreach ($this->rows as $i => $row) {
		
			if (isset($row['tag'])) {
				
				$html[$i] = $row['tag']->getHtml();
				
			} else {
				
				$html[$i] = $row;
				
			}
		
		}
		
		return $html;
		
	}
	
	
	public function clear ($html)
	{
		
		// Убираем двойные пробелы и тильды из кода 
		$html = CPesFormat::trimTwo(str_replace("~", "-", $html));
		
		// Remove breaks and tabs
		$html = str_replace(["\n","\t"], " ", $html);
		
		// Correction some tags
		$search = array("<br>","<br >","<br />","</br>","</ br>");
		
		$html = str_replace($search, "<br/>", $html);
		
		return $html;		
	}
	
	
	
	public function htmlToArray($html)
	{
		
		$html = str_replace(["<", ">"], ["~<", ">~"], $html);
		$html = str_replace(["~ ~", "~~"], "~", $html);
		
		$array = explode("~", $html);
		
		unset($array[0]);
		
		return array_map("trim", $array);
		
	}
	
	
	
	public function parse ($html)
	{

		$parentlevel = 0;
		$parentID[0] = 0;
		
		//pr($html);
		
		foreach($html as $i => $row) {

			if (substr_count($row, "</") > 0) {
				
				$id = $parentID[$parentlevel];

				$this->rows[$id]['tag']->setEnd($i);
				$this->rows[$i] = $row; 
				
				$parentlevel--;
				
				if ($parentlevel < 0) {
					
					$parentlevel = 0;
					
				}
				
			
			} elseif ($row[0] == "<") {
			
				$this->rows[$i] = [
					'tag' 		=> new CPesTag($row, $i),
					'patter' 	=> $parentlevel - 1
				];
				
				if ($this->rows[$i]['tag']->isType(["open", "mixed"])) {
				
					$parentlevel++;
					
					$parentID[$parentlevel] = $i;
					
				}
			
			} else {
				
				$this->rows[$i] = $row;
				
			}
		
		}
		
	}
}