<?php
/**
 * Вспомагательный класс для изменения формата и/или кодировки данных
 */

namespace Pes;

class CPesFormat{

	static public $encodes = array('ASCII','UTF-8','cp1251'); // доступные кодировки


	// Конвертируем атрибут, т.е. разбиваем его из значения class:fan в attr->class и val->fan
	static public function attrConvert($attr){
        if(substr_count($attr,':')>0){
         	$el=explode(":",$attr.":");
    		$ok['attr']=$el[0];
    		$ok['val']= mb_strtolower($el[1],"UTF-8");       
        }
        else{
        	$ok['attr']=$attr;
    		$ok['val']= true;
        }
        return $ok;
	}
		
	// —оздаем новый массив без пустых €чеек
	static public function clearArray($array){
	    foreach ($array as $key => $val){
			
			if (!empty($array[$key][0])) {
				$code = ord($array[$key][0]);
				if($code!=32 && $code!=0)
					$ok[] = $array[$key];
			}
	    }
	    return $ok;
	}
	
	// ‘ункци€ изменени€ кодировки в UTF-8
	static public function InUtf8($code){
		if(!is_array($code)){
			$newCode['body'] = $code;
			$code = $newCode;
		}

		$encode = (empty($code['encode'])) ? mb_detect_encoding($code['body'],self::$encodes) : $code['encode'];
		
		if($encode!="UTF-8"){
			//$code['body'] = iconv($code['encode'],'UTF-8',$code['body']);
			$code['body'] = iconv($encode,'UTF-8',$code['body']);
		}

		return $code['body'];
	}

	// Удаляем двойные пробелы
	static public function trimTwo($str){
	    $str = str_replace("&nbsp;", " ", $str);
	    return preg_replace('/\s+/', ' ', $str);
	}
}