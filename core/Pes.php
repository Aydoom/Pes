<?php
/**
 * Класс Pes
 * основной класс для работы с библиотекой, содержит функции обработки 
 * html - страниц.
 *
 * В настройках необходимо php:
 * safe_mode - выкл
 * open_basedir - выкл.
 * 
 * Функции:
 * @add 	- добавляет теги и вначале и вконце кода
 * @attr	- возвращает значение соответветствующего атрибута
 * @delete	- приказываем выкинуть элемент вместе с содержимым
 * @each	- смотрим на все то, что осталось в виде массива по родителю "0"
 * @eq      - выбираем номер элемента из всей выборки (по умолчанию 0) возвращает doc элемента
 * @find	- приказываем, что нам нужно найти
 * @findAttr- приказываем, что нам нужно найти по атрибуту
 * @fix		- фиксирует(запоминаем) (html - код), чтобы к ней можно было вернуться
 * @fixDel	- удаляем фиксацию (html - код)
 * @fixExt	- извлекаем фиксацию (html - код) (up && del)
 * @foo		- чистим все фиксации и кэш
 * @getId   - возвращает Id первого элемента
 * @html	- возврат Html - кода
 * @intoTag - обрамляет весь текст внутри тегов в тег "teg"
 * @is		- возвращает TRUE при наличие селектора в html - коде
 * @is_404	- возвращает TRUE если страница возвращает статус Not Found
 * @link    - выбираем адрес ближайшей ссылки
 * @load	- загрузка Html - кода для обработки
 * @mDom	- возврат дом-а со стилями пса
 * @mHtml	- возврат html-кода со стилями пса
 !* @post    - функция загрузки страницы с передачей данных методом post
 * @remove	- удаление элемента с сохранением содержимого
 * @rename	- приказываем переименовать один элемент на другой
 * @replace	- приказываем заменить одну группу символов другой
 * @slice   - возвращает срез массива doc по id-шникам
 * @tags	- устанавливаем теги, по которым будет производиться обработка
 * @text	- возвращаем содержимое тегов (контент)
 * @words   - поиск слов (находит теги содержащий искомые слова)
 * @wrap    - оборачивает в тег
 * @up		- возвращает зафиксированную площадку для пса (html - код)
 *
*/

namespace Pes\Core;

class Pes extends CPesBasic
{

    // Константа добавления тегов
    const TagsAdd = "add";
    const TagsNew = "new";

    /**
     * Переменная для хранения объекта парсируемой страницы
     * @var type object
     */
    public $page;

    /**
     * Флаг указывающий на необходимость остановки скрипта после выполнения
     * функций обратной связи (начинающихся на m...)
     * @var type boolean
     */
    public $stop_after_m = true;

    /*
     * Конструктор Pes
     * @param type $url - адресс страницы которую будем парсить
     * @param type $encode - кодировка страницы
     * @param type $redirect - разрешение на перезагрузку странци которую парсим,
     * при автоматическом перенаправлении на другую страницу.
     */
    public function __construct($url = false, $encode = null, $redirect = true) {
        
        if ($url) {
			
            $url_path = explode("/", $url);
			
            if (empty(end($url_path))) {
				
                $url.= "index.php";
				
            }

            $this->load($url, $encode, $redirect);
        }
    }

	
	
	// Function __toString()
    public function __toString() {

        return $this->html();

    }
	
    /*
     * Добавление тегов в начало или конец кода
     * @place - start (вначале открывающий), end - (вконце закрывающий), 
	 * default - (оборачиваем html  в тег)
    */
    public function add($tag, $place = false) {
		
        if ($place == 'start') {

            $html = "<" . $tag . ">" . $this->html();
			
        } elseif ($place == 'end') {
			
            $html = $this->html() . "</" . $tag . ">";
			
        } else {
			
            $html = "<" . $tag . ">" . $this->html() . "</" . $tag . ">";
			
        }
        
        $this->html($html);
		
        return $this;
		
    }

    /*
     *  Функция возвращения и замены значение атрибута первого элемента
     * @attr - атрибут, значение которго нужно вернуть
     * @change - значение на которое необходимо заменить найденные атрибуты
     */
    public function attr($attr, $change = false) {
        
        $doc = $this->page->getDom();
        
        if (!$change) {
            
            return $doc->getRow(1)->getAttr($attr);
            
        } else {
            
            $doc->getRow(1)->setAttr($attr, $change);
            
            return $this;
            
        }
        
    }

    /*
     *  Функция удаления тега со всем содержимым из кода
     * @sel - селлектор
     * @type - тип удаления, all - во всем документе, in - внутри отцовского
     */
    public function delete($sel, $type = "all") {
        
        $start = ($type == "all") ? 0 : 1;
        
        $ids = CPesTaskFind::create($sel)->id($this->page, $start);
        
        $this->page->delete($ids);
        
        return $this;
        
    }

	/*
	 * Функция выведения массивом данных по тегам начиная с самого первого
	 * @function - колл бэк функция (1-ый параметр - $pes, 2-ой $html-код)
	 * @delEmpty - удаляем элементы без содержимого
	*/
    public function each($function = false,$delEmpty = true){
        $dom = $this->page->getDom();
        $cI = count($dom);
        $key = 0;
        $haveText = true;
        for($i=0;$i<$cI;$i++){
            if($dom[$i]['patter']===-1){
                $cU = (isset($dom[$i]['end'])) ? $dom[$i]['end'] : $i;
                $code = array();
                for($u=$i;$u<=$cU;$u++)
                    $code[] = CPesHandler::inHtml($dom[$u]['old']);
                $html = CPesFormat::trimTwo(implode("",$code));

                if($delEmpty)
                    $haveText = (strlen($this->text($html))>0);
                if(strlen($html)>0 && $haveText){
                    $ok[$key]=$html;
                    $i = $u-1;
                    $key++;
                }
            }
        }
        if(is_callable($function) && !empty($ok)){
            foreach($ok as $html){
                $result[] = $function($this,$html);
            }
            $ok = $result;
        }
        return $ok;
    }

	/**
	 *  Функция выбора номера элемента
	 * @elsement - порядковый номер элемента /(last - последний)
	 * дом куска без изменения основы
	 */
	public function eq($element = 0)
	{
		$dom = $this->page->getDom();
		
		//$this->fix("_eq");
		
		$countDom = count($dom);
		$id = 0;
		
		if ($element === "last") {
			$id = $countDom--;
			$element = 0;
			$mark = "last";
		}
		
		$is_do = true;
		do {
			if (isset($dom[$id]['patter']) && $dom[$id]['patter'] == -1) {
				
				if ($element == 0) {
					$f = $dom[$id]['end'] - $id + 1;
					$ok = array_slice($dom, $id, $f);
					$this->page->setDom(CPesHandler::patters($ok));
					return $this;
				} else {
					$element--;
				}
			}
			
			if (isset($mark) && $mark == "last") {
				$id--;
				if ($id <= 0) {
					$is_do = false;
				}
			} else {
				if (!empty($dom[$id]['end'])) {
					$id = $dom[$id]['end'] + 1;
				} else {
					$id++;
				}
				
				if ($id >= $countDom) {
					$is_do = false;
				}
			}
		} while($is_do);
		return $this;
	}
	
	/**
	 * Функция поиска по селектору
	 *
	*/
    public function find($sel,$start = 1){
		$result = CPesTaskFind::create($sel)->find($this->page,$start);
		$this->page->setDom($result);
		return $this;
	}

	// Функция поиска дочернего элемента с определенным атрибутом атрибут и значение через :
	public function findAttr($attr=false,$sel="",$start = 0){
		if(!$attr) return false;
		$attr = CPesFormat::attrConvert($attr);
		$result = CPesTaskFind::create($sel)->find($this->page,$start,$attr);
		$this->page->setDom($result);
		return $this;
	}
	
	/**
	 * Функция поиска по селектору с возратом текста
	*/
    public function findText($sel,$start = 1){
        $ok = $this->fix("_text")->find($sel,$start)->text();
        $this->fixExt("_text");
		return $ok;
	}

	// Функция фиксации html-кода (запаминаем, чтобы можно было вернуться)
	public function fix($flag = "user"){
		$this->page->fix($flag);
		return $this;
	}

	// Функция удаления фиксации html-кода ()
	public function fixDel($flag = "user"){
		$this->page->unset_fix($flag);
	}
	
	// Функция извлечение фиксации html-кода ()
	public function fixExt($flag = "user"){
	    $this->up($flag);
		$this->fixDel($flag);
	}

	// Функция для очистки всей информации хранимой псом
	public function foo(){
		$this->page->setDom(null);
		$this->page->setUrl(null);
		$this->page->fixDel();
		CPesTaskFind::$sels = array();
		CPesCurl::$is_post = false;
		CPesCurl::$query = false;
		CPesCurl::$errCurl = array();		
	}
	
	/*
	 *  Функция возврата id - элемента
	 */
	public function getId($sel,$start=1){
		return CPesTaskFind::create($sel)->first($this->page,$start);
	}

	
	// Функция выведения html - кода
    public function html($html = false)
	{
		if($html) {
			
			/*
			// Преобразум страницу в массив
			$html = CPesHandler::convert($html);
			
			// Выстраиваем ДОМ
			$dom = CPesHandler::buildDom($html);
			*/
			
			// Преобразуем страницу в объект
			$dom = new CPesTransform($html);
			
			$this->page->setDom($dom);
			
			return $this;
			
		} else {
			
			return $this->page->html();
			
		}
	}

	
	
	// Функция перевода всего текста(old) в тег teg
	public function intoTag($tag = "tag"){
		$m = true;
		$dom = $this->page->getDom();
		foreach ($dom as $i => $val){
			if($m){
				if(substr_count($dom[$i]['old'], "<~pes:")>0) $m=false;
				elseif(!isset($dom[$i]['tag']))
					$dom[$i]['old'] = "~$tag>".$dom[$i]['old']."~/$tag>";
			}
			else{
				if(substr_count($dom[$i]['old'], "<~/pes:")>0) $m=true;
			}
		}
		$this->page->setDom($dom);
		$html = $this->html();
		// Преобразум страницу в массив
		$html = CPesHandler::convert($html);
		// Выстраиваем ДОМ
		$dom = CPesHandler::buildDom($html);
		$this->page->setDom($dom);
		
		return $this;
	}

	// Функция поиска наличия селектора в коде
	public function is($sel){
		return CPesTaskFind::create($sel)->is($this->page,$start);
	}

	// Функция определения 404 ошибки
	public function is_404(){
		return !empty(CPsCurl::$errCurl['404']);
	}

    /**
     * Выбираем адрес ближайшей ссылки
     * @global -> возвращает алрес (глобальный, false - локальный)
    */
    public function link($global = true)
	{
        $this->fix('link');
        $href = $this->find('a:first')->attr('href');
		$url = $this->page->getUrl();
		
        // Проверяем на глобальность
		if (($global) && (isset($url['url']))) {
			if ((substr_count($href, 'http://')==0) || (substr_count($href, 'https://') == 0)) {
                $url = $this->page->getUrl();
				if ($href[0] == '/') {
					$href = $url['scheme'] . '://' . $url['host'] . $href;
				} else {
					$href = $url['scheme'] . '://' . $url['host'] . '/' . $url['path'] . '/' . $href; 
				}
			}
		}
        $this->up('link');
        $this->fixDel('link');
        return $href;
    }
	
    /*
     * Загрузка кода для парсинга
     * Загрузка возможна с удаленного сервера, из файла с
     * локального сревера или напрямую при передаче кода
     * 
     * @param type $html - может быть как html - код, так и url - адрес
     * @param type $encode - при необходимости можно заранее определить кодировку
     * @param type $redirect - разрешение на перезагрузку странци которую парсим,
     * при автоматическом перенаправлении на другую страницу.
     */
    public function load($html, $encode = false, $redirect = true) {
        
		$load = new CPesLoad($html, $encode, $redirect);

        // Меняем кодировку
        $html = CPesFormat::inUtf8($load->getHtml());
		
		/*
        // Преобразум страницу в массив
        $html_old = CPesHandler::convert($html);
		
        // Выстраиваем ДОМ
        $dom = CPesHandler::buildDom($html_old);
		
		//pr($dom);
		*/

		// Преобразуем страницу в объект
		$dom = new CPesTransform($html);
		
		//pr($dom);
		
		
        // Создаем объект страницы
        $this->page = new CPesPage($url, $dom);
        /*
		$this->page->setDom($dom);
        $this->page->setUrl($url);
		*/
		
    }
	
	// Вывод на экран doc массива
    public function mDom()
	{
		CPesMsg::display($this->page->getDom());
		if ($stop_after_m) {exit;}
	}
	
	// Вывод на экран результата обработки в html
    public function mHtml($stop_after_m = true)
	{
		CPesMsg::style();
		echo $this->html();
		if ($stop_after_m){exit;}
	}

	/*
	 *  Устанавливаем данные, которые будут переданы методом post
	 * @data - данные в виде массива
	 * @const - указывает при true что, данные не будут удалены после обращении к странице
	 */
	public function post($data = array(),$url = null,$encode=null){
		// Подготавливаем запрос
		$query = null;
		foreach ($data as $key => $val)
			$query.= $key."=".$val."&";
			CPesCurl::$is_post = true;
			CPesCurl::$query = rtrim($query,"&");
		// Ищем адрес формы
		if(empty($url)){
			$this->find("form");
			$url = $this->attr("action");
		}
		// Грузим страницу
		$this->load($url,$encode);
	}


	// Функция удаления тега из кода
    public function remove($sel){
		$dom = $this->page;
		$result = CPesTaskFind::create($sel)->id($dom,0);
		$newDom = $dom->getDom();
		foreach($result as $element){
			foreach($element as $id){
				if(isset($newDom[$id])) unset($newDom[$id]);
			}
		}
		$this->page->setDom(CPesHandler::patters($newDom));
		return $this;
	}	

	/*
	 * Функция для переименовывания тегов
	 * @seach - селектор для поиска тега, который необходимо заменить
	 * @need  - название тега, в который необходимо переименовать
	*/
	public function rename($seach,$need="td"){
		$ids = CPesTaskFind::create($seach)->all($this->page,0);
		$dom = $this->page->getDom();
		// Переименовываем
		if(count($ids)>0){
			foreach($ids as $key=>$val){
				$end = $val['end'];
				$start = $val['start'];
					$tag = $dom[$start]['tag'];
					// Сначало закрывающий, далее открывающий (не наоборот)
					$dom[$end]['tag'] = $dom[$start]['tag'] = $need;
					$dom[$end]['old'] = str_replace("~/".$tag,"~/".$need, $dom[$end]['old']);
					$dom[$start]['old'] = str_replace("~".$tag,"~".$need, $dom[$start]['old']);
			}
			$this->page->setDom($dom);
		}
		return $this;
	}
	
	// Функция замены одних элементов другими
    public function replace($seach, $needle)
	{
	    CPesHandler::$autoCloseTags = false;
		$html = $this->html();

		if (is_array($seach)) {
			$need = (is_string($needle)) ?	array($needle) : $needle;
			foreach ($seach as $key=>$val) {
				if(empty($need[$key])) {
					$need[$key] = end($need);
				}
				$html = str_replace($seach[$key], $need[$key], $html);
			}
		} else {
			$html = str_replace($seach, $needle, $html);
		}
		$this->html($html);
		return $this;
	}
	
	/**
	* Функция возвращаюшая часть кода между двумя точками
	* $start и $end - это селекторы, могут быть и цифрами
	*/
	public function slice($start=false,$end=false){
		$dom = $this->page->getDom();
		$start = (!$start) ? 0 : $start;
		$end = (!$end) ? count($dom)-1 : $end;
		// Получаем id старта и конца среза
		if(!is_numeric($start)) $start = $this->getId($start);
		if(!is_numeric($end)) $end = $this->getId($end);
		// Получаем срез
		$newDom = array_slice($dom, $start, $end-$start);
		$this->page->setDom(CPesHandler::patters($newDom));
		return $this;
	}
	
	/**
	* Функция установки тегов для парсинга,теги через запятую
	* в type - ставим константу TagsAdd, TagsNew
	*/
	public function tags($tags,$type=TagsAdd){
		$tagsDefault = CPesHandler::$tagsDefault;
		if($type==TagsAdd){
			$tag = explode(",",$tags);
			foreach ($tag as $val){
				$t = trim($val);
				if(!in_array($t, $tagsDefault)) 
					array_push($tagsDefault, $t);
			}
		}
		else{
			$tag = explode(",",$tags.",");
			$del = count($tag)-1;
			unset($tag[$del]);
			$tagsDefault = $tag;
			CPesHandler::$activeTags = true;
		}
		CPesHandler::$tagsDefault = $tagsDefault;
		$html = $this->page->html();
		return $this->html($html);
	}
	
	// Возвращаем текст
    public function text($html=false){
		return ($html) ? CPesHandler::getText($html) : $this->page->text();
	}

    // Функция поиска тега по слову в тексте (слова через запятую) изменяет дерево
    public function words($words, $encode = null){
        $word = explode(",", $words . ",");
        $cI = count($word) - 1;
        unset($word[$cI]);
        
        foreach($word as $key => $val){
            $word[$key] = trim($word[$key]);
            $encode = ($encode == null) ? 
                    mb_detect_encoding($word[$key], "auto") : $encode;
            if($encode != "UTF-8"){
                $word[$key] = mb_strtolower(CPesFormat::inUtf8($word[$key], $encode));
            }
        }
        $dom = $this->page->getDom();
        $cD = count($dom);
        
        for($i = 0; $i < $cD; $i++) {
            $str = strip_tags((str_replace("~", "<", $dom[$i]['old'])));
            if(!empty($str)) {
                $str = mb_strtolower(CPesFormat::inUtf8($str, mb_detect_encoding($str, "auto")),"UTF-8");
                foreach($word as $phrase) {
                    if(substr_count($str,$phrase) > 0){
                        $start = $dom[$i]['patter'];
                        $end = $dom[$start]['end'];
                        for($u = $start; $u <= $end; $u++){
                            $result[] = $dom[$u];
                        }
                        $i=$end;
                        break;
                    }
                }
            }
        }
        $this->page->setDom(CPesHandler::patters($result));
        return $this;
    }

	/**
	* Функция оборачивания
	* $start и $end теги;
	*/
	public function wrap($start, $end, $tag = "item")
	{
		if (empty($tag)) {
			return false;
		}
		
		// Получаем id открывающих и закрывающих тегов
		$id['open'] = CPesTaskFind::create($start)->id($this->page, 0);
		$id['close'] = CPesTaskFind::create($end)->id($this->page, 1);
		$ok = array();
		$types = array('open', 'close');
		
		foreach ($id['open'] as $val) {
			$key = $val["start"];
			$ok[$key] = $val;
			$ok[$key]["type"] = "start";
		}
		foreach ($id['close'] as $val) {
			$key = $val["start"];
			$ok[$key] = $val;
			$ok[$key]["type"] = "close";
		}
		
		ksort($ok);
		$id = array_values($ok);
		
		$type="start";
		$id_end = 0;
		$dom = $this->page->getDom();
		foreach($id as $val){
			if($val["type"]==$type && $val["start"]>$id_end){
				$id_end = $val["end"];
				if($type=="start"){
					$type = "close";
					$u = $val["start"];
				}
				else{
					$type = "start";
					$i = $val["end"];
                    $dom[$u]['old'] = "~$tag>".$dom[$u]['old'];
					$dom[$i]['old'] = $dom[$i]['old']."~/$tag>";
 				}
			}
		}
		$this->page->setDom($dom);
		$html = $this->html();
		// Преобразум страницу в массив
		$html = CPesHandler::convert($html);
		// Выстраиваем ДОМ
		$dom = CPesHandler::buildDom($html);
		$this->page->setDom($dom);
		
		return $this;
	}
	
	// Функция возвращения зафиксированного html-кода
	public function up($flag="user"){
		$this->page->up($flag);
		return $this;
	}
}