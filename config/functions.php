<?php

function pr($array = "Test Run", $end = true)
{
    /*echo "<pre>";
        print_r(debug_backtrace());
    echo "</pre>";*/
    echo "<pre>";
        print_r($array);
    echo "</pre>";
	
    echo "<br/><br/><br/><br/>";
	echo "<p> ------------ DEBUG -------------- </p>";
    echo "<pre>";
		print_r(debug_backtrace());
    echo "</pre>";
    
    if ($end) {
        exit();
    }
}