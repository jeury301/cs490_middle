<?php
/**
 * student_middle.php
 *
 * Created by Michael Anderson on October 4, 2017
 *
 * Parses the request received from the front-end and
 * performs table-specifc validation on it.
 * If validation fails, the script will return a 
 * formatted JSON error response directly to
 * the front end.  If validation is successful, the 
 * front-end request will be reformatted and passed
 * to the back end, and the back-end response will
 * in turn be returned to the front as JSON.
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
$table_name = "student";

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

$backend_endpoint = $BACKEND_ENDPOINTS["student"];

if ($action == "insert") {
    // TODO: Do table-specific validation for insert,
} else if ($action == "edit") {
    // TODO: Do table-specific validation for edit
} else if ($action == "delete") {
    // TODO: Do table-specific validation for delete
} else if ($action == "list") {
    // TODO: Do table-specific validation for insert
} else {
    // This code should never execute;
    // initial_json_parse should validate that 
    // a valid action was passed 
    exit("student_middle.php: Something went catastrophically wrong");
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
 * if so, returning the appropriate error JSON response
 */
$backend_json_response = curl_to_backend($header, 
                                         $backend_endpoint, 
                                         http_build_query($new_post_params));
// Return response to the front end
http_response_code(200);
header('Content-Type: application/json');
exit($backend_json_response);
