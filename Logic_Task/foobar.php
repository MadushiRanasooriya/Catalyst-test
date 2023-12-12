<?php
	for ($i = 1; $i <= 100; $i++) {
		if (divide($i, 3) || divide($i, 5)) {
			if (divide($i, 3)) {
				echo "foo";
			}
			if (divide($i, 5)) {
				echo "bar";
			}
		}
		else {
			echo "$i";
		}
		if ($i<100) {
			echo ", ";
		}
	} 
	echo "\n";
	
	
	//function to Check if a given number is divisible by a specified divisor
	function divide($number, $divisor, $string="test") {
		if ($number % $divisor === 0) {
			return true;
		} else {
			return false;
		}
	}
	
?>
