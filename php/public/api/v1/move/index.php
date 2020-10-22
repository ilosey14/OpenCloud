<?php
# /public/api/v1/move.php

//
// TODO: HERE - TEST
//

const ROOT = '/var/www/';
require_once ROOT . '/src/common.php';

// validate user
if (!Common::getLoginStatus())
	Common::return(401);

// validate request
Common::validateRequest($_POST, [ 'cd', 'target' ]);

$cd = ROOT . "/cloud/{$_POST['cd']}";
$target = ROOT . "/cloud/{$_POST['target']}";

if (!is_dir($cd) || !Common::isAuthorizedPath($cd))
	Common::return(403, "Invalid location \"{$_POST['cd']}\".");

if (!is_dir($target) || !Common::isAuthorizedPath($target))
	Common::return(403, "Invalid target \"{$_POST['target']}\".");

// move
$errors = [];

if (isset($_POST['files']) &&
	strlen($_POST['files']))
{
	$files = json_decode($_POST['files'], true);

	if ($files) {
		foreach ($files as $name) {
			$name = trim($name);
			$basename = pathinfo($name, PATHINFO_BASENAME);
			$from = "$cd/$name";
			$to = "$target/$basename";

			if (!Common::isAuthorizedPath($from) ||
				!Common::isAuthorizedPath($target) ||
				!file_exists($from) ||
				!is_dir($target) ||
				!rename($from, $to))
			{
				$errors[] = "$name -> {$_POST['target']}";
			}
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
			$from = "$cd/$name";
			$to = "$target/$name";

			if (!Common::isAuthorizedPath($from) ||
				!Common::isAuthorizedPath($target) ||
				!is_dir($from) ||
				!is_dir($target) ||
				!rename($from, $to))
			{
				$errors[] = "$name -> {$_POST['target']}";
			}
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