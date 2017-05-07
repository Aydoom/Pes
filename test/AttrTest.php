<?php 

namespace Pes\Test;

class AttrTest extends Test {


	public function setHtml() {
		
		$this->html = '<div id="search-inset0" class="formSearch"></div>';
		
	}

	
	public function setTests() {
		
		$this->tests = [
		
			'addOneAttr_test',
		
		/*
			$this->addOneAttrFromOne_test(),
			
			$this->addOneAttrFromPoly_test(),
			
			
			$this->addManyAttr_test(),
			
			$this->addManyAttrFromOne_test(),
			
			$this->addManyAttrFromPoly_test(),
			
			
			$this->addPolyAttr_test(),
			
			$this->addPolyAttrFromOne_test(),
			
			$this->addPolyAttrFromMany_test(),
			
			
			$this->changeAttr_test()
		*/
		];
		
	}
	
	
	
	public function addOneAttr_test() {
		
		$this->pes->attr('style', 'color:black');
		
		$new_html = '<div id="search-inset0" class="formSearch" style="color:black"></div>';

		
		return ($this->pes->html() === $new_html);
		
	}


}