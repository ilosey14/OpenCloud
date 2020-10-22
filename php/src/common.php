<?php

/**
 * A class of common functions.
 *
 * @package Common
 */
class Common
{
	private const INVALID_CHARS = '/\\\\\\/:\*\?"<>\|/';
	private const TOKEN_CHARS = '-0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz';

	public const COOKIE_PATH = '/api/v1';
	public const COOKIE_DOMAIN = '';
	public const ROOT = '/var/www';
	public const CLOUD_ROOT = '/var/www/cloud';
	public const SESSION_NAME = 'oc_id';

	public const FILTER_SANITIZE	= 0b1;
	public const FILTER_SAFE		= 0b10;

	/**
	 * Sets a response code and exists.
	 * Optionally echos a message to the body.
	 */
	public static function return(int $code = 200, string $message = null): void
	{
		http_response_code($code);

		if ($message) {
			header('Content-Type: text/plain');
			echo $message;
		}

		exit;
	}

	/**
	 * Redirects the client to an absolute url.
	 * Sets the "Location" header property.
	 * @param string $url The absolute url to the root domain
	 * @param array $params Associative array of url parameters
	 * @param string $hash A hash string succeeding the '#' character
	 */
	public static function redirect(string $url = '', array $params = null, string $hash = null): void
	{
		$location = [ $url ];

		if (isset($params)) {
			$paramArray = [];

			foreach ($params as $key => $value)
				$paramArray[] = "$key=" . rawurlencode($value);

			$location[] = '?';
			$location[] = implode('&', $paramArray);
		}

		if ($hash) {
			$location[] = '#';
			$location[] = $hash;
		}

		header('Location: ' . implode($location));
		exit;
	}

	/**
	 * Filters a string.
	 * Strips code points < 32 and > 127 be default.
	 * Pass options flags for additional filters.
	 *
	 * | Option | Description |
	 * |-|-|
	 * | `FILTER_SANITIZE` | Encodes html special characters |
	 * | `FILTER_SAFE` | Removes file-system unsafe characters (Windows/Linux/Mac) |
	 *
	 * @param string $value Value to sanitize
	 * @param int $options Filter options
	 * @see https://www.php.net/manual/en/function.filter-var.php
	 * @see https://www.php.net/manual/en/filter.filters.php
	 */
	public static function filterString(string $value, int $options = 0): string
	{
		$sanitize	= (($options & self::FILTER_SANITIZE) === self::FILTER_SANITIZE) ? FILTER_SANITIZE_STRING : FILTER_DEFAULT;
		$safe		= (($options & self::FILTER_SAFE )=== self::FILTER_SAFE);

		$value = filter_var($value, $sanitize, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

		return $safe
			? preg_replace(self::INVALID_CHARS, '', $value)
			: $value;
	}

	/**
	 * Deletes a file from an authorized path.
	 * @param string $filename
	 * @return bool True on success or false on failure
	 */
	public static function deleteFile(string $filename): bool
	{
		return (file_exists($filename) && self::isAuthorizedPath($filename))
			? unlink($filename)
			: false;
	}

	/**
	 * Gets the contents of a folder.
	 * @param string $path
	 * @return null|array An array of items, or `null` if the path is invalid
	 */
	public static function getFolder(string $path): ?array
	{
		return (is_dir($path) && self::isAuthorizedPath($path))
			? array_diff(scandir($path), ['.', '..'])
			: null;
	}

	/**
	 * Creates a new folder at an authorized path.
	 * @param string $path The path where the folder will be created
	 * @param string $name The folder name
	 * @return bool True on success or false on failure
	 */
	public static function createFolder(string $path, string $name): bool
	{
		// path must be valid
		if (!is_dir($path) || !self::isAuthorizedPath($path))
			return false;

		// name must be safe
		if ($name !== self::filterString($name, self::FILTER_SAFE))
			return false;

		// create folder
		$pathname = "$path/$name";

		return !is_dir($pathname)
			? mkdir($pathname, 0770, true)
			: false;
	}

	/**
	 * Deletes a folder from an authorized path.
	 * @param string $path
	 * @param bool $recursive Whether to delete all folder content
	 * @return bool True on success or false on failure
	 * @see https://www.php.net/manual/en/function.rmdir.php
	 */
	public static function deleteFolder(string $path, bool $recursive = false): bool
	{
		if (!is_dir($path) || !self::isAuthorizedPath($path))
			return false;

		if (!$recursive)
			return rmdir($path);

		$items = self::getFolder($path);

		foreach ($items as $name) {
			$pathname = "$path/$name";

			if (is_dir($pathname))
				self::deleteFolder($pathname);
			else
				unlink($pathname);
		}

		return rmdir($path);
	}

	/**
	 * Determines whether a given real path is within the valid path.
	 * @param string $path The path to validate
	 * @param string $valid_path An optional path from the `ROOT` to check `$path` against instead
	 */
	public static function isAuthorizedPath(string $path, string $valid_path = self::CLOUD_ROOT): bool
	{
		$path = realpath($path);
		$cloud_root = realpath($valid_path);

		return (strpos($path, $cloud_root) === 0);
	}

	/**
	 * Reorganizes the `$_FILES` array into individual named associative arrays.
	 */
	public static function organizeFiles(): void
	{
		$files = [];

		// named file set
		foreach ($_FILES as $name => $info) {
			$files_count = count($info['name']);
			$files[$name] = array_fill(0, $files_count, []);

			// file in an info field
			for ($i = 0; $i < $files_count; $i++) {
				// info field
				foreach ($info as $key => $values)
					$files[$name][$i][$key] = $values[$i];
			}
		}

		// update files
		$_FILES = $files;
	}

	/**
	 * Validates the request array against the provided required fields.
	 * Then sanitizes and updates the request.
	 * @param array $request The request array to validate (i.e. $_GET)
	 * @param array $fields Required fields to check for
	 */
	public static function validateRequest(array &$request, array $fields = []): void
	{
		if (!count($request))
			self::return(400);

		// check for required fields
		foreach ($fields as $field) {
			if (!isset($request[$field]))
				self::return(400, "Field \"$field\" is required.");
		}

		// validate all request fields
		foreach ($request as $field => $value) {
			if (is_string($value))
				$request[$field] = self::filterString($value);
			else if (is_array($value))
				self::validateRequest($request[$field]);
		}
	}

	/**
	 * Sets a cookie via `setcookie`.
	 * @param string $name The name of the cookie
	 * @param string $value The value of the cookie
	 * @param int $expire The time the cookie expires.
	 * @link https://php.net/manual/en/function.setcookie.php
	 */
	public static function setCookie(string $name, string $value = '', int $expire = 0): void
	{
		setcookie($name, $value, $expire, self::COOKIE_PATH, self::COOKIE_DOMAIN, false /* true */, true);
	}

	/**
	 * Generates a random token string.
	 * Token characters := `/[\-0-9A-Z_a-z]/`
	 */
	public static function randomToken(int $length): string
	{
		$token = array_fill(0, $length, null);
		$chars_length = strlen(self::TOKEN_CHARS) - 1;

		for ($i = 0; $i < $length; $i++)
			$token[$i] = substr(self::TOKEN_CHARS, mt_rand(0, $chars_length), 1);

		return implode($token);
	}

	/**
	 * Gets the login status of the user.
	 * Verifies access tokens between the client and the server session.
	 */
	public static function getLoginStatus(): bool
	{
		if (isset(self::$isLoggedIn)) return self::$isLoggedIn;

		session_start([
			'name' => self::SESSION_NAME,
			'read_and_close' => true
		]);

		if (isset($_SESSION['tokens']) && count($_SESSION['tokens'])) {
			$token_count = count($_SESSION['tokens']);
			$cookie_count = 0;

			foreach ($_SESSION['tokens'] as $id => $token) {
				if (isset($_COOKIE[$id]) && $_COOKIE[$id] === $token)
					$cookie_count++;
			}

			if ($token_count === $cookie_count)
				return self::$isLoggedIn = true;

			// if invalid credentials, clear cookies and destroy session
			foreach ($_COOKIE as $key => $value)
				self::setCookie($key, '');

			session_start();
			session_destroy();
		}

		return self::$isLoggedIn = false;
	}

	private static bool $isLoggedIn;
}