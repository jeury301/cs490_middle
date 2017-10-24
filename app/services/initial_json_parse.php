<?php

/**
 * initial_json_parse.php
 *
 * Created by Michael Anderson on September 10, 2017
 *
 * Parses the JSON received from the front and perform
 * validation general to all tables.
 *
 * If parse and validate is successful, return a PHP
 * array of the parsed JSON. Else, print a JSON error 
 * message and exit.
 */

function initial_json_parse() {
	// Check if $_POST is empty; if so, return an error
	$post_data = $_POST;
	if (empty($post_data)) {
		$error_response = array(
	            "action" => "unknown",
	            "status" => "error",
	            "user_message" => "An error has occured.",
	            "internal_message" => 'initial_json_parse.php: ' . 
	            'Empty $_POST array received from front end'
	    );
	    exit(json_encode($error_response));
	}

	// Check if 'json_string' exists; if not, return an error
	if (!isset($post_data['json_string'])) {
		$error_response = array(
	            "action" => "unknown",
	            "status" => "error",
	            "user_message" => "An error has occured.",
	            "internal_message" => 'initial_json_parse.php: Variable' . 
	            ' `json_string` missing from $_POST data',
	    );
	    exit(json_encode($error_response));
	}

	/*
	 * Must first save JSON post data as separate variable, *THEN* parse 
	 * it as PHP array.
	 * 
	 * Also, note that json_decode() will return an object, not an  
	 * associative array unless true is passed as a second parameter. 
	 */
	$raw_json_string = $post_data['json_string'];
	$parsed_post_data = json_decode($raw_json_string, true);

	// Confirm the key 'action' exists in the parsed array
	if (!isset($parsed_post_data['action'])) {
		$error_response = array(
	            "action" => "unknown",
	            "status" => "error",
	            "user_message" => "An error has occured.",
	            "internal_message" => 'initial_json_parse.php:Variable ' . 
	            '"action" missing from parsed JSON data',
	    );
	    exit(json_encode($error_response));
	} else {
		if ($parsed_post_data['action'] != 'login' &&
			$parsed_post_data['action'] != 'insert' &&
			$parsed_post_data['action'] != 'edit' &&
			$parsed_post_data['action'] != 'delete' &&
			$parsed_post_data['action'] != 'list' &&
			$parsed_post_data['action'] != 'list_available_for_student' &&
			$parsed_post_data['action'] != 'list_test_to_be_released') {
				$error_response = array(
			            "action" => "unknown",
			            "status" => "error",
			            "user_message" => "An error has occured.",
			            "internal_message" => 'initial_json_parse.php: ' . 
			            'Invalid value for key `action` in JSON data. Action "' . 
			            $parsed_post_data['action'] . '" is not valid.',
			    );
	    		exit(json_encode($error_response));
		} else {
			return $parsed_post_data;
		}
	}
}