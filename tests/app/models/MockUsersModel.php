<?php


class MockUsersModel extends UsersModel
{
	public function prepare(& $data) {
		return parent::prepare($data);
	}

	public function complete($result) {
		return parent::complete($result);
	}

}