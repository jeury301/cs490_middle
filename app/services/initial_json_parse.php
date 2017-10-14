<?php

/**
 * initial_json_parse.php
 *
 * Created by Michael Anderson on September 10, 2017
 *
 * Parses the JSON received from the front and confirm
 * that it contains the 'action' key and a valid 'action'
 * value. 
 *
 * If parse and validate is successful, return a PHP
 * array of the parsed JSON. Else, return a PHP array
 * corresponding to the error format.
 */

function initial_json_parse() {
	// Check if $_POST is empty; if so, return an error
	if (empty($_POST)) {
		$error_response = array(
	            "action" => "unknown",
	            "status" => "error",
	            "user_message" => "An error has occured.",
	            "internal_message" => 'Empty $_POST array received from front end'
	    );
	    return $error_response;
	}

	// Check if 'json_string' exists; if not, return an error
	if (!isset($_POST['json_string'])) {
		$error_response = array(
	            "action" => "unknown",
	            "status" => "error",
	            "user_message" => "An error has occured.",
	            "internal_message" => 'Variable `json_string` missing from $_POST data'
	    );
	    return $error_response;
	}

	/*
	 * Must first save JSON post data as separate variable, *THEN* parse 
	 * it as PHP array.
	 * 
	 * Also, note that json_decode() will return an object, not an  
	 * associative array unless true is passed as a second parameter. 
	 */
	$raw_json_string = $_POST['json_string'];
	$parsed_post_data = json_decode($raw_json_string, true);

	// Confirm the key 'action' exists in the parsed array
	if (!isset($parsed_post_data['action'])) {
		$error_response = array(
	            "action" => "unknown",
	            "status" => "error",
	            "user_message" => "An error has occured.",
	            "internal_message" => 'Variable "action" missing from parsed JSON data'
	    );
	    return $error_response;
	} else {
		if ($parsed_post_data['action'] != 'login' &&
			$parsed_post_data['action'] != 'insert' &&
			$parsed_post_data['action'] != 'edit' &&
			$parsed_post_data['action'] != 'delete' &&
			$parsed_post_data['action'] != 'list') {
				$error_response = array(
			            "action" => "unknown",
			            "status" => "error",
			            "user_message" => "An error has occured.",
			            "internal_message" => 'Invalid value for key `action` in JSON data'
			    );
	    		return $error_response;
		} else {
			return $parsed_post_data;
		}
	}
}