<?php

/**
 * Users model.
 *
 * @author     Roman Sklenář
 * @package    Blog
 */
class UsersModel extends BaseModel implements IAuthenticator
{
	/** @var string  table name */
	const TABLE = 'Users';

	/** @var string  primary key column name */
	const PRIMARY = 'id';


	/**
	 * User data pre-processing.
	 * @param  array|object
	 * @return array
	 */
	protected function prepare(& $data)
	{
		parent::prepare($data);

		if (isset($data['login']))
			$data['login'] = strtolower($data['login']);
	
		if (isset($data['password']))
			$data['password'] = md5(strrev($data['password']));
	
		return $data;
	}


	/**
	 * Performs an authentication
	 * @param  array
	 * @return void
	 * @throws AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		$this->prepare($credentials);

		$login = $credentials[self::USERNAME];
		$password = $credentials[self::PASSWORD];

		$user = $this->fetch($login, 'login');

		if (!$user) {
			throw new AuthenticationException("User '$login' not found.", self::IDENTITY_NOT_FOUND);
		}

		if ($user->password !== $password) {
			throw new AuthenticationException("Invalid password.", self::INVALID_CREDENTIAL);
		}

		unset($user->password);
		return new Identity($user->username, NULL, $user);
	}
}