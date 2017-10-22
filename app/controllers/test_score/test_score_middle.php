<?php
/**
 * test_score_middle.php
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
$table_name = "test_score";

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

$backend_endpoint = $BACKEND_ENDPOINTS["test_score"];

if ($action == "insert") {
    /*
     * CALCULATE THE GRADE BASED ON STUDENT'S QUESTION_ANSWERS
     */

    // Make sure the fields data we received includes student_id, 
    // student_name, test_id, and test_name. If not, the request 
    // is malformed and we should return an error
    if (!isset($fields["student_id"]) || 
        !isset($fields["student_name"]) || 
        !isset($fields["test_id"]) || 
        !isset($fields["test_name"]) ) {
        $error = array(
            "action" => "insert",
            "status" => "error",
            "user_message" => "Could not insert test_score.",
            "internal_message" => "test_score_middle.php: Malformed " . 
            "request when trying to insert.  Missing key 'student_id', " .
            "'student_name', 'test_id', or 'test_name'",
        );
        http_response_code(400);
        header('Content-Type: application/json');
        exit(json_encode($error));
    }
    // scores_released is always false when a test_score is created
    $fields["scores_released"] = 0;

    $test_score = $fields;
    //print_r($test_score);

    // Get the related question answers for the student and the exam
    $question_answers = get_question_answers($test_score);
    // echo "Question answers: ";
    // print_r($question_answers);

    // Grade based on the question answers and update test_score
    $test_score = grade_test($test_score, $question_answers);

    // Update the parsed post data appropriately before making
    // CURL call to back end
    $parsed_post_data["fields"] = $test_score;


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
    exit("test_score_middle.php: Something went catastrophically wrong");
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


/*
 * Method for getting DB records necessary to grade test
 */

function get_question_answers($test_score) {
    // TODO
    global $BACKEND_ENDPOINTS;
    $student_id = $test_score["student_id"];
    // echo "value of student_id: $student_id";
    $test_id = $test_score["test_id"];
    // echo "value of test_id: $test_id";
    $backend_endpoint = $BACKEND_ENDPOINTS["question_answer"];
    $header = array();
    $fields = array(
        "student_id" => $student_id,
        "test_id" => $test_id,
    );
    $data = array(
        "action" => "list",
        "table_name" => "question_answer",
        "fields" => $fields,
    );
    $post_data = array(
        "json_string" => json_encode($data),
    );
    // echo $post_data["json_string"];
    $backend_json_response = curl_to_backend($header, 
                                             $backend_endpoint, 
                                             http_build_query($post_data));
    // echo "<br/>Backend json response: $backend_json_response <br/>";
    $response = json_decode($backend_json_response, true);
    if (isset($response["items"]) && !empty($response["items"])) {
        return $response["items"];
    } else {
        // echo "In else clause...";
        // echo "Value of backend_json_response: $backend_json_response...";
        return false;
    }
}


/*
 * Function to grade a test
 */

function grade_test($test_score, $question_answers) {
    // Calculate the maximum number of points possible
    $max_points = 10 * count($question_answers);
    if ($max_points == 0) {
        // Avoid divide by zero
        $max_points = 1;
        // Though this should never happen...
    }

    // Calculate points actually earned
    $sum_points = 0;
    foreach ($question_answers as $answer) {
        $sum_points += (int)$answer["grade"];
    }

    /*
     * Grading the test:
     * - Each question_answer has been graded on a ten-point scale
     * - We have calculated the sum of question_answer scores ('SUM')
     *   and the maximum numer of possible points ('MAX')
     * - Grade is (SUM/MAX) * 100
     *     - Floating-point division by casting 'MAX' as float
     *     - Result then rounded to the nearest integer using round()
     * - Min grade is 0, max grade is 100
     */
    $grade = round(($sum_points/(float)$max_points) * 100);

    $test_score["grade"] = $grade;
    return $test_score;

}





