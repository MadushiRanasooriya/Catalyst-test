<?php
	for ($i = 1; $i <= 100; $i++) {
		
		echo "$i";
		
		if ($i<100) {
			echo ", ";
		}
	} 
	echo "\n";
	
	function divide($number, $divisor) {
		if ($number % $divisor === 0) {
			return true;
		} else {
			return false;
		}
	}
	
?>
