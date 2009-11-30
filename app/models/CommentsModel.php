<?php

/**
 * Comments model.
 *
 * @author     Roman Sklenář
 * @package    Blog
 */
class CommentsModel extends BaseModel
{
	/** @var string  table name */
	const TABLE = 'Comments';

	/** @var string  primary key column name */
	const PRIMARY = 'id';


	/**
	 * Inserts data into table.
	 * @param array $data
	 * @return int  new primary key
	 */
	public function insert(array $data) {
		// SQLite3 < 3.6.19	missing CONSTRAINT FOREIGN KEY work-around
		// for full CONSTRAINT support in PHP 5.2.* use php_pdo_sqlite.so from PHP 5.3.1
		$pragma = $this->getConnection()->nativeQuery('PRAGMA foreign_keys')->fetchSingle();
		if (!$pragma) {
			$articles = new ArticlesModel();
			if (!$articles->articleExists($data['articleId'])) {
				throw new InvalidStateException("Trying to reference to non existing Article");
			}
		}
		return parent::insert($data);
	}
}