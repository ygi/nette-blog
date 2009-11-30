<?php

require_once 'PHPUnit/Framework.php';


/**
 * Test class for ArticlesModel.
 */
class ArticlesModelTest extends PHPUnit_Framework_TestCase {

	/** @var ArticlesModel */
	protected $object;

	/** @var array */
	protected $testValues = array(
		'id' => 5,
		'author' => 'Jimmy Doe',
		'title' => 'Fifth blog post',
		'text' => 'Lorem ipsum...',
		'createdAt' => NULL,
	);


	public function __construct() {
		BaseModel::initialize();
		$this->object = new MockArticlesModel();
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
		$conn->query("INSERT INTO [Articles] VALUES (3, 'Jack Doe', 'Third blog post', 'Sir dolor amet...', NULL)");
		$conn->query("INSERT INTO [Articles] VALUES (4, 'Jimmy Doe', 'Fourth blog post', 'Sir dolor amet...', NULL)");

		$conn->query("INSERT INTO [Comments] VALUES (1, 1, 'John Doe', 'Lorem ipsum...', NULL)");
		$conn->query("INSERT INTO [Comments] VALUES (2, 1, 'Jane Doe', 'Lorem ipsum...', NULL)");
		$conn->query("INSERT INTO [Comments] VALUES (3, 2, 'Jack Doe', 'Lorem ipsum...', NULL)");
		$conn->query("INSERT INTO [Comments] VALUES (4, 2, 'Jimmy Doe', 'Lorem ipsum...', NULL)");
		$conn->query("INSERT INTO [Comments] VALUES (5, 3, 'Jane Doe', 'Lorem ipsum...', NULL)");
	}


    public function testGetTable() {
		$model = $this->object;
        $this->assertEquals('Articles', $model->getTable());
    }


    public function testGetPrimary() {
		$model = $this->object;
        $this->assertEquals('id', $model->getPrimary());
    }


    public function testFindAll() {
		$model = $this->object;

        $this->assertEquals('SELECT * FROM [Articles]', String::strip((string) $model->findAll()));
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


	public function testArticleExists() {
		$model = $this->object;
		
		$this->assertTrue($model->articleExists(1));
		$this->assertTrue($model->articleExists(2));
		$this->assertFalse($model->articleExists(7));
	}


	public function testHasComments() {
		$model = $this->object;

		$this->assertTrue($model->hasComments(1));
		$this->assertTrue($model->hasComments(2));
		$this->assertFalse($model->hasComments(4));
		$this->assertFalse($model->hasComments(888));
	}


	public function testFindAllComments() {
		$model = $this->object;

		$this->assertEquals(2, $model->findAllComments(1)->count());
		$this->assertEquals(2, $model->findAllComments(2)->count());
		$this->assertEquals(1, $model->findAllComments(3)->count());
		$this->assertEquals(0, $model->findAllComments(4)->count());
		$this->assertEquals(0, $model->findAllComments(888)->count());
	}


	public function testFetchAllComments() {
		$model = $this->object;

		$rows = $model->fetchAllComments(1);
		$this->assertTrue(is_array($rows));
		$this->assertEquals(2, count($rows));
		$this->assertTrue($rows[0] instanceof DibiRow);

		$rows = $model->fetchAllComments(3);
		$this->assertTrue(is_array($rows));
		$this->assertEquals(1, count($rows));
		$this->assertTrue($rows[0] instanceof DibiRow);

		$rows = $model->fetchAllComments(4);
		$this->assertTrue(empty($rows));
		$this->assertTrue(is_array($rows));
		$this->assertEquals(0, count($rows));

		$rows = $model->fetchAllComments(888);
		$this->assertTrue(empty($rows));
		$this->assertTrue(is_array($rows));
		$this->assertEquals(0, count($rows));
	}


	public function testDeleteAllComments() {
		$model = $this->object;
		$comments = new CommentsModel();

		$affected = $model->deleteAllComments(1);
		$this->assertEquals(2, $affected);
		$this->assertFalse($model->hasComments(1));
		$this->assertEquals(3, $comments->findAll()->count());

		$affected = $model->deleteAllComments(2);
		$this->assertEquals(2, $affected);
		$this->assertFalse($model->hasComments(2));
		$this->assertEquals(1, $comments->findAll()->count());

		$affected = $model->deleteAllComments(888);
		$this->assertEquals(0, $affected);
		$this->assertFalse($model->hasComments(888));
		$this->assertEquals(1, $comments->findAll()->count());
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
		$values[ArticlesModel::PRIMARY] = 1;

		$this->setExpectedException('DibiDriverException');
		$model->insert($values);
		$this->fail('An expected exception has not been raised.');
	}


	public function testInsertNegativePrimary() {
		$model = $this->object;
		$values = $this->testValues;
		$values[ArticlesModel::PRIMARY] = -5;

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
		$this->assertEquals('Jimmy Doe', $result->author);
		$this->assertEquals('Fifth blog post', $result->title);
	}


	public function testDelete() {
		$model = $this->object;
		$comments = new CommentsModel();

		$affected = $model->delete(1);
		$this->assertEquals(1, $affected);
		$this->assertFalse($model->hasComments(1));
		$this->assertEquals(3, $model->findAll()->count());
		$this->assertEquals(3, $comments->findAll()->count());

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
