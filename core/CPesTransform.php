<?php
/**
 * Основной класс библиотеки, производит распарсивание страницы
 * и создает объект для дальнейшей обработки
 */

namespace Pes\Core;

class CPesTransform
{
	
	public $rows = [];
	
	
	
	public function __construct($html)
	{
		// Убираем лишние символы из кода
		$html = $this->clear($html);
		
		// Удаляем комментарии
		$html = CPesComment::remove($html);
		
		// Создаем из html массив для удобства дальнейшей обработки
		$html = $this->htmlToArray($html);
		
		// Запускаем парсер
		$this->parse($html);

	}
	
	
	
	public function clear ($html)
	{
		
		// Убираем двойные пробелы и тильды из кода 
		return CPesFormat::trimTwo(str_replace("~", "-", $html));
		
	}
	
	
	
	public function htmlToArray($html)
	{
		
		$html = str_replace(["<", ">"], ["~<", ">~"], $html);
		
		$array = explode("~", $html);
		
		unset($array[0]);
		
		return $array;
		
	}
	
	
	
	public function parse ($html)
	{

		$parentlevel = 0;
		$parentID[0] = 0;
		
		foreach($html as $id => $row) {
		
			if (substr_count($row, "</") > 0) {
				
				$pId = $parentID[$parentlevel];

				$this->rows[$pId]->setEnd($id);
				
				$parentlevel--;
				
				if ($parentlevel < 0) {
					
					$parentlevel = 0;
					
				}
				
			
			} else {
			
				$this->rows[$id] = new CPesTag($row, $id);
				
				$parentlevel++;
				
				$parentID[$parentlevel] = $id;
			
			}
		
		}
		
	}
}