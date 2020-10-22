<?php
# /src/upload_file.php

const MAX_FILESIZE = 1e9;
require_once 'common.php';

/**
 * Validates and creates a permanent file from a
 * temporary upload.
 *
 * @see https://www.php.net/manual/en/features.file-upload.php#114004
 *
 * @param array $file Named file object from $_FILES
 * @param string $destination Directory path to move file
 */
function upload_file(array $file, string $destination): void {
	// Request is invalid if fails by
	// null | multiple files | corruption attack
	if (!isset($file['error']) ||
		is_array($file['error']))
	{
		throw new RuntimeException('Invalid parameters.');
	}

	// check error value
	switch ($file['error']) {
		case UPLOAD_ERR_OK:
			break;

		case UPLOAD_ERR_NO_FILE:
			throw new RuntimeException('No file sent.');

		case UPLOAD_ERR_INI_SIZE:
		case UPLOAD_ERR_FORM_SIZE:
			throw new RuntimeException('Filesize limit exceeded.');

		default:
			throw new RuntimeException('Unknown error.');
	}

	// hard stop on filesize
	if ($file['size'] > MAX_FILESIZE)
		throw new RuntimeException('Filesize limit exceeded.');

	// validate file name
	if (!Common::isAuthorizedPath($destination))
		throw new RuntimeException('Invalid location.');

	$file['name'] = Common::filterString($file['name'], Common::FILTER_SAFE);
	$filename = "$destination/{$file['name']}";

	if (!move_uploaded_file($file['tmp_name'], $filename))
		throw new RuntimeException('Invalid file.');
}