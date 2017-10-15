<?php

/*
 * This script simply generates a password hash and salt (all as one string)
 * so that the appropriate hash can be manually stored in a database for the
 * purposes of the Alpha assignment. The plaintext password is sent in a 
 * POST request as 'plaintext_password'.
 *
 * It should ALWAYS echo a message that the password and hash match.
 */


$plaintext_password = $_POST['plaintext_password'];

echo "Value of plaintext_password: $plaintext_password <br/>";

$hashed_password_and_salt = password_hash($plaintext_password, PASSWORD_DEFAULT);

echo "Value of hashed_password_and_salt: BEGIN$hashed_password_and_salt"."END <br/>";

echo "Verifying if hash matches the plaintext... <br/>";

if (password_verify($plaintext_password, $hashed_password_and_salt)) {
	echo "plaintext_password and hashed_password_and_salt MATCH!<br/>";
} else {
	echo "plaintext_password and hashed_password_and_salt DO NOT MATCH!<br/>";
}

?>