<?php

// THIS FILE IS JUST A SANDBOX.  NOT TO BE USED IN PRODUCTION

$innerArray = array(
	"innerKey1" => "innerValue1",
	"innerKey2" => "innerValue2",
	"innerKey3" => "innerValue3"
);

$outerArray = array(
	"outerKey1" => "outerKey1",
	"outerKey2" => "outerKey2",
	"outerKeyI" => $innerArray
);

//header('Content-Type: application/json');
echo(json_encode($outerArray));

$rawJson = '{"outerKey6":"outerKey6","outerKey7":"outerKey7","outerKeyX":{"innerKey8":"innerValue8","innerKey9":"innerValue9","innerKey0":"innerValue0"}}';

$parsedJson = json_decode($rawJson, True);

echo "<br/>parsedJson1: </br>";
print_r($parsedJson);
echo "<br/>parsedJson['outerKeyX']: </br>";
print_r($parsedJson["outerKeyX"]);

/*
$rawJson = '{"outerKey6":"outerKey6","outerKey7":"outerKey7","outerKeyX":[{"innerKey8":"innerValue8"},{"innerKey9":"innerValue9"},{"innerKey0":"innerValue0"}]}';

$parsedJson = json_decode($rawJson, True);

echo "<br/>parsedJson2: ";
echo $parsedJson;
echo $parsedJson["outerKeyX"];
*/