<?php
/**
 * FixtureTask Test case
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
 * @package       Cake.Test.Case.Console.Command.Task
 * @since         CakePHP(tm) v 1.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('ShellDispatcher', 'Console');
App::uses('Shell', 'Console');
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('ModelTask', 'Console/Command/Task');
App::uses('FixtureTask', 'Console/Command/Task');
App::uses('TemplateTask', 'Console/Command/Task');
App::uses('DbConfigTask', 'Console/Command/Task');

/**
 * FixtureTaskTest class
 *
 * @package       Cake.Test.Case.Console.Command.Task
 */
#[\AllowDynamicProperties]
class FixtureTaskTest extends CakeTestCase {

/**
 * fixtures
 *
 * @var array
 */
	public $fixtures = array('core.article', 'core.comment', 'core.datatype', 'core.binary_test', 'core.user');

/**
 * Whether backup global state for each test method or not
 *
 * @var bool
 */
	public $backupGlobals = false;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Task = $this->getMock('FixtureTask',
			array('in', 'err', 'createFile', '_stop', 'clear'),
			array($out, $out, $in)
		);
		$this->Task->Model = $this->getMock('ModelTask',
			array('in', 'out', 'err', 'createFile', 'getName', 'getTable', 'listAll'),
			array($out, $out, $in)
		);
		$this->Task->Template = new TemplateTask($out, $out, $in);
		$this->Task->DbConfig = $this->getMock('DbConfigTask', array(), array($out, $out, $in));
		$this->Task->Template->initialize();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Task);
	}

/**
 * test that initialize sets the path
 *
 * @return void
 */
	public function testConstruct() {
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$Task = new FixtureTask($out, $out, $in);
		$this->assertEquals(APP . 'Test' . DS . 'Fixture' . DS, $Task->path);
	}

/**
 * test import option array generation
 *
 * @return void
 */
	public function testImportOptionsSchemaRecords() {
		$this->Task->interactive = true;
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));
		$this->Task->expects($this->at(1))->method('in')->will($this->returnValue('y'));

		$result = $this->Task->importOptions('Article');
		$expected = array('schema' => 'Article', 'records' => true);
		$this->assertEquals($expected, $result);
	}

/**
 * test importOptions choosing nothing.
 *
 * @return void
 */
	public function testImportOptionsNothing() {
		$this->Task->interactive = true;
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('n'));
		$this->Task->expects($this->at(1))->method('in')->will($this->returnValue('n'));
		$this->Task->expects($this->at(2))->method('in')->will($this->returnValue('n'));

		$result = $this->Task->importOptions('Article');
		$expected = array();
		$this->assertEquals($expected, $result);
	}

/**
 * test importOptions with overwriting command line options.
 *
 * @return void
 */
	public function testImportOptionsWithCommandLineOptions() {
		$this->Task->params = array('schema' => true, 'records' => true);

		$result = $this->Task->importOptions('Article');
		$expected = array('schema' => 'Article', 'fromTable' => true);
		$this->assertEquals($expected, $result);
	}

/**
 * test importOptions with overwriting CLI options
 *
 * @return void
 */
	public function testImportOptionsWithCommandLineOptionsPlugin() {
		$this->Task->params = array('schema' => true, 'records' => true, 'plugin' => 'TestPlugin');

		$result = $this->Task->importOptions('Article');
		$expected = array('schema' => 'TestPlugin.Article', 'fromTable' => true);
		$this->assertEquals($expected, $result);
	}

/**
 * test importOptions with schema.
 *
 * @return void
 */
	public function testImportOptionsWithSchema() {
		$this->Task->interactive = true;
		$this->Task->params = array('schema' => true);
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('n'));
		$this->Task->expects($this->at(1))->method('in')->will($this->returnValue('n'));

		$result = $this->Task->importOptions('Article');
		$expected = array('schema' => 'Article');
		$this->assertEquals($expected, $result);
	}

/**
 * test importOptions with records.
 *
 * @return void
 */
	public function testImportOptionsWithRecords() {
		$this->Task->interactive = true;
		$this->Task->params = array('records' => true);
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('n'));

		$result = $this->Task->importOptions('Article');
		$expected = array('fromTable' => true);
		$this->assertEquals($expected, $result);
	}

/**
 * test importOptions choosing from Table.
 *
 * @return void
 */
	public function testImportOptionsTable() {
		$this->Task->interactive = true;
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('n'));
		$this->Task->expects($this->at(1))->method('in')->will($this->returnValue('n'));
		$this->Task->expects($this->at(2))->method('in')->will($this->returnValue('y'));
		$result = $this->Task->importOptions('Article');
		$expected = array('fromTable' => true);
		$this->assertEquals($expected, $result);
	}

/**
 * test generating a fixture with database conditions.
 *
 * @return void
 */
	public function testImportRecordsFromDatabaseWithConditionsPoo() {
		$this->Task->interactive = true;
		$this->Task->expects($this->at(0))->method('in')
			->will($this->returnValue('WHERE 1=1'));
		$this->Task->expects($this->at(1))->method('in')
			->with($this->anything(), $this->anything(), '3')
			->will($this->returnValue('2'));

		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';

		$result = $this->Task->bake('Article', false, array(
			'fromTable' => true, 'schema' => 'Article', 'records' => false
		));

		$this->assertContains('class ArticleFixture extends CakeTestFixture', $result);
		$this->assertContains('public $records', $result);
		$this->assertContains('public $import', $result);
		$this->assertContains("'title' => 'First Article'", $result, 'Missing import data');
		$this->assertContains('Second Article', $result, 'Missing import data');
	}

/**
 * test that connection gets set to the import options when a different connection is used.
 *
 * @return void
 */
	public function testImportOptionsAlternateConnection() {
		$this->Task->connection = 'test';
		$result = $this->Task->bake('Article', false, array('schema' => 'Article'));
		$this->assertContains("'connection' => 'test'", $result);
	}

/**
 * Ensure that fixture data doesn't get overly escaped.
 *
 * @return void
 */
	public function testImportRecordsNoEscaping() {
		$db = ConnectionManager::getDataSource('test');
		if ($db instanceof Sqlserver) {
			$this->markTestSkipped('This test does not run on SQLServer');
		}

		$Article = ClassRegistry::init('Article');
		$Article->updateAll(array('body' => "'Body \"value\"'"));

		$this->Task->interactive = true;
		$this->Task->expects($this->at(0))
			->method('in')
			->will($this->returnValue('WHERE 1=1 LIMIT 10'));

		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$result = $this->Task->bake('Article', false, array(
			'fromTable' => true,
			'schema' => 'Article',
			'records' => false
		));
		$this->assertContains("'body' => 'Body \"value\"'", $result, 'Data has bad escaping');
	}

/**
 * test that execute includes import options
 *
 * @return void
 */
	public function testExecuteWithImportSchema() {
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('article');
		$this->Task->params = array(
			'schema' => true,
			'records' => false,
		);
		$filename = '/my/path/ArticleFixture.php';

		$this->Task->expects($this->never())
			->method('in');

		$this->Task->expects($this->at(0))
			->method('createFile')
			->with($filename, $this->logicalAnd(
				$this->stringContains('class ArticleFixture'),
				$this->stringContains("\$import = array('model' => 'Article'")
			));

		$this->Task->execute();
	}

/**
 * test that execute includes import options
 *
 * @return void
 */
	public function testExecuteWithImportRecords() {
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('article');
		$this->Task->params = array(
			'schema' => true,
			'records' => true,
		);
		$filename = '/my/path/ArticleFixture.php';

		$this->Task->expects($this->never())
			->method('in');

		$this->Task->expects($this->at(0))
			->method('createFile')
			->with($filename, $this->logicalAnd(
				$this->stringContains('class ArticleFixture'),
				$this->stringContains("\$import = array('model' => 'Article', 'connection' => 'test')")
			));

		$this->Task->execute();
	}

/**
 * test that execute passes runs bake depending with named model.
 *
 * @return void
 */
	public function testExecuteWithNamedModel() {
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('article');
		$filename = '/my/path/ArticleFixture.php';

		$this->Task->expects($this->at(0))->method('createFile')
			->with($filename, $this->stringContains('class ArticleFixture'));

		$this->Task->execute();
	}

/**
 * test that execute runs all() when args[0] = all
 *
 * @return void
 */
	public function testExecuteIntoAll() {
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('all');
		$this->Task->Model->expects($this->any())
			->method('listAll')
			->will($this->returnValue(array('articles', 'comments')));

		$filename = '/my/path/ArticleFixture.php';
		$this->Task->expects($this->at(0))
			->method('createFile')
			->with($filename, $this->stringContains('class ArticleFixture'));

		$filename = '/my/path/CommentFixture.php';
		$this->Task->expects($this->at(1))
			->method('createFile')
			->with($filename, $this->stringContains('class CommentFixture'));

		$this->Task->execute();
	}

/**
 * test using all() with -count and -records
 *
 * @return void
 */
	public function testAllWithCountAndRecordsFlags() {
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('all');
		$this->Task->params = array('count' => 10, 'records' => true);

		$this->Task->Model->expects($this->any())->method('listAll')
			->will($this->returnValue(array('Articles', 'comments')));

		$filename = '/my/path/ArticleFixture.php';
		$this->Task->expects($this->at(0))->method('createFile')
			->with($filename, $this->stringContains("'title' => 'Third Article'"));

		$filename = '/my/path/CommentFixture.php';
		$this->Task->expects($this->at(1))->method('createFile')
			->with($filename, $this->stringContains("'comment' => 'First Comment for First Article'"));
		$this->Task->expects($this->exactly(2))->method('createFile');

		$this->Task->all();
	}

/**
 * test using all() with -schema
 *
 * @return void
 */
	public function testAllWithSchemaImport() {
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('all');
		$this->Task->params = array('schema' => true);

		$this->Task->Model->expects($this->any())->method('listAll')
			->will($this->returnValue(array('Articles', 'comments')));

		$filename = '/my/path/ArticleFixture.php';
		$this->Task->expects($this->at(0))->method('createFile')
			->with($filename, $this->stringContains('public $import = array(\'model\' => \'Article\''));

		$filename = '/my/path/CommentFixture.php';
		$this->Task->expects($this->at(1))->method('createFile')
			->with($filename, $this->stringContains('public $import = array(\'model\' => \'Comment\''));
		$this->Task->expects($this->exactly(2))->method('createFile');

		$this->Task->all();
	}

/**
 * test interactive mode of execute
 *
 * @return void
 */
	public function testExecuteInteractive() {
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';

		$this->Task->expects($this->any())->method('in')->will($this->returnValue('y'));
		$this->Task->Model->expects($this->any())->method('getName')->will($this->returnValue('Article'));
		$this->Task->Model->expects($this->any())->method('getTable')
			->with('Article')
			->will($this->returnValue('articles'));

		$filename = '/my/path/ArticleFixture.php';
		$this->Task->expects($this->once())->method('createFile')
			->with($filename, $this->stringContains('class ArticleFixture'));

		$this->Task->execute();
	}

/**
 * Test that bake works
 *
 * @return void
 */
	public function testBake() {
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';

		$result = $this->Task->bake('Article');
		$this->assertContains('class ArticleFixture extends CakeTestFixture', $result);
		$this->assertContains('public $fields', $result);
		$this->assertContains('public $records', $result);
		$this->assertNotContains('public $import', $result);

		$result = $this->Task->bake('Article', 'comments');
		$this->assertContains('class ArticleFixture extends CakeTestFixture', $result);
		$this->assertContains('public $table = \'comments\';', $result);
		$this->assertContains('public $fields = array(', $result);

		$result = $this->Task->bake('Article', 'comments', array('records' => true));
		$this->assertContains("public \$import = array('records' => true, 'connection' => 'test');", $result);
		$this->assertNotContains('public $records', $result);

		$result = $this->Task->bake('Article', 'comments', array('schema' => 'Article'));
		$this->assertContains("public \$import = array('model' => 'Article', 'connection' => 'test');", $result);
		$this->assertNotContains('public $fields', $result);

		$result = $this->Task->bake('Article', 'comments', array('schema' => 'Article', 'records' => true));
		$this->assertContains("public \$import = array('model' => 'Article', 'records' => true, 'connection' => 'test');", $result);
		$this->assertNotContains('public $fields', $result);
		$this->assertNotContains('public $records', $result);
	}

/**
 * test record generation with various integer, float and binary types
 *
 * @return void
 */
	public function testRecordGenerationForBinaryFloatAndIntegerTypes() {
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';

		$result = $this->Task->bake('Article', 'datatypes');
		$this->assertContains("'float_field' => 1", $result);
		$this->assertContains("'bool' => 1", $result);
		$this->assertContains("'tiny_int' => 1", $result);
		$this->assertContains("'small_int' => 1", $result);
		$this->assertContains("'huge_int' => 1", $result);

		$result = $this->Task->bake('Article', 'binary_tests');
		$this->assertContains("'data' => 'Lorem ipsum dolor sit amet'", $result);
	}

/**
 * Test that file generation includes headers and correct path for plugins.
 *
 * @return void
 */
	public function testGenerateFixtureFile() {
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$filename = '/my/path/ArticleFixture.php';

		$this->Task->expects($this->at(0))->method('createFile')
			->with($filename, $this->stringContains('ArticleFixture'));

		$this->Task->expects($this->at(1))->method('createFile')
			->with($filename, $this->stringContains('<?php'));

		$this->Task->generateFixtureFile('Article', array());
		$this->Task->generateFixtureFile('Article', array());
	}

/**
 * test generating files into plugins.
 *
 * @return void
 */
	public function testGeneratePluginFixtureFile() {
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->plugin = 'TestFixture';
		$filename = APP . 'Plugin' . DS . 'TestFixture' . DS . 'Test' . DS . 'Fixture' . DS . 'ArticleFixture.php';

		//fake plugin path
		CakePlugin::load('TestFixture', array('path' => APP . 'Plugin' . DS . 'TestFixture' . DS));
		$this->Task->expects($this->at(0))->method('createFile')
			->with($filename, $this->stringContains('class Article'));

		$this->Task->generateFixtureFile('Article', array());
		CakePlugin::unload();
	}

}
