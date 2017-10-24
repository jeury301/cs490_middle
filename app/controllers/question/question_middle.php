<?php

/**
 * question_middle.php
 *
 * Created by Michael Anderson on October 4, 2017
 *
 * Parses the request received from the front-end and
 * performs general, table-agnostic validation on the
 * data received from the front end.
 *
 * If validation fails, the script will return a 
 * formatted JSON error response directly to
 * the front end.  If validation is successful, the 
 * front-end request will be reformatted and passed
 * to the back end, and the back-end response will
 * in turn be returned to the front as JSON.
 *
 * This controller also performs the following validation/ 
 * actions SPECIFIC TO THE QUESTION TABLE:
 *
 * - When action is 'delete': 
 *    - Confirm a primary key has been passed in json_string
 *    - Determine if a question has already been used in a test;
 *      if so, prevent delete and return an error.  If not, 
 *      first delete the child test_cases of the question to be
 *      deleted.
 */     

// Uncomment to turn debug mode on:
// ini_set('display_startup_errors', 1);
// ini_set('display_errors', 1);
// error_reporting(-1);

require '../../services/initial_json_parse.php';
require '../../services/curl_functions.php';
$BACKEND_ENDPOINTS = include '../../backend_endpoints.php';

/* 
 * Preliminarily validate and parse JSON received as POST data,
 * e.g., check if valid "action" value passed; if JSON not validated
 * the function will generate an error response and exit
 */
$parsed_post_data = initial_json_parse();

$action = $parsed_post_data["action"];
$table_name = "question";

//print_r($parsed_post_data["fields"]);

/*
 * Set $fields to $parsed_post_data["fields"], or, if 
 * there is no "fields" array in the parsed JSON, 
 * instantiate an empty array and set $fields to it
 *
 * There are valid reasons to have an empty "fields"
 * array (e.g., for the delete action), but the protcol
 * specifies that "fields" be provided to the 
 * back end in any case
 */
if (isset($parsed_post_data["fields"])) {
    $fields = $parsed_post_data["fields"];
} else {
    $fields = array();
}

$backend_endpoint = $BACKEND_ENDPOINTS["question"];

if ($action == "insert") {
    // TODO: Do table-specific validation for insert,

} else if ($action == "edit") {
    // TODO: Do table-specific validation for edit

} else if ($action == "delete") {
    /*
     * Confirm a primary_key was passed in the post data.
     * If not, return an error message and exit
     */
    if (!isset($parsed_post_data["primary_key"]) ||
         empty($parsed_post_data["primary_key"]) ) {
        $error_msg = array(
            "action" => "delete",
            "status" => "error",
            "user_message" => "Error attempting to delete question.",
            "internal_message" => "question_middle.php: Missing primary_key in post data."
        );
        http_response_code(400); // Bad request
        header('Content-Type: application/json');
        exit(json_encode($error_msg));
    }
    /*
     * Check if the question has been used in a test already.
     * If so, prevent deletion and return an error message
     */
    if (has_question_been_used_in_test($parsed_post_data["primary_key"])) {
        $error_msg = array(
            "action" => "delete",
            "status" => "error",
            "user_message" => "This question cannot be deleted because it has " . 
                              "been used already in a test.",
            "internal_message" => "question_middle.php: Cannot delete question with " .
                                  "primary key " . $parsed_post_data["primary_key"] . 
                                  ". Already used as foreign key in test_question record.",
        );
        http_response_code(400); // Bad request
        header('Content-Type: application/json');
        exit(json_encode($error_msg));        
    }
    /*
     * Delete all test_cases that correspond to the question
     * before deleting the question iteself
     */
    delete_all_test_cases($parsed_post_data["primary_key"]);


} else if ($action == "list") {
    // TODO: Do table-specific validation for insert
} else {
    // This code should never execute;
    // initial_json_parse should validate that 
    // a valid action was passed 
    exit("question_middle.php: Something went catastrophically wrong");
}

/*
 * Here, we are sending a POST request to the backend server with
 * a single key-value pair sent as form data: json_string, which 
 * in turn contains sanitized and otherwise modified JSON data we 
 * received from the front
 */
$parsed_post_data["table_name"] = $table_name;
$new_post_params = array("json_string" => json_encode($parsed_post_data));
// Function takes a header arg, but not necessary here
$header = array(); 
/* 
 * The function curl_to_backend will handle configuring the
 * CURL request and checking if there is a CURL error, and,
 * if so, will return the appropriate error JSON response
 */
$backend_json_response = curl_to_backend($header, 
                                         $backend_endpoint, 
                                         http_build_query($new_post_params));
// Return response to the front end
http_response_code(200);
header('Content-Type: application/json');
exit($backend_json_response);

function has_question_been_used_in_test($question_primary_key) {
    global $BACKEND_ENDPOINTS;
    $backend_endpoint = $BACKEND_ENDPOINTS["test_question"];
    $fields = array(
        "question_id" => $question_primary_key,
    );
    $data = array(
        "action" => "list",
        "table_name" => "test_question",
        "fields" => $fields,
    );
    $post_data = array(
        "json_string" => json_encode($data),
    );
    $header = array();
    $backend_json_response = curl_to_backend($header, 
                                             $backend_endpoint, 
                                             http_build_query($post_data));
    $response = json_decode($backend_json_response, true);
    if (isset($response["items"]) && !empty($response["items"])) {
        return true;
    } else {
        return false;
    }
}

function delete_all_test_cases($question_primary_key) {
    global $BACKEND_ENDPOINTS;
    $backend_endpoint = $BACKEND_ENDPOINTS["test_case"];
    $fields = array(
        "question_id" => $question_primary_key,
    );
    $data = array(
        "action" => "list",
        "table_name" => "test_case",
        "fields" => $fields,
    );
    $post_data = array(
        "json_string" => json_encode($data),
    );
    $header = array();
    $backend_json_response = curl_to_backend($header, 
                                             $backend_endpoint, 
                                             http_build_query($post_data));
    $response = json_decode($backend_json_response, true);
    /*
     * If there are test_cases for the question, delete them
     */
    if (isset($response["items"]) && !empty($response["items"])) {
        $test_cases = $response["items"];
        $backend_endpoint = $BACKEND_ENDPOINTS["test_case"];
        $header = array();
        foreach($test_cases as $test_case) {
            $primary_key = $test_case["primary_key"];
            $data = array(
                "action" => "delete",
                "table_name" => "test_case",
                "primary_key" => $primary_key,
                "fields" => array(),
            );
            $post_data = array(
                "json_string" => json_encode($data),
            );
            $backend_json_response = curl_to_backend($header, 
                                             $backend_endpoint, 
                                             http_build_query($post_data));
        }
    } 
}
