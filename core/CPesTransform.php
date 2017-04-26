<?php
/**
 * Основной класс библиотеки, производит распарсивание страницы
 * и создает объект для дальнейшей обработки
 */

namespace Pes\Core;

class CPesTransform
{
	
	public $tags = [];
	
	
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
		return CPesFormat::trimTwo(str_replace("~", "-", $html))
		
	}
	
	
	public function htmlToArray($html)
	{
		
		$html = str_replace("<", "~<", $html);
		
		$array = explode("~", $html);
		
		unset($array[0]);
		
		return $array;
		
	}
	
	
	public function parse ($html)
	{
		
		$id = 1;
		$parentID = 0;
		
		while($html) {
			
			$tag = new CPesTag($html, $id, $parentID);

			if ($tag->hasChildren()) {
			
				$parentID = $id;
				
			} else {
			
				$parentID = 0;
			
			}
			
			$html = $tag->getRestHtml();
			
			$tag->deleteRestHtml();
			
			$this->tags[] = $tag;
			
			$id++;
			
		}
		
	}
}
	

