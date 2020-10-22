<?php
# /public/api/v1/upload/index.php

const ROOT = '/var/www';
require_once ROOT . '/src/common.php';

// validate user
if (!Common::getLoginStatus())
	Common::return(401);

// validate request
Common::validateRequest($_POST, [ 'cd' ]);
Common::validateRequest($_FILES, [ 'files' ]);

$cd = ROOT . "/cloud/{$_POST['cd']}";

if (!file_exists($cd) && !is_dir($cd))
	Common::return(403, "Invalid location \"{$_POST['cd']}\".");

// upload
require_once ROOT . '/src/upload_file.php';

$errors = [];

Common::organizeFiles();

foreach ($_FILES['files'] as $file) {
	try {
		upload_file($file, $cd);
	}
	catch (Exception $e) {
		$errors[$file['name']] = $e->getMessage();
	}
}

if (count($errors)) {
	Common::return(400, json_encode([
		'error' => $errors
	]));
}