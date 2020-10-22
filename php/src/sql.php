<?php

/**
 * SQL interface.
 *
 * @package SQL
 */
class SQL
{
	private const HOST = 'localhost';
	private const USER = 'root';
	private const PASSWORD = '';

	private mysqli $link;

	/**
	 * Creates a new SQL instance.
	 * @param string $database
	 * @throws RuntimeException
	 */
	public function __construct(string $database)
	{
		$this->link = new mysqli(self::HOST, self::USER, self::PASSWORD, $database);

		if ($this->link->connect_errno)
			throw new RuntimeException("[SQL] Connection error ({$this->link->connect_errno}): {$this->link->connect_error}");
	}

	/**
	 * Prepares and executes an SQL query.
	 * @param string $query
	 * @param mixed[] $params
	 * @return mysqli_stmt
	 * @throws RuntimeException If unable to prepare statement from query
	 */
	public function query(string $query, ...$params): mysqli_stmt
	{
		$stmt = $this->link->prepare($query);

		if (!$stmt)
			throw new RuntimeException("[SQL] Unable to prepare statement from query:\n\t\"$query\"");

		// bind params
		if (isset($params) && count($params)) {
			foreach ($params as $param)
				$types[] = substr(gettype($param), 0, 1);

			$stmt->bind_param(implode($types), ...$params);
		}

		// execute
		$stmt->execute();
		return $stmt;
	}

	/**
	 * Inserts a new ACL item.
	 * @param string $id
	 * @param array $tokens
	 * @return bool Whether the item was successfully inserted
	 */
	public function insertAclItem(string $id, array $tokens): bool
	{
		$query = "INSERT INTO acl (id,tokens) VALUES (?,?)";
		$stmt = $this->query($query, $id, json_encode($tokens));

		return ($stmt->affected_rows === 1);
	}

	/**
	 * Gets an ACL item by id.
	 * @param string $id
	 * @return null|array
	 */
	public function getAclItem(string $id): ?array
	{
		// convert `timestamp` from mysql timestamp to int
		$query = 'SELECT id, tokens, UNIX_TIMESTAMP(timestamp) as timestamp FROM acl WHERE id=? LIMIT 1';
		$stmt = $this->query($query, $id);

		$item = self::fetchOne($stmt);

		// json string must be parse in php
		if ($item)
			$item['tokens'] = json_decode($item['tokens'], true) ?? [];

		return $item;
	}

	/**
	 * Deletes an ACL item by id.
	 * @param string $id
	 * @return bool Whether the item was successfully deleted
	 */
	public function deleteAclItem(string $id): bool
	{
		$query = 'DELETE FROM acl WHERE id=? LIMIT 1';
		$stmt = $this->query($query, $id);

		return ($stmt->affected_rows === 1);
	}

	/**
	 * Fetches the first result row.
	 * @param mysqli_stmt $stmt
	 * @return null|array
	 */
	public static function fetchOne(mysqli_stmt $stmt): ?array
	{
		if ($result = $stmt->get_result())
			return $result->fetch_assoc();

		return null;
	}

	/**
	 * Fetches all result rows.
	 * @param mysqli_stmt $stmt
	 * @return null|array
	 */
	public static function fetchAll(mysqli_stmt $stmt): ?array
	{
		if ($result = $stmt->get_result())
			return $result->fetch_all(MYSQLI_ASSOC);

		return null;
	}
}