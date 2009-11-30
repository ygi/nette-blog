<?php

require_once 'PHPUnit/Framework.php';


/**
 * Test class for BaseModel.
 */
class BaseModelTest extends PHPUnit_Framework_TestCase {

	/** @var BaseModel */
	protected $object;

	/** @var array */
	protected $testValues = array(
		'id' => 5,
		'title' => 'fifth',
		'author' => 'Joel Doe',
		'isSingle' => TRUE,
		'salary' => 1515,
		'createdAt' => NULL,
	);


	public function __construct() {
		BaseModel::initialize();
		$this->object = new MockBaseModel();
	}


	public function __destruct() {
		$conn = $this->object->connection;
		$conn->query("DROP TABLE [Mock]");
		BaseModel::disconnect();
	}


	public function setUp() {
		$this->setUpTable();
		$this->setUpData();
	}


	private function setUpTable() {
		$conn = $this->object->connection;
		$conn->query("
			CREATE TABLE IF NOT EXISTS [Mock] (
				[id] INTEGER  NOT NULL PRIMARY KEY UNIQUE CHECK ([id] > 0),
				[title] VARCHAR(128)  NOT NULL,
				[author] VARCHAR(48)  NOT NULL,
				[isSingle] BOOLEAN  NOT NULL DEFAULT 1,
				[salary] INTEGER NOT NULL CHECK ([salary] > 0),
				[createdAt] TIMESTAMP  NULL DEFAULT CURRENT_TIMESTAMP
			)"
		);
	}


	private function setUpData() {
		$conn = $this->object->connection;
		$conn->query("DELETE FROM [Mock]");
		$conn->query("INSERT INTO [Mock] ([id], [title], [author], [isSingle], [salary]) VALUES (1, 'first', 'John Doe', 0, 1111)");
		$conn->query("INSERT INTO [Mock] ([id], [title], [author], [isSingle], [salary]) VALUES (2, 'second', 'Jane Doe', 1, 1212)");
		$conn->query("INSERT INTO [Mock] ([id], [title], [author], [isSingle], [salary]) VALUES (3, 'third', 'Jack Doe', 1, 1313)");
		$conn->query("INSERT INTO [Mock] ([id], [title], [author], [isSingle], [salary]) VALUES (4, 'fourth', 'Jimmy Doe', 0, 1414)");
	}


	public function testInitialize() {
		BaseModel::initialize();
		$conn = $this->object->connection;
		$this->assertTrue($conn->isConnected());

	}


	public function testDisconnect() {
		BaseModel::disconnect();
		$conn = $this->object->connection;
		$this->assertFalse($conn->isConnected());
	}


	public function testGetConnection() {
		$conn = $this->object->connection;
		$this->assertTrue($conn instanceof DibiConnection);
	}

	
	public function testSetConnection() {
		$tmp = new MockBaseModel;
		$conf = Environment::getConfig('database');
		$conn = new DibiConnection($conf);
		
		$tmp->setConnection($conn);
		$this->assertTrue($tmp->connection instanceof DibiConnection);
	}

	
    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testSetConnectionInvalidArgument() {
		$tmp = new MockBaseModel;
		$tmp->setConnection(array());
    }


    public function testGetTable() {
		$model = $this->object;
        $this->assertEquals('Mock', $model->getTable());
    }


    public function testGetPrimary() {
		$model = $this->object;
        $this->assertEquals('id', $model->getPrimary());
    }


	public function testGetColumnNames() {
		$model = $this->object;
		$fields = $model->getColumnNames();
		$this->assertType('array', $fields);
		$this->assertEquals(6, count($fields));
		$this->assertContains('id', $fields);
		$this->assertContains('isSingle', $fields);
		$this->assertContains('author', $fields);
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
		$arr['isSingle'] = TRUE;
		$this->assertNotEquals($arr, $obj);
	}


	public function testComplete() {
		$model = $this->object;
		$values = $this->testValues;
		$values['createdAt'] = new DateTime();
		$model->insert($values);

		$row = $model->fetch(5);

		$this->assertType('int', $row->id);
		$this->assertType('string', $row->author);

		$this->assertTrue(is_bool($row->isSingle) ||
			(is_int($row->isSingle) && in_array($row->isSingle, array(0,1), TRUE)));

		$this->assertType('string', $row->createdAt);
		$this->assertTrue('0000-00-00 00:00:00' !== $row->createdAt);
		$this->assertEquals($values['createdAt']->format('Y-m-d H:i:s'), $row->createdAt);
	}


    public function testFindAll() {
		$model = $this->object;
		
        $this->assertEquals('SELECT * FROM [Mock]', String::strip((string) $model->findAll()));
		$this->assertEquals(4, $model->findAll()->count());
    }


    public function testFind() {
		$model = $this->object;

		$fluent = $model->find(1);
        $this->assertEquals("SELECT * FROM [Mock] WHERE [id]=1", String::strip((string) $fluent));
		$row = $fluent->execute()->fetch();
		$this->assertEquals('first', $row->title);

		$fluent = $model->find('1');
        $this->assertEquals("SELECT * FROM [Mock] WHERE [id]='1'", String::strip((string) $fluent));
		$row = $fluent->execute()->fetch();
		$this->assertEquals('first', $row->title);

		$fluent = $model->find('third', 'title');
		$this->assertEquals("SELECT * FROM [Mock] WHERE [title]='third'", String::strip((string) $fluent));
		$row = $fluent->execute()->fetch();
		$this->assertEquals(3, $row->id);
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
		$this->assertEquals('first', $row->title);

		$row = $model->fetch('1');
		$this->assertEquals('first', $row->title);

		$row = $model->fetch('third', 'title');
		$this->assertEquals(3, $row->id);
	}


	public function testUpdate() {
		$model = $this->object;
		
		$affected = $model->update(1, array('author' => 'John Paul Jones'));
		$this->assertEquals(1, $affected);

		$result = $model->find(1)->fetch();
		$this->assertEquals('John Paul Jones', $result->author);
		

		$affected = $model->update(array(2,3,4), array('author' => 'John Paul Jones'));
		$this->assertEquals(3, $affected);

		$result = $model->find(2)->fetch();
		$this->assertEquals('John Paul Jones', $result->author);

		$result = $model->find(4)->fetch();
		$this->assertEquals('John Paul Jones', $result->author);
	}


	public function testInsert() {
		$model = $this->object;
		
		$last = $model->insert($this->testValues);
		$this->assertEquals(5, $last);
		$this->assertEquals(5, $model->findAll()->count());

		$result = $model->find(5)->fetch();
		$this->assertEquals('Joel Doe', $result->author);
		$this->assertEquals(1, $result->isSingle);
		$this->assertTrue((bool) $result->isSingle);
	}


	public function testInsertDuplicatePrimary() {
		$model = $this->object;
		$values = $this->testValues;
		$values[MockBaseModel::PRIMARY] = 1;

		$this->setExpectedException('DibiDriverException');
		$model->insert($values);
		$this->fail('An expected exception has not been raised.');
	}


	public function testInsertNegativePrimary() {
		$model = $this->object;
		$values = $this->testValues;
		$values[MockBaseModel::PRIMARY] = -5;

		$this->setExpectedException('DibiDriverException');
		$model->insert($values);
		$this->fail('An expected exception has not been raised.');
	}


	public function testInsertNegativeSalary() {
		$model = $this->object;
		$values = $this->testValues;
		$values['salary'] -= 10000000;

		$this->setExpectedException('DibiDriverException');
		$model->insert($values);
		$this->fail('An expected exception has not been raised.');
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


	public function testMagicFetch() {
		$model = $this->object;
		$model->update(array(2,3), array('author' => 'John Paul Jones'));

		$row = $model->fetchByAuthor('John Paul Jones');
		$this->assertTrue($row instanceof DibiRow);
		$this->assertEquals(2, $row->id);

		$rows = $model->fetchAllByAuthor('John Paul Jones');
		$this->assertTrue(is_array($rows));
		$this->assertEquals(2, count($rows));
		$this->assertTrue($rows[0] instanceof DibiRow);
		$this->assertTrue($rows[1] instanceof DibiRow);

		$rows = $model->fetchByAuthorAndIsSingleAndId('John Paul Jones', TRUE, 3);
		$this->assertTrue($row instanceof DibiRow);
		$this->assertEquals(3, $rows->id);
		$this->assertEquals(TRUE, (bool) $rows->isSingle);

		$rows = $model->fetchAllByAuthorAndIsSingleAndId('John Paul Jones', TRUE, 3);
		$this->assertTrue(is_array($rows));
		$this->assertEquals(1, count($rows));
		$this->assertTrue($rows[0] instanceof DibiRow);
		$this->assertEquals(3, $rows[0]->id);

		$pairs = $model->fetchPairsByIdAndAuthor();
		$this->assertTrue(is_array($pairs));
		$this->assertEquals(4, count($pairs));
		$this->assertEquals($pairs[1], 'John Doe');
		$this->assertEquals($pairs[2], 'John Paul Jones');
	}


	public function testMagicFind() {
		$model = $this->object;
		$model->update(array(2,3), array('author' => 'John Paul Jones'));
		
		$fluent = $model->findAllByIsSingle(TRUE);
		$this->assertEquals(2, $fluent->count());

		$fluent = $model->findAllByAuthorAndIsSingle('John Paul Jones', TRUE);
		$this->assertEquals(2, $fluent->count());

		$fluent = $model->findAllByAuthorAndIsSingleAndId('John Paul Jones', TRUE, 3);
		$this->assertEquals(1, $fluent->count());
	}


	public function testMagicFetchInvalidArguments() {
		$model = $this->object;
		
		$this->setExpectedException('InvalidArgumentException');
		$model->fetchAllByIsSingle(TRUE, TRUE, TRUE);
		$this->fail('An expected exception has not been raised.');
	}

	
	public function testMagicFetchInvalidMethodName() {
		$model = $this->object;

		$this->setExpectedException('MemberAccessException');
		$model->fetchInvalidMethod();
		$this->fail('An expected exception has not been raised.');
	}

}
