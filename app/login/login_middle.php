<?php

/**
 * login_middle.php
 *
 * Created by Michael Anderson on September 15, 2017 at 9:29 PM
 *
 * Receives JSON from front-end as $_POST['json_string'] containing
 * username and plaintext password values.  Makes two CURL
 * requests: 1.) POST to NJIT login page to attempt to authenticate
 * using credentials received from front end and 2.) POST to back end
 * DB server with username.  Request to backend server returns hashed
 * password and salt, which is verified against plaintext password.
 * Script returns JSON object to front end indicating whether 
 * authentication was successful with NJIT login and/or user record
 * in project's database.
 */

/*
 * Response object that will be eventually encoded to JSON and 
 * returned to front end.  By default, "njitLoginSuccess" and 
 * "dbLoginSuccess" are false 
 */
$response = array(
    "njitLoginSuccess" => false,
    "dbLoginSuccess" => false,
);

/*
 * In case of a Curl error trying to authenticate with NJIT, encode this
 * as JSON, echo it, and exit the script
 */
$njit_authentication_error = array(
    "httpStatus" => 500,
    "error" => "Unexpected error attempting to authenticate with NJIT portal.",
);

/*
 * In case of a Curl error trying to communicate with the database, encode this
 * as JSON, echo it, and exit the script
 */
$db_authentication_error = array(
    "httpStatus" => 500,
    "error" => "Unexpected error attempting to authenticate back end database.",
);

/*
 * Must first save JSON post data as separate variable, *THEN* parse 
 * it as PHP array.
 * 
 * Also, note that json_decode() will return an object, not an  
 * associative array unless true is passed as a second parameter. 
 */
$raw_json_string = $_POST['json_string'];
$parsed_post_data = json_decode($raw_json_string, true);

/*
 * Here, we are sending a POST request to the backend server
 * with a single key/value pair: username=$parsed_post_data['username'],
 * We expect to receive JSON containing the corresponding 
 * password hash-and-salt string in response
 */
$post_params = array(
  "username" => $parsed_post_data['username'],
);
$backend_endpoint = "https://web.njit.edu/~ps592/cs_490/app/login/login_back.php";
$header = array(); // Function takes a header arg, but not necessary here
$backend_json_response = curl_to_backend($header, $backend_endpoint, http_build_query($post_params));
$parsed_backend_response = json_decode($backend_json_response, true);

/*
 * Check whether password matches hash/salt retrieved from DB.
 * If so, update $response array
 */
if (password_verify($parsed_post_data['plaintext_password'], 
    $parsed_backend_response['hashed_password'])) {
    // echo "Password matches<br/>";
    $response['dbLoginSuccess'] = True;
}

/*
 * Attempt to authenticate with NJIT Highlander Pipeline login 
 * using credentials passed from front end.
 * 
 * Form we are trying to spoof is at https://www6.njit.edu/cp/login.php
 * Line refererences refer to that page's source
 */
$post_params = array(
  "user" => $parsed_post_data['username'],    // 'user' is hidden field (Line 184)
  "pass" => $parsed_post_data['plaintext_password'],
  "uuid" => '0xACA021',     // 'uuid' is hidden field (Line 184) hard-coded to 0xACA021
);
$highlander_pipeline_url = "https://cp4.njit.edu/cp/home/login";
$header = array(); // Function takes a header arg (an array), but not necessary here
$njitResponse = spoof_njit_login($header, $highlander_pipeline_url, http_build_query($post_params));

/*
 * If the NJIT Authentication is successful, the response will 
 * include the string "Login Successful".  If that text is found
 * in $njitResponse, update $response array
 */
if (strpos($njitResponse, 'Login Successful') !== false) {
    $response['njitLoginSuccess'] = True;
}

// Return JSON response to the front end
echo json_encode($response);

function curl_to_backend($header, $url, $post) {       
    $curl_obj = curl_init();

    // Set Curl options (See http://php.net/manual/en/function.curl-setopt-array.php)
    curl_setopt_array($curl_obj, array(
        CURLOPT_URL => $url,
        CURLOPT_FOLLOWLOCATION => 1,    // True - Follow HTTP 3xx redirects (probably unneeded)
        CURLOPT_MAXREDIRS => 10,        // Max no. of redirects to follow (see above)
        CURLOPT_RETURNTRANSFER => 1,    // Sets return value of curl_exec to true
        CURLOPT_ENCODING => "",         // If "", header containing all supported encoding types is sent
        CURLOPT_TIMEOUT => 30,          // In seconds
        CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0",
        CURLOPT_HEADER => 0,            // False - DON'T incude response header in output
        CURLOPT_HTTPHEADER => $header,  // A PHP array of HTTP header fields
        CURLOPT_POST => 1,              // True - This is a post request
        CURLOPT_POSTFIELDS => $post,    // NOTE: $post is a *query string*, NOT a PHP array
    ));

    // Execute the Curl request and return response
    $response_data = curl_exec($curl_obj);
    curl_close($curl_obj);
    return ($response_data); 
}

function spoof_njit_login($header, $url, $post) {       
    $cookie = "cookie.txt"; // Required, since NJIT portal sets cookie, then checks for it
    $curl_obj = curl_init();

    // Set Curl options (See http://php.net/manual/en/function.curl-setopt-array.php)
    curl_setopt_array($curl_obj, array(
        CURLOPT_URL => $url,
        CURLOPT_FOLLOWLOCATION => 1,    // True - Follow HTTP 3xx redirects (NJIT will redirect)
        CURLOPT_MAXREDIRS => 10,        // Max no. of redirects to follow (see above)
        CURLOPT_RETURNTRANSFER => 1,    // Sets return value of curl_exec to true
        CURLOPT_ENCODING => "",         // If "", header containing all supported encoding types is sent
        CURLOPT_TIMEOUT => 30,          // In seconds
        CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0",
        CURLOPT_REFERER => $url,        // Make it look like request originated from $url
        CURLOPT_HEADER => 0,            // False - DON'T incude response header in output
        CURLOPT_HTTPHEADER => $header,  // A PHP array of HTTP header fields
        CURLOPT_POST => 1,              // True - This is a post request
        CURLOPT_POSTFIELDS => $post,    // NOTE: $post is a *query string*, NOT a PHP array
        CURLOPT_COOKIEJAR => realpath($cookie),     // Where cookies are stored
        CURLOPT_COOKIEFILE => realpath($cookie),    // Where cookies are read from
    ));

    // Execute the Curl request and return response
    $response_data = curl_exec($curl_obj);
    curl_close($curl_obj);
    return ($response_data); 
}

?>