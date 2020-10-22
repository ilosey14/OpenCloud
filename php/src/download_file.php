<?php
# /src/download_file.php

require_once 'common.php';

/**
 * Download a file.
 * Sets the return headers and outputs the specified file.
 * @see https://www.php.net/manual/en/function.readfile.php
 * @see https://stackoverflow.com/a/7263943/12588503
 * @see https://stackoverflow.com/a/1754359/12588503
 *
 * @param string $filename Path to the file
 * @param string $root_path Optional path from the document root that's allowed to be accessed.
 */
function download_file(string $filename, string $root_path = '/cloud'): void {
	if (!file_exists($filename))
		throw new InvalidArgumentException('The file "' . basename($filename) . '" does not exist.');
	if (!Common::isAuthorizedPath($filename, $root_path))
		throw new InvalidArgumentException('The file "' . basename($filename) . '" is invalid.');

	// get file info
	$content_type = mime_content_type($filename);
	$basename = basename($filename);
	$content_length = filesize($filename);

	// set headers
	header("Content-Type: $content_type");
	header("Content-Disposition: attachment; filename=\"$basename\"");
	header("Content-Length: $content_length");

	// clear buffer and flush headers
	ob_clean();
	flush();

	// output file contents
	readfile($filename);
}