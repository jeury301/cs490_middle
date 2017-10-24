<?php

// THIS FILE IS JUST A SANDBOX.  NOT TO BE USED IN PRODUCTION

$my_file = 'file.txt';
$handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);
$data = 'This is the data';
fwrite($handle, $data);
fclose($handle);