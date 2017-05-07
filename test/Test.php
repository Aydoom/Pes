<?php 

namespace Pes\Test;

abstract class Test {

	public $pes;
	
	public $html;
	
	public $tests = [];
	
	
	abstract function setHtml();
	abstract function setTests();
	
	
	public function __construct() {
		
		// Download html from the doughter class
		$this->setHtml();
		
		$this->pes = new \Pes\Core\Pes($this->html);
		
		// Download tests from the doughter class
		$this->setTests();
		
	}
	
	
	
	public function check() {
	
		$acceptable = true;
		
		foreach($this->tests as $test) {
		
			if (!$this->$test()) {
			
				$acceptable = false;
			
			}
		
		}
		
		if ($acceptable) {
		
			$this->right();
		
		} else {
			
			$this->wrong();
			
		}
	
	}
	
	
	
	public function right() {
	
		\Pes\Core\CPesMsg::message('All ok!');
		die();
	
	}
	
	
	
	public function wrong() {
		
		\Pes\Core\CPesMsg::error('Error!');
		die();
		
	}
	
}