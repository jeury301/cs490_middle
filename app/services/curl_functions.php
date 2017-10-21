<?php

/**
 * curl_functions.php
 *
 * Created by Michael Anderson on October 4, 2017
 *
 * Utility function(s) for making CURL requests
 */
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

    // Execute the Curl request
    $response_data = curl_exec($curl_obj);

    /*
     * In case of a Curl error trying to communicate with the database,
     * return JSON with error message and exit the script
     */
    $err = curl_error($curl_obj);
    if ($err) {
        http_response_code(500);
        $curl_error = array(
            "action" => "unknown",
            "status" => "error",
            "user_message" => "An error has occured.",
            "internal_message" => "curl_functions.php: CURL error from Middle to Back: $err " .
                                  "| Header: " . json_encode($header) . " | URL: $url | " .
                                  " Post data: " . json_encode($post),
    );
        curl_close($curl_obj);
        http_response_code(500);
        header('Content-Type: application/json');
        exit(json_encode($curl_error));
    } 

    // Return response
    curl_close($curl_obj);
    return ($response_data); 
}