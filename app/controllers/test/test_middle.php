<?php
/**
 * test_middle.php
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
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

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
$table_name = "test";

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

$backend_endpoint = $BACKEND_ENDPOINTS["test"];

if ($action == "insert") {
    // TODO: Do table-specific validation for insert,
} else if ($action == "edit") {
    // TODO: Do table-specific validation for edit
} else if ($action == "delete") {
    // TODO: Do table-specific validation for delete
} else if ($action == "list") {
    // TODO: Do table-specific validation for insert
} else if ($action == "list_available_for_student") {
    list_available_for_student($parsed_post_data["primary_key"]);
} else if ($action == "list_test_to_be_released") {
    list_test_to_be_released();
} else {
    // This code should never execute;
    // initial_json_parse should validate that 
    // a valid action was passed 
    exit("test_middle.php: Something went catastrophically wrong");
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

function list_test_to_be_released() {
    global $BACKEND_ENDPOINTS;

    // Get a list of tests that have been finalized,
    // but where scores have not been released
    $backend_endpoint = $BACKEND_ENDPOINTS["test"];
    $header = array();
    $fields = array(
        "finalized" => 1,
        "scores_released" => 0,
    ); 
    $data = array(
        "action" => "list",
        "table_name" => "test",
        "fields" => $fields,
    );
    $post_data = array(
        "json_string" => json_encode($data),
    );
    $backend_json_response = curl_to_backend($header, 
                                             $backend_endpoint, 
                                             http_build_query($post_data));
    $response = json_decode($backend_json_response, true);
    if (isset($response["items"]) && !empty($response["items"])) {
        $tests_pending_release = $response["items"];
    } else {
        $tests_pending_release = array();
    }

    // We want to filter out tests that don't have test_scores
    // associated with them (ie, no students have taken them).
    // We will get a list test_ids associated with test_scores
    // 
    // First, get all test_scores
    $backend_endpoint = $BACKEND_ENDPOINTS["test_score"];
    $header = array();
    $fields = array(); 
    $data = array(
        "action" => "list",
        "table_name" => "test_score",
        "fields" => $fields,
    );
    $post_data = array(
        "json_string" => json_encode($data),
    );
    $backend_json_response = curl_to_backend($header, 
                                             $backend_endpoint, 
                                             http_build_query($post_data));
    $response = json_decode($backend_json_response, true);
    if (isset($response["items"]) && !empty($response["items"])) {
        $test_scores = $response["items"];
    } else {
        $test_scores = array();
    }

    // Get the test_ids associated with the test score
    $test_ids = array();
    $i = 0;
    foreach($test_scores as $test_score) {
        if (!in_array($test_score["test_id"], $test_ids)) {
            $test_ids[$i] = $test_score["test_id"];
            $i++;
        }
    }

    // Get a list of tests pending release where we have filtered
    // out tests that don't have an associated test score
    $tests_pending_release_with_assd_test_scores = array();
    $i = 0;
    foreach ($tests_pending_release as $test) {
        if (in_array($test["primary_key"], $test_ids)) {
            $tests_pending_release_with_assd_test_scores[$i] = $test;
            $i++;
        }
    }
    $response = array(
        "action" => "list_test_to_be_released",
        "status" => "success",
        "items" => $tests_pending_release_with_assd_test_scores,
    );
    http_response_code(200);
    header('Content-Type: application/json');
    exit(json_encode($response));
}

function list_available_for_student($student_pk){
    global $BACKEND_ENDPOINTS;

    // Get list of tests already taken by student getting all
    // test scores that have the student's PK as
    // their student_id.  Then, extract the value of
    // test_id from the test_score and add it to an array
    $backend_endpoint = $BACKEND_ENDPOINTS["test_score"];
    $header = array();
    $fields = array(
        "student_id" => $student_pk,
    ); 
    $data = array(
        "action" => "list",
        "table_name" => "test_score",
        "fields" => $fields,
    );
    $post_data = array(
        "json_string" => json_encode($data),
    );
    $backend_json_response = curl_to_backend($header, 
                                             $backend_endpoint, 
                                             http_build_query($post_data));
    $response = json_decode($backend_json_response, true);
    if (isset($response["items"]) && !empty($response["items"])) {
        $test_scores = $response["items"];
    } else {
        $test_scores = array();
    }

    $tests_taken_by_student = array();
    $i = 0;
    foreach ($test_scores as $test_score) {
        $tests_taken_by_student[$i] = $test_score["test_id"];
        $i++;
    }
    // echo "tests_taken_by_student: ";
    // print_r($tests_taken_by_student);

    // Get all tests that have been finalized
    $backend_endpoint = $BACKEND_ENDPOINTS["test"];
    $header = array();
    $fields = array(
        "finalized" => "1",
    );
    $data = array(
        "action" => "list",
        "table_name" => "test",
        "fields" => $fields,
    );
    $post_data = array(
        "json_string" => json_encode($data),
    );
    $backend_json_response = curl_to_backend($header, 
                                             $backend_endpoint, 
                                             http_build_query($post_data));
    $response = json_decode($backend_json_response, true);
    if (isset($response["items"]) && !empty($response["items"])) {
        $all_finalized_tests = $response["items"];
    } else {
        $all_finalized_tests = array();
    }
    // echo "all_finalized_tests: ";
    // print_r($all_finalized_tests);

    $tests_the_student_hasnt_taken = array();
    $i = 0;
    foreach ($all_finalized_tests as $test) {
        // If the student hasn't taken the test already
        if (!in_array($test["primary_key"], $tests_taken_by_student)) {
            $tests_the_student_hasnt_taken[$i] = $test;
            $i++;
        }
    }
    // echo "tests_the_student_hasnt_taken";
    // print_r($tests_the_student_hasnt_taken);

    $response = array(
        "action" => "list_available_for_student",
        "status" => "success",
        "items" => $tests_the_student_hasnt_taken,
    );
    http_response_code(200);
    header('Content-Type: application/json');
    exit(json_encode($response));
}
