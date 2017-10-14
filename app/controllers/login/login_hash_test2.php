<?php

/* 
 * This script simply checks if a plaintext password and a hash/salt string
 * match.  The parameters should be POSTed: 'plaintext_password' and
 * 'hashed_password_and_salt'.
 */

$plaintext_password = $_POST['plaintext_password'];
$hashed_password_and_salt = $_POST['hashed_password_and_salt'];

if (password_verify($plaintext_password, $hashed_password_and_salt)) {
	echo "The plaintext_password and the hashed_password_and_salt MATCH!<br/>";
} else {
	echo "The plaintext_password and the hashed_password_and_salt DO NOT MATCH! :(<br/>";
}

?>