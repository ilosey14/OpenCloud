<?php
# /public/api/v1/download/index.php

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

// download
require_once ROOT . '/src/download_file.php';

$files = (isset($_GET['files']) && strlen($_GET['files']))
	? json_decode($_GET['files'], true)
	: null;
$folders = (isset($_GET['folders']) && strlen($_GET['folders']))
	? json_decode($_GET['folders'], true)
	: null;

// return single file
if ($files &&
	!$folders &&
	(count($files) === 1))
{
	$filename = "$cd/{$files[0]}";

	try {
		if (Common::isAuthorizedPath($filename) && file_exists($filename))
			download_file($filename);
		else
			throw new RuntimeException("The file \"{$files[0]}\" does not exist or is invalid.");
	}
	catch (Exception $e) {
		Common::return(400, $e->getMessage());
	}

	exit;
}

// multiple files/folders
//$compression = strtolower($_GET['compression']) ?? 'zip';

// create archive
// https://www.php.net/manual/en/class.ziparchive.php
// TODO: implement multiple archiving options
// TODO: https://www.php.net/manual/en/refs.compression.php

$archive = new ZipArchive;
$archive_name = ROOT . '/tmp/download.zip';

$archive_errors = [];
$archive_open_error = $archive->open($archive_name, ZipArchive::CREATE | ZipArchive::OVERWRITE);

if ($archive_open_error !== true)
	Common::return(500, "Error [$archive_open_error]: could not create archive.");

if ($files && count($files)) {
	foreach ($files as $name) {
		$filename = "$cd/$name";

		if (!Common::isAuthorizedPath($filename) ||
			!file_exists($filename) ||
			!$archive->addFile($filename, $name))
		{
			$archive_errors[] = "{$_GET['directory']}/$name";
		}
	}
}

if ($folders && count($folders)) {
	foreach ($folders as $name) {
		$foldername = "$cd/$name";

		if (!Common::isAuthorizedPath($foldername) ||
			!is_dir($foldername) ||
			!$archive->addGlob("$foldername/*", GLOB_NOSORT, [
				'remove_all_path' => true,
				'add_path' => "$name/"
			]))
		{
			$archive_errors[] = "{$_GET['directory']}/$name";
		}
	}
}

// add errors as file entry
if (count($archive_errors))
	$archive->addFromString('.ERRORS', implode("\n", $archive_errors));

// close
if (!$archive->close())
	Common::return(500, 'Error [' . ZipArchive::ER_CLOSE .']: could not create archive.');

// download file
try {
	download_file($archive_name, '/tmp');
}
catch (Exception $e) {
	Common::return(500, 'Server error: archive was not created.');
}

// delete tmp
unlink($archive_name);