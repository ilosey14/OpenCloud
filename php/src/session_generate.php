<?php
# /src/session_generate.php

const TMP = '/var/www/tmp';
require_once 'common.php';
require_once 'sql.php';

/**
 * Generates a new OpenCloud session.
 *
 * @param bool $quick_scan Whether to generate a QR code to scan from the terminal
 */
function session_generate(bool $quick_scan): void
{
	$id = Common::randomToken(8);

	$tokens = [];
	$tokens[] = Common::randomToken(64);
	$tokens[] = Common::randomToken(64);
	$tokens[] = Common::randomToken(64);

	// log session in acl
	try {
		$client = new SQL('admin');
	}
	catch (Exception $e) {
		echo $e->getMessage();
		exit;
	}

	$success = $client->insertAclItem($id, $tokens);

	// validate result
	if (!$success) {
		echo '[SQL] Could not insert acl session into database.';
		exit;
	}

	// output session keys
	echo 'Session Key', PHP_EOL, PHP_EOL;
	echo "name: $id", PHP_EOL;

	foreach ($tokens as $i => $token) {
		$i++;
		echo "[$i] ", $token, PHP_EOL;
	}

	echo PHP_EOL;

	// output qr code to console
	if ($quick_scan) {
		// TODO: for image2ascii only, see below
		echo 'Please zoom out to view QR code.', PHP_EOL;
		echo 'Press [Enter] to continue.', PHP_EOL;
		readline();

		// generate qr image
		require_once '/var/www/vendor/autoload.php';
		$qr = new Endroid\QrCode\QrCode("$id\n" . implode("\n", $tokens));

		// save to tmp
		$id = Common::randomToken(8);
		$filename = TMP . "/$id.png";

		$qr->writeFile($filename);

		// display to terminal
		// to get enough resolution we need to zoom out on terminals that support it
		// TODO: incorporate options for
		// TODO: - frame buffer viewers: fim, viu, user config, etc.
		// TODO: - opening windowed image viewers: Feh, Ristretto, Mirage, ImageMagick, etc.
		require_once 'image2ascii.php';
		echo image2ascii($filename);
		echo PHP_EOL;

		// delete tmp
		unlink($filename);
	}
}
