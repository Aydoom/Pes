<?php
/**
 * Основной класс библиотеки, производит распарсивание страницы
 * и создает объект для дальнейшей обработки
 */

namespace Pes\Core;

class CPesHandler{

	static public $typeCode = "html";
	static public $tagsDefault = array("p","div","a","img","table","tbody","thead","td",
	    "tr","li","ul","ol","h1","h2","h3","select","option","input",
	    "h4","h5","link","body","meta","map","area","style","title",
	    "center","strong","b","br","html","head","adress","item","span","dl","tag");
	static public $activeTags = false;
	static private $patterDo = true; // Разрешение на выполнение функции иерархирования
	
	static public $autoCloseTags = true;
	
	// Функция построения Dom дерева
	static public function buildDom($html){
		mb_regex_encoding('UTF-8');
		mb_internal_encoding("UTF-8"); 
		// Самозамыкающиеся теги
		$selfTags = array("img","br","meta","input");
		$type = self::$typeCode;
		$cI = count($html);
		for($i=0;$i<$cI;$i++){
            $html[$i] = trim($html[$i]);
            if(!empty($html[$i])){
                // Старая запись
                $dom[$i]['old']=$html[$i];
                // Тег
                $e = mb_split("[\s,>]",$html[$i]);
                if($e[0][0]=="~"){
                    $tags = self::$tagsDefault;
                    $tag=trim($e[0],"~/");
                    if(in_array($tag,$tags) || $type=="xml" && !self::$activeTags){
                        // Ищем тег
                        $dom[$i]['tag']=$tag;
                        // Пишем атрибуты
                        $dom[$i]=self::getAttr($html[$i],$dom[$i]);
                        // Тип тега
                        if($e[0][1]!="/" && $e[0][0]=="~" ){
                            $dom[$i]['start']='on';
                            $tagEl = explode("/",$dom[$i]['old']);
                            $last = end($tagEl);
                            if($last==" >" || $dom[$i]['tag']=="link"){
                                $dom[$i]['start']='self';
                            }
                            if(in_array($tag, $selfTags)){
                                $dom[$i]['start']='self';
                            }
                        }
                        elseif($e[0][1]=="/" && $e[0][0]=="~"){
                            $dom[$i]['end']='on';
                        }
                    }
                    else{
                        $dom[$i]['old']=CPesFormat::inUtf8("<".$dom[$i]['old']);
                    }
                }
            }
		}
		// Определение позиций и родителей
		$dom = self::patters($dom);
		return $dom;
	}

	// Функция вспомогательная для rename(), делает сам факт замены в html- коде
	static function changeTeg($i=0,$tag,$need,$dom, $type="start"){
		$dom[$i]['tag'] = $need;
		$dS = ($type=="end") ? "~/" : "~";
		$dom[$i]['old'] = str_replace($dS.$tag,$dS.$need, $dom[$i]['old']);
		return $dom;
	}

	// Закрываем теги
	static function closeTags($html){
		if(!self::$autoCloseTags){
			self::$autoCloseTags = true;
			return $html;
		}
		// Заменяем теги
		$seach = array("<br>","<br >","<br />","</br>","</ br>");
		$html = str_replace($seach, "<br/>", $html);
		
		unset($seach,$value);

		// Заменяем теги
		$tag_['p'] = array("p","h1","h2","h3","h4","h5","h6","ul","dl","ol","li","/li","/ul","/dl","/ol"); // Теги закрывающие тег p
		$tag_['li'] = array("ul","dl","ol","/ul","/dl","/ol"); // Теги закрывающие тег li
		$tag_['ul'] = array("ul","dl","ol"); // Теги незакрывающие
		
		foreach($tag_['p'] as $v){
			$seach[] = "<".$v." ";
			$seach[] = "<".$v.">";
			$seach[] = "</".$v.">";
			$value[] = "~<".$v." ";
			$value[] = "~<".$v.">";
			$value[] = "~</".$v.">";
		}
		$html = str_replace($seach, $value, $html);
		$el = explode("~",$html);

		// открытые теги по умолчанию
		$openP = false;
		$openLi = false;
		
		$i = 0;
		foreach ($el as $k=>$v){
			$tel = mb_split("[\s\>]",$v);
			$tag = trim($tel[0],"< \n\r\t");

				// Если тег - p
				if($tag == "p"){
						if($openP) $el[$k] = "</p>".$el[$k];
						else $openP = true;
				}
				// Если тег - /p
				elseif($tag == "/p"){
						$openP = false;
				}
				// Если тег - li
				elseif($tag == "li"){
						// Тег - p открыт
						if($openP){
								$el[$k] = "</p>".$el[$k];
								$openP = false;
						}
						// Тег li - открыт
						if(!empty($openLi[$i])) $el[$k] = "</li>".$el[$k];
						else $openLi[$i] = true;
				}
				// Если тег - /li
				elseif($tag == "/li"){
						// Тег - p открыт
						if($openP){
								$el[$k] = "</p>".$el[$k];
								$openP = false;
						}
						if($openLi[$i]){
							$openLi[$i] = false;
						}
				}
				else{
					// Если тег P открыт
					if($openP){
						if(in_array($tag,$tag_['p'])){
							$el[$k] = "</p>".$el[$k];
							$openP = false;
						}
					}
					// Если тег Li открыт
					if(in_array($tag,$tag_['li']) && $openLi[$i]){
						$el[$k] = "</li>".$el[$k];
						$openLi[$i] = false;
						$i--;
					}
					elseif(in_array($tag,$tag_['ul'])){
						$i++;
					}
				}
		}
		$html = implode("",$el);
		
		if($openP) $html.= "</p>";
		if($openLi[$i]) $html.= "</li></ul>";

		return $html;
	}

	static public function convert($html){
		// Убираем двойные пробелы и тильды из кода 
		$html = str_replace("~", "-", $html);
		$html = CPesFormat::trimTwo($html);
		// Убираем коментарии
		$html = CPesComment::remove($html);
		// Убираем теги внутри тега
		$hh1 = str_replace(array("%","'",'"'), array("","%!","%~"), $html);
		// Разбиваем по равно
		$ell = $el = explode("=",$hh1);
		foreach ($el as $k => $v){
			$dt = '"';
			$lee = explode("%~",$v);
			
			if (count($lee)>1) {
				if(empty($lee[0])) {
					$lee[1] = str_replace(array("<", ">"), array("&#60;","&#62;"), $lee[1]);
				} else {
					$dt = "'";
					$lee = explode("%!",$v);
					if(empty($lee[0]))$lee[1] = str_replace(array("<",">"), array("&#60;","&#62;"), $lee[1]);
				}
			}
			
			$ell[$k] = implode($dt,$lee);
		}
		$html = str_replace(array("%~","%!"), array('"',"'"), implode("=",$ell));
		unset($el,$ell);
		
		// переводим теги в нижний регистр
		$html = preg_replace("/<((\w*)|(\/\w*))[\s>]/is", strtolower("$0"), $html);
		
		// Закрываем незакрытые p и li теги
		$html = self::closeTags($html);
		
		$seach = array("~","<",">","\n","\t","\r");
		$value = array("-","~<",">~",""," ","");
		$html = str_replace($seach, $value, $html);

		$html = str_replace(array("~~","~ ~"), array("~","~"), $html);
		

		$el = explode("~",$html);
		foreach ($el as $key => $val){
			if (!empty($val)) {
				$el[$key][0]=trim($el[$key][0]," \t\n\r");
				$code = ord($el[$key][0]);
				if($code!=32){
					if(!is_array($el[$key])){
						$e = preg_split("/[\s>]+/",$el[$key]);
						if($e[0][0]=="<"){
							$e[0] = strtolower($e[0]);
								if(!empty($e[0][1]) && $e[0][1]=="/"){
									$e[0].=">";
								}
								else{
									$cE = count($e)-1;
									$e[$cE].=">";
								}
							$e[0] = str_replace("<", "~", $e[0]);
						}
						$ok[]=implode(" ",$e);
					}
				}
			}
		}
		return $ok;
	}

	// Функция определения html - сущности
	static function inHtml($html){
		if(is_array($html)){
			$cI=count($html);
			for($i=0;$i<$cI;$i++)
				$code[] = self::inHtml($html[$i]['old']);
			if(count($code)>0)
				$html = implode('',$code);
		}
		$html = trim($html," \t\r\n");
		$html = str_replace(array("~ "," >","\t","\r","\n"), array("<",">","<","","",""), $html);
		$html = str_replace("~","<", $html);
		return str_replace("<<","<", $html);
	}
	
	// Функция получения атрибутов
	static private function getAttr($html,$dom){
		mb_regex_encoding('UTF-8');
		mb_internal_encoding("UTF-8"); 
		// Поиск атрибутов
		$sectors= mb_split("[\s]",$html);
			if(count($sectors)>2){
				$seach = array(" ","=",'"',"'","%0%","%1%");
				$value = array("+~+","+=+",'+$"+',"+$'+",'"',"'");
				$into = str_replace($seach, $value, trim($html,"~/> "))."+~+";
				// Получаем окончательный масив с атрибутами
				$in = CPesFormat::clearArray(explode("+",$into));
				// Начинаем обработку массива
				$cont = "off"; // Контейнер
				$pr = 0; // Приоритет
				$text = "";// Любой текст
				$val = ""; // Значение атрибута
				$key = ""; // Название атрибута
				
				$index = $key;
				$attr = null; 
				foreach($in as $k => $v){
					$s=true;
						if($v=="$'"){$max="max1";}
						if($v=='$"'){$v="$'";$max="max2";}
					if($v=="$'"){
						$s=false;
						if($cont=="on"){
							if($pr=="min"){
								if($text==""){
									$pr=$max;}
								else{
									$text.="'";}
							}
							elseif($pr==$max){
								if($key==""){
									$key=$text;
									$text="";
									$cont="off";}
								else{
									if($text==""){
										$val=true;
										$cont="off";}
									else{
										$val=$text;
										$text="";
										$cont="off";}
									}
							}
							else{
								$text.=$v;}
						}
						else{
							$cont="on";
							$pr=$max;}
					}
					// ~
					elseif($v=="~"){
						$s=false;
						if($cont=="on"){
							if($pr=="min"){
								if($key==""){
									$key=$text;
									$text="";
									$val=true;}
								else{
									$val=$text;
									$text="";}
							}
							else{
								$text.=" ";}
						}
						else{
							if($key!="" && $text!=""){
								$val=$text;
								$text="";}
							$cont="on";
							$pr="min";
						}
					}
					// =
					elseif($v=="="){
						$s=false;
						if($cont=="on"){
							if($pr=="min"){
								if($key==""){
									$key=$text;
									$text="";}
								else{
									$text.="=";}
							}
							else{
								$text.="=";}
						}
						else{
							$val="";
							$key=$index;
						}
					}
					// text
					if($cont=="on" && $s==true){
						$text.=$v;}

					if($key!="" && $val!=""){
						$key=strtolower(trim($key,"~"));
						$expulsion = array("alt","title","href","src");
						if(!in_array($key, $expulsion)){
							$val=mb_strtolower($val);}
						if($key=="class"){
							$attr[$key]=explode(" ",$val." ");
							end($attr[$key]);
							$last = key($attr[$key]);
							unset($attr[$key][$last]);
						}
						else{
							$attr[$key]=$val;}
						$index=$key;
						$key="";
						$val="";
					}
				}
				$dom['attr']=$attr;
			}
		return $dom;
	}
	
	// Функция вспомагательная: только выводит текст (для функции text());
	static function getText($text){
		if(is_array($text)) 
			$text = self::inHtml($text);
		$html = preg_replace(array("#<script(.*?)>(.*?)</script>#is","#<style(.*?)>(.*?)</style>#is","#<pes:comment(.*?)>(.*?)</pes:comment>#is"), "", $text);
		// удаляем теги
		$ok = trim(strip_tags($html)," \t\r\n");
		// в случае если ошибки есть в html коде и функция strip_tags не сработала
		if(empty($ok)){
			preg_match_all("#>(.*?)<(\w|\/\w)+#is", $html,$el);
			$html ='';
			foreach($el[1] as $k => $v){
				if(!empty($v)) $ok.= trim($v," \t\r\n");
			}
		}
		return CPesFormat::trimTwo($ok);
	}
	
	// Функция определения дочерних элементов
	static function patters($dom){
		// Если иерархия запрещена
		if(!self::$patterDo)return $dom;
		// Сбрасываем ключи
		if(!is_array($dom))
			CPesMsg::error('$dom - не массив');
		$dom = array_values($dom);
        $p = array();
		// Значение радителя стартового элемента
		$index=-1;
		// Кол-во элементов в полученном массиве
		$cI = count($dom);
		// Начинаем перебор элементов
		for($i=0;$i<$cI;$i++){
			// Удаляем старое значение родителя
			unset($dom[$i]['patter']);
			// Устанавливаем сокращение тега действующего элемента
			$tag = (empty($dom[$i]['tag'])) ? null : $dom[$i]['tag'];
			// Проверяем является ли элемент стартовым
			if(isset($dom[$i]['start']) && $dom[$i]['start']==="on"){
				// Заносим данные во вспомогательные массивы
				$u = (empty($p[$tag])) ? 0 : count($p[$tag]);
				$p[$tag][$u] = array("id"=>$i,"end"=>true);
				// Устанавливаем активному элементу родитель
				$dom[$i]['patter']=$index;
				// Меняем значение родителя
				$index=$i;
			}
			// Проверяем является ли элемент конечным
			elseif(isset($dom[$i]['end']) && $dom[$i]['end']==="on" && isset($p[$tag])){
				// Подготавливаем вспомагательный массив
				$u = count($p[$tag])-1;
					$start = (empty($p[$tag][$u]['id'])) ? 0 : $p[$tag][$u]['id'];
					if(empty($start) && $start!==0)
                        $dom[$i]['start'] = $i;
					else{
                        $dom[$i]['start'] = $start;
                        $index = $dom[$start]['patter'];
					}
					if(!empty($start) || $start===0){
                        $dom[$start]['end'] = $i;
                    }
					unset($p[$tag][$u]);
			}
			elseif(isset($dom[$i]['old']))$dom[$i]['patter']=$index;
		}
        return $dom;
	}

}