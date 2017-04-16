<?php
/**
 * Работаем с Curl библиотекой
 * делаються запросы к страницами и передаче файлов
 */
class CPesCurl{

	static public $is_post = false;
	static public $query = false;
	static public $errCurl = array();
	
	// Простая загрузка страницы
	static public function load($url,$encode,$redirect){
		// Создаем фантом
		// Получаем базовые настройки
		$ch = self::default_curl($url,$redirect);
		// Получаем страницу
		$code = self::getHtml($ch,$url,$redirect);
		if(empty($code["encode"])) $code["encode"]=$encode;
		return $code;
	}

	// Загрузка страницы с POST данными
	static public function post($url,$query,$encode,$redirect){
		// Создаем фантом
		// Получаем базовые настройки
		$ch = self::default_curl($url,$redirect);
		// Устанавливаем флаги POST
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
		// Получаем страницу
		return self::getHtml($ch,$url,$redirect);
	}
	
	// Загоняем картинку
	static public function take($url,$filename){
		// Сохраняем файл
		// Инициализируем CURL-сессию 
		$ch = curl_init(); 
		// Устанавливаем для работы нужный файл 
		curl_setopt($ch, CURLOPT_URL, $url); 
		//Открываем "поток" для сохранения файла на Вашем сервере: 
		$fp = fopen($filename, "w+"); 
		//Указываем на него ссылку: 
		curl_setopt($ch, CURLOPT_FILE, $fp);
		// Нагло подделываем REFERER: 
		curl_setopt($ch, CURLOPT_REFERER, 'fail.fzx'); 
		//Устанавливаем опцию хождения по всем редиректам 
		curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE); 
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION , TRUE); 
		//Выполняем CURL-процедуру с заданными параметрами: 
		curl_exec($ch); 
		//Закрываем сеанс CURL: 
		curl_close($ch); 
		//Закрываем файл: fclose($fp);
	}
	
	// Базовые настройки 
	static protected function default_curl($url,$redirect){
		// Запускаем CUrl
		$ch = curl_init ();
		// Установка флагов
		// для ручной обработки
		curl_setopt($ch, CURLOPT_HEADER, 1);
		// адрес загрузки страницы
		curl_setopt ($ch , CURLOPT_URL , trim($url));
		//
		curl_setopt ($ch , CURLOPT_RETURNTRANSFER , 1 );
		// Установка флага для редиректа
		if($redirect)
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		// Установка браузера
		curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22');
		return $ch;
	}
	
	// Возврат страницы и закрытие curl
	static protected function getHtml($ch,$url,$redirect){
		// Получаем результат
		if(!$html = curl_exec($ch))
			CPesMsg::error('Ошибка curl: ' . curl_error($ch));
		// Заголовки
		$header = substr($html,0, curl_getinfo($ch,CURLINFO_HEADER_SIZE));
		// Определяем статус страницы
		self::$errCurl['404'] = (curl_getinfo($ch,CURLINFO_HTTP_CODE) == 404) ? true : false;
		// Тело
		$body = substr($html,curl_getinfo($ch,CURLINFO_HEADER_SIZE));
		$code['body'] = $body;

		// Кодировка
		$code['encode'] = false;
		if (preg_match("|Content-Type: .*?charset=(.*)\n|imsU", $header, $results)) $code['encode']=trim($results[1]);
		$el = explode(";",$code['encode']);
		if(count($el)>1) $code['encode'] = $el[1];
		
		if($html === false){
			CPesMsg::error("cURL Error: " . curl_error($ch)."<br/>function createFontom()");
		}
		// Переадресация 
		if ($redirect==true && preg_match("|Location: (.*)\n|imsU", $header, $results)){
			if(substr_count($results[1], "http://")==0 && substr_count($results[1], "https://")==0){
				$el = explode("/",$url);
				$results[1] = $el[0]."//".$el[2].$results[1];
			}
			if($results[1]!=$url){
				curl_close($ch);
				$code = self::load($results[1],false,true);
			}
			else 
				curl_close($ch);
		}
		return $code;
	}
}