<?php
/**
 * DboSqliteTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Model.Datasource.Database
 * @since         CakePHP(tm) v 1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('Sqlite', 'Model/Datasource/Database');

require_once dirname(dirname(dirname(__FILE__))) . DS . 'models.php';

/**
 * DboSqliteTestDb class
 *
 * @package       Cake.Test.Case.Model.Datasource.Database
 */
#[\AllowDynamicProperties]
class DboSqliteTestDb extends Sqlite {

/**
 * simulated property
 *
 * @var array
 */
	public $simulated = array();

/**
 * execute method
 *
 * @param mixed $sql
 * @return void
 */
	protected function _execute($sql, $params = array(), $prepareOptions = array()) {
		$this->simulated[] = $sql;
		return null;
	}

/**
 * getLastQuery method
 *
 * @return void
 */
	public function getLastQuery() {
		return $this->simulated[count($this->simulated) - 1];
	}

}

/**
 * DboSqliteTest class
 *
 * @package       Cake.Test.Case.Model.Datasource.Database
 */
#[\AllowDynamicProperties]
class SqliteTest extends CakeTestCase {

/**
 * Do not automatically load fixtures for each test, they will be loaded manually using CakeTestCase::loadFixtures
 *
 * @var bool
 */
	public $autoFixtures = false;

/**
 * Fixtures
 *
 * @var object
 */
	public $fixtures = array('core.user', 'core.uuid', 'core.datatype');

/**
 * Actual DB connection used in testing
 *
 * @var DboSource
 */
	public $Dbo = null;

/**
 * Sets up a Dbo class instance for testing
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Cache.disable', true);
		$this->Dbo = ConnectionManager::getDataSource('test');
		if (!$this->Dbo instanceof Sqlite) {
			$this->markTestSkipped('The Sqlite extension is not available.');
		}
	}

/**
 * Sets up a Dbo class instance for testing
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Configure::write('Cache.disable', false);
	}

/**
 * Tests that SELECT queries from DboSqlite::listSources() are not cached
 *
 * @return void
 */
	public function testTableListCacheDisabling() {
		$this->assertFalse(in_array('foo_test', $this->Dbo->listSources()));

		$this->Dbo->query('CREATE TABLE foo_test (test VARCHAR(255))');
		$this->assertTrue(in_array('foo_test', $this->Dbo->listSources()));

		$this->Dbo->cacheSources = false;
		$this->Dbo->query('DROP TABLE foo_test');
		$this->assertFalse(in_array('foo_test', $this->Dbo->listSources()));
	}

/**
 * test Index introspection.
 *
 * @return void
 */
	public function testIndex() {
		$name = $this->Dbo->fullTableName('with_a_key', false, false);
		$this->Dbo->query('CREATE TABLE ' . $name . ' ("id" int(11) PRIMARY KEY, "bool" int(1), "small_char" varchar(50), "description" varchar(40) );');
		$this->Dbo->query('CREATE INDEX pointless_bool ON ' . $name . '("bool")');
		$this->Dbo->query('CREATE UNIQUE INDEX char_index ON ' . $name . '("small_char")');
		$expected = array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'pointless_bool' => array('column' => 'bool', 'unique' => 0),
			'char_index' => array('column' => 'small_char', 'unique' => 1),

		);
		$result = $this->Dbo->index($name);
		$this->assertEquals($expected, $result);
		$this->Dbo->query('DROP TABLE ' . $name);

		$this->Dbo->query('CREATE TABLE ' . $name . ' ("id" int(11) PRIMARY KEY, "bool" int(1), "small_char" varchar(50), "description" varchar(40) );');
		$this->Dbo->query('CREATE UNIQUE INDEX multi_col ON ' . $name . '("small_char", "bool")');
		$expected = array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'multi_col' => array('column' => array('small_char', 'bool'), 'unique' => 1),
		);
		$result = $this->Dbo->index($name);
		$this->assertEquals($expected, $result);
		$this->Dbo->query('DROP TABLE ' . $name);
	}

/**
 * Tests that cached table descriptions are saved under the sanitized key name
 *
 * @return void
 */
	public function testCacheKeyName() {
		Configure::write('Cache.disable', false);

		$dbName = 'db' . mt_rand() . '$(*%&).db';
		$this->assertFalse(file_exists(TMP . $dbName));

		try {
			$db = new Sqlite(array_merge($this->Dbo->config, array('database' => TMP . $dbName)));
		} catch (MissingConnectionException $e) {
			// This might be caused by NTFS file systems, where '*' is a forbidden character. Repeat without this character.
			$dbName = str_replace('*', '', $dbName);
			$db = new Sqlite(array_merge($this->Dbo->config, array('database' => TMP . $dbName)));
		}
		$this->assertTrue(file_exists(TMP . $dbName));

		$db->execute("CREATE TABLE test_list (id VARCHAR(255));");

		$db->cacheSources = true;
		$this->assertEquals(array('test_list'), $db->listSources());
		$db->cacheSources = false;

		$fileName = '_' . preg_replace('/[^A-Za-z0-9_\-+]/', '_', TMP . $dbName) . '_list';

		$result = Cache::read($fileName, '_cake_model_');
		$this->assertEquals(array('test_list'), $result);

		Cache::delete($fileName, '_cake_model_');
		Configure::write('Cache.disable', true);
	}

/**
 * test building columns with SQLite
 *
 * @return void
 */
	public function testBuildColumn() {
		$data = array(
			'name' => 'int_field',
			'type' => 'integer',
			'null' => false,
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '"int_field" integer NOT NULL';
		$this->assertEquals($expected, $result);

		$data = array(
			'name' => 'name',
			'type' => 'string',
			'length' => 20,
			'null' => false,
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '"name" varchar(20) NOT NULL';
		$this->assertEquals($expected, $result);

		$data = array(
			'name' => 'testName',
			'type' => 'string',
			'length' => 20,
			'default' => null,
			'null' => true,
			'collate' => 'NOCASE'
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '"testName" varchar(20) DEFAULT NULL COLLATE NOCASE';
		$this->assertEquals($expected, $result);

		$data = array(
			'name' => 'testName',
			'type' => 'string',
			'length' => 20,
			'default' => 'test-value',
			'null' => false,
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '"testName" varchar(20) DEFAULT \'test-value\' NOT NULL';
		$this->assertEquals($expected, $result);

		$data = array(
			'name' => 'testName',
			'type' => 'integer',
			'length' => 10,
			'default' => 10,
			'null' => false,
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '"testName" integer(10) DEFAULT 10 NOT NULL';
		$this->assertEquals($expected, $result);

		$data = array(
			'name' => 'testName',
			'type' => 'integer',
			'length' => 10,
			'default' => 10,
			'null' => false,
			'collate' => 'BADVALUE'
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '"testName" integer(10) DEFAULT 10 NOT NULL';
		$this->assertEquals($expected, $result);

		$data = array(
			'name' => 'testName',
			'type' => 'smallinteger',
			'length' => 6,
			'default' => 6,
			'null' => false,
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '"testName" smallint(6) DEFAULT 6 NOT NULL';
		$this->assertEquals($expected, $result);

		$data = array(
			'name' => 'testName',
			'type' => 'tinyinteger',
			'length' => 4,
			'default' => 4,
			'null' => false,
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '"testName" tinyint(4) DEFAULT 4 NOT NULL';
		$this->assertEquals($expected, $result);

		$data = array(
			'name' => 'huge',
			'type' => 'biginteger',
			'length' => 20,
			'null' => false,
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '"huge" bigint(20) NOT NULL';
		$this->assertEquals($expected, $result);

		$data = array(
			'name' => 'id',
			'type' => 'biginteger',
			'length' => 20,
			'null' => false,
			'key' => 'primary',
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '"id" bigint(20) NOT NULL PRIMARY KEY';
		$this->assertEquals($expected, $result);
	}

/**
 * test describe() and normal results.
 *
 * @return void
 */
	public function testDescribe() {
		$this->loadFixtures('User');
		$Model = new Model(array(
			'name' => 'User',
			'ds' => 'test',
			'table' => 'users'
		));

		$this->Dbo->cacheSources = true;
		Configure::write('Cache.disable', false);

		$result = $this->Dbo->describe($Model);
		$expected = array(
			'id' => array(
				'type' => 'integer',
				'key' => 'primary',
				'null' => false,
				'default' => null,
				'length' => 11
			),
			'user' => array(
				'type' => 'string',
				'length' => 255,
				'null' => true,
				'default' => null
			),
			'password' => array(
				'type' => 'string',
				'length' => 255,
				'null' => true,
				'default' => null
			),
			'created' => array(
				'type' => 'datetime',
				'null' => true,
				'default' => null,
				'length' => null,
			),
			'updated' => array(
				'type' => 'datetime',
				'null' => true,
				'default' => null,
				'length' => null,
			)
		);
		$this->assertEquals($expected, $result);

		$result = $this->Dbo->describe($Model->useTable);
		$this->assertEquals($expected, $result);

		$result = Cache::read('test_users', '_cake_model_');
		$this->assertEquals($expected, $result);
	}

/**
 * Test that datatypes are reflected
 *
 * @return void
 */
	public function testDatatypes() {
		$this->loadFixtures('Datatype');
		$Model = new Model(array(
			'name' => 'Datatype',
			'ds' => 'test',
			'table' => 'datatypes'
		));
		$result = $this->Dbo->describe($Model);
		$expected = array(
			'id' => array(
				'type' => 'integer',
				'null' => false,
				'default' => '',
				'length' => 11,
				'key' => 'primary',
			),
			'float_field' => array(
				'type' => 'float',
				'null' => false,
				'default' => '',
				'length' => '5,2',
			),
			'decimal_field' => array(
				'type' => 'decimal',
				'null' => true,
				'default' => '0.000',
				'length' => '6,3',
			),
			'huge_int' => array(
				'type' => 'biginteger',
				'null' => true,
				'default' => null,
				'length' => 20,
			),
			'normal_int' => array(
				'type' => 'integer',
				'null' => true,
				'default' => null,
				'length' => null
			),
			'small_int' => array(
				'type' => 'smallinteger',
				'null' => true,
				'default' => null,
				'length' => null
			),
			'tiny_int' => array(
				'type' => 'tinyinteger',
				'null' => true,
				'default' => null,
				'length' => null
			),
			'bool' => array(
				'type' => 'boolean',
				'null' => false,
				'default' => '0',
				'length' => null
			),
		);
		$this->assertSame($expected, $result);
	}

/**
 * test that describe does not corrupt UUID primary keys
 *
 * @return void
 */
	public function testDescribeWithUuidPrimaryKey() {
		$tableName = 'uuid_tests';
		$this->Dbo->query("CREATE TABLE {$tableName} (id VARCHAR(36) PRIMARY KEY, name VARCHAR, created DATETIME, modified DATETIME)");
		$Model = new Model(array('name' => 'UuidTest', 'ds' => 'test', 'table' => 'uuid_tests'));
		$result = $this->Dbo->describe($Model);
		$expected = array(
			'type' => 'string',
			'length' => 36,
			'null' => false,
			'default' => null,
			'key' => 'primary',
		);
		$this->assertEquals($expected, $result['id']);
		$this->Dbo->query('DROP TABLE ' . $tableName);

		$tableName = 'uuid_tests';
		$this->Dbo->query("CREATE TABLE {$tableName} (id CHAR(36) PRIMARY KEY, name VARCHAR, created DATETIME, modified DATETIME)");
		$Model = new Model(array('name' => 'UuidTest', 'ds' => 'test', 'table' => 'uuid_tests'));
		$result = $this->Dbo->describe($Model);
		$expected = array(
			'type' => 'string',
			'length' => 36,
			'null' => false,
			'default' => null,
			'key' => 'primary',
		);
		$this->assertEquals($expected, $result['id']);
		$this->Dbo->query('DROP TABLE ' . $tableName);
	}

/**
 * Test that describe ignores `default current_timestamp` in timestamp columns.
 *
 * @return void
 */
	public function testDescribeHandleCurrentTimestamp() {
		$name = $this->Dbo->fullTableName('timestamp_default_values');
		$sql = <<<SQL
CREATE TABLE $name (
	id INT NOT NULL,
	phone VARCHAR(10),
	limit_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id)
);
SQL;
		$this->Dbo->execute($sql);
		$model = new Model(array(
			'table' => 'timestamp_default_values',
			'ds' => 'test',
			'alias' => 'TimestampDefaultValue'
		));
		$result = $this->Dbo->describe($model);
		$this->Dbo->execute('DROP TABLE ' . $name);

		$this->assertNull($result['limit_date']['default']);

		$schema = new CakeSchema(array(
			'connection' => 'test',
			'testdescribes' => $result
		));
		$result = $this->Dbo->createSchema($schema);
		$this->assertContains('"limit_date" timestamp NOT NULL', $result);
	}

/**
 * Test virtualFields with functions.
 *
 * @return void
 */
	public function testVirtualFieldWithFunction() {
		$this->loadFixtures('User');
		$User = ClassRegistry::init('User');
		$User->virtualFields = array('name' => 'SUBSTR(User.user, 5, LENGTH(User.user) - 4)');

		$result = $User->find('first', array(
			'conditions' => array('User.user' => 'garrett')
		));
		$this->assertEquals('ett', $result['User']['name']);
	}

/**
 * Test that records can be inserted with UUID primary keys, and
 * that the primary key is not blank
 *
 * @return void
 */
	public function testUuidPrimaryKeyInsertion() {
		$this->loadFixtures('Uuid');
		$Model = ClassRegistry::init('Uuid');

		$data = array(
			'title' => 'A UUID should work',
			'count' => 10
		);
		$Model->create($data);
		$this->assertTrue((bool)$Model->save());
		$result = $Model->read();

		$this->assertEquals($data['title'], $result['Uuid']['title']);
		$this->assertTrue(Validation::uuid($result['Uuid']['id']), 'Not a UUID');
	}

/**
 * Test nested transaction
 *
 * @return void
 */
	public function testNestedTransaction() {
		$this->Dbo->useNestedTransactions = true;
		$this->skipIf($this->Dbo->nestedTransactionSupported() === false, 'The Sqlite version do not support nested transaction');

		$this->loadFixtures('User');
		$model = new User();
		$model->hasOne = $model->hasMany = $model->belongsTo = $model->hasAndBelongsToMany = array();
		$model->cacheQueries = false;
		$this->Dbo->cacheMethods = false;

		$this->assertTrue($this->Dbo->begin());
		$this->assertNotEmpty($model->read(null, 1));

		$this->assertTrue($this->Dbo->begin());
		$this->assertTrue($model->delete(1));
		$this->assertEmpty($model->read(null, 1));
		$this->assertTrue($this->Dbo->rollback());
		$this->assertNotEmpty($model->read(null, 1));

		$this->assertTrue($this->Dbo->begin());
		$this->assertTrue($model->delete(1));
		$this->assertEmpty($model->read(null, 1));
		$this->assertTrue($this->Dbo->commit());
		$this->assertEmpty($model->read(null, 1));

		$this->assertTrue($this->Dbo->rollback());
		$this->assertNotEmpty($model->read(null, 1));
	}

/**
 * Test the limit function.
 *
 * @return void
 */
	public function testLimit() {
		$db = $this->Dbo;

		$result = $db->limit('0');
		$this->assertNull($result);

		$result = $db->limit('10');
		$this->assertEquals(' LIMIT 10', $result);

		$result = $db->limit('FARTS', 'BOOGERS');
		$this->assertEquals(' LIMIT 0 OFFSET 0', $result);

		$result = $db->limit(20, 10);
		$this->assertEquals(' LIMIT 20 OFFSET 10', $result);

		$result = $db->limit(10, 300000000000000000000000000000);
		$scientificNotation = sprintf('%.1E', 300000000000000000000000000000);
		$this->assertNotContains($scientificNotation, $result);
	}

/**
 * Test that fields are parsed out in a reasonable fashion.
 *
 * @return void
 */
	public function testFetchRowColumnParsing() {
		$this->loadFixtures('User');
		$sql = 'SELECT "User"."id", "User"."user", "User"."password", "User"."created", (1 + 1) AS "two" ' .
			'FROM "users" AS "User" WHERE ' .
			'"User"."id" IN (SELECT MAX("id") FROM "users") ' .
			'OR "User.id" IN (5, 6, 7, 8)';
		$result = $this->Dbo->fetchRow($sql);

		$expected = array(
			'User' => array(
				'id' => 4,
				'user' => 'garrett',
				'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				'created' => '2007-03-17 01:22:23'
			),
			0 => array(
				'two' => 2
			)
		);
		$this->assertEquals($expected, $result);

		$sql = 'SELECT "User"."id", "User"."user" ' .
			'FROM "users" AS "User" WHERE "User"."id" = 4 ' .
			'UNION ' .
			'SELECT "User"."id", "User"."user" ' .
			'FROM "users" AS "User" WHERE "User"."id" = 3';
		$result = $this->Dbo->fetchRow($sql);

		$expected = array(
			'User' => array(
				'id' => 3,
				'user' => 'larry',
			),
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test parsing more complex field names.
 *
 * @return void
 */
	public function testFetchColumnRowParsingMoreComplex() {
		$this->loadFixtures('User');
		$sql = 'SELECT
			COUNT(*) AS User__count,
			COUNT(CASE id WHEN 2 THEN 1 ELSE NULL END) as User__case,
			AVG(CAST("User"."id" AS BIGINT)) AS User__bigint
			FROM "users" AS "User"
			WHERE "User"."id" > 0';
		$result = $this->Dbo->fetchRow($sql);

		$expected = array(
			'0' => array(
				'User__count' => '4',
				'User__case' => '1',
				'User__bigint' => '2.5',
			),
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test Sqlite Datasource doesn't support locking hint
 *
 * @return void
 */
	public function testBuildStatementWithoutLockingHint() {
		$model = new TestModel();
		$sql = $this->Dbo->buildStatement(
			array(
				'fields' => array('id'),
				'table' => 'users',
				'alias' => 'User',
				'order' => array('id'),
				'limit' => 1,
				'lock' => true,
			),
			$model
		);
		$expected = 'SELECT id FROM users AS "User"   WHERE 1 = 1   ORDER BY "id" ASC  LIMIT 1';
		$this->assertEquals($expected, $sql);
	}
}
