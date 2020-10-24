<?php
# /public/api/v1/login/index.php

const ROOT = '/var/www';
require_once ROOT . '/src/common.php';
require_once ROOT . '/src/get_acl.php';
require_once ROOT . '/src/delete_acl.php';

// validate user
if (Common::getLoginStatus())
	Common::return();

// validate request
Common::validateRequest($_POST, [ 'name', 'tokens' ]);

// get acl tokens
$result = get_acl($_POST['name']);

if ($result && !delete_acl($_POST['name']))
	Common::return(500);

if (!$result ||
	!($acl_tokens = $result['tokens'] ?? []) ||
	!is_array($acl_tokens))
{
	$acl_tokens = [];
}

// get count for creating access tokens later
$acl_count = count($acl_tokens);

// validate tokens
foreach ($_POST['tokens'] as $post_token) {
	for ($i = 0; $i < count($acl_tokens); $i++) {
		if (hash_equals($acl_tokens[$i], $post_token)) {
			// remove valid acl token from list
			array_splice($acl_tokens, $i, 1);
			break;
		}
	}
}

// create session
$authorized = 401;
$session_lifetime = time() + (365 * 24 * 60 * 60);	// TODO: allow *user session* expired time to be config
$login_lifetime = 12 * 60 * 60;						// TODO: allow *login token* expired time to be config
$login_expired = ($result['timestamp'] ?? 0) + $login_lifetime;

session_start([
	'name' => Common::SESSION_NAME,
	'cookie_lifetime' => $session_lifetime
]);
session_regenerate_id(true);

$_SESSION = [ 'tokens' => [] ];

// session is valid if all tokens were matched...
if (count($acl_tokens) === 0) {
	// ...and the login lifetime hasn't expired
	if (time() <= $login_expired)
		$authorized = 200;
	else {
		$authorized = 410;
		$acl_count = 0;
	}

	// create access tokens
	for ($i = 0; $i < $acl_count; $i++) {
		$key = Common::randomToken(8);
		$value = Common::randomToken(128);

		// handle duplicate key
		if (isset($_SESSION[$key])) {
			$i--;
			continue;
		}

		$_SESSION['tokens'][$key] = $value;
		Common::setCookie($key, $value, $session_lifetime);
	}

}

// return
Common::return($authorized);