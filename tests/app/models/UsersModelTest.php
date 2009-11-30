<?php

require_once 'PHPUnit/Framework.php';


/**
 * Test class for UsersModel.
 */
class UsersModelTest extends PHPUnit_Framework_TestCase {

	/** @var UsersModel */
	protected $object;

	/** @var array */
	protected $testValues = array(
		'id' => 5,
		'login' => 'user5',
		'password' => 'demo',
		'username' => 'Jimmy Doe',
	);


	public function __construct() {
		BaseModel::initialize();
		$this->object = new MockUsersModel();
	}


	public function __destruct() {
		$conn = $this->object->connection;
		$conn->query("DROP TABLE [Users]");
		BaseModel::disconnect();
	}


	public function setUp() {
		$this->setUpTable();
		$this->setUpData();
	}


	private function setUpTable() {
		$conn = $this->object->connection;
		$conn->query("
			CREATE TABLE IF NOT EXISTS [Users] (
				[id] INTEGER  NOT NULL PRIMARY KEY UNIQUE CHECK ([id] > 0),
				[login] VARCHAR(64)  NOT NULL UNIQUE,
				[password] VARCHAR(128)  NOT NULL,
				[username] VARCHAR(64)  NOT NULL
			)"
		);
	}


	private function setUpData() {
		$conn = $this->object->connection;
		$conn->query("DELETE FROM [Users]");

		// password to all users: demo
		$conn->query("INSERT INTO [Users] VALUES (1, 'user1', '07fe835b1c25c1a2c986963f404a6615', 'John Doe')");
		$conn->query("INSERT INTO [Users] VALUES (2, 'user2', '07fe835b1c25c1a2c986963f404a6615', 'Jane Doe')");
		$conn->query("INSERT INTO [Users] VALUES (3, 'user3', '07fe835b1c25c1a2c986963f404a6615', 'Jack Doe')");
		$conn->query("INSERT INTO [Users] VALUES (4, 'user4', '07fe835b1c25c1a2c986963f404a6615', 'Jimmy Doe')");
	}


    public function testGetTable() {
		$model = $this->object;
        $this->assertEquals('Users', $model->getTable());
    }


    public function testGetPrimary() {
		$model = $this->object;
        $this->assertEquals('id', $model->getPrimary());
    }


	public function testPrepare() {
		$model = $this->object;

		$obj = new stdClass();
		$cnt = count((array) $obj);
		$arr = $model->prepare($obj);

		$this->assertType('array', $arr);
		$this->assertType('array', $obj);
		$this->assertEquals($cnt, count($arr));
		$this->assertEquals($cnt, count($obj));

		$this->assertEquals($arr, $obj);
		$arr['id'] = 888;
		$this->assertNotEquals($arr, $obj);
	}


	public function testComplete() {
		$model = $this->object;
		$values = $this->testValues;
		$model->insert($values);

		$row = $model->fetch(5);

		$this->assertType('int', $row->id);
		$this->assertType('string', $row->login);
		$this->assertType('string', $row->password);
		$this->assertType('string', $row->username);
	}


    public function testFindAll() {
		$model = $this->object;

        $this->assertEquals('SELECT * FROM [Users]', String::strip((string) $model->findAll()));
		$this->assertEquals(4, $model->findAll()->count());
    }


	public function testFetchAll() {
		$model = $this->object;

		$rows = $model->fetchAll();
		$this->assertTrue(is_array($rows));
		$this->assertEquals(4, count($rows));
		$this->assertTrue($rows[0] instanceof DibiRow);
	}


	public function testFetch() {
		$model = $this->object;

		$row = $model->fetch(1);
		$this->assertEquals('John Doe', $row->username);

		$row = $model->fetch('1');
		$this->assertEquals('John Doe', $row->username);

		$row = $model->fetch('Jack Doe', 'username');
		$this->assertEquals(3, $row->id);
	}


	public function testUpdate() {
		$model = $this->object;

		$affected = $model->update(1, array('username' => 'John Paul Jones'));
		$this->assertEquals(1, $affected);

		$result = $model->find(1)->fetch();
		$this->assertEquals('John Paul Jones', $result->username);


		$affected = $model->update(array(2,3,4), array('username' => 'John Paul Jones'));
		$this->assertEquals(3, $affected);

		$result = $model->find(2)->fetch();
		$this->assertEquals('John Paul Jones', $result->username);

		$result = $model->find(4)->fetch();
		$this->assertEquals('John Paul Jones', $result->username);
	}


	public function testInsertDuplicatePrimary() {
		$model = $this->object;
		$values = $this->testValues;
		$values[UsersModel::PRIMARY] = 1;

		$this->setExpectedException('DibiDriverException');
		$model->insert($values);
		$this->fail('An expected exception has not been raised.');
	}


	public function testInsertDuplicateLogin() {
		$model = $this->object;
		$values = $this->testValues;
		$values[UsersModel::PRIMARY] = 1;

		$this->setExpectedException('DibiDriverException');
		$model->insert($values);
		$this->fail('An expected exception has not been raised.');
	}


	public function testInsertNegativePrimary() {
		$model = $this->object;
		$values = $this->testValues;
		$values[UsersModel::PRIMARY] = -5;

		$this->setExpectedException('DibiDriverException');
		$model->insert($values);
		$this->fail('An expected exception has not been raised.');
	}


	public function testInsert() {
		$model = $this->object;

		$last = $model->insert($this->testValues);
		$this->assertEquals(5, $last);
		$this->assertEquals(5, $model->findAll()->count());

		$result = $model->find(5)->fetch();
		$this->assertEquals('Jimmy Doe', $result->username);
		$this->assertEquals('user5', $result->login);
		$this->assertEquals(md5(strrev('demo')), $result->password);
	}


	public function testDelete() {
		$model = $this->object;

		$affected = $model->delete(4);
		$this->assertEquals(1, $affected);
		$this->assertEquals(3, $model->findAll()->count());

		$affected = $model->delete(array(2,3));
		$this->assertEquals(2, $affected);
		$this->assertEquals(1, $model->findAll()->count());
	}


	public function testFlushTable() {
		$model = $this->object;

		$model->flushTable();
		$this->assertEquals(0, $model->findAll()->count());
	}


	public function testAuthenticateSuccess() {
		$model = $this->object;

		$credentials = array(IAuthenticator::USERNAME => 'user1', IAuthenticator::PASSWORD => 'demo');
		$identity = $model->authenticate($credentials);
		$this->assertTrue($identity instanceof Identity);
		$this->assertEquals('John Doe', $identity->name);
	}


	public function testAuthenticateNonExistingLogin() {
		$model = $this->object;

		try {
			$credentials = array(IAuthenticator::USERNAME => 'bad', IAuthenticator::PASSWORD => 'demo');
			$identity = $model->authenticate($credentials);
			$this->fail('An expected exception has not been raised.');
		} catch (AuthenticationException $e) {
			$this->assertEquals(IAuthenticator::IDENTITY_NOT_FOUND, $e->getCode());
		}
	}


	public function testAuthenticateWrongPassword() {
		$model = $this->object;

		try {
			$credentials = array(IAuthenticator::USERNAME => 'user1', IAuthenticator::PASSWORD => 'bad');
			$identity = $model->authenticate($credentials);
		} catch (AuthenticationException $e) {
			$this->assertEquals(IAuthenticator::INVALID_CREDENTIAL, $e->getCode());
		}
	}
}
