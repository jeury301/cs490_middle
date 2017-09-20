<?php
// Response that will be eventually encoded to JSON and returned to front end
$response = array(
    "njitLoginSuccess" => False,
    "dbLoginSuccess" => False,
);

$njitAuthenticationError = array(
    "httpStatus" => 500,
    "error" => "Unexpected error attempting to authenticate with NJIT portal",
);

$dbAuthenticationError = array(
    "httpStatus" => 500,
    "error" => "Unexpected error attempting to authenticate back end database",
);


$raw_json_string = $_POST['json_string'];

// echo "Value of raw_json_string: $raw_json_string <br/>";


$data = json_decode($raw_json_string, true);

// echo "Here is the value of the parsed-JSON array data: <br/>";
// print_r($data);
// echo "<br/>";

// echo "If this goes right, you should have your username and password echoed back to you: <br/>";
// echo "username: " . $data['username'] . "<br/>";
// echo "plaintext_password: " . $data['plaintext_password'] . "<br/>";


// echo "at line 32";


/*
 * Here, we are sending a POST request to the backend server
 * with a single key/value pair- username:$data['username'],
 * We expect to receive JSON containing the corresponding 
 * password hash-and-salt string in response
 */
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://web.njit.edu/~ps592/cs_490/app/login/login_back.php",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"username\"\r\n\r\n" . $data['username'] . "\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
  CURLOPT_HTTPHEADER => array(
    "cache-control: no-cache",
    "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW",
    "postman-token: 093aaaa1-ba89-895a-4bfa-200429187ddb"
  ),
));

$backend_json_response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

// echo "Response from backend server:<br/>";
// if ($err) {
//   echo "cURL Error #:" . $err;
// } else {
//   echo $backendJsonResponse;
// }

$parsed_backend_response = json_decode($backend_json_response, true);

// echo "-------- BEGIN DEBUGGING MSG --------<br/>";
// echo "Value of backendJsonResponse:<br/>";
// echo $backend_json_response;
// echo "--------- END DEBUGGING MSG ---------<br/>";


// print_r($parsed_backend_response);

// echo "-------- BEGIN DEBUGGING MSG --------<br/>";
// echo 'Value of $data[\'plaintext_password\']:<br/>';
// echo $data['plaintext_password'];
// echo 'Value of $parsed_backend_response[\'hashed_password\']:<br/>';
// echo $parsed_backend_response['hashed_password'];
// echo "<br/>";
// echo "--------- END DEBUGGING MSG ---------<br/>";


// Check whether password ma
if (password_verify($data['plaintext_password'], $parsed_backend_response['hashed_password'])) {
    // echo "Password matches<br/>";
    $response['dbLoginSuccess'] = True;
}


/*
 * Attempt to authenticate with NJIT Highlander Pipeline login 
 * using credentials passed from front end 
 */
$post['user'] = $data['username'];
$post['pass'] = $data['plaintext_password'];
$post['uuid'] = '0xACA021';                         // This is hard-coded in the NJIT page source code
$highlanderPipelineURL = "https://cp4.njit.edu/cp/home/login";
$header['Upgrade-Insecure-Requests'] = 1;
$njitResponse = spoof_njit_login($header, $highlanderPipelineURL, http_build_query($post));

/*
 * If the NJIT Authentication is successful, the response will 
 * include the string "Login Successful".  If that text is found
 * in $njitResponse, set $response['njitLoginSuccess'] to true
 */
if (strpos($njitResponse, 'Login Successful') !== false) {
    $response['njitLoginSuccess'] = True;
}


function spoof_njit_login($header = array(), $url, $post = false) {       
    $cookie = "cookie.txt";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate,sdch');
    if (isset($header) && !empty($header))
    {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 200);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36");
    curl_setopt($ch, CURLOPT_COOKIEJAR, realpath($cookie));
    curl_setopt($ch, CURLOPT_COOKIEFILE, realpath($cookie));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_REFERER, $url);

    //if it's a POST request instead of GET
    if (isset($post) && !empty($post) && $post)
    {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return ($data);     //this will return page on successful, false otherwise
}

echo json_encode($response);


?>