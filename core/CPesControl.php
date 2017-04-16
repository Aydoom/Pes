<?php
/**
 * Класс контроля работы библиотеки
 * Следит за установкой паузы при повторных запросах к одному и тому же домену
 */
class CPesControl{

	static $countLoad = 0; // Количество загрузок
	static $domenInLoad = array(); // Загруженные домены
	static $sleepTime = 3; // Количество секунд сна
	
	// Пауза в скрипте
	static function sleep($url){

		// Если загрузка не в первый раз, то включаем паузу между загрузками
		if(self::$countLoad>0 && in_array($url['host'], self::$domenInLoad))
			sleep(rand(self::$sleepTime*0.5, self::$sleepTime*1.5));
		else
			array_push(self::$domenInLoad,$url['host']);
		// Увеличиваем счетчик загрузок
		self::$countLoad++;
		
	}	
	
}