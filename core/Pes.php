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

/**
 * This file is part of the Pes package.
 * 
 * (c) Pavel A.D. <aydoom@li.ru>
 */

namespace Pes\Core;

/**
 * Html parser
 */
class Pes extends CPesBasic
{

    /**
     * Константа добавления тегов
     */
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

    /**
     * Конструктор Pes
     * @param type $url - адресс страницы которую будем парсить
     * @param type $encode - кодировка страницы
     * @param type $redirect - разрешение на перезагрузку странци которую парсим,
     * при автоматическом перенаправлении на другую страницу.
     */
    public function __construct($url = false, $encode = null, $redirect = true)
    {
        if ($url) {
            $url.= (empty(array_pop(explode("/", $url)))) ? "index.php" : null;
            $this->load($url, $encode, $redirect);
        }
    }

    /**
     * __toString()
     * @return type
     */
    public function __toString()
    {
        return $this->html();
    }
	
    /**
     * Add() Добавление тегов в начало или конец кода, 
     * default - (оборачиваем html  в тег)
     * 
     * @param type $tag
     * @param type $place - start (вначале открывающий), 
     * end - (вконце закрывающий)
     * 
     * @return \Pes\Core\Pes
     */
    public function add($tag, $place = false)
    {
        switch ($place) {
            case 'start':
                $html = "<$tag>" . $this->html();
                break;
            case 'end':
                $html = $this->html() . "</$tag>";
                break;
            default:
                $html = "<$tag>" . $this->html() . "</$tag>";
        }
        
        $this->html($html);
		
        return $this;
    }

    /*
     *  Функция возвращения и замены значение атрибута первого элемента
     * @attr - атрибут, значение которого нужно вернуть
     * @change - значение на которое необходимо заменить найденные атрибуты
     */
    public function attr($attr, $change = false)
    {
        $block = $this->page->getDom()->getFirstRow();

        if ($change) {
            $block['tag']->changeAttr($attr, $change);
            return $this;
        } elseif ($block['tag']->hasAttr($attr)) {
            CPesMsg::notes("атрибут $attr не найден");
            return false;
        } else {
            return $block['tag']->getAttr($attr);
        }
    }
	
    /*
     * Функция удаления тега со всем содержимым из кода
     * @sel - селлектор
     * @type - тип удаления, all - во всем документе, in - внутри отцовского
     */
    public function delete($sel, $type = "all") 
    {
        $start = ($type === "all") ? 0 : 1;
        $id = CPesTaskFind::create($sel)->id($this->page, $start);
        $this->page->delete($id);

        return $this;
    }
	
    /*
     * Функция выведения массивом данных по тегам начиная с самого первого
     * @function - колл бэк функция (1-ый параметр - $pes, 2-ой $html-код)
     * @delEmpty - удаляем элементы без содержимого
    */
    public function each($function = false, $delEmpty = true)
    {
        $dom = $this->page->getDom();
        $cI = count($dom);
        $key = 0;
        $haveText = true;
        
        for($i = 0; $i < $cI; $i++) {
            if($dom[$i]['patter'] === -1) {
                $cU = (isset($dom[$i]['end'])) ? $dom[$i]['end'] : $i;
                $code = array();
                
                for($u = $i; $u <= $cU; $u++) {
                    $code[] = CPesHandler::inHtml($dom[$u]['old']);
                }
                
                $html = CPesFormat::trimTwo(implode("", $code));

                if($delEmpty) {
                    $haveText = (strlen($this->text($html)) > 0);
                }
                
                if(strlen($html) > 0 && $haveText) {
                    $ok[$key] = $html;
                    $i = $u - 1;
                    $key++;
                }
            }
        }
        
        if(is_callable($function) && !empty($ok)) {
            foreach($ok as $html){
                $result[] = $function($this, $html);
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
        
            if (isset($mark) && $mark === "last") {
                $id--;
                $is_do = ($id > 0);
            } else {
                if (!empty($dom[$id]['end'])) {
                    $id = $dom[$id]['end'] + 1;
                } else {
                    $id++;
                }

                $is_do = ($id < $countDom);
            }
        } while($is_do);
        
        return $this;
    }
	
    /**
     * Функция поиска по селектору
     *
    */
    public function find($sel, $start = 1)
    {
        $result = CPesTaskFind::create($sel)->find($this->page, $start);
        $this->page->setDom($result);
        
        return $this;
    }


    /**
     * Функция поиска дочернего элемента с определенным атрибутом 
     * атрибут и значение через :
     * 
     * @param type $attr
     * @param type $sel
     * @param type $start
     * 
     * @return boolean|\Pes\Core\Pes
     */
    public function findAttr($attr = false, $sel = "",$start = 0)
    {
        if ($attr === false) {
            return false;
        } else {
           $this->page->setDom(CPesTaskFind::create($sel)->find(
                    $this->page, 
                    $start, 
                    CPesFormat::attrConvert($attr)));
        }
            
        return $this;
    }
	
    /**
     * Функция поиска по селектору с возратом текста
    */
    public function findText($sel, $start = 1)
    {
        $output = $this->fix("_text")->find($sel, $start)->text();
        $this->fixExt("_text");
        
        return $output;
    }

    // Функция фиксации html-кода (запаминаем, чтобы можно было вернуться)
    public function fix($flag = "user")
    {
        $this->page->fix($flag);
        
        return $this;
    }

    // Функция удаления фиксации html-кода ()
    public function fixDel($flag = "user")
    {
        $this->page->unset_fix($flag);
    }
	
    // Функция извлечение фиксации html-кода ()
    public function fixExt($flag = "user")
    {
        $this->up($flag)->fixDel($flag);
    }

    // Функция для очистки всей информации хранимой псом
    public function foo()
    {
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
    public function getId($sel, $start = 1)
    {
        return CPesTaskFind::create($sel)->first($this->page, $start);
    }

	
    // Функция выведения html - кода
    public function html($html = false)
    {
        if($html) {
            // Преобразуем страницу в объект
            $dom = new CPesTransform($html);
            $this->page->setDom($dom);

            return $this;
        } else {
            return $this->page->html();
        }
    }
	
    // Функция перевода всего текста(old) в тег tag
    public function intoTag($tag = "tag")
    {
        $m = true;
        $dom = $this->page->getDom();
        foreach ($dom as $i => $val){
            if($m && !isset($val['tag'])) {
                $dom[$i]['old'] = "~$tag>" . $val['old'] . "~/$tag>";
            } elseif(substr_count($val['old'], "<~/pes:") > 0) {
                $m = !$m;
            }
        }
        
        $this->page->setDom($dom);
        // Преобразум страницу в массив и Выстраиваем ДОМ
        $this->page->setDom(CPesHandler::buildDom(
                 CPesHandler::convert($this->html())));

        return $this;
    }

    /**
     * Функция поиска наличия селектора в коде
     * @param type $sel
     * @return type
     */
    public function is($sel)
    {
        return CPesTaskFind::create($sel)->is($this->page, 0);
    }

    /**
     * Функция определения 404 ошибки
     * @return type
     */
    public function is_404()
    {
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
        if ($global && isset($url['url'])) {
            if ((substr_count($href, 'http://') == 0) 
                 || (substr_count($href, 'https://') == 0)
                ) {
                    if ($href[0] === '/') {
                        $href = $url['scheme'] . '://' . $url['host'] . $href;
                    } else {
                        $href = $url['scheme'] . '://' . $url['host'] . '/'
                                . $url['path'] . '/' . $href; 
                    }
                }
        }
        $this->up('link')->fixDel('link');

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
    public function load($html, $encode = false, $redirect = true) 
    {
        $load = new CPesLoad($html, $encode, $redirect);
        // Меняем кодировку и Преобразуем страницу в объект
        $dom = new CPesTransform(CPesFormat::inUtf8($load->getHtml()));
        // Создаем объект страницы
        $this->page = new CPesPage(false, $dom);
    }
	
    // Вывод на экран doc массива
    public function mDom($stop_after_m = true)
    {
        CPesMsg::display($this->page->getDom());
        if ($stop_after_m) {
            exit;
        }
    }
	
    // Вывод на экран результата обработки в html
    public function mHtml($stop_after_m = true)
    {
        CPesMsg::style();
        echo $this->html();
        if ($stop_after_m) {
            exit;
        }
    }

    /*
     *  Устанавливаем данные, которые будут переданы методом post
     * @data - данные в виде массива
     * @const - указывает при true что, данные не будут удалены после обращении к странице
     */
    public function post($data = array(), $url = false, $encode = null)
    {
        // Подготавливаем запрос
        CPesCurl::$is_post = true;
        foreach ($data as $key => $val) {
            $query[] = "$key=$val&";
        }
        CPesCurl::$query = implode("&", $query);
        
        // Ищем адрес формы
        if(!$url){
            $url = $this->find("form")->attr("action");
        }
        
        // Грузим страницу
        $this->load($url, $encode);
    }

    // Функция удаления тега из кода
    public function remove($sel)
    {
        $dom = $this->page;
        $result = CPesTaskFind::create($sel)->id($dom, 0);
        $newDom = $dom->getDom();
        foreach($result as $element){
            foreach($element as $id){
                if(isset($newDom[$id])) {
                    unset($newDom[$id]);
                }
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
    public function rename($seach, $need = "td")
    {
        $ids = CPesTaskFind::create($seach)->all($this->page, 0);
        $dom = $this->page->getDom();
        // Переименовываем
        if(count($ids)>0) {
            foreach($ids as $val){
                $end = $val['end'];
                $start = $val['start'];
                $tag = $dom[$start]['tag'];
                // Сначало закрывающий, далее открывающий (не наоборот)
                $dom[$end]['tag'] = $dom[$start]['tag'] = $need;
                $dom[$end]['old'] = 
                        str_replace("~/$tag", "~/$need", $dom[$end]['old']);
                $dom[$start]['old'] = 
                        tr_replace("~$tag", "~$need", $dom[$start]['old']);
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
            $need = (is_string($needle)) ? array($needle) : $needle;
            foreach ($seach as $key => $val) {
                if(empty($need[$key])) {
                    $need[$key] = end($need);
                }
                $html = str_replace($val, $need[$key], $html);
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
    public function slice($start = false, $end = false)
    {
        $dom = $this->page->getDom();
        
        // Получаем id старта и конца среза
        if ($start === false) {
            $start = 0;
        } elseif(!is_numeric($start)) {
            $start = $this->getId($start);
        }
        
        if ($end === false) {
            $end = count($dom) - 1;
        } elseif(!is_numeric($end)) {
            $end = $this->getId($end);
        }
        
        // Получаем срез
        $this->page->setDom(CPesHandler::patters(
                 array_slice($dom, $start, $end - $start)));
        
        return $this;
    }
	
    /**
    * Функция установки тегов для парсинга,теги через запятую
    * в type - ставим константу TagsAdd, TagsNew
    */
    public function tags($tags, $type = TagsAdd)
    {
        $tagsDefault = CPesHandler::$tagsDefault;
        if($type == TagsAdd) {
            $tag = explode(",", $tags);
            foreach ($tag as $val){
                $t = trim($val);
                if(!in_array($t, $tagsDefault)) {
                    array_push($tagsDefault, $t);
                }
            }
        } else {
            $tag = explode(",", $tags . ",");
            $del = count($tag) - 1;
            unset($tag[$del]);
            $tagsDefault = $tag;
            CPesHandler::$activeTags = true;
        }
        CPesHandler::$tagsDefault = $tagsDefault;
        
        return $this->html($this->page->html());
    }
	
    // Возвращаем текст
    public function text($html = false)
    {
        return ($html) ? CPesHandler::getText($html) : $this->page->text();
    }

    // Функция поиска тега по слову в тексте (слова через запятую) изменяет дерево
    public function words($words, $encode = null)
    {
        $word = explode(",", $words . ",");
        $cI = count($word) - 1;
        unset($word[$cI]);
        
        foreach($word as $key => $val){
            $word[$key] = trim($val);
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
        
        foreach(['open', 'close'] as $type) {
            foreach ($id[$type] as $val) {
                $key = $val["start"];
                $ok[$key] = $val;
                $ok[$key]["type"] = $type;
            }            
        }

        ksort($ok);
        $id = array_values($ok);

        $type = "start";
        $id_end = 0;
        $dom = $this->page->getDom();
        foreach($id as $val){
            if($val["type"] == $type && $val["start"] > $id_end) {
                $id_end = $val["end"];
                if($type == "start") {
                    $type = "close";
                    $u = $val["start"];
                } else {
                    $type = "start";
                    $i = $val["end"];
                    $dom[$u]['old'] = "~$tag>" . $dom[$u]['old'];
                    $dom[$i]['old'] = $dom[$i]['old'] . "~/$tag>";
                }
            }
        }
        $this->page->setDom($dom);
        // Преобразум страницу в массив, Выстраиваем ДОМ
        $this->page->setDom(CPesHandler::buildDom(
                CPesHandler::convert($this->html())));

        return $this;
    }
	
    // Функция возвращения зафиксированного html-кода
    public function up($flag="user")
    {
            $this->page->up($flag);
            return $this;
    }
}