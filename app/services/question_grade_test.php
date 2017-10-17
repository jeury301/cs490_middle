<?php

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
                        "question_text": "Write a function named hello that takes a string parameter name called hello that returns the string \'Hello \' concatenated with the value of the parameter name",
                        "func_name": "hello",
                        "param_names": "name"
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
                            "input": "Michael",
                            "output": "Hello Michael"
                        },
                        {
                            "primary_key": "45",
                            "question_id": "5",
                            "input": "Jeury",
                            "output": "Hello Jeury"
                        },
                        {
                            "primary_key": "46",
                            "question_id": "5",
                            "input": "Mickey",
                            "output": "Hello Mickey"
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
            "answer_text": "def hello(name):\r\n\treturn \"Hello \" + name",
            "grade": "",
            "notes": ""
        }
    ]
}';
    return $json;
}

function construct_python_script($answer_text) {
    // TODO
    $json = '';
    return $json;
}

