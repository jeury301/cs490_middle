<?php

// THIS FILE IS JUST A SANDBOX.  NOT TO BE USED IN PRODUCTION

require 'curl_functions.php';
$BACKEND_ENDPOINTS = include '../backend_endpoints.php';

// Debug mode if uncommented
// ini_set('display_startup_errors', 1);
// ini_set('display_errors', 1);
// error_reporting(-1);

// Get data
/*
$tmp = json_decode(get_question_db_response(), true);
$question = $tmp["items"][0];
echo "question: <br/>";
print_r($question);
echo "<br/><br/>";


$tmp = json_decode(get_test_cases_db_response(), true);
$test_cases = $tmp["items"];
echo "test_cases: <br/>";
print_r($test_cases);
echo "<br/><br/>";

$tmp = json_decode(get_question_answer_db_response(), true);
$question_answer = $tmp["items"][0];
echo "question_answer: <br/>";
print_r($question_answer);
echo "<br/><br/>";
*/

/*
$tmp_json = get_alt_question_answer_db_response();
$parsed_json = json_decode($tmp_json, true);
$alt_question_answer = $parsed_json["items"][0];
echo "<b>ALT QUESTION ANSWER: </b>";
print_r($alt_question_answer);
echo "<br/><br/>";
*/

/*
 * In the actual program, we will not be fetching the
 * quesiton answer from the database - we will be grading
 * the question answer before it is ever written to the databse
 */

$alt_question_answer = get_alt_alt_alt_question_answer_db_response("123");
echo "<b>QUESTION ANSWER last revised 10-22 at 11:26 AM: </b>"; 
print_r($question_answer);
echo "<br/><br/>";

// Get question answers and test cases from DB
$alt_question = get_question_for_question_answer($alt_question_answer);
echo "<b>ALT QUESTION: </b>";
print_r($alt_question);
echo "<br/><br/>";

$alt_test_cases = get_test_cases_for_question_answer($alt_question_answer);
echo "<b>ALT TEST CASES: </b>";
print_r($alt_test_cases);
echo "<br/><br/>";




//grade_question_answer($question_answer, $question, $test_cases);

$graded_question_answer = grade_question_answer($alt_question_answer, $alt_question, $alt_test_cases);
echo "<b>Graded Question Answer: </b>";
print_r($graded_question_answer);
echo "<br/><br/>";

/*
 * Mock functions to replicate database
 */

function get_question_db_response() {
    $json = '{
                "action": "list",
                "status": "success",
                "items": [
                    {
                        "primary_key": "5",
                        "question_text": "Write a function named factorial that takes an integer parameter name called n that returns n! (n factorial).  You can assume that n will be nonnegative.",
                        "func_name": "factorial",
                        "param_names": "n"
                    }
                ]
              }';
    return $json;
}

function get_test_cases_db_response() {
    // TODO
    $json = '{
                    "action": "list",
                    "status": "success",
                    "items": [
                        {
                            "primary_key": "44",
                            "question_id": "5",
                            "input": "0",
                            "output": "1"
                        },
                        {
                            "primary_key": "45",
                            "question_id": "5",
                            "input": "1",
                            "output": "1"
                        },
                        {
                            "primary_key": "46",
                            "question_id": "5",
                            "input": "2",
                            "output": "2"
                        },
                        {
                            "primary_key": "47",
                            "question_id": "5",
                            "input": "3",
                            "output": "6"
                        },
                        {
                            "primary_key": "48",
                            "question_id": "5",
                            "input": "4",
                            "output": "24"
                        }
                    ]
                }';
    return $json;
}

function get_question_answer_db_response() {
    $json = '{
    "action": "list",
    "status": "success",
    "items": [
        {
            "primary_key": "42",
            "question_id": "5",
            "test_id": "37",
            "student_id": "6",
            "answer_text": "def factorial(n):\r\n\tif n < 0:\r\n\t\traise ValueError(\"n must be greater than 0\")\r\n\telif n == 0:\r\n\t\treturn 1\r\n\telse:\r\n\t\treturn n * factorial(n-1)",
            "grade": "",
            "notes": ""
        }
    ]
}';
    return $json;
}

function get_alt_question_answer_db_response() {
    $json = '{
    "action": "list",
    "status": "success",
    "items": [
        {
            "primary_key": "42",
            "question_id": "209",
            "test_id": "37",
            "student_id": "6",
            "answer_text": "def factorial(n):\r\n\tif n < 0:\r\n\t\traise ValueError(\"n must be greater than 0\")\r\n\telif n == 0:\r\n\t\treturn 1\r\n\telse:\r\n\t\treturn n * factorial(n-1)",
            "grade": "",
            "notes": ""
        }
    ]
}';
    return $json;
}
function get_alt_alt_question_answer_db_response() {
    $json = '{
    "action": "list",
    "status": "success",
    "items": [
        {
            "primary_key": "307",
            "question_id": "224",
            "test_id": "116",
            "student_id": "6",
            "answer_text": "def factorial(n):\r\n\tif n < 0:\r\n\t\traise ValueError(\"n must be greater than 0\")\r\n\telif n == 0:\r\n\t\treturn 1\r\n\telse:\r\n\t\treturn n * factorial(n-1)",
            "grade": "",
            "notes": ""
        }
    ]
}';
    $parsed_json = json_decode($json,true);
    return $parsed_json["items"]["0"];
}

function get_alt_alt_alt_question_answer_db_response() {
    $json = '{
    "action": "list",
    "status": "success",
    "items": [
        {
            "primary_key": "308",
            "question_id": "225",
            "test_id": "116",
            "student_id": "6",
            "answer_text": "def concatYoloX(inputz):\r\n\tif inputz == \"sandwich\":\r\n\t\treturn \"I LOVE SANDWICHES\"\r\n\telse:\r\n\t\treturn inputz + \"Yolo\"",
            "grade": "",
            "notes": ""
        }
    ]
}';
    $parsed_json = json_decode($json,true);
    return $parsed_json["items"]["0"];
}

/*
 * Functions to get records from database:
 */

function get_question_answer_for_pk($pk){
    echo "in get_question_for_question_answer()...";
    global $BACKEND_ENDPOINTS;
    $question_answer_pk = $pk;
    $backend_endpoint = $BACKEND_ENDPOINTS["question_answer"];
    $header = array();
    $fields = array(
        "primary_key" => $question_answer_pk,
    );
    echo "Value of question_pk: $question_answer_pk...";
    $data = array(
        "action" => "list",
        "table_name" => "question_answer",
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
        echo "In else clause...";
        echo "Value of backend_json_response: $backend_json_response...";
        return false;
    }
}

function get_question_for_question_answer($question_answer){
    echo "in get_question_for_question_answer()...";
    global $BACKEND_ENDPOINTS;
    $question_pk = $question_answer["question_id"];
    $backend_endpoint = $BACKEND_ENDPOINTS["question"];
    $header = array();
    $fields = array(
        "primary_key" => $question_pk,
    );
    echo "Value of question_pk: $question_pk...";
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
        echo "In else clause...";
        echo "Value of backend_json_response: $backend_json_response...";
        return false;
    }
}


function get_test_cases_for_question_answer($question_answer){
    echo "in get_test_cases_for_question_answer()...";
    global $BACKEND_ENDPOINTS;
    $question_pk = $question_answer["question_id"];
    $backend_endpoint = $BACKEND_ENDPOINTS["test_case"];
    $header = array();
    $fields = array(
        "question_id" => $question_pk,
    );
    echo "Value of question_pk: $question_pk...";
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
        echo "In else clause...";
        echo "Value of backend_json_response: $backend_json_response...";
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

function grade_question_answer($question_answer, $question, $test_cases) {
    // Create an array that will store information about how
    // the question_answer did on each test_case
    $results = array(
        "testCaseCount" => 0,
        "passedTestCases" => 0,
        "comments" => array(),
        "doesFunctionNameMatch" => false,
        "doParametersMatch" => false,
        "questionScore" => 0,
        "professorComments" => "",
    );

    // Construct string that will serve as base python script
    $base_script = construct_python_script($question_answer["answer_text"], $question);
    echo json_encode($base_script);
    echo "<br/><br/>";

    // Check if function name matches

    // Get beginning of function declaration from answer_text
    $func_dec = explode("(", $base_script, 2)[0];
    echo "<br/><br/><b> \$func_dec:</b><br/>";
    echo $func_dec . "<br/>";

    // Check if answer's function declaration matches the one specified in question
    $correct_func_dec = "def " . $question["func_name"];
    echo "<br/><br/><b> \$correct_func_dec:</b><br/>";
    echo $correct_func_dec . "<br/>";

    if ($func_dec == $correct_func_dec) {
        // If correct, update $results["doesFunctionNameMatch"]
        echo "<br/><b>func_dec MATCHES correct_func_dec</b><br/>";
        $results["doesFunctionNameMatch"] = true;
    } else {
        // Update answer_text to have the correct function declaration
        // ...but points will be docked later
        echo "<br/><b>func_dec does not match correct_func_dec</b><br/>";
        $base_script = str_replace($func_dec, $correct_func_dec, $base_script);
    }

    /*
     * Check if the answer's parameters match those specified in the question
     * $correct_params. Also, strip out spaces before comparing
     */
    $answer_params = explode("(", $base_script, 2)[1];
    $answer_params = explode(")", $answer_params, 2)[0];
    $answer_params = str_replace(' ', '', $answer_params);
    $correct_params = str_replace(' ', '', $question["param_names"]);
    echo "<br/><b>Value of \$answer_params:</b> $answer_params<br/>";
    echo "<br/><b>Value of \$correct_params:</b> $correct_params<br/>";

    if ($answer_params == $correct_params) {
        // If correct, update $results["doParametersMatch"]
        echo "<br/><b>answer_params MATCHES correct_params</b><br/>";
        $results["doParametersMatch"] = true;
    } else {
        // It's not actually necessary to modify the script; it will work
        // regardless of how the parameters in the function declaration
        // are named.  But we will dock a point later.
        echo "<br/><b>answer_params DOES NOT MATCH correct_params</b><br/>";
    }

    // Implicitly create and open file where python script will be saved
    $my_file = 'tmp.py';
    $handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);

    // For each test case, insert the appropriate parameters into the script
    for ($i=0; $i<count($test_cases); $i++) {
        $complete_script = insert_params($base_script, $test_cases[$i]["input"]);
        echo "=========================<br/>";
        echo "<br/><br/> \$test_case[$i]:<br/>";
        print_r($test_cases[$i]);
        echo "<br/><br/> \$complete_script:<br/>";
        echo json_encode($complete_script);
        $data = $complete_script;
        fwrite($handle, $data);
        echo "<br/>";

        $cmd = "python3 $my_file";
        $output = exec($cmd);
        echo "<br/><br/> Output: <br/><br/>";
        echo $output;

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

        if ($expected_output == $output) {
            echo "<br/>The output $output matched the expected output " . 
            $expected_output . "<br/><br/>";
            $results["testCaseCount"]++;
            $results["passedTestCases"]++;
            $results["comments"][$i] .= "Test case $i PASSED; for input " .
                $test_cases[$i]["input"] . ", expected " . $expected_output . 
                " and received $output.";
        } else {
            echo "<br/>The output $output did not match the expected output " . 
            $expected_output . "<br/><br/>";
            $results["testCaseCount"]++;
            $results["comments"][$i] .= "Test case $i FAILED; for input " .
                $test_cases[$i]["input"] . ", expected " . $expected_output . 
                " but received $output.";
        }

    }
    fclose($handle);
    exec("rm tmp.py");

    /*
     * Grading the question answer:
     * - Questions will be graded on a 10-point scale
     * - Base grade is (passedTestCases/testCaseCount) * 10
     *     - Floating-point division by casting testCaseCount as float
     *     - Result rounded to the nearest integer using round()
     * - If doesFunctionNameMatch is false, deduct 1 point
     * - If doParametersMatch is false, deduct 1 point
     * - Negative scores are not permitted
     */

    $test_case_ratio_float = $results["passedTestCases"] / 
        (float) $results["testCaseCount"];
    $grade = round($test_case_ratio_float * 10);
    if (!$results["doesFunctionNameMatch"]) {
        if ($grade - 1 > 0) {
            $grade -= 1;
            $results["comments"][count($results["comments"])] = 
                "Function name does not match specified function name. " . 
                "One point deducted.";
        } else {
            $grade = 0;
        }
    }
    if (!$results["doParametersMatch"]) {
        if ($grade - 1 > 0) {
            $grade -= 1;
            $results["comments"][count($results["comments"])] = 
                "The names of the function's parameters does not match " . 
                " specified function name. One point deducted.";
        } else {
            $grade = 0;
        }
    }

    $results["questionScore"] = $grade;

    $question_answer["grade"] = $results["questionScore"];
    $question_answer["notes"] = json_encode($results);
    return $question_answer;

}
