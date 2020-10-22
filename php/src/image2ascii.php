<?php
# /src/image2ascii.php

/**
 * Converts an image to ascii text.
 *
 * | option | type | default |
 * |-|-|-|
 * | `fill_char` | *string* | `'██'` |
 * | `empty_char` | *string* | `'  '` |
 * | `new_line` | *string* | `PHP_EOL`
 *
 * TODO: map rgb weight to luminance values. \
 * TODO: n/a for qr codes however
 *
 * @see https://stackoverflow.com/a/36101876/12588503
 *
 * @param string $filename
 * @param null|int $height Height in lines
 * @param null|int $width Width in characters
 * @param null|array $options
 */
function image2ascii(string $filename, ?int $width = null, ?int $height = null, ?array $options = null): string {
	error_reporting(E_ALL & ~E_NOTICE);

	// create file resource from supported types
	$type = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
	$image_create = "imagecreatefrom$type";

	if (!function_exists($image_create))
		throw new RuntimeException("Image file type \"$type\" not supported.");

	$image = $image_create($filename);

	// get options
	$fill_char  = $options['fill_char']  ?? "\u{2588}\u{2588}";
	$empty_char = $options['empty_char'] ?? '  ';
	$new_line   = $options['new_line']   ?? PHP_EOL;

	list($image_width, $image_height) = getimagesize($filename);

	if (!$width || $width < 0) $width = $image_width;
	if (!$height || $height < 0) $height = $image_height;

	$width_step = $image_width / $width;
	$height_step = $image_height / $height;

	$rows = array_fill(0, $height + 2, null);
	$cols = array_fill(0, $width + 2, null);
	$fill_val = 0xffffff / 2;

	// echo "image: $image_width x $image_height", PHP_EOL;
	// echo "size:  $width x $height", PHP_EOL;
	// echo "block: $width_step x $height_step", PHP_EOL, PHP_EOL;

	// loop by step size
	$rows[0] = implode(array_fill(0, $width + 2, '--'));
	$i = 1;

	for ($y = 0; $y < $image_height; $y += $height_step) {
		$cols[0] = '| ';
		$j = 1;

		for ($x = 0; $x < $image_width; $x += $width_step) {
			$rgb = _get_block_color($image, $x, $y, $width_step, $height_step);
			$cols[$j++] = ($rgb < $fill_val) ? $fill_char : $empty_char;
		}

		$cols[$j] = ' |';
		$rows[$i++] = implode($cols);
	}

	$rows[$i] = $rows[0];

	return implode($new_line, $rows);
}

function _get_block_color(&$image, int $x, int $y, int $width, int $height): int {
	$value = 0;
	$count = 0;

	for ($i = 0; $i < $width; $i++) {
		for ($j = 0; $j < $height; $j++) {
			// calculate rolling average
			$value = ($count * $value + imagecolorat($image, $x + $i, $y + $j)) / ++$count;
		}
	}

	return $value;
}

$imagecreatefromjpg = 'imagecreatefromjpeg';