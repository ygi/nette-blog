<?php

require_once 'PHPUnit/Framework.php';


/**
 * Test class for CommentsModel.
 */
class CommentsModelTest extends PHPUnit_Framework_TestCase {

	/** @var CommentsModel */
	protected $object;

	/** @var array */
	protected $testValues = array(
		'id' => 5,
		'articleId' => 1,
		'author' => 'Jimmy Doe',
		'text' => 'Lorem ipsum...',
		'createdAt' => NULL,
	);


	public function __construct() {
		BaseModel::initialize();
		$this->object = new CommentsModel();
	}


	public function __destruct() {
		$conn = $this->object->connection;
		$conn->query("DROP TABLE [Articles]");
		$conn->query("DROP TABLE [Comments]");
		BaseModel::disconnect();
	}


	public function setUp() {
		$this->setUpTable();
		$this->setUpData();
	}


	private function setUpTable() {
		$conn = $this->object->connection;
		$conn->query("
			CREATE TABLE IF NOT EXISTS [Articles] (
				[id] INTEGER  NOT NULL PRIMARY KEY UNIQUE CHECK ([id] > 0),
				[author] VARCHAR(64)  NOT NULL,
				[title] VARCHAR(128)  NOT NULL,
				[text] TEXT  NOT NULL,
				[createdAt] TIMESTAMP  NULL DEFAULT CURRENT_TIMESTAMP
			)"
		);

		$conn->query("
			CREATE TABLE IF NOT EXISTS [Comments] (
				[id] INTEGER  NOT NULL PRIMARY KEY UNIQUE CHECK ([id] > 0),
				[articleId] INTEGER  NOT NULL REFERENCES [Articles] ON DELETE CASCADE,
				[author] VARCHAR(64)  NOT NULL,
				[text] TEXT  NOT NULL,
				[createdAt] TIMESTAMP  NULL DEFAULT CURRENT_TIMESTAMP
			)"
		);
	}


	private function setUpData() {
		$conn = $this->object->connection;
		$conn->query("DELETE FROM [Articles]");
		$conn->query("DELETE FROM [Comments]");

		$conn->query("INSERT INTO [Articles] VALUES (1, 'John Doe', 'First blog post', 'Lorem ipsum...', NULL)");
		$conn->query("INSERT INTO [Articles] VALUES (2, 'Jane Doe', 'Second blog post', 'Sir dolor amet...', NULL)");
		
		$conn->query("INSERT INTO [Comments] VALUES (1, 1, 'John Doe', 'Lorem ipsum...', NULL)");
		$conn->query("INSERT INTO [Comments] VALUES (2, 1, 'Jane Doe', 'Lorem ipsum...', NULL)");
		$conn->query("INSERT INTO [Comments] VALUES (3, 2, 'Jack Doe', 'Lorem ipsum...', NULL)");
		$conn->query("INSERT INTO [Comments] VALUES (4, 2, 'Jimmy Doe', 'Lorem ipsum...', NULL)");
	}


    public function testGetTable() {
		$model = $this->object;
        $this->assertEquals('Comments', $model->getTable());
    }


    public function testGetPrimary() {
		$model = $this->object;
        $this->assertEquals('id', $model->getPrimary());
    }


    public function testFindAll() {
		$model = $this->object;

        $this->assertEquals('SELECT * FROM [Comments]', String::strip((string) $model->findAll()));
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
		$this->assertEquals('John Doe', $row->author);

		$row = $model->fetch('1');
		$this->assertEquals('John Doe', $row->author);

		$row = $model->fetch('Jack Doe', 'author');
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


	public function testInsertDuplicatePrimary() {
		$model = $this->object;
		$values = $this->testValues;
		$values[CommentsModel::PRIMARY] = 1;

		$this->setExpectedException('DibiDriverException');
		$model->insert($values);
		$this->fail('An expected exception has not been raised.');
	}


	public function testInsertNegativePrimary() {
		$model = $this->object;
		$values = $this->testValues;
		$values[CommentsModel::PRIMARY] = -5;

		$this->setExpectedException('DibiDriverException');
		$model->insert($values);
		$this->fail('An expected exception has not been raised.');
	}


	public function testInsertToNonExistingArticle() {
		$model = $this->object;
		$values = $this->testValues;
		$values['articleId'] = 888;

		$this->setExpectedException('InvalidStateException');
		$model->insert($values);
		$this->fail('An expected exception has not been raised.');
	}


	public function testInsert() {
		$model = $this->object;

		$last = $model->insert($this->testValues);
		$this->assertEquals(5, $last);
		$this->assertEquals(5, $model->findAll()->count());

		$result = $model->find(5)->fetch();
		$this->assertEquals('Jimmy Doe', $result->author);
		$this->assertEquals(1, $result->articleId);
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

}
