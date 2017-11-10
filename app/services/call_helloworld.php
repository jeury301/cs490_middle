<?php
	echo "Trying to call helloworld.py";
	exec('python helloworld.py 2>&1', $output);
	print_r($output);