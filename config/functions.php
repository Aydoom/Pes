<?php

function pr($array = "Test Run", $end = true)
{
    /*echo "<pre>";
        print_r(debug_backtrace());
    echo "</pre>";*/
    echo "<pre>";
        print_r($array);
    echo "</pre>";
	
	 var_dump(debug_backtrace());
    
    if ($end) {
        exit();
    }
}