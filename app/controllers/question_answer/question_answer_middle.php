<?php
/**
 * question_answer_middle.php
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
 * actions SPECIFIC TO THE QUESTION_ANSWER TABLE:
 *
 * - When action is 'insert': 
 *    - Confirm the required fields are found in json_string
 *    - Auto-grade the quesiton_answer before submitting to
 *      the database:
 *      - Fetch related question from database
 *      - Fetch related test_cases from database
 *      - For each test case, compose, save, and execute a
 *        Python script based on question_answer's answer_
 *        text field and test_case's parameters
 *      - Compare output of Python script to test_case target
 *        output
 *      - Update question_answer's grade and note fields 
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
$table_name = "question_answer";

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

$backend_endpoint = $BACKEND_ENDPOINTS["question_answer"];

if ($action == "insert") {
    /*
     *  AUTOGRADE QUESTION_ANSWER BEFORE INSERT
     */

    // Make sure the fields data we received includes  "question_id", 
    // "test_id", "student_id", and "answer_text" and that they 
    // are not null.  If not, the request is malformed and 
    // we should return an error

    if (!isset($fields["question_id"]) || 
        !isset($fields["test_id"]) || 
        !isset($fields["student_id"]) || 
        !isset($fields["answer_text"]) ) {
        $error = array(
            "action" => "insert",
            "status" => "error",
            "user_message" => "Could not insert question_answer.",
            "internal_message" => "question_answer_middle.php: Malformed " . 
            "request when trying to insert.  Missing key 'question_id', " .
            "'test_id', 'student_id', or 'answer_text'",
        );
        http_response_code(400);
        header('Content-Type: application/json');
        exit(json_encode($error));
    }

    $question_answer = $fields;
    // Get the related question
    $question = get_question_for_question_answer($question_answer);
    // Get the related test_cases
    $test_cases = get_test_cases_for_question_answer($question_answer);
    // Grade the question_answer before inserting it
    $question_answer = grade_question_answer($question_answer, $question, $test_cases);
    // Update the parsed post data appropriately before making
    // CURL call to back end
    $parsed_post_data["fields"] = $question_answer;

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
    exit("question_answer_middle.php: Something went catastrophically wrong");
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
 * Methods for getting DB records necessary to grade a question_answer
 */

/* Get the point value associated with a given test question */
function get_test_question_point_value($question_answer) {
    global $BACKEND_ENDPOINTS;
    $backend_endpoint = $BACKEND_ENDPOINTS["test_question"];
    $header = array();
    $fields = array(
        "question_id" => $question_answer["question_id"],
        "test_id" => $question_answer["test_id"]
    );
    // echo "Value of question_pk: $question_pk...";
    $data = array(
        "action" => "list",
        "table_name" => "test_question",
        "fields" => $fields,
    );
    $post_data = array(
        "json_string" => json_encode($data),
    );
    $backend_json_response = curl_to_backend($header, 
                                             $backend_endpoint, 
                                             http_build_query($post_data));
    $response = json_decode($backend_json_response, true);
    if (isset($response["items"][0]["point_value"]) && 
        !empty($response["items"][0]["point_value"])) {
        return $response["items"][0]["point_value"];
    } else {
        // echo "In else clause...";
        // echo "Value of backend_json_response: $backend_json_response...";
        return false;
    }

}

function get_question_for_question_answer($question_answer){
    // echo "in get_question_for_question_answer()...";
    global $BACKEND_ENDPOINTS;
    $question_pk = $question_answer["question_id"];
    $backend_endpoint = $BACKEND_ENDPOINTS["question"];
    $header = array();
    $fields = array(
        "primary_key" => $question_pk,
    );
    // echo "Value of question_pk: $question_pk...";
    $data = array(
        "action" => "list",
        "table_name" => "question",
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
        return $response["items"][0];
    } else {
        // echo "In else clause...";
        // echo "Value of backend_json_response: $backend_json_response...";
        return false;
    }
}


function get_test_cases_for_question_answer($question_answer){
    // echo "in get_test_cases_for_question_answer()...";
    global $BACKEND_ENDPOINTS;
    $question_pk = $question_answer["question_id"];
    $backend_endpoint = $BACKEND_ENDPOINTS["test_case"];
    $header = array();
    $fields = array(
        "question_id" => $question_pk,
    );
    // echo "Value of question_pk: $question_pk...";
    $data = array(
        "action" => "list",
        "table_name" => "test_case",
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
        return $response["items"];
    } else {
        // echo "In else clause...";
        // echo "Value of backend_json_response: $backend_json_response...";
        return false;
    }
}


/* 
 * Functions to create and execute Python script
 */

function construct_python_script($answer_text, $question) {
    $boilerplate = "{{function_definition}}\r\n\r\nprint({{func_name}}({{param_names}}))\r\n\r\n";
    $script = str_replace("{{function_definition}}", $answer_text, $boilerplate);
    $script = str_replace("{{func_name}}", $question["func_name"], $script);
    // $script = str_replace("{{param_names}}", $question["param_names"], $script);
    return $script;
}

function insert_params($script, $param_names) {
    $full_script = str_replace("{{param_names}}", $param_names, $script);
    return $full_script;
}


/*
 * Function to grade a question_answer
 */

function grade_question_answer($question_answer, $question, $test_cases) {
    // Create an array that will store information about how
    // the question_answer did on each test_case
    $results = array(
        "testCaseCount" => 0,
        "passedTestCases" => 0,
        "comments" => array(),
        "testCaseResults" => array(),
        "doesFunctionNameMatch" => false,
        "doParametersMatch" => false,
        "questionScore" => 0,
        "professorComments" => "",
    );

    // Generate a random string of 10 letters to serve as the file name.
    // This is necessary so that different threads don't try to read
    // and write to the same file
    $hashstring = substr(md5(microtime()),rand(0,26),5);

    // Construct string that will serve as base python script
    $base_script = construct_python_script($question_answer["answer_text"], $question);
    // echo json_encode($base_script);
    // echo "<br/><br/>";

    // Check if function name matches

    // Get beginning of function declaration from answer_text
    $func_dec = explode("(", $base_script, 2)[0];
    // echo "<br/><br/><b> \$func_dec:</b><br/>";
    // echo $func_dec . "<br/>";

    // Check if answer's function declaration matches the one specified in question
    $correct_func_dec = "def " . $question["func_name"];
    // echo "<br/><br/><b> \$correct_func_dec:</b><br/>";
    // echo $correct_func_dec . "<br/>";

    if ($func_dec == $correct_func_dec) {
        // If correct, update $results["doesFunctionNameMatch"]
        // echo "<br/><b>func_dec MATCHES correct_func_dec</b><br/>";
        $results["doesFunctionNameMatch"] = true;
    } else {
        // Update answer_text to have the correct function declaration
        // ...but points will be docked later
        // echo "<br/><b>func_dec does not match correct_func_dec</b><br/>";
        $base_script = str_replace($func_dec, $correct_func_dec, $base_script);

        // And instances of the function name throughout the script,
        // in case of recursion
        $func_name = substr($func_dec, 4);  // Strip "def ", thus start at 4
        $correct_func_name = substr($correct_func_dec, 4);
        $base_script = str_replace($func_name . '(', $correct_func_name . '(', $base_script);
    }

    /*
     * Check if the answer's parameters match those specified in the question
     * $correct_params. Also, strip out spaces before comparing
     */
    $answer_params = explode("(", $base_script, 2)[1];
    $answer_params = explode(")", $answer_params, 2)[0];
    $answer_params = str_replace(' ', '', $answer_params);
    $correct_params = str_replace(' ', '', $question["param_names"]);
    // echo "<br/><b>Value of \$answer_params:</b> $answer_params<br/>";
    // echo "<br/><b>Value of \$correct_params:</b> $correct_params<br/>";

    if ($answer_params == $correct_params) {
        // If correct, update $results["doParametersMatch"]
        // echo "<br/><b>answer_params MATCHES correct_params</b><br/>";
        $results["doParametersMatch"] = true;
    } else {
        // It's not actually necessary to modify the script; it will work
        // regardless of how the parameters in the function declaration
        // are named.  But we will dock a point later.
        // echo "<br/><b>answer_params DOES NOT MATCH correct_params</b><br/>";
    }

    // Implicitly create and open file where python script will be saved
    $my_file = "$hashstring.py";
    $handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);

    
    /* Get the point value of the given question 
     * - First, see if there is a specific point_value associated
     *   with the test_question record; if not, check what the
     *   default point value of the question is; if both of those
     *   fail, use a point value of 10
     */
    $test_score_point_value = get_test_question_point_value($question_answer);
    if ($test_score_point_value) {
        $question_point_value = $test_score_point_value;
    } else if (isset($question["point_value"])) {
        $question_point_value = $question["point_value"];
    } else {
        $question_point_value = 10;
    }
    // Determine how many points each test case is worth, rounded to 0.1
    $points_per_test_case = $question_point_value / (float) count($test_cases);
    $points_per_test_case = round($points_per_test_case, 1);

    // For each test case, insert the appropriate parameters into the script
    for ($i=0; $i<count($test_cases); $i++) {
        $complete_script = insert_params($base_script, $test_cases[$i]["input"]);
        // echo "=========================<br/>";
        // echo "<br/><br/> \$test_case[$i]:<br/>";
        // print_r($test_cases[$i]);
        // echo "<br/><br/> \$complete_script:<br/>";
        // echo json_encode($complete_script);
        $data = $complete_script;
        fwrite($handle, $data);
        // echo "<br/>";

        $cmd = "timeout 1 python $my_file 2>&1";
        $output = exec($cmd);
        // echo "<br/><br/> Output: <br/><br/>";
        // echo $output;

        // We need to check if the expected output specified by
        // the test_case has quotation marks as its first and
        // last characters.  Because the actual output that
        // the script prints will not have quotation marks, we
        // must remove the quotation marks from the expected
        // output
        $expected_output = $test_cases[$i]["output"];
        // Remove opening quotation mark if necessary
        if ($expected_output[0]== "\"") {
            $expected_output = substr($expected_output, 1);
        }
        // Remove ending quotation mark if necessary
        if ($expected_output[strlen($expected_output)-1] == "\"") {
            $expected_output = substr($expected_output, 0, -1);
        }

        // TODO:
        // If the expected output is an array, get rid of whitespace

        if ($expected_output == $output) {
            // echo "<br/>The output $output matched the expected output " . 
            $expected_output . "<br/><br/>";
            $results["testCaseCount"]++;
            $results["passedTestCases"]++;
            $results["comments"][$i] .= "Test case $i PASSED; for input '" .
                $test_cases[$i]["input"] . "', expected '" . $expected_output . 
                "' and received '$output'.";
            $passedLastTestCase = 1;
            $pointsEarnedOnThisTestCase = $points_per_test_case;
        } else {
            // echo "<br/>The output $output did not match the expected output " . 
            $expected_output . "<br/><br/>";
            $results["testCaseCount"]++;
            $results["comments"][$i] .= "Test case $i FAILED; for input '" .
                $test_cases[$i]["input"] . "', expected '" . $expected_output . 
                "' but received '$output'. $points_per_test_case points" .
                " deducted out of a possible total of $question_point_value.";
            $passedLastTestCase = 0;
            $pointsEarnedOnThisTestCase = 0;
        }

        /* 
         * Populate testCaseResults array with testCaseReult
         * arrays for easy display of results in table form 
         */

        // Truncate output strings if longer than 15 characters
        $truncated_output = strlen($output) > 15 ? substr($output,0,15)."..." : $output;
        $truncated_expected_output = strlen($expected_output) > 15 ? 
            substr($expected_output,0,15)."..." : $expected_output;
        $expected_input_output = $question["func_name"] . "(" . $test_cases[$i]["input"] . 
            ")" . " -> " . $truncated_expected_output;
        $testCaseResult = array(
            "expected" => $expected_input_output,
            "actual" => $truncated_output,
            "didItPass" => $passedLastTestCase,
            "pointsEarned" => $pointsEarnedOnThisTestCase,
        );
        $results["testCaseResults"][$i] = $testCaseResult;

    }
    fclose($handle);
    exec("rm $hashstring.py");

    /*
     * Grading the question answer:
     * - Questions will be graded on a variable-point scale
     * - Base grade is (passedTestCases/testCaseCount) * question_point_value
     *     - Floating-point division by casting testCaseCount as float
     *     - Result rounded to the nearest integer using round()
     * - If doesFunctionNameMatch is false, deduct 1 point
     * - If doParametersMatch is false, deduct 1 point
     * - Negative scores are not permitted
     */
    
    $test_case_ratio_float = $results["passedTestCases"] / 
        (float) $results["testCaseCount"];
    $grade = round($test_case_ratio_float * $question_point_value);
    if (!$results["doesFunctionNameMatch"]) {
        if ($grade - 1 > 0) {
            $grade -= 1;
            $results["comments"][count($results["comments"])] = 
                "Function name does not match specified function name. " . 
                "One point deducted.";
        } else {
            $grade = 0;
            $results["comments"][count($results["comments"])] = 
                "Function name does not match specified function name.";
        }
    }
    if (!$results["doParametersMatch"]) {
        if ($grade - 1 > 0) {
            $grade -= 1;
            $results["comments"][count($results["comments"])] = 
                "The names of the function's parameter(s) do not match " . 
                "the specified parameter name(s). One point deducted.";
        } else {
            $grade = 0;
            $results["comments"][count($results["comments"])] = 
                "The names of the function's parameter(s) do not match " . 
                "the specified parameter name(s).";
        }
    }
    $results["comments"][count($results["comments"])] = 
    "A total of $grade out of $question_point_value points " . 
    "were earned on this question.";

    $results["questionScore"] = $grade;

    $question_answer["point_value"] = $question_point_value;
    $question_answer["grade"] = $results["questionScore"];
    $question_answer["notes"] = json_encode($results);
    $question_answer["professor_notes"] = "";
    return $question_answer;
}
