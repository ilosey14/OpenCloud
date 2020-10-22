<?php
# /src/delete_acl.php

require_once 'sql.php';

/**
 * Delete a named token group.
 * @param string $name Named token group
 * @return bool Whether the item was successfully deleted
 */
function delete_acl(string $name): ?int {
	// remove acl collection doc
	try {
		$client = new SQL('admin');
	}
	catch (Exception $e) {
		// TODO: log error
		return null;
	}

	// delete doc
	return $client->deleteAclItem($name);
}