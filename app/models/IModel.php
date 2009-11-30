<?php

/**
 * Common model inteface.
 *
 * @author     Roman Sklenář
 * @package    Blog
 */
interface IModel {


	/**
	 * Setups database connection
	 * @return void
	 */
	public static function initialize();



	/***** Public getters and setters *****/



	/**
	 * Gets table name
	 * @return string
	 */
	public function getTable();


	/**
	 * Gets primary key(s)
	 * @return string
	 */
	public function getPrimary();



	/***** Model's API *****/



	/**
	 * Finds all values in table.
	 * @return DibiFluent
	 */
	public function findAll();


	/**
	 * Find occurrences matching column by value.
	 * @param mixed $value
	 * @return DibiFluent
	 */
	public function find($value);


	/**
	 * Updates database row.
	 * @param int $id
	 * @param array $data
	 * @return DibiFluent
	 */
	public function update($id, array $data);


	/**
	 * Inserts data into table.
	 * @param array $data
	 * @return int
	 */
	public function insert(array $data);


	/**
	 * Deletes row(s) matching primary key.
	 * @param int $id
	 * @return DibiFluent
	 */
	public function delete($id);


	/**
	 * Fetch occurrences matching by value.
	 * @param mixed $value
	 * @param string $column
	 * @return DibiRow
	 */
	public function fetch($value);


	/**
	 * Fetch all values in table.
	 * @return array  of DibiRow
	 */
	public function fetchAll();
}