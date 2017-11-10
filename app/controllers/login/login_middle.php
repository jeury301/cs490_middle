<?php

/**
 * login_middle.php
 *
 * Created by Michael Anderson on September 15, 2017 at 9:29 PM
 * *
 * Receives JSON from front-end as $_POST['json_string'] containing
 * username and plaintext password values.  Makes one CURL
 * request: 1.) POST to back end DB server with username specifying
 * either the Student or Professor table.  Request to backend server 
 * returns user record, including hashed password and salt, which is 
 * verified against plaintext password. If password is verified,
 * script returns JSON representation of the user record to the 
 * front end.
 */

// Uncomment to turn on debug mode
// ini_set('display_startup_errors', 1);
// ini_set('display_errors', 1);
// error_reporting(-1);

require '../../services/initial_json_parse.php';
require '../../services/curl_functions.php';
$BACKEND_ENDPOINTS = include '../../backend_endpoints.php';

// Preliminarily validate and parse JSON received as POST data
$parsed_post_data = initial_json_parse();

// Check if 'error' for 'status' in $parsed_post_data

if ($parsed_post_data['status'] == 'error') {
    // Bad request by front end
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode($parsed_post_data));
} 



/*
 * If  "username", "role", or "plaintext_password" isn't a key in array
 * $parsed_post_data, return JSON with error message and exit the script
 */
if (!isset($parsed_post_data['username']) || 
    !isset($parsed_post_data['plaintext_password']) )  {
    $error_response = array(
            "action" => "login",
            "status" => "error",
            "user_message" => "An error has occured.",
            "internal_message" => 'login_middle.php: Error: key `username` or ' .
                                  '`plaintext_password` missing from JSON POST data ' .
                                  'received from front end'
    );
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode($error_response));
}

// Based on the role, set the backend endpoint to CURL to
// Case-insensitive string comparison
// if (strcasecmp($parsed_post_data['role'], 'student') == 0) {
//     $backend_endpoint = $BACKEND_ENDPOINTS["loginStudent"];
//     $role = "student";
// } else if (strcasecmp($parsed_post_data['role'], 'professor') == 0) {
//     $backend_endpoint = $BACKEND_ENDPOINTS["loginProfessor"];
//     $role = "professor";
// } else {
//     // If invalid value for 'role', return an error
//     $error_response = array(
//             "action" => "login",
//             "status" => "error",
//             "user_message" => "An error has occured.",
//             "internal_message" => 'login_middle.php: Error: invalid value for key '. 
//                                   '`role`. Expecting `professor` or `student`.'
//     );
//     http_response_code(400);
//     header('Content-Type: application/json');
//     exit(json_encode($error_response));
// }

/* 
 * We will first try to log in to the student table; if successful,
 * we will return and exit with role "student".  If not, we will 
 * try to log in to the professor table; if successful, we will
 * return and exit with role "professor".  If both fail, we 
 * will return the appropriate error JSON
 */

/*
 * Here, we are sending a POST request to the backend server
 * with a single key/value pair: username=$parsed_post_data['username'],
 * We expect to receive JSON containing the corresponding 
 * user record, including the password hash and salt
 */
$post_params = array(
    "action" => "login",
    "user_name" => $parsed_post_data['username']
);
$header = array(); // Function takes a header arg, but not necessary here

// First, try student table:
$role = "student";
$backend_endpoint = $BACKEND_ENDPOINTS["loginStudent"];
$backend_json_response = curl_to_backend($header, $backend_endpoint, http_build_query($post_params));
$parsed_backend_response = json_decode($backend_json_response, true);
//print_r($parsed_backend_response);

/*
 * Check whether password matches hash/salt retrieved from student
 * table. If so, update $response array 
 */
if (password_verify($parsed_post_data['plaintext_password'], 
    $parsed_backend_response['items'][0]['hash_salt'])) {
    $response = array(
        "action" => "login",
        "status" => "successful",
        "role" => $role,
        "items" => $parsed_backend_response['items']
    );
} else {
    // Second, try the professor table
    $role = "professor";
    $backend_endpoint = $BACKEND_ENDPOINTS["loginProfessor"];
    $backend_json_response = curl_to_backend($header, $backend_endpoint, http_build_query($post_params));
    $parsed_backend_response = json_decode($backend_json_response, true);

    /*
     * Check whether password matches hash/salt retrieved from professor
     * table. If so, update $response array 
     */
    if (password_verify($parsed_post_data['plaintext_password'], 
        $parsed_backend_response['items'][0]['hash_salt'])) {
        $response = array(
            "action" => "login",
            "status" => "successful",
            "role" => $role,
            "items" => $parsed_backend_response['items']
        );
    } else {
        $response = array(
            "action" => "login",
            "status" => "error",
            "role" => "",
            "user_message" => "Login failed.  Unknown username/password combination.",
            "internal_message" => "login_middle.php: User password did not match hash."
    );
    }
}

// Return CURL response to the front end
http_response_code(200);
header('Content-Type: application/json');
exit(json_encode($response));


?>