<?php

require_once __DIR__ . '/lib/social/VkAuth.php';

$access_token = Social\VkAuth::auth([
	'email' => 'you_email',
	'pass' => 'you_pass',
	'client_id' => 4436284,
	'scope' => 81054,
]);


echo $access_token;
