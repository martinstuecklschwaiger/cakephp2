<?php
/**
 * PaginatorComponentTest file
 *
 * Series of tests for paginator component.
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Controller.Component
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');
App::uses('PaginatorComponent', 'Controller/Component');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');

require_once dirname(dirname(dirname(__FILE__))) . DS . 'Model' . DS . 'models.php';

/**
 * PaginatorTestController class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class PaginatorTestController extends Controller {

/**
 * components property
 *
 * @var array
 */
	public $components = array('Paginator');
}

/**
 * PaginatorControllerPost class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
#[\AllowDynamicProperties]
class PaginatorControllerPost extends CakeTestModel {

/**
 * useTable property
 *
 * @var string
 */
	public $useTable = 'posts';

/**
 * invalidFields property
 *
 * @var array
 */
	public $invalidFields = array('name' => 'error_msg');

/**
 * lastQueries property
 *
 * @var array
 */
	public $lastQueries = array();

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('PaginatorAuthor' => array('foreignKey' => 'author_id'));

/**
 * beforeFind method
 *
 * @param mixed $query
 * @return void
 */
	public function beforeFind($query) {
		array_unshift($this->lastQueries, $query);
	}

/**
 * find method
 *
 * @param mixed $type
 * @param array $options
 * @return void
 */
	public function find($conditions = null, $fields = array(), $order = null, $recursive = null) {
		if ($conditions === 'popular') {
			$conditions = array($this->name . '.' . $this->primaryKey . ' > ' => '1');
			$options = Hash::merge($fields, compact('conditions'));
			return parent::find('all', $options);
		}
		return parent::find($conditions, $fields);
	}

}

/**
 * ControllerPaginateModel class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
#[\AllowDynamicProperties]
class ControllerPaginateModel extends CakeTestModel {

/**
 * useTable property
 *
 * @var string
 */
	public $useTable = 'comments';

/**
 * paginate method
 *
 * @return array
 */
	public function paginate($conditions, $fields, $order, $limit, $page, $recursive, $extra) {
		$this->extra = $extra;
		return array(true);
	}

/**
 * paginateCount
 *
 * @return void
 */
	public function paginateCount($conditions, $recursive, $extra) {
		$this->extraCount = $extra;
	}

}

/**
 * PaginatorControllerComment class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
#[\AllowDynamicProperties]
class PaginatorControllerComment extends CakeTestModel {

/**
 * name property
 *
 * @var string
 */
	public $name = 'Comment';

/**
 * useTable property
 *
 * @var string
 */
	public $useTable = 'comments';

/**
 * alias property
 *
 * @var string
 */
	public $alias = 'PaginatorControllerComment';
}

/**
 * PaginatorAuthor class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
#[\AllowDynamicProperties]
class PaginatorAuthor extends CakeTestModel {

/**
 * useTable property
 *
 * @var string
 */
	public $useTable = 'authors';

/**
 * alias property
 *
 * @var string
 */
	public $virtualFields = array(
		'joined_offset' => 'PaginatorAuthor.id + 1'
	);

}

/**
 * PaginatorCustomPost class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
#[\AllowDynamicProperties]
class PaginatorCustomPost extends CakeTestModel {

/**
 * useTable property
 *
 * @var string
 */
	public $useTable = 'posts';

/**
 * belongsTo property
 *
 * @var string
 */
	public $belongsTo = array('Author');

/**
 * findMethods property
 *
 * @var array
 */
	public $findMethods = array(
		'published' => true,
		'totals' => true,
		'totalsOperation' => true
	);

/**
 * _findPublished custom find
 *
 * @return array
 */
	protected function _findPublished($state, $query, $results = array()) {
		if ($state === 'before') {
			$query['conditions']['published'] = 'Y';
			return $query;
		}
		return $results;
	}

/**
 * _findTotals custom find
 *
 * @return array
 */
	protected function _findTotals($state, $query, $results = array()) {
		if ($state === 'before') {
			$query['fields'] = array('author_id');
			$this->virtualFields['total_posts'] = "COUNT({$this->alias}.id)";
			$query['fields'][] = 'total_posts';
			$query['group'] = array('author_id');
			$query['order'] = array('author_id' => 'ASC');
			return $query;
		}
		$this->virtualFields = array();
		return $results;
	}

/**
 * _findTotalsOperation custom find
 *
 * @return array
 */
	protected function _findTotalsOperation($state, $query, $results = array()) {
		if ($state === 'before') {
			if (!empty($query['operation']) && $query['operation'] === 'count') {
				unset($query['limit']);
				$query['recursive'] = -1;
				$query['fields'] = array('COUNT(DISTINCT author_id) AS count');
				return $query;
			}
			$query['recursive'] = 0;
			$query['callbacks'] = 'before';
			$query['fields'] = array('author_id', 'Author.user');
			$this->virtualFields['total_posts'] = "COUNT({$this->alias}.id)";
			$query['fields'][] = 'total_posts';
			$query['group'] = array('author_id', 'Author.user');
			$query['order'] = array('author_id' => 'ASC');
			return $query;
		}
		$this->virtualFields = array();
		return $results;
	}

}

#[\AllowDynamicProperties]
class PaginatorComponentTest extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array('core.post', 'core.comment', 'core.author',
			'core.translated_article', 'core.translate_article', 'core.user');

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->request = new CakeRequest('controller_posts/index');
		$this->request->params['pass'] = $this->request->params['named'] = array();
		$this->Controller = new Controller($this->request);
		$this->Paginator = new PaginatorComponent($this->getMock('ComponentCollection'), array());
		$this->Paginator->Controller = $this->Controller;
		$this->Controller->Post = $this->getMock('Model');
		$this->Controller->Post->alias = 'Post';
	}

/**
 * testPaginate method
 *
 * @return void
 */
	public function testPaginate() {
		$Controller = new PaginatorTestController($this->request);
		$Controller->uses = array('PaginatorControllerPost', 'PaginatorControllerComment');
		$Controller->request->params['pass'] = array('1');
		$Controller->request->query = array();
		$Controller->constructClasses();

		$Controller->PaginatorControllerPost->order = null;

		$Controller->Paginator->settings = array(
			'order' => array('PaginatorControllerComment.id' => 'ASC')
		);
		$results = Hash::extract($Controller->Paginator->paginate('PaginatorControllerComment'), '{n}.PaginatorControllerComment.id');
		$this->assertEquals(array(1, 2, 3, 4, 5, 6), $results);

		$Controller->Paginator->settings = array(
			'order' => array('PaginatorControllerComment.id DESC')
		);
		$results = Hash::extract($Controller->Paginator->paginate('PaginatorControllerComment'), '{n}.PaginatorControllerComment.id');
		$this->assertEquals(array(6, 5, 4, 3, 2, 1), $results);

		$Controller->Paginator->settings = array(
			'order' => array('PaginatorControllerPost.id' => 'ASC')
		);
		$results = Hash::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEquals(array(1, 2, 3), $results);

		$Controller->modelClass = null;

		$Controller->uses[0] = 'Plugin.PaginatorControllerPost';
		$results = Hash::extract($Controller->Paginator->paginate(), '{n}.PaginatorControllerPost.id');
		$this->assertEquals(array(1, 2, 3), $results);

		$Controller->request->params['named'] = array('page' => '-1');
		$results = Hash::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEquals(1, $Controller->params['paging']['PaginatorControllerPost']['page']);
		$this->assertEquals(array(1, 2, 3), $results);

		$Controller->request->params['named'] = array('sort' => 'PaginatorControllerPost.id', 'direction' => 'asc');
		$results = Hash::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEquals(1, $Controller->params['paging']['PaginatorControllerPost']['page']);
		$this->assertEquals(array(1, 2, 3), $results);

		$Controller->request->params['named'] = array('sort' => 'PaginatorControllerPost.id', 'direction' => 'desc');
		$results = Hash::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEquals(1, $Controller->params['paging']['PaginatorControllerPost']['page']);
		$this->assertEquals(array(3, 2, 1), $results);

		$Controller->request->params['named'] = array('sort' => 'id', 'direction' => 'desc');
		$results = Hash::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEquals(1, $Controller->params['paging']['PaginatorControllerPost']['page']);
		$this->assertEquals(array(3, 2, 1), $results);

		$Controller->request->params['named'] = array('sort' => 'NotExisting.field', 'direction' => 'desc', 'limit' => 2);
		$Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertEquals(1, $Controller->params['paging']['PaginatorControllerPost']['page']);
		$this->assertEquals(array(), $Controller->PaginatorControllerPost->lastQueries[1]['order'], 'no order should be set.');

		$Controller->request->params['named'] = array(
			'sort' => 'PaginatorControllerPost.author_id', 'direction' => 'allYourBase'
		);
		$results = Hash::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEquals(array('PaginatorControllerPost.author_id' => 'asc'), $Controller->PaginatorControllerPost->lastQueries[0]['order']);
		$this->assertEquals(array(1, 3, 2), $results);

		$Controller->request->params['named'] = array();
		$Controller->Paginator->settings = array('limit' => 0, 'maxLimit' => 10, 'paramType' => 'named');
		$Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertSame(1, $Controller->params['paging']['PaginatorControllerPost']['page']);
		$this->assertSame($Controller->params['paging']['PaginatorControllerPost']['pageCount'], 3);
		$this->assertFalse($Controller->params['paging']['PaginatorControllerPost']['prevPage']);
		$this->assertTrue($Controller->params['paging']['PaginatorControllerPost']['nextPage']);

		$Controller->request->params['named'] = array();
		$Controller->Paginator->settings = array('limit' => 'garbage!', 'maxLimit' => 10, 'paramType' => 'named');
		$Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertSame(1, $Controller->params['paging']['PaginatorControllerPost']['page']);
		$this->assertSame($Controller->params['paging']['PaginatorControllerPost']['pageCount'], 3);
		$this->assertFalse($Controller->params['paging']['PaginatorControllerPost']['prevPage']);
		$this->assertTrue($Controller->params['paging']['PaginatorControllerPost']['nextPage']);

		$Controller->request->params['named'] = array();
		$Controller->Paginator->settings = array('limit' => '-1', 'maxLimit' => 10, 'paramType' => 'named');
		$Controller->Paginator->paginate('PaginatorControllerPost');

		$this->assertSame($Controller->params['paging']['PaginatorControllerPost']['limit'], 1);
		$this->assertSame(1, $Controller->params['paging']['PaginatorControllerPost']['page']);
		$this->assertSame($Controller->params['paging']['PaginatorControllerPost']['pageCount'], 3);
		$this->assertFalse($Controller->params['paging']['PaginatorControllerPost']['prevPage']);
		$this->assertTrue($Controller->params['paging']['PaginatorControllerPost']['nextPage']);

		$Controller->Paginator->settings = array('conditions' => array('PaginatorAuthor.user' => 'mariano'));
		$Controller->Paginator->paginate('PaginatorControllerPost');

		$this->assertSame(2, $Controller->params['paging']['PaginatorControllerPost']['count']);
	}

	public function testPaginateNotCondition() {
		$Controller = new PaginatorTestController($this->request);
		$Controller->uses = array('PaginatorControllerPost', 'PaginatorControllerComment');
		$Controller->request->params['pass'] = array('1');
		$Controller->request->query = array();
		$Controller->constructClasses();
		$Controller->Paginator->settings = array(
			'conditions' => array(
				'NOT' => array('PaginatorAuthor.user' => 'mariano'),
			),
		);
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertEquals('larry', $result[0]['PaginatorAuthor']['user']);
		$this->assertCount(1, $result);
	}

	public function testPaginateI18nConditionUser() {
		$Request = new CakeRequest('articles/index');
		$Controller = new PaginatorTestController($Request);
		$Controller->uses = array('TranslatedArticle');
		$Controller->constructClasses();
		$Controller->TranslatedArticle->locale = 'eng';
		$Controller->Paginator->settings = array(
			'recursive' => 0,
			'conditions' => array(
				'User.user' => 'mariano',
			),
		);
		$result = $Controller->Paginator->paginate('TranslatedArticle');
		$this->assertEquals('Title (eng) #1', $result[0]['TranslatedArticle']['title']);
		$this->assertEquals('mariano', $result[0]['User']['user']);
		$this->assertEquals('Title (eng) #3', $result[1]['TranslatedArticle']['title']);
		$this->assertEquals('mariano', $result[1]['User']['user']);
		$this->assertCount(2, $result);
	}

	public function testPaginateI18nConditionTitle() {
		$Request = new CakeRequest('articles/index');
		$Controller = new PaginatorTestController($Request);
		$Controller->uses = array('TranslatedArticle');
		$Controller->constructClasses();
		$Controller->TranslatedArticle->locale = 'eng';
		$Controller->Paginator->settings = array(
			'recursive' => 0,
			'conditions' => array(
				'I18n__title.content' => 'Title (eng) #3',
			),
		);
		$result = $Controller->Paginator->paginate('TranslatedArticle');
		$this->assertEquals('Title (eng) #3', $result[0]['TranslatedArticle']['title']);
		$this->assertCount(1, $result);
	}

	public function testPaginateI18nConditionNotTitle() {
		$Request = new CakeRequest('articles/index');
		$Controller = new PaginatorTestController($Request);
		$Controller->uses = array('TranslatedArticle');
		$Controller->constructClasses();
		$Controller->TranslatedArticle->locale = 'eng';
		$Controller->Paginator->settings = array(
			'recursive' => 0,
			'conditions' => array(
				'I18n__title.content !=' => '',
			),
		);
		$result = $Controller->Paginator->paginate('TranslatedArticle');
		$this->assertEquals('Title (eng) #1', $result[0]['TranslatedArticle']['title']);
		$this->assertEquals('Title (eng) #2', $result[1]['TranslatedArticle']['title']);
		$this->assertEquals('Title (eng) #3', $result[2]['TranslatedArticle']['title']);
		$this->assertCount(3, $result);
	}

	public function testPaginateI18nConditionNotTitleArraySyntax() {
		$Request = new CakeRequest('articles/index');
		$Controller = new PaginatorTestController($Request);
		$Controller->uses = array('TranslatedArticle');
		$Controller->constructClasses();
		$Controller->TranslatedArticle->locale = 'eng';
		$Controller->Paginator->settings = array(
			'recursive' => 0,
			'conditions' => array(
				'NOT' => array('I18n__title.content' => ''),
			),
		);
		$result = $Controller->Paginator->paginate('TranslatedArticle');
		$this->assertEquals('Title (eng) #1', $result[0]['TranslatedArticle']['title']);
		$this->assertEquals('Title (eng) #2', $result[1]['TranslatedArticle']['title']);
		$this->assertEquals('Title (eng) #3', $result[2]['TranslatedArticle']['title']);
		$this->assertCount(3, $result);
	}

	public function testPaginateI18nConditionNotTitleWithLimit() {
		$Request = new CakeRequest('articles/index');
		$Controller = new PaginatorTestController($Request);
		$Controller->uses = array('TranslatedArticle');
		$Controller->constructClasses();
		$Controller->TranslatedArticle->locale = 'eng';
		$Controller->Paginator->settings = array(
			'recursive' => 0,
			'conditions' => array(
				'NOT' => array('I18n__title.content' => ''),
			),
			'limit' => 2,
		);
		$result = $Controller->Paginator->paginate('TranslatedArticle');
		$this->assertEquals('Title (eng) #1', $result[0]['TranslatedArticle']['title']);
		$this->assertEquals('Title (eng) #2', $result[1]['TranslatedArticle']['title']);
		$this->assertCount(2, $result);
	}

/**
 * Test that non-numeric values are rejected for page, and limit
 *
 * @return void
 */
	public function testPageParamCasting() {
		$this->Controller->Post->expects($this->at(0))
			->method('hasMethod')
			->with('paginate')
			->will($this->returnValue(false));

		$this->Controller->Post->expects($this->at(1))
			->method('find')
			->will($this->returnValue(array('stuff')));

		$this->Controller->Post->expects($this->at(2))
			->method('hasMethod')
			->with('paginateCount')
			->will($this->returnValue(false));

		$this->Controller->Post->expects($this->at(3))
			->method('find')
			->will($this->returnValue(2));

		$this->request->params['named'] = array('page' => '1 " onclick="alert(\'xss\');">');
		$this->Paginator->settings = array('limit' => 1, 'maxLimit' => 10, 'paramType' => 'named');
		$this->Paginator->paginate('Post');
		$this->assertSame(1, $this->request->params['paging']['Post']['page'], 'XSS exploit opened');
	}

/**
 * testPaginateExtraParams method
 *
 * @return void
 */
	public function testPaginateExtraParams() {
		$Controller = new PaginatorTestController($this->request);

		$Controller->uses = array('PaginatorControllerPost', 'PaginatorControllerComment');
		$Controller->request->params['pass'] = array('1');
		$Controller->params['url'] = array();
		$Controller->constructClasses();

		$Controller->request->params['named'] = array('page' => '-1', 'contain' => array('PaginatorControllerComment'));
		$Controller->Paginator->settings = array(
			'order' => array('PaginatorControllerPost.id' => 'ASC')
		);
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertEquals(1, $Controller->params['paging']['PaginatorControllerPost']['page']);
		$this->assertEquals(array(1, 2, 3), Hash::extract($result, '{n}.PaginatorControllerPost.id'));
		$this->assertTrue(!isset($Controller->PaginatorControllerPost->lastQueries[1]['contain']));

		$Controller->Paginator->settings = array(
			'order' => array('PaginatorControllerPost.author_id')
		);
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertEquals(1, $Controller->params['paging']['PaginatorControllerPost']['page']);
		$this->assertEquals(array(1, 3, 2), Hash::extract($result, '{n}.PaginatorControllerPost.id'));

		$Controller->request->params['named'] = array('page' => '-1');
		$Controller->Paginator->settings = array(
			'PaginatorControllerPost' => array(
				'contain' => array('PaginatorControllerComment'),
				'maxLimit' => 10,
				'paramType' => 'named',
				'order' => array('PaginatorControllerPost.id' => 'ASC')
			),
		);
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertEquals(1, $Controller->params['paging']['PaginatorControllerPost']['page']);
		$this->assertEquals(array(1, 2, 3), Hash::extract($result, '{n}.PaginatorControllerPost.id'));
		$this->assertTrue(isset($Controller->PaginatorControllerPost->lastQueries[0]['contain']));

		$Controller->Paginator->settings = array(
			'PaginatorControllerPost' => array(
				'popular', 'fields' => array('id', 'title'), 'maxLimit' => 10, 'paramType' => 'named'
			),
		);
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertEquals(array(2, 3), Hash::extract($result, '{n}.PaginatorControllerPost.id'));
		$this->assertEquals(array('PaginatorControllerPost.id > ' => '1'), $Controller->PaginatorControllerPost->lastQueries[0]['conditions']);

		$Controller->request->params['named'] = array('limit' => 12);
		$Controller->Paginator->settings = array('limit' => 30, 'maxLimit' => 100, 'paramType' => 'named');
		$Controller->Paginator->paginate('PaginatorControllerPost');
		$paging = $Controller->params['paging']['PaginatorControllerPost'];

		$this->assertEquals(12, $Controller->PaginatorControllerPost->lastQueries[0]['limit']);
		$this->assertEquals(12, $paging['options']['limit']);

		$Controller = new PaginatorTestController($this->request);
		$Controller->uses = array('ControllerPaginateModel');
		$Controller->request->query = array();
		$Controller->constructClasses();
		$Controller->Paginator->settings = array(
			'ControllerPaginateModel' => array(
				'contain' => array('ControllerPaginateModel'),
				'group' => 'Comment.author_id',
				'maxLimit' => 10,
				'paramType' => 'named'
			)
		);
		$Controller->Paginator->paginate('ControllerPaginateModel');
		$expected = array(
			'contain' => array('ControllerPaginateModel'),
			'group' => 'Comment.author_id',
			'maxLimit' => 10,
			'paramType' => 'named',
			'queryScope' => null
		);
		$this->assertEquals($expected, $Controller->ControllerPaginateModel->extra);
		$this->assertEquals($expected, $Controller->ControllerPaginateModel->extraCount);

		$Controller->Paginator->settings = array(
			'ControllerPaginateModel' => array(
				'foo', 'contain' => array('ControllerPaginateModel'),
				'group' => 'Comment.author_id',
				'maxLimit' => 10,
				'paramType' => 'named',
				'queryScope' => null
			)
		);
		$Controller->Paginator->paginate('ControllerPaginateModel');
		$expected = array(
			'contain' => array('ControllerPaginateModel'),
			'group' => 'Comment.author_id',
			'type' => 'foo',
			'maxLimit' => 10,
			'paramType' => 'named',
			'queryScope' => null
		);
		$this->assertEquals($expected, $Controller->ControllerPaginateModel->extra);
		$this->assertEquals($expected, $Controller->ControllerPaginateModel->extraCount);
	}

/**
 * Test that special paginate types are called and that the type param doesn't leak out into defaults or options.
 *
 * @return void
 */
	public function testPaginateSpecialType() {
		$Controller = new PaginatorTestController($this->request);
		$Controller->uses = array('PaginatorControllerPost', 'PaginatorControllerComment');
		$Controller->request->params['pass'][] = '1';
		$Controller->params['url'] = array();
		$Controller->constructClasses();

		$Controller->Paginator->settings = array(
			'PaginatorControllerPost' => array(
				'popular',
				'fields' => array('id', 'title'),
				'maxLimit' => 10,
				'paramType' => 'named'
			)
		);
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');

		$this->assertEquals(array(2, 3), Hash::extract($result, '{n}.PaginatorControllerPost.id'));
		$this->assertEquals(
			$Controller->PaginatorControllerPost->lastQueries[0]['conditions'],
			array('PaginatorControllerPost.id > ' => '1')
		);
		$this->assertFalse(isset($Controller->params['paging']['PaginatorControllerPost']['options'][0]));
	}

/**
 * testDefaultPaginateParams method
 *
 * @return void
 */
	public function testDefaultPaginateParams() {
		$Controller = new PaginatorTestController($this->request);
		$Controller->modelClass = 'PaginatorControllerPost';
		$Controller->params['url'] = array();
		$Controller->constructClasses();
		$Controller->Paginator->settings = array(
			'order' => 'PaginatorControllerPost.id DESC',
			'maxLimit' => 10,
			'paramType' => 'named'
		);
		$results = Hash::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEquals('PaginatorControllerPost.id DESC', $Controller->params['paging']['PaginatorControllerPost']['order']);
		$this->assertEquals(array(3, 2, 1), $results);
	}

/**
 * test paginate() and model default order
 *
 * @return void
 */
	public function testPaginateOrderModelDefault() {
		$Controller = new PaginatorTestController($this->request);
		$Controller->uses = array('PaginatorControllerPost');
		$Controller->params['url'] = array();
		$Controller->constructClasses();
		$Controller->PaginatorControllerPost->order = array(
			$Controller->PaginatorControllerPost->alias . '.created' => 'desc'
		);

		$Controller->Paginator->settings = array(
			'fields' => array('id', 'title', 'created'),
			'maxLimit' => 10,
			'paramType' => 'named'
		);
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$expected = array('2007-03-18 10:43:23', '2007-03-18 10:41:23', '2007-03-18 10:39:23');
		$this->assertEquals($expected, Hash::extract($result, '{n}.PaginatorControllerPost.created'));
		$this->assertEquals(
			$Controller->PaginatorControllerPost->order,
			$Controller->request->paging['PaginatorControllerPost']['options']['order']
		);

		$Controller->PaginatorControllerPost->order = array('PaginatorControllerPost.id');
		$result = $Controller->Paginator->validateSort($Controller->PaginatorControllerPost, array());
		$this->assertEquals(array('PaginatorControllerPost.id' => 'asc'), $result['order']);

		$Controller->PaginatorControllerPost->order = 'PaginatorControllerPost.id';
		$result = $Controller->Paginator->validateSort($Controller->PaginatorControllerPost, array());
		$this->assertArrayNotHasKey('order', $result);

		$Controller->PaginatorControllerPost->order = array(
			'PaginatorControllerPost.id',
			'PaginatorControllerPost.created' => 'asc'
		);
		$result = $Controller->Paginator->validateSort($Controller->PaginatorControllerPost, array());
		$expected = array(
			'PaginatorControllerPost.id' => 'asc',
			'PaginatorControllerPost.created' => 'asc'
		);
		$this->assertEquals($expected, $result['order']);

		$Controller->PaginatorControllerPost->order = array(
			'PaginatorControllerPost.id ASC',
			'PaginatorControllerPost.created DESC'
		);
		$result = $Controller->Paginator->validateSort($Controller->PaginatorControllerPost, array());
		$expected = array(
			'PaginatorControllerPost.id' => 'ASC',
			'PaginatorControllerPost.created' => 'DESC'
		);
		$this->assertEquals($expected, $result['order']);
	}

/**
 * test paginate() and virtualField interactions
 *
 * @return void
 */
	public function testPaginateOrderVirtualField() {
		$Controller = new PaginatorTestController($this->request);
		$Controller->uses = array('PaginatorControllerPost', 'PaginatorControllerComment');
		$Controller->params['url'] = array();
		$Controller->constructClasses();
		$Controller->PaginatorControllerPost->virtualFields = array(
			'offset_test' => 'PaginatorControllerPost.id + 1'
		);

		$Controller->Paginator->settings = array(
			'fields' => array('id', 'title', 'offset_test'),
			'order' => array('offset_test' => 'DESC'),
			'maxLimit' => 10,
			'paramType' => 'named'
		);
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertEquals(array(4, 3, 2), Hash::extract($result, '{n}.PaginatorControllerPost.offset_test'));

		$Controller->request->params['named'] = array('sort' => 'offset_test', 'direction' => 'asc');
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertEquals(array(2, 3, 4), Hash::extract($result, '{n}.PaginatorControllerPost.offset_test'));
	}

/**
 * test paginate() and virtualField on joined model
 *
 * @return void
 */
	public function testPaginateOrderVirtualFieldJoinedModel() {
		$Controller = new PaginatorTestController($this->request);
		$Controller->uses = array('PaginatorControllerPost');
		$Controller->params['url'] = array();
		$Controller->constructClasses();
		$Controller->PaginatorControllerPost->recursive = 0;
		$Controller->Paginator->settings = array(
			'order' => array('PaginatorAuthor.joined_offset' => 'DESC'),
			'maxLimit' => 10,
			'paramType' => 'named'
		);
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertEquals(array(4, 2, 2), Hash::extract($result, '{n}.PaginatorAuthor.joined_offset'));

		$Controller->request->params['named'] = array('sort' => 'PaginatorAuthor.joined_offset', 'direction' => 'asc');
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertEquals(array(2, 2, 4), Hash::extract($result, '{n}.PaginatorAuthor.joined_offset'));
	}

/**
 * Tests for missing models
 *
 * @expectedException MissingModelException
 * @return void
 */
	public function testPaginateMissingModel() {
		$Controller = new PaginatorTestController($this->request);
		$Controller->constructClasses();
		$Controller->Paginator->paginate('MissingModel');
	}

/**
 * test that option merging prefers specific models
 *
 * @return void
 */
	public function testMergeOptionsModelSpecific() {
		$this->Paginator->settings = array(
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
			'paramType' => 'named',
			'queryScope' => null,
			'Post' => array(
				'page' => 1,
				'limit' => 10,
				'maxLimit' => 50,
				'paramType' => 'named',
			)
		);
		$result = $this->Paginator->mergeOptions('Silly');
		$this->assertEquals($this->Paginator->settings, $result);

		$result = $this->Paginator->mergeOptions('Post');
		$expected = array('page' => 1, 'limit' => 10, 'paramType' => 'named', 'maxLimit' => 50, 'queryScope' => null);
		$this->assertEquals($expected, $result);
	}

/**
 * test mergeOptions with named params.
 *
 * @return void
 */
	public function testMergeOptionsNamedParams() {
		$this->request->params['named'] = array(
			'page' => 10,
			'limit' => 10
		);
		$this->Paginator->settings = array(
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
			'paramType' => 'named',
		);
		$result = $this->Paginator->mergeOptions('Post');
		$expected = array('page' => 10, 'limit' => 10, 'maxLimit' => 100, 'paramType' => 'named', 'queryScope' => null);
		$this->assertEquals($expected, $result);
	}

/**
 * test mergeOptions with custom scope
 *
 * @return void
 */
	public function testMergeOptionsCustomScope() {
		$this->request->params['named'] = array(
			'page' => 10,
			'limit' => 10,
			'scope' => array(
				'page' => 2,
				'limit' => 5,
			)
		);
		$this->Paginator->settings = array(
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
			'findType' => 'myCustomFind',
		);
		$result = $this->Paginator->mergeOptions('Post');
		$expected = array(
			'page' => 10,
			'limit' => 10,
			'maxLimit' => 100,
			'findType' => 'myCustomFind',
			'paramType' => 'named',
			'queryScope' => null
		);
		$this->assertEquals($expected, $result);

		$this->Paginator->settings = array(
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
			'findType' => 'myCustomFind',
			'queryScope' => 'non-existent',
		);
		$result = $this->Paginator->mergeOptions('Post');
		$expected = array(
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
			'findType' => 'myCustomFind',
			'paramType' => 'named',
			'queryScope' => 'non-existent',
		);
		$this->assertEquals($expected, $result);

		$this->Paginator->settings = array(
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
			'findType' => 'myCustomFind',
			'queryScope' => 'scope',
		);
		$result = $this->Paginator->mergeOptions('Post');
		$expected = array(
			'page' => 2,
			'limit' => 5,
			'maxLimit' => 100,
			'findType' => 'myCustomFind',
			'paramType' => 'named',
			'queryScope' => 'scope',
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test mergeOptions with customFind key
 *
 * @return void
 */
	public function testMergeOptionsCustomFindKey() {
		$this->request->params['named'] = array(
			'page' => 10,
			'limit' => 10
		);
		$this->Paginator->settings = array(
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
			'paramType' => 'named',
			'findType' => 'myCustomFind'
		);
		$result = $this->Paginator->mergeOptions('Post');
		$expected = array(
			'page' => 10,
			'limit' => 10,
			'maxLimit' => 100,
			'paramType' => 'named',
			'findType' => 'myCustomFind',
			'queryScope' => null
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test merging options from the querystring.
 *
 * @return void
 */
	public function testMergeOptionsQueryString() {
		$this->request->params['named'] = array(
			'page' => 10,
			'limit' => 10
		);
		$this->request->query = array(
			'page' => 99,
			'limit' => 75
		);
		$this->Paginator->settings = array(
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
			'paramType' => 'querystring',
		);
		$result = $this->Paginator->mergeOptions('Post');
		$expected = array(
			'page' => 99,
			'limit' => 75,
			'maxLimit' => 100,
			'paramType' => 'querystring',
			'queryScope' => null
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test that the default whitelist doesn't let people screw with things they should not be allowed to.
 *
 * @return void
 */
	public function testMergeOptionsDefaultWhiteList() {
		$this->request->params['named'] = array(
			'page' => 10,
			'limit' => 10,
			'fields' => array('bad.stuff'),
			'recursive' => 1000,
			'conditions' => array('bad.stuff'),
			'contain' => array('bad')
		);
		$this->Paginator->settings = array(
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
			'paramType' => 'named',
		);
		$result = $this->Paginator->mergeOptions('Post');
		$expected = array('page' => 10, 'limit' => 10, 'maxLimit' => 100, 'paramType' => 'named', 'queryScope' => null);
		$this->assertEquals($expected, $result);
	}

/**
 * test that modifying the whitelist works.
 *
 * @return void
 */
	public function testMergeOptionsExtraWhitelist() {
		$this->request->params['named'] = array(
			'page' => 10,
			'limit' => 10,
			'fields' => array('bad.stuff'),
			'recursive' => 1000,
			'conditions' => array('bad.stuff'),
			'contain' => array('bad')
		);
		$this->Paginator->settings = array(
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
			'paramType' => 'named',
		);
		$this->Paginator->whitelist[] = 'fields';
		$result = $this->Paginator->mergeOptions('Post');
		$expected = array(
			'page' => 10,
			'limit' => 10,
			'maxLimit' => 100,
			'paramType' => 'named',
			'queryScope' => null,
			'fields' => array('bad.stuff')
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test mergeOptions with limit > maxLimit in code.
 *
 * @return void
 */
	public function testMergeOptionsMaxLimit() {
		$this->Paginator->settings = array(
			'limit' => 200,
			'paramType' => 'named',
		);
		$result = $this->Paginator->mergeOptions('Post');
		$expected = array('page' => 1, 'limit' => 200, 'maxLimit' => 100, 'paramType' => 'named', 'queryScope' => null);
		$this->assertEquals($expected, $result);

		$this->Paginator->settings = array(
			'maxLimit' => 10,
			'paramType' => 'named',
		);
		$result = $this->Paginator->mergeOptions('Post');
		$expected = array('page' => 1, 'limit' => 20, 'maxLimit' => 10, 'paramType' => 'named', 'queryScope' => null);
		$this->assertEquals($expected, $result);

		$this->request->params['named'] = array(
			'limit' => 500
		);
		$this->Paginator->settings = array(
			'limit' => 150,
			'paramType' => 'named',
		);
		$result = $this->Paginator->mergeOptions('Post');
		$expected = array('page' => 1, 'limit' => 500, 'maxLimit' => 100, 'paramType' => 'named', 'queryScope' => null);
		$this->assertEquals($expected, $result);
	}

/**
 * test that invalid directions are ignored.
 *
 * @return void
 */
	public function testValidateSortInvalidDirection() {
		$model = $this->getMock('Model');
		$model->alias = 'model';
		$model->expects($this->any())->method('hasField')->will($this->returnValue(true));

		$options = array('sort' => 'something', 'direction' => 'boogers');
		$result = $this->Paginator->validateSort($model, $options);

		$this->assertEquals('asc', $result['order']['model.something']);
	}

/**
 * Test that a really large page number gets clamped to the max page size.
 *
 * @expectedException NotFoundException
 * @return void
 */
	public function testOutOfRangePageNumberGetsClamped() {
		$Controller = new PaginatorTestController($this->request);
		$Controller->uses = array('PaginatorControllerPost');
		$Controller->params['named'] = array(
			'page' => 3000,
		);
		$Controller->constructClasses();
		$Controller->PaginatorControllerPost->recursive = 0;
		$Controller->Paginator->paginate('PaginatorControllerPost');
	}

/**
 * Test that a really REALLY large page number gets clamped to the max page size.
 *
 * @expectedException NotFoundException
 * @return void
 */
	public function testOutOfVeryBigPageNumberGetsClamped() {
		$Controller = new PaginatorTestController($this->request);
		$Controller->uses = array('PaginatorControllerPost');
		$Controller->params['named'] = array(
			'page' => '3000000000000000000000000',
		);
		$Controller->constructClasses();
		$Controller->PaginatorControllerPost->recursive = 0;
		$Controller->Paginator->paginate('PaginatorControllerPost');
	}

/**
 * testOutOfRangePageNumberAndPageCountZero
 *
 * @return void
 */
	public function testOutOfRangePageNumberAndPageCountZero() {
		$Controller = new PaginatorTestController($this->request);
		$Controller->uses = array('PaginatorControllerPost');
		$Controller->params['named'] = array(
			'page' => '3000',
		);
		$Controller->constructClasses();
		$Controller->PaginatorControllerPost->recursive = 0;
		$Controller->paginate = array(
			'conditions' => array('PaginatorControllerPost.id >' => 100)
		);

		try {
			$Controller->Paginator->paginate('PaginatorControllerPost');
			$this->fail();
		} catch (NotFoundException $e) {
			$this->assertEquals(
				1,
				$Controller->request->params['paging']['PaginatorControllerPost']['page'],
				'Page number should not be 0'
			);
		}
	}

/**
 * test that fields not in whitelist won't be part of order conditions.
 *
 * @return void
 */
	public function testValidateSortWhitelistFailure() {
		$model = $this->getMock('Model');
		$model->alias = 'model';
		$model->expects($this->any())->method('hasField')->will($this->returnValue(true));

		$options = array('sort' => 'body', 'direction' => 'asc');
		$result = $this->Paginator->validateSort($model, $options, array('title', 'id'));

		$this->assertNull($result['order']);
	}

/**
 * test that fields in the whitelist are not validated
 *
 * @return void
 */
	public function testValidateSortWhitelistTrusted() {
		$model = $this->getMock('Model');
		$model->alias = 'model';
		$model->expects($this->never())->method('hasField');

		$options = array('sort' => 'body', 'direction' => 'asc');
		$result = $this->Paginator->validateSort($model, $options, array('body'));

		$expected = array('body' => 'asc');
		$this->assertEquals($expected, $result['order']);
	}

/**
 * test that virtual fields work.
 *
 * @return void
 */
	public function testValidateSortVirtualField() {
		$model = $this->getMock('Model');
		$model->alias = 'model';

		$model->expects($this->at(0))
			->method('hasField')
			->with('something')
			->will($this->returnValue(false));

		$model->expects($this->at(1))
			->method('hasField')
			->with('something', true)
			->will($this->returnValue(true));

		$options = array('sort' => 'something', 'direction' => 'desc');
		$result = $this->Paginator->validateSort($model, $options);

		$this->assertEquals('desc', $result['order']['something']);
	}

/**
 * test that sorting fields is alias specific
 *
 * @return void
 */
	public function testValidateSortSharedFields() {
		$model = $this->getMock('Model');
		$model->alias = 'Parent';
		$model->Child = $this->getMock('Model');
		$model->Child->alias = 'Child';

		$model->expects($this->never())
			->method('hasField');

		$model->Child->expects($this->at(0))
			->method('hasField')
			->with('something')
			->will($this->returnValue(true));

		$options = array('sort' => 'Child.something', 'direction' => 'desc');
		$result = $this->Paginator->validateSort($model, $options);

		$this->assertEquals('desc', $result['order']['Child.something']);
	}
/**
 * test that multiple sort works.
 *
 * @return void
 */
	public function testValidateSortMultiple() {
		$model = $this->getMock('Model');
		$model->alias = 'model';
		$model->expects($this->any())->method('hasField')->will($this->returnValue(true));

		$options = array(
			'order' => array(
				'author_id' => 'asc',
				'title' => 'asc'
			)
		);
		$result = $this->Paginator->validateSort($model, $options);
		$expected = array(
			'model.author_id' => 'asc',
			'model.title' => 'asc'
		);

		$this->assertEquals($expected, $result['order']);
	}

/**
 * Test that no sort doesn't trigger an error.
 *
 * @return void
 */
	public function testValidateSortNoSort() {
		$model = $this->getMock('Model');
		$model->alias = 'model';
		$model->expects($this->any())->method('hasField')->will($this->returnValue(true));

		$options = array('direction' => 'asc');
		$result = $this->Paginator->validateSort($model, $options, array('title', 'id'));
		$this->assertFalse(isset($result['order']));

		$options = array('order' => 'invalid desc');
		$result = $this->Paginator->validateSort($model, $options, array('title', 'id'));

		$this->assertEquals($options['order'], $result['order']);
	}

/**
 * Test sorting with incorrect aliases on valid fields.
 *
 * @return void
 */
	public function testValidateSortInvalidAlias() {
		$model = $this->getMock('Model');
		$model->alias = 'Model';
		$model->expects($this->any())->method('hasField')->will($this->returnValue(true));

		$options = array('sort' => 'Derp.id');
		$result = $this->Paginator->validateSort($model, $options);
		$this->assertEquals(array(), $result['order']);
	}

/**
 * test that maxLimit is respected
 *
 * @return void
 */
	public function testCheckLimit() {
		$result = $this->Paginator->checkLimit(array('limit' => 1000000, 'maxLimit' => 100));
		$this->assertEquals(100, $result['limit']);

		$result = $this->Paginator->checkLimit(array('limit' => 'sheep!', 'maxLimit' => 100));
		$this->assertEquals(1, $result['limit']);

		$result = $this->Paginator->checkLimit(array('limit' => '-1', 'maxLimit' => 100));
		$this->assertEquals(1, $result['limit']);

		$result = $this->Paginator->checkLimit(array('limit' => null, 'maxLimit' => 100));
		$this->assertEquals(1, $result['limit']);

		$result = $this->Paginator->checkLimit(array('limit' => 0, 'maxLimit' => 100));
		$this->assertEquals(1, $result['limit']);
	}

/**
 * testPaginateMaxLimit
 *
 * @return void
 */
	public function testPaginateMaxLimit() {
		$Controller = new Controller($this->request);

		$Controller->uses = array('PaginatorControllerPost', 'ControllerComment');
		$Controller->request->params['pass'][] = '1';
		$Controller->constructClasses();

		$Controller->request->params['named'] = array(
			'contain' => array('ControllerComment'), 'limit' => '1000'
		);
		$Controller->paginate('PaginatorControllerPost');
		$this->assertEquals(100, $Controller->params['paging']['PaginatorControllerPost']['options']['limit']);

		$Controller->request->params['named'] = array(
			'contain' => array('ControllerComment'), 'limit' => '1000', 'maxLimit' => 1000
		);
		$Controller->paginate('PaginatorControllerPost');
		$this->assertEquals(100, $Controller->params['paging']['PaginatorControllerPost']['options']['limit']);

		$Controller->request->params['named'] = array('contain' => array('ControllerComment'), 'limit' => '10');
		$Controller->paginate('PaginatorControllerPost');
		$this->assertEquals(10, $Controller->params['paging']['PaginatorControllerPost']['options']['limit']);

		$Controller->request->params['named'] = array('contain' => array('ControllerComment'), 'limit' => '1000');
		$Controller->paginate = array('maxLimit' => 2000, 'paramType' => 'named');
		$Controller->paginate('PaginatorControllerPost');
		$this->assertEquals(1000, $Controller->params['paging']['PaginatorControllerPost']['options']['limit']);

		$Controller->request->params['named'] = array('contain' => array('ControllerComment'), 'limit' => '5000');
		$Controller->paginate('PaginatorControllerPost');
		$this->assertEquals(2000, $Controller->params['paging']['PaginatorControllerPost']['options']['limit']);
	}

/**
 * test paginate() and virtualField overlapping with real fields.
 *
 * @return void
 */
	public function testPaginateOrderVirtualFieldSharedWithRealField() {
		$Controller = new Controller($this->request);
		$Controller->uses = array('PaginatorControllerPost', 'PaginatorControllerComment');
		$Controller->constructClasses();
		$Controller->PaginatorControllerComment->virtualFields = array(
			'title' => 'PaginatorControllerComment.comment'
		);
		$Controller->PaginatorControllerComment->bindModel(array(
			'belongsTo' => array(
				'PaginatorControllerPost' => array(
					'className' => 'PaginatorControllerPost',
					'foreignKey' => 'article_id'
				)
			)
		), false);

		$Controller->paginate = array(
			'fields' => array(
				'PaginatorControllerComment.id',
				'title',
				'PaginatorControllerPost.title'
			),
		);
		$Controller->request->params['named'] = array(
			'sort' => 'PaginatorControllerPost.title',
			'direction' => 'desc'
		);
		$result = Hash::extract(
			$Controller->paginate('PaginatorControllerComment'),
			'{n}.PaginatorControllerComment.id'
		);
		$result1 = array_splice($result, 0, 2);
		sort($result1);
		$this->assertEquals(array(5, 6), $result1);

		sort($result);
		$this->assertEquals(array(1, 2, 3, 4), $result);
	}

/**
 * test paginate() and custom find, to make sure the correct count is returned.
 *
 * @return void
 */
	public function testPaginateCustomFind() {
		$Controller = new Controller($this->request);
		$Controller->uses = array('PaginatorCustomPost');
		$Controller->constructClasses();
		$data = array('author_id' => 3, 'title' => 'Fourth Article', 'body' => 'Article Body, unpublished', 'published' => 'N');
		$Controller->PaginatorCustomPost->create($data);
		$result = $Controller->PaginatorCustomPost->save();
		$this->assertTrue(!empty($result));

		$result = $Controller->paginate();
		$this->assertEquals(array(1, 2, 3, 4), Hash::extract($result, '{n}.PaginatorCustomPost.id'));

		$result = $Controller->params['paging']['PaginatorCustomPost'];
		$this->assertEquals(4, $result['current']);
		$this->assertEquals(4, $result['count']);

		$Controller->paginate = array('published');
		$result = $Controller->paginate();
		$this->assertEquals(array(1, 2, 3), Hash::extract($result, '{n}.PaginatorCustomPost.id'));

		$result = $Controller->params['paging']['PaginatorCustomPost'];
		$this->assertEquals(3, $result['current']);
		$this->assertEquals(3, $result['count']);

		$Controller->paginate = array('published', 'limit' => 2);
		$result = $Controller->paginate();
		$this->assertEquals(array(1, 2), Hash::extract($result, '{n}.PaginatorCustomPost.id'));

		$result = $Controller->params['paging']['PaginatorCustomPost'];
		$this->assertEquals(2, $result['current']);
		$this->assertEquals(3, $result['count']);
		$this->assertEquals(2, $result['pageCount']);
		$this->assertTrue($result['nextPage']);
		$this->assertFalse($result['prevPage']);
	}
/**
 * test paginate() and custom find with fields array, to make sure the correct count is returned.
 *
 * @return void
 */
	public function testPaginateCustomFindFieldsArray() {
		$Controller = new Controller($this->request);
		$Controller->uses = array('PaginatorCustomPost');
		$Controller->constructClasses();
		$data = array('author_id' => 3, 'title' => 'Fourth Article', 'body' => 'Article Body, unpublished', 'published' => 'N');
		$Controller->PaginatorCustomPost->create($data);
		$result = $Controller->PaginatorCustomPost->save();
		$this->assertTrue(!empty($result));

		$Controller->paginate = array(
			'list',
			'conditions' => array('PaginatorCustomPost.published' => 'Y'),
			'limit' => 2
		);
		$result = $Controller->paginate();
		$expected = array(
			1 => 'First Post',
			2 => 'Second Post',
		);
		$this->assertEquals($expected, $result);
		$result = $Controller->params['paging']['PaginatorCustomPost'];
		$this->assertEquals(2, $result['current']);
		$this->assertEquals(3, $result['count']);
		$this->assertEquals(2, $result['pageCount']);
		$this->assertTrue($result['nextPage']);
		$this->assertFalse($result['prevPage']);
	}
/**
 * test paginate() and custom find with customFind key, to make sure the correct count is returned.
 *
 * @return void
 */
	public function testPaginateCustomFindWithCustomFindKey() {
		$Controller = new Controller($this->request);
		$Controller->uses = array('PaginatorCustomPost');
		$Controller->constructClasses();
		$data = array('author_id' => 3, 'title' => 'Fourth Article', 'body' => 'Article Body, unpublished', 'published' => 'N');
		$Controller->PaginatorCustomPost->create($data);
		$result = $Controller->PaginatorCustomPost->save();
		$this->assertTrue(!empty($result));

		$Controller->paginate = array(
			'conditions' => array('PaginatorCustomPost.published' => 'Y'),
			'findType' => 'list',
			'limit' => 2
		);
		$result = $Controller->paginate();
		$expected = array(
			1 => 'First Post',
			2 => 'Second Post',
		);
		$this->assertEquals($expected, $result);
		$result = $Controller->params['paging']['PaginatorCustomPost'];
		$this->assertEquals(2, $result['current']);
		$this->assertEquals(3, $result['count']);
		$this->assertEquals(2, $result['pageCount']);
		$this->assertTrue($result['nextPage']);
		$this->assertFalse($result['prevPage']);
	}

/**
 * test paginate() and custom find with fields array, to make sure the correct count is returned.
 *
 * @return void
 */
	public function testPaginateCustomFindGroupBy() {
		$Controller = new Controller($this->request);
		$Controller->uses = array('PaginatorCustomPost');
		$Controller->constructClasses();
		$data = array('author_id' => 2, 'title' => 'Fourth Article', 'body' => 'Article Body, unpublished', 'published' => 'N');
		$Controller->PaginatorCustomPost->create($data);
		$result = $Controller->PaginatorCustomPost->save();
		$this->assertTrue(!empty($result));

		$Controller->paginate = array(
			'totals',
			'limit' => 2
		);
		$result = $Controller->paginate();
		$expected = array(
			array(
				'PaginatorCustomPost' => array(
					'author_id' => '1',
					'total_posts' => '2'
				)
			),
			array(
				'PaginatorCustomPost' => array(
					'author_id' => '2',
					'total_posts' => '1'
				)
			)
		);
		$this->assertEquals($expected, $result);
		$result = $Controller->params['paging']['PaginatorCustomPost'];
		$this->assertEquals(2, $result['current']);
		$this->assertEquals(3, $result['count']);
		$this->assertEquals(2, $result['pageCount']);
		$this->assertTrue($result['nextPage']);
		$this->assertFalse($result['prevPage']);

		$Controller->paginate = array(
			'totals',
			'limit' => 2,
			'page' => 2
		);
		$result = $Controller->paginate();
		$expected = array(
			array(
				'PaginatorCustomPost' => array(
					'author_id' => '3',
					'total_posts' => '1'
				)
			),
		);
		$this->assertEquals($expected, $result);
		$result = $Controller->params['paging']['PaginatorCustomPost'];
		$this->assertEquals(1, $result['current']);
		$this->assertEquals(3, $result['count']);
		$this->assertEquals(2, $result['pageCount']);
		$this->assertFalse($result['nextPage']);
		$this->assertTrue($result['prevPage']);
	}

/**
 * test paginate() and custom find with returning other query on count operation,
 * to make sure the correct count is returned.
 *
 * @return void
 */
	public function testPaginateCustomFindCount() {
		$Controller = new Controller($this->request);
		$Controller->uses = array('PaginatorCustomPost');
		$Controller->constructClasses();
		$data = array('author_id' => 2, 'title' => 'Fourth Article', 'body' => 'Article Body, unpublished', 'published' => 'N');
		$Controller->PaginatorCustomPost->create($data);
		$result = $Controller->PaginatorCustomPost->save();
		$this->assertTrue(!empty($result));

		$Controller->paginate = array(
			'totalsOperation',
			'limit' => 2
		);
		$result = $Controller->paginate();
		$expected = array(
			array(
				'PaginatorCustomPost' => array(
					'author_id' => '1',
					'total_posts' => '2'
				),
				'Author' => array(
					'user' => 'mariano',
				)
			),
			array(
				'PaginatorCustomPost' => array(
					'author_id' => '2',
					'total_posts' => '1'
				),
				'Author' => array(
					'user' => 'nate'
				)
			)
		);
		$this->assertEquals($expected, $result);
		$result = $Controller->params['paging']['PaginatorCustomPost'];
		$this->assertEquals(2, $result['current']);
		$this->assertEquals(3, $result['count']);
		$this->assertEquals(2, $result['pageCount']);
		$this->assertTrue($result['nextPage']);
		$this->assertFalse($result['prevPage']);
	}
}
