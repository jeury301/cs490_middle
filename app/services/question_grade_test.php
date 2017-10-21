<?php

// Debug mode if uncommented
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);


// Get data
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

grade_question_answer($question_answer, $question, $test_cases);

function grade_question_answer($question_answer, $question, $test_cases) {
    // Construct string that will serve as base python script
    $base_script = construct_python_script($question_answer["answer_text"], $question);
    echo json_encode($base_script);
    echo "<br/><br/>";

    // Implicitly create and open file where python script will be saved
    $my_file = 'tmp.py';
    $handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);

    // For each test case, insert the appropriate parameters into the script
    for ($i=0; $i<count($test_cases); $i++) {
        $complete_script = insert_params($base_script, $test_cases[$i]["input"]);
        echo "<br/><br/> \$test_case[$i]:<br/>";
        print_r($test_cases[$i]);
        echo "<br/><br/> \$complete_script:<br/>";
        echo json_encode($complete_script);
        $data = $complete_script;
        fwrite($handle, $data);
        echo "<br/><br/>";

        $cmd = "python3 $my_file";
        $output = exec($cmd);
        echo "<br/><br/> Output: <br/><br/>";
        echo $output;

        if ($test_cases[$i]["output"] == $output) {
            echo "<br/>The output $output matched the expected output " . 
            $test_cases[$i]["output"] . "<br/><br/>";
        } else {
            echo "<br/>The output $output did not match the expected output " . 
            $test_cases[$i]["output"] . "<br/><br/>";
        }

    }
    fclose($handle);
    exec("rm tmp.py");
}

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

/* 
 * Functions to create Python script
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

