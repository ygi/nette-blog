<?php


class MockBaseModel extends BaseModel
{
	/** @var string  table title */
	const TABLE = 'Mock';

	/** @var string  primary key column title */
	const PRIMARY = 'id';


	public function setConnection($connection) {
		parent::setConnection($connection);
	}

	public function getColumnNames() {
		return parent::getColumnNames();
	}

	public function prepare(& $data) {
		return parent::prepare($data);
	}

	public function complete($result) {
		return parent::complete($result);
	}

}