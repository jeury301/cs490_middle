<?php

function njitCurl($header = array(), $url, $post = false) {       
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
    } //endif
    $data = curl_exec($ch);
    curl_close($ch);
    if($info['http_code'] == 200){
        return ($data);     //this will return page on successful, false otherwise
    } else {
        return false;
    }
}

?>