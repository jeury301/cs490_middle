<?php

/**
 * test_score_middle.php
 *
 * Created by Michael Anderson on October 4, 2017
 *
 * @TODO: Add description 
 */

require '../../services/initial_json_parse.php';
require '../../services/curl_functions.php';

// Preliminarily validate and parse JSON received as POST data
$parsed_post_data = initial_json_parse();

// Check if 'error' for 'status' in $parsed_post_data
if ($parsed_post_data['status'] == 'error') {
    // Bad request by front end
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode($parsed_post_data));
} 

$action = $parsed_post_data['action'];

/*
 * Here, we are sending a POST request to the backend server with
 * key-value pairs corresponding to the JSON data we received
 */
$post_params = $parsed_post_data;
$header = array(); // Function takes a header arg, but not necessary here
$backend_json_response = curl_to_backend($header, 
										 $backend_endpoint, 
										 http_build_query($post_params));
$parsed_backend_response = json_decode($backend_json_response, true);

http_response_code(200);
header('Content-Type: application/json');
exit(json_encode($response));
