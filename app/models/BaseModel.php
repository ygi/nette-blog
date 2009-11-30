<?php

require_once __DIR__ . '/IModel.php';
require_once __DIR__ . '/../../libs/Nette/Object.php';

/**
 * Base abstract model class by pattern Table Data Gateway.
 *
 * @author     Roman Sklenář
 * @package    Blog
 */
abstract class BaseModel extends Object implements IModel {

	/** @var string  table name */
	const TABLE = '';

	/** @var string  primary key column name */
	const PRIMARY = '';
	
	/** @var DibiConnection */
	private static $connection;

	/** @var array  of function(IModel $sender) */
	public $onShutdown;


	public function __destruct() {
		$this->onShutdown($this);
	}


	public static function initialize() {
		$conf = Environment::getConfig('database');
		$connection = new DibiConnection($conf);

		if ($conf->profiler) {
			$profiler = is_numeric($conf->profiler) || is_bool($conf->profiler) ?
				new DibiProfiler : new $conf->profiler;
			$profiler->setFile(Environment::expand('%logDir%') . '/sql.log');
			$connection->setProfiler($profiler);
		}

		$connection->nativeQuery("PRAGMA foreign_keys = ON");
		self::$connection = $connection;
	}


	public static function disconnect() {
		self::$connection->disconnect();
	}



	/***** Public getters and setters *****/



	/**
	 * Gets database connection abstraction
	 * @return DibiConnection
	 */
	public function getConnection() {
		return self::$connection;
	}


	/**
	 * Sets database connection abstraction
	 * @param  DibiConnection $connection
	 * @throws DibiException
	 * @return void
	 */
	protected function setConnection(DibiConnection $connection) {
		self::$connection = $connection;
	}


	/**
	 * Gets model's table name
	 * @return string
	 */
	public function getTable() {
		return parent::getReflection()->getConstant('TABLE');
	}


	/**
	 * Gets model's primary key column name
	 * @return string
	 */
	public function getPrimary() {
		return parent::getReflection()->getConstant('PRIMARY');
	}


	/**
	 * Gets model's column names (specific for SQLite).
	 * @return array
	 */
	protected function getColumnNames() {
		$pragma = $this->getConnection()->query('PRAGMA table_info(%n)', $this->getTable())->fetchAssoc('name');
		return array_keys($pragma);
	}
	
	
	
	/***** User data pre/post processing. *****/



	/**
	 * User data pre-processing.
	 * @param  array|object
	 * @return array
	 */
	protected function prepare(& $data)
	{
		if (!is_object($data) && !is_array($data))
			throw new InvalidArgumentException('Dataset must be array or anonymous object.');

		if (is_object($data))
			$data = (array) $data;
/*
		// TODO: je treba aktualizovat jen chtene atributy, takto by se zaktualizovaly vsechny s hodnotou NULL
		$fields = $this->getConnection()->query('PRAGMA table_info(%n)', $this->getTable())->fetchAssoc('name');
		
		foreach ($this->getColumnNames() as $column)
			$data[$column] = ArrayTools::get($data, $column, NULL);
*/
		return $data;
	}


	/**
	 * User DibiResult post-processing.
	 * @param  DibiResult
	 * @return DibiResult
	 */
	protected function complete($result)
	{
		$result->detectTypes();
		return $result;
	}



	/***** IModel interface *****/



	/**
	 * Finds all values in table.
	 * @return DibiFluent
	 */
	public function findAll() {
		return $this->getConnection()->select('*')->from($this->getTable());
	}


	/**
	 * Find occurrences matching column by value.
	 * @param mixed $value
	 * @param string $column
	 * @return DibiFluent
	 */
	public function find($value, $column = NULL) {
		$column = $column === NULL ? $this->getPrimary() : $column;
		$m = is_int($value) ? '%i' : '%s';

		return $this->getConnection()->select('*')->from($this->getTable())
			->where("%n=$m", $column, $value);
	}


	/**
	 * Updates database row(s).
	 * @param int|array $id
	 * @param array $data
	 * @return int  number of updated rows
	 */
	public function update($id, array $data) {
		$id = is_array($id) ? $id : array($id);
		$this->getConnection()->update($this->getTable(), $this->prepare($data))
			->where("%n IN %l", $this->getPrimary(), $id)->execute();
		return $this->getConnection()->affectedRows();
	}


	/**
	 * Inserts data into table.
	 * @param array $data
	 * @return int  new primary key
	 */
	public function insert(array $data) {
		return $this->getConnection()->insert($this->getTable(), $this->prepare($data))->execute(dibi::IDENTIFIER);
	}


	/**
	 * Deletes row(s) matching primary key.
	 * @param int|array $id
	 * @return int  number of deleted rows
	 */
	public function delete($id) {
		$id = is_array($id) ? $id : array($id);
		$this->getConnection()->delete($this->getTable())
			->where("%n IN %l", $this->getPrimary(), $id)->execute();

		return $this->getConnection()->affectedRows();
	}


	/**
	 * Deletes all rows from table that model is representing
	 * @return void
	 */
	public function flushTable() {
		return $this->getConnection()->delete($this->getTable())->execute();
	}



	/***** Additional model's API *****/



	/**
	 * Fetch occurrences matching column by value.
	 * @param mixed $value
	 * @param string $column
	 * @return DibiRow
	 */
	public function fetch($value, $column = NULL)
	{
		return $this->complete($this->find($value, $column)->execute())->fetch();
	}


	/**
	 * Fetch all values in table.
	 * @return array of DibiRow
	 */
	public function fetchAll()
	{
		return $this->complete($this->findAll()->execute())->fetchAll();
	}


	/**
	 * Magic fetch/find.
	 * - $row = $model->fetchByUrl('about-us');
	 * - $arr = $model->fetchAllByCategoryIdAndVisibility(5, TRUE);
	 * - $arr = $model->fetchPairsByNameAndLogin();
	 * - $flu = $model->findAllByCategory(3);
	 *
	 * @param string $name
	 * @param array $args
	 * @return DibiFluent|DibiRow|array
	 */
	public function __call($name, $args)
	{
		if (strncmp($name, 'fetchBy', 7) === 0) { // single row
			$method = 'fetch';
			$name = substr($name, 7);

		} elseif (strncmp($name, 'fetchAllBy', 10) === 0) { // multi row
			$method = 'fetchAll';
			$name = substr($name, 10);

		} elseif (strncmp($name, 'findAllBy', 9) === 0) { // fluent
			$method = 'findAll';
			$name = substr($name, 9);

		} elseif (strncmp($name, 'fetchPairsBy', 12) === 0) { // pairs row
			$method = 'fetchPairs';
			$name = substr($name, 12);

		} else {
			return parent::__call($name, $args);
		}

		// ProductIdAndTitle -> array('productId', 'title')
		$parts = array_map('lcfirst', explode('And', $name));

		if (count($parts) !== count($args) && $method !== 'fetchPairs') {
			throw new InvalidArgumentException("Magic fetch/find expects " . count($parts) . " parameters, but " . count($args) . " was given.");
		}

		$fluent = $this->findAll();
		if ($method == 'fetchPairs') {
			return call_user_func_array(array($fluent, $method), $parts);
		}

		$fluent->where('%and', array_combine($parts, $args));
		return $method == 'findAll' ? $fluent : call_user_func(array($this->complete($fluent->execute()), $method));
	}

}