<?php
# /src/get_acl.php

require_once 'sql.php';

/**
 * Gets access tokens of a named token group.
 * @param string $name Named token group
 * @return null|array Token group or null.
 */
function get_acl(string $name) {
	// get acl collection
	try {
		$client = new SQL('admin');
	}
	catch (Exception $e) {
		// TODO: log error
		return null;
	}

	// query access tokens
	return $client->getAclItem($name);
}