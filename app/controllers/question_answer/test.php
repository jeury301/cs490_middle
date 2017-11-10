<?php

$output = exec('./call_python.sh test.py 2>&1');
echo $output;