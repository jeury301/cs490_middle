<?php


ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

require '../services/curl_functions.php';
$BACKEND_ENDPOINTS = include '../backend_endpoints.php';

$time = time();
/*
 * TEST INSERT FUNCTIONALITY FOR ALL TABLES
 */
echo "<h1>INSERT TEST:</h1></br>";

$insertJsonPayloads = array(
    "question" => '{"action":"insert", "table_name":"question", "fields":{"question_text":"DummyQuestion' . $time . '", "func_name":"My dumb test function", "param_names":"7, 6, 5, 4, 3"}}',
    "test_case" => '{"action":"insert", "table_name":"test_case", "fields":{"question_id":10,"input":"DummyTestCase' . $time .'", "output":"output_val goes here, or more"}}',
    "student" => '{"action":"insert", "table_name":"student", "fields":{"user_name":"DummyStudent' . $time .'", "hash_salt":"$2y$10$BBp8BHUg9rfwFrOqLLeMY.c0SimhUAyW3J8K3.qwY500lcnT1ccPGEND"}}',
    "professor" => '{"action":"insert", "table_name":"professor", "fields":{"user_name":"DummyProf' . $time .'", "hash_salt":"$2y$10$BBp8BHUg9rfwFrOqLLeMY.c0SimhUAyW3J8K3.qwY500lcnT1ccPGEND"}}',
    "test" => '{"action":"insert", "table_name":"test", "fields":{"professor_id":1,"scores_released":0,"finalized":0}}',
    "question_answer" => '{"action":"insert", "table_name":"question_answer", "fields":{"question_id":1,"test_id":1,"student_id":1,"answer_text":"echo $helloworld","grade":100,"notes":"lorem ipsum"}}',
    "test_question" => '{"action":"insert", "table_name":"test_question", "fields":{"test_id":1,"question_id":1}}',
    "test_score" => '{"action":"insert", "table_name":"test_score", "fields":{"student_id":1,"test_id":1,"grade":100}}',
);

$insertedItems = array();
$keysArray = array_keys($insertJsonPayloads);

for ($i=0; $i<count($keysArray); $i++) {
    echo "============================<br/>";
    echo "Trying to insert record into " . $keysArray[$i] . " table...</br>";
    $json = $insertJsonPayloads[$keysArray[$i]];
    $backend_endpoint = $BACKEND_ENDPOINTS["insert"];
    $new_post_params = array("json_string" => $json);

    // Function takes a header arg, but not necessary here
    $header = array(); 
    // Make the CURL request
    $backend_json_response = curl_to_backend($header, 
                                             $backend_endpoint, 
                                             http_build_query($new_post_params));
    $parsed_response = json_decode($backend_json_response,true);
    if ($parsed_response["status"] == "success") {
        echo "Successfully performed " . $keysArray[$i] . "</br>";
        $indexName = $keysArray[$i];
        $insertedItems[$indexName] = $parsed_response["items"]["0"];
    } else {
        echo '<font color= "red"> ERROR </font> trying to ' . $keysArray[$i] . "</br>";
    }
    echo "Response received from back end: </br>";
    echo $backend_json_response;
    echo "</br>";
}

echo "============================<br/>";
echo 'Value of $insertedItems: <br/>';
print_r($insertedItems);
echo '<br/>';
echo "============================<br/>";
echo "<h1>DELETE TEST:</h1></br>";

foreach($insertedItems as $key => $value) {
    echo "============================<br/>";
    $json = '{"action":"delete", "table_name":"'. $key .'", "primary_key":"' . $value["primary_key"] .'", "fields":{}}';
    $backend_endpoint = $BACKEND_ENDPOINTS["delete"];
    $new_post_params = array("json_string" => $json);

    // Function takes a header arg, but not necessary here
    $header = array(); 
    // Make the CURL request
    $backend_json_response = curl_to_backend($header, 
                                             $backend_endpoint, 
                                             http_build_query($new_post_params));
    $parsed_response = json_decode($backend_json_response,true);
    if ($parsed_response["status"] == "success") {
        echo "Successfully deleted item with primary_key " . $value["primary_key"] . "</br>";
    } else {
        echo '<font color= "red"> ERROR </font> trying to delete item with primary_key ' . $value["primary_key"] . "</br>";
    }
    echo "Response received from back end: </br>";
    echo $backend_json_response;
    echo "</br>";
}


