<?php
# /public/api/v1/delete/index.php

const ROOT = '/var/www';
require_once ROOT . '/src/common.php';

// validate user
if (!Common::getLoginStatus())
	Common::return(401);

// validate request
Common::validateRequest($_POST, [ 'cd' ]);

$cd = ROOT . "/cloud/{$_POST['cd']}";

if (!is_dir($cd) || !Common::isAuthorizedPath($cd))
	Common::return(403, "Invalid location \"{$_POST['cd']}\".");

// delete
$recursive = isset($_POST['recursive']) && !str_ireplace('true', '', $_POST['recursive']);
$errors = [];

if (isset($_POST['files']) &&
	strlen($_POST['files']))
{
	$files = json_decode($_POST['files'], true);

	if ($files) {
		foreach ($files as $name) {
			$name = trim($name);
			$path = "$cd/$name";

			if (!Common::deleteFile($path))
				$errors[] = $name;
		}
	}
	else
		$errors[] = 'Invalid field "files".';
}

if (isset($_POST['folders']) &&
	strlen($_POST['folders']))
{
	$folders = json_decode($_POST['folders'], true);

	if ($folders) {
		foreach ($folders as $name) {
			$name = trim($name);
			$path = "$cd/$name";

			if (!Common::deleteFolder($path, $recursive))
				$errors[] = $name;
		}
	}
	else
		$errors[] = 'Invalid field "folders".';
}

// return errors
if (count($errors)) {
	Common::return(500, json_encode([
		'error' => $errors
	]));
}