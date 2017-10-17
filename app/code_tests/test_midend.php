<?php


ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

require '../services/curl_functions.php';
$MIDEND_ENDPOINTS = include '../midend_endpoints.php';

$error_count = 0;

$time = time();
/*
 * TEST INSERT FUNCTIONALITY FOR ALL TABLES
 */
echo "<h1>INSERT TEST:</h1></br>";

$insertJsonPayloads = array(
    "question" => '{"action":"insert", "fields":{"question_text":"DummyQuestion' . $time . '", "func_name":"My dumb test function", "param_names":"7, 6, 5, 4, 3"}}',
    "test_case" => '{"action":"insert", "fields":{"question_id":10,"input":"DummyTestCase' . $time .'", "output":"output_val goes here, or more"}}',
    "student" => '{"action":"insert", "fields":{"user_name":"DummyStudent' . $time .'", "hash_salt":"$2y$10$BBp8BHUg9rfwFrOqLLeMY.c0SimhUAyW3J8K3.qwY500lcnT1ccPGEND"}}',
    "professor" => '{"action":"insert", "fields":{"user_name":"DummyProf' . $time .'", "hash_salt":"$2y$10$BBp8BHUg9rfwFrOqLLeMY.c0SimhUAyW3J8K3.qwY500lcnT1ccPGEND"}}',
    "test" => '{"action":"insert", "fields":{"professor_id":7,"scores_released":0,"finalized":0}}',
    "question_answer" => '{"action":"insert", "fields":{"question_id":5,"test_id":37,"student_id":6,"answer_text":"DummyQuestionAnswer' . $time . '","grade":100,"notes":"lorem ipsum"}}',
    "test_score" => '{"action":"insert", "fields":{"student_id":6,"test_id":35,"grade":100}}',
    "test_question" => '{"action":"insert", "fields":{"test_id":37,"question_id":5}}',
);

$insertedItems = array();
$keysArray = array_keys($insertJsonPayloads);

for ($i=0; $i<count($keysArray); $i++) {
    echo "============================<br/>";
    echo "Trying to insert record into " . $keysArray[$i] . " table at url (".$MIDEND_ENDPOINTS[$keysArray[$i]].")...</br>";
    $json = $insertJsonPayloads[$keysArray[$i]];
    $midend_endpoint = $MIDEND_ENDPOINTS[$keysArray[$i]];
    $new_post_params = array("json_string" => $json);
    $header = array(); 
    $backend_json_response = curl_to_backend($header, 
                                             $midend_endpoint, 
                                             http_build_query($new_post_params));
    $parsed_response = json_decode($backend_json_response,true);
    if ($parsed_response["status"] == "success") {
        echo "Successfully performed " . $keysArray[$i] . "</br>";
        $indexName = $keysArray[$i];
        $insertedItems[$indexName] = $parsed_response["items"]["0"];
    } else {
        echo '<font color= "red"> ERROR </font> trying to ' . $keysArray[$i] . "</br>";
        $error_count++;
    }
    echo "Response received from middle end: </br>";
    echo $backend_json_response;
    echo "</br>";
}

echo "============================<br/>";
echo 'Value of $insertedItems: <br/>';
print_r($insertedItems);

echo '<br/>';
echo "============================<br/>";
echo "<h1>EDIT TEST:</h1></br>";

$editJsonPayloads = array(
    "question" => '{"primary_key":"' . $insertedItems["question"]["primary_key"] .'","action":"edit", "table_name":"question", "fields":{"question_text":"DummyQuestionEDITED' . $time . '", "func_name":"My dumb test functionEDITED", "param_names":"7, 6, 5, 4, 3, EDITED"}}',
    "test_case" => '{"primary_key":"' . $insertedItems["test_case"]["primary_key"] .'","action":"edit", "table_name":"test_case", "fields":{"question_id":10,"input":"DummyTestCaseEDITED' . $time .'", "output":"output_val goes here, or moreEDITED"}}',
    "student" => '{"primary_key":"' . $insertedItems["student"]["primary_key"] .'","action":"edit", "table_name":"student", "fields":{"user_name":"DummyStudent' . $time .'", "hash_salt":"$2y$10$BBp8BHUg9rfwFrOqLLeMY.c0SimhUAyW3J8K3.qwY500lcnT1ccPGENDEDITED"}}',
    "professor" => '{"primary_key":"' . $insertedItems["professor"]["primary_key"] .'","action":"edit", "table_name":"professor", "fields":{"user_name":"DummyProf' . $time .'", "hash_salt":"$2y$10$BBp8BHUg9rfwFrOqLLeMY.c0SimhUAyW3J8K3.qwY500lcnT1ccPGENDEDITED"}}',
    "test" => '{"primary_key":"' . $insertedItems["test"]["primary_key"] .'","action":"edit", "table_name":"test", "fields":{"professor_id":7,"scores_released":1,"finalized":1}}',
    "question_answer" => '{"primary_key":"' . $insertedItems["question_answer"]["primary_key"] .'","action":"edit", "table_name":"question_answer", "fields":{"question_id":5,"test_id":37,"student_id":6,"answer_text":"DummyQuestionAnswerEDITED' . $time . '","grade":100,"notes":"lorem ipsum"}}',
    "test_question" => '{"primary_key":"' . $insertedItems["test_question"]["primary_key"] .'","action":"edit", "table_name":"test_question", "fields":{"test_id":38,"question_id":5}}',
    "test_score" => '{"primary_key":"' . $insertedItems["test_score"]["primary_key"] .'","action":"edit", "table_name":"test_score", "fields":{"student_id":6,"test_id":35,"grade":50}}',
);

//print_r($editJsonPayloads);

foreach($insertedItems as $key => $value) {
    echo "============================<br/>";
    $json = $editJsonPayloads[$key];
    $midend_endpoint = $MIDEND_ENDPOINTS[$key];
    $new_post_params = array("json_string" => $json);
    $header = array(); 
    $backend_json_response = curl_to_backend($header, 
                                             $midend_endpoint, 
                                             http_build_query($new_post_params));
    $parsed_response = json_decode($backend_json_response,true);
    if ($parsed_response["status"] == "success") {
        echo "Successfully edited $key with primary_key " . $value["primary_key"] . "</br>";
    } else {
        echo '<font color= "red"> ERROR </font> trying to edit item with primary_key ' . $value["primary_key"] . "</br>";
        $error_count++;
    }
    echo "Response received from middle end: </br>";
    echo $backend_json_response;
    echo "</br>";
}

echo '<br/>';
echo "============================<br/>";
echo "<h1>LIST TEST:</h1></br>";

foreach($insertedItems as $key => $value) {
    echo "============================<br/>";
    $json = '{"action":"list", "table_name":"'. $key .'", "fields":{"primary_key":"' . $value["primary_key"] .'"}}';
    $midend_endpoint = $MIDEND_ENDPOINTS[$key];
    $new_post_params = array("json_string" => $json);

    // Function takes a header arg, but not necessary here
    $header = array(); 
    // Make the CURL request
    $backend_json_response = curl_to_backend($header, 
                                             $midend_endpoint, 
                                             http_build_query($new_post_params));
    $parsed_response = json_decode($backend_json_response,true);
    if ($parsed_response["status"] == "success") {
        echo "Successfully queried $key for record with primary_key " . $value["primary_key"] . "</br>";
    } else {
        echo '<font color= "red"> ERROR </font> trying to query item with primary_key ' . $value["primary_key"] . "</br>";
        $error_count++;
    }
    echo "Response received from middle end: </br>";
    echo $backend_json_response;
    echo "</br>";
}

echo '<br/>';
echo "============================<br/>";
echo "<h1>DELETE TEST:</h1></br>";

foreach($insertedItems as $key => $value) {
    echo "============================<br/>";
    $json = '{"action":"delete", "table_name":"'. $key .'", "primary_key":"' . $value["primary_key"] .'", "fields":{}}';
    $midend_endpoint = $MIDEND_ENDPOINTS[$key];
    $new_post_params = array("json_string" => $json);

    // Function takes a header arg, but not necessary here
    $header = array(); 
    // Make the CURL request
    $backend_json_response = curl_to_backend($header, 
                                             $midend_endpoint, 
                                             http_build_query($new_post_params));
    $parsed_response = json_decode($backend_json_response,true);
    if ($parsed_response["status"] == "success") {
        echo "Successfully deleted $key with primary_key " . $value["primary_key"] . "</br>";
    } else {
        echo '<font color= "red"> ERROR </font> trying to delete item with primary_key ' . $value["primary_key"] . "</br>";
        $error_count++;
    }
    echo "Response received from middle end: </br>";
    echo $backend_json_response;
    echo "</br>";
}

if ($error_count > 0) {
    echo '<font color= "red"><h1> Test failed with $error_count error(s) </h1></font></br>';
} else {
    echo '<font color= "green"><h1> Test passed with no errors </h1></font></br>';
}



