<?php
# /public/api/v1/list/index.php

const ROOT = '/var/www';
require_once ROOT . '/src/common.php';

// validate user
if (!Common::getLoginStatus())
	Common::return(401);

// validate request
Common::validateRequest($_GET, [ 'cd' ]);

$cd = ROOT . "/cloud/{$_GET['cd']}";

if (!is_dir($cd) || !Common::isAuthorizedPath($cd))
	Common::return(403, "Invalid location \"{$_GET['cd']}\".");

// list
$contents = Common::getFolder($cd);
$items = [];

foreach ($contents as $name) {
	$path = "$cd/$name";
	$info = pathinfo($path);

	$items[] = [
		'name' => $info['filename'],
		'ext' => $info['extension'] ?? null,
		'type' => is_dir($path)
			? 'folder'
			: mime_content_type($path)
	];
}

// response
echo json_encode([
	'cd' => $_GET['cd'],
	'items' => $items
]);