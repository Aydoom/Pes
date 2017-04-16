<?php

/**
 * Класс - хранилище комментариев
*/
namespace Pes;

class CPesComment{

	static private $comments;
	
	static public function setComment($comment){
		self::$comments = $comment;
	}
	
	static public function getComment($comment){
		return self::$comments;
	}

	// Возвращаем ранее удаленные комментарии в код
	static public function add($html){
		$comment = self::$comments;
		$search = [];
		$needle = [];
		foreach($comment as $key => $val){
			$search[] = "<pes:comment>".$key."</pes:comment>";
			$needle[] = $val;
		}
		return str_replace($search, $needle, $html);
	}
	
	// Удаляем комментарии из кода
	static public function remove($html){
		$comment = array();
		$html = str_replace(array("<!--","-->"), array("~$","~^"), $html);
		$el = explode("~",$html);
		$cI = count($el);
		$start = false;
		$k=0;
		$new_html = "";
		for($i=0;$i<$cI;$i++){
			if($start){
				if($el[$i][0]=="^"){
					$comment[$k].="~^";
					$start = false;
					$new_html.="<pes:comment>".$k."</pes:comment>~".$el[$i];
					$k++;
				}
			}
			elseif($el[$i][0]=="$"){
				$start = true;
				$comment[$k] = "~".$el[$i];
			}
			else $new_html.=$el[$i];
		}
		foreach($comment as $k => $v){
			$comment[$k] = str_replace(array("~$","~^"), array("<!--","-->"), $comment[$k]);
		}
		self::setComment($comment);
		return str_replace(array("~$","~^"), "", $new_html);	
	}
}