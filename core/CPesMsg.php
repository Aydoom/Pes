<?php

class CPesMsg{

	static $style = 0;
	static $active = true;
	
	// Вывод элементов кода
	//  на экран
	static public function display($msg){
		self::style();
		if(is_array($msg)){
			foreach($msg as $key=>$str){
				$msg[$key]['old']=CPesFormat::inUtf8($msg[$key]['old']);
			}
		}
		else{
			$msg = CPesFormat::inUtf8($msg);
		}
		echo "<div class='pes_block'>";
		if(is_array($msg)) {
			echo "<pre>";
				print_r($msg);
			echo "</pre>";
		}
		else{
			echo $msg;
		}
		echo "</div>";
	}
	// Функция выведения сообщения о отсутствии искомого элемента
	static public function notFind($sel,$function = null,$fatal=false){
		echo "<h5> Пес не нашел такого: (<u>$sel</u>), при исполнении функции - <u>{$function}()</u> </h5>";
		if($fatal){
			exit();
		}
	}
	// Функция выведения замечания
	static public function notes($msg){
		if(!self::$active) {return true;}
		self::style();
		echo '<div class="notes"><p style="color: #000000; font-weight: bold;">ЗАМЕЧАНИЕ: '.$msg.'</p></div>';
	}
	// Функция выведения замечания
	static public function message($msg){
		if(!self::$active) {return true;}
		self::style();
		echo '<div class="message"><p style="color: #000000; font-weight: bold;">'.$msg.'</p></div>';
	}
	// Функция выведения ошибки
	static public function error($msg){
		if(!self::$active) {return true;}
		self::style();
		echo '<div class="error"><p style="color: #ffffff; font-weight: bold;">ОШИБКА: '.$msg.'</p></div>';
		throw new Exception();
	}

	// Функция вывода CSS - стиля
	static public function style(){
		if(self::$style==0){
			echo "
				<style>
					html,body{margin: 0;padding: 0;width:100%;background-color: #fbf9cb;color: #482d06;font-family: tahoma;}
					div,table,ul,ol,dl{border: 1px solid #482d06;width: 96%;margin: 5px 1%;}
					h1,h2,h3,h4,h5,h6{font-weight: bold;font-size: 16px;}
					li{margin-left:3%;padding-left:1%;color: #f69f09;}
					li a{color: #f69f09;}
					p{font-size: 15px;text-indent: 1.5em;text-align: justify;}
					span{font-family: arial;font-style: italic;text-decoration: underline;}
					a,a:action{font-size: 15px;color: #344ea3;}
					a:hover{color: #f67e09;text-decoration: underline;}
					.pes_block{margin: 1% 1%;width:95%;padding-left:1%;font-weight: bold;font-size: 15px;border-radius: 5px;}
					.error,.notes,.message{border-radius: 5px;}
					.error{background-color: #ff0000;margin: 3px, 0px;}
					.notes{background-color: #fff600;margin: 3px, 0px;}
					.message{background-color: #8bf673;margin: 3px, 0px;}
				</style>";
			self::$style=1;
		}
	}
}