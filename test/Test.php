<?php 

namespace Pes\Test;

abstract class Test {

    public $pes;

    public $html;

    public $tests = [];

    public $errorMsg;


    abstract function setHtml();
    abstract function setTests();


    public function __construct() {

        // Download html from the doughter class
        $this->setHtml();

        $this->pes = new \Pes\Core\Pes($this->html);

        // Download tests from the doughter class
        $this->setTests();

    }
	
	
    /**
     * function check()
     */
    public function check() {

        foreach($this->tests as $test) {

            $answer = $this->$test();

            if ($this->pes->html() != $answer) {

                $this->wrong($test, $answer);
                break;

            } else {
                
                $this->right($test);
                
            }

        }
       
        die();
    }
	
	
    /**
     * 
     * @param type $test
     */
    public function right($test) {

        $msg = get_class() . "::" . $test . "() return true: <br>";
        
        \Pes\Core\CPesMsg::message($msg);
        
    }
	
	
    /**
     * 
     * @param type $test
     * @param type $answer
     */
    public function wrong($test, $answer) {
        
        $msg = "<br>" . get_class() . "::" . $test . "() return false: <br>"
                . "&#9<u>actuality</u>: " . $this->_r($answer) . "<br>"
                . "&#9<u>texpected</u>: " . $this->_r($this->pes->html());
        
        \Pes\Core\CPesMsg::error($msg);
        die();

    }
    
    
    /**
     * 
     * @param type $html
     * @return type
     */
    public function _r($html) {
        
        $output = str_replace(["<", ">"], ["&#60;","&#62;"], $html);
        
        return $output;
    }
	
}