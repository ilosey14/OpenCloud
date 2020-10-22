<?php
# /public/api/v1/new-folder/index.php

const ROOT = '/var/www';
require_once ROOT . '/src/common.php';

// validate user
if (!Common::getLoginStatus())
	Common::return(401);

// validate request
Common::validateRequest($_POST, [ 'cd', 'name' ]);

$cd = ROOT . "/cloud/{$_POST['cd']}";

if (!is_dir($cd) || !Common::isAuthorizedPath($cd))
	Common::return(403, "Invalid location \"{$_POST['cd']}\".");

// new folder
$name = Common::filterString($_POST['name'], Common::FILTER_SAFE);

if (!Common::createFolder($cd, $name))
	Common::return(500, "Could not create folder \"$name\".");