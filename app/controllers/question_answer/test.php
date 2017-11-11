<?php

//$output = exec('./call_python.sh test.py 2>&1');


$output = exec('cmdpid=$BASHPID; (sleep 2; kill $cmdpid) & ./call_python.sh test.py 2>&1')


echo $output;