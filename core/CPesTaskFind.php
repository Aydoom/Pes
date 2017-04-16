<?php
/**
 * Класс для обработки поиска
 *
 * С помощью данного класса осуществляется поиск, на основании селекторов, в 
 * отформатированном документе.
 * 
*/

namespace Pes;

class CPesTaskFind
{
	public static $selectors = array();
	public $activeSel;
	public $dom;
	
    // Запрет на обход каждого из элементов
	public $order = false;
	
	// Ошибки при поиске
	static $errors = array(); 

	/**
	 * Возвращает TRUE если у элемента есть искомый атрибут
	 */
	public function attrFind($element, $attr=false)
    {
		if (!$attr)
			return true;

		// Определяем значение атрибута
		$key = $attr['attr'];

		// Сама проверки
		if (isset($element['attr'][$key])) {
            if ($attr['val'] == mb_strtolower($element['attr'][$key], "UTF-8")
               || $attr['val'] === true
            ) {
                return true;
            }
 		}

		return false;
	}

	/**
     * Создаем экземпляр класса, с активным селектором
     */
	static function create($sel)
    {
		$task = new self();

		// Определяем наличие селектора
		if(isset(self::$selectors[$sel])) {
			$task->activeSel = self::$selectors[$sel];
		} else {
			$task->activeSel = self::$selectors[$sel] = self::parsSelector($sel);
		}

		return $task;
	}

	/**
     * Вспомогательная функция для реализации совпадения
     */
	private function commFind($element, $sel, $id = false)
    {
		// Если селектор пустой, то возвращаем сразу true
		if ($sel["search"] == 0) {
			return true;
		}

        // Если функция запущена через поиск текста
		$find = $id;

		// Ищем совпадения
		// По тегу
		if ($sel['index']['tag']) {
			
			if (isset($element['tag'])) {
				$find = ($sel['tag'] == $element['tag']) ? true : false;
			} else {
				$find = false;
			}
		}

		// По классу
		if ($sel['index']['class']) {
			// Class элемента существует и совпадает с селектором
			$find = (isset($element['attr']['class']) && (is_array($element['attr']['class']))
                    && (isset($sel['class']))
                    && (in_array($sel['class'], $element['attr']['class']))
                    ) ? true : false;
		}

		// По id существования
		if (isset($element['attr']['id'])){
			if ($sel['index']['idIs']) {
				// ID элемент только существует
				$find = isset($element['attr']['id']);
			} elseif ($sel['index']['id']) {
				// ID элемента существует и совпадает с селектором
				$find = ($element['attr']['id'] == $sel['id']) ? true : false;
			}
		}

		// По тексту
		if ($sel['index']['text'] && !$id) {
			
			$count = substr_count(
						mb_strtolower($element['old'], "UTF-8"), 
						$sel['text']
					);
			
			if ((empty($element['tag'])) && ($count > 0)) {
				$u['patter'] = $element['patter'];
				while (($u['patter'] > 0) && (!$u['find'])) {
					$u = $this->commFind($u['patter'], $sel, true);
				}
				if ($u['find']) {
                    $find['patter'] = $u['patter'];
                }
			} else {
                $find = false;
            }
			unset($count);
		}

		// Возвращаем родителя вслучае поиска по тексту
		if ($id) {
			// возможно вместо $element['id'] = 0
			$u['patter'] = ($find) ? $element['id'] : $element['patter']; 
			$u['find'] = $find;
			$find = $u;
		}
		
		return $find;
	}

	// Получаем из селектора массив для поиска
	public static function getSelector($sel)
	{
		mb_regex_encoding('UTF-8');
		mb_internal_encoding("UTF-8"); 

		$sel = mb_strtolower(CPesFormat::inUtf8($sel));
		
		$ok['search'] = 0;
		$ok['index']['tag'] = false;
		$ok['index']['class'] = false;
		$ok['index']['id'] = false;
		$ok['index']['idIs'] = false;
		$ok['index']['text'] = false;

		$af = mb_split("[.#!]", $sel);
		if (strlen($af[0]) > 0) {
			$ok['tag'] = trim($af[0]);
			$ok['search'] += 1000;
			$ok['index']['tag'] = true;
		}
		
		$af = mb_split("\.", $sel);
		if (isset($af[1])) {
			$af2 = mb_split("[#\!]", $af[1]);
			$ok['class'] = trim($af2[0]);
			$ok['index']['class'] = true;
			$ok['search'] += 100;
		}
		
		$af = mb_split("#", $sel);
		if (isset($af[1])) {
			$af2 = mb_split("\!", $af[1]);
			$ok['id'] = trim($af2[0]);
			if (strlen($ok['id']) == 0) {
				$ok['index']['idIs'] = true;
				$ok['search'] += 20;
			} else {
				$ok['index']['id'] = true;
				$ok['search'] += 10;
			}
		}
		
		$af = mb_split("!", $sel);
		if (isset($af[1])) {
			$af[1] = CPesFormat::inUtf8($af[1]);
			$af[1] = mb_strtolower($af[1], "UTF-8");
			$ok['text'] = trim($af[1]);
			$ok['index']['text'] = true;
			$ok['search'] += 1;
		}
		
		return $ok;
	}
	
	// Обрабатываем селектор
	public static function parsSelector($sel)
	{
		$selector = array();
		$sel = trim($sel);
		// Разбиваем на равные
		$key = 0;
		$neighbors = explode(',', $sel);
		foreach ($neighbors as $neighbor) {
			// Разбиваем на наследников
			$childrens = explode('>', $neighbor);
			foreach ($childrens as $children) {
				$order = array_map(
						create_function('$val', 'return trim($val);'), 
						explode(':', $children));
				$order['sel'] = self::getSelector($order[0]);
				$selector[$key][] = $order;
			}
			$key++;
		}
		return $selector;
	}

	// Для сложных селекторов
	public function find(CPesPage $page, $start = 1, $attr = false)
	{
		$total = array();
		foreach ($this->activeSel as $val) {
			// Дерево
			$dom = $page->getDom();
			
			foreach ($val as $parent => $sel) {
				// Ядерный поиск
				$result = $this->onceFind($dom, $sel, $start, $attr);
				
				if (count($result) == 0) {
                    $selErr = "";
                    if ($parent > 0) {
                        for ($y = 0; $y <= $parent; $y++) {
                            $selErr .= ">" . $val[$y][0];
                        }
                        $selErr = ltrim($selErr, ">");
                    } else {
                        $selErr = $sel[0];
					}
					CPesMsg::notes("По селектору ($selErr) ни чего не найдено");
					
					if (empty(self::$errors[$selErr])) {
						self::$errors[$selErr] = 1;
					} else {					
						self::$errors[$selErr]++;
					}
				} else {
					foreach ($result as $keys) {
						if (!empty($keys)) {
							for($i = $keys['start']; $i <= $keys['end']; $i++) {
								$newDom[] = $dom[$i];
							}
						}
					}
					$dom = CPesHandler::patters($newDom);
					$newDom = array();
				}
			}
			$total = array_merge($total, $dom);
		}
		if (count($this->activeSel) > 1) {
			$total = CPesHandler::patters($total);
		}
		return $total;
	}
	
	// Возвращаем первое совпадение
	public function first(CPesPage $page, $start = 1, $attr = false)
	{
		$this->_addFirst();
		$result = $this->id($page, $start, $attr);
		foreach ($result as $id) {
			$ok[] = $id["start"];
		}
		sort($ok);
		return (int)$ok[0];
	}

	// Возвращаем true если элемент существует
	public function is(CPesPage $page, $start = 1, $attr = false)
	{
		$this->_addFirst();
		return (count($this->id($page, $start, $attr)) > 0);
	}
	
	// Добавляет к селектору определитель first
	protected function _addFirst()
	{
		foreach ($this->activeSel as $k => $val) {
			foreach ($val as $key => $sel) {
				if (empty($this->activeSel[$k][$key][1])) {
					$this->activeSel[$k][$key][1] = "first";
				}
			}
		}
	}
	
	// Возврат id элементов
	public function id(CPesPage $page, $start = 1, $attr = false){
		$result = array();
		foreach ($this->activeSel as $val) {
			// Дерево
			$dom = $page->getDom();
            // Получаем последний ключ в массиве
            $last_key = key(array_slice($val, -1, 1, TRUE));
			foreach ($val as $key => $sel) {
				// Ядерный поиск
				$in_result = $this->onceFind($dom, $sel, $start, $attr);
                if (empty($in_result)) {
                    $in_result = array();
                }
                // Если будет проводится еще один поиск, то Обновляем Дом
                if ($key != $last_key) {
                    $dom_old = $dom;
                    $dom = array();
                    foreach ($in_result as $path) {
                        for ($i = $path['start'] + 1; $i < $path['end']; $i++) {
                            $dom[$i] = $dom_old[$i];
                        }
                    }
                    unset($dom_old);
                }
			}
            $result = array_merge($result, $in_result);
		}
		return $result;
	}

	// Возврат id элементов
	public function all(CPesPage $page, $start = 1, $attr = false)
	{
		$this->order = true;
		$result = array();
		foreach ($this->activeSel as $val) {
			// Дерево
			$dom = $page->getDom();
			foreach ($val as $key => $sel) {
				// Ядерный поиск
				$in_result = $this->onceFind($dom, $sel, $start, $attr);
                if (empty($in_result)) {
                    $in_result = array();
                }
			}
            $result = array_merge($result, $in_result);
		}
		return $result;
	}

	// Ядерный поиск
	protected function onceFind($dom, $sel, $start, $attr = false)
	{
		// Колличество элементов в документе
        end($dom);
		$cI = key($dom);
		$result = null;
		
		// Перебираем все элементы
		for ($i = $start; $i <= $cI; $i++) {
            if (empty($dom[$i])) {
                continue;
            }
			$element = $dom[$i];
			// Значение для поиска по атрибутам
			if ($this->attrFind($element, $attr)) {
				//-------------------------------
				// Обработка с проверкой совпадения
				if ($find = $this->commFind($element, $sel['sel'])) {
					if (isset($element['patter'])) {
				        if ($start > 0 && $element['patter'] == -1) {
				            continue;
				        } elseif (!isset($element['tag'])) { // Если нет данных о теге поиск по тексту
							// Определяем id родителя
							$id_patter = $find['patter'];
							// Определяем наличие закрывающего тега у родителя
							if ($dom[$id_patter]['end']) {
								$end = $dom[$id_patter]['end'] - 1;
							} else {
								$end = $cI - 1;
							}
							// Результат
							$res = array('start' => $id_patter,'end' => $end);
						} elseif ((!isset($element['end'])) // Если нет данных о положении закрывающего тега
								  && ($element['start'] != 'self')
						) { 
							// Определяем id родителя
							$id_patter = $element['patter'];
							// Определяем наличие закрывающего тега у родителя
							if ($dom[$id_patter]['end']) {
								$end = $dom[$id_patter]['end'] - 1;
							} else {
								$end = count($dom) - 1;
							}
							// Результат
							$res = array('start' => $i,'end' => $end);
						} elseif ($element['start'] == 'self') { // Если тег самозакрывающийся
							$res = array('start' => $i, 'end' => $i);
						} else { // Остальные случаи
							$res = array('start' => $i,'end' => $element['end']);
						}
						
						$end2 = (empty($res['end'])) ? $i : $res['end'];
						// Проверка на цикл
						if ($end2 < $i) {
							CPesMsg::error("PES уходит в цикл при поиске &lt;"
									. $sel['tag']
									. "&gt; [line:"
									. __LINE__
									. "]."
							);
						}
						// Присваиваем значение следующего тега если не установлен запрет
						if (!$this->order) {
							$i = ($end2 < $i) ? $i : $end2;
						}
						// Записываем результат
						$result[] = $res;
						// Возврат первого совпадения
						if (isset($sel[1]) && $sel[1] == 'first') {
							return $result;
						}	
					}
				}
			}
		}
		if (isset($sel[1]) && $sel[1] == 'last') {
			
			if (count($result) > 1) {
				return array_slice($result, -1);
			} else {
				return $result;
			}
		}
		
		return $result;
	}
}