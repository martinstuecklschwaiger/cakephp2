<?php
/**
 * ThemeViewTest file
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
 * @package       Cake.Test.Case.View
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('View', 'View');
App::uses('ThemeView', 'View');
App::uses('Controller', 'Controller');

/**
 * ThemePosts2Controller class
 *
 * @package       Cake.Test.Case.View
 */
class ThemePosts2Controller extends Controller {

/**
 * name property
 *
 * @var string
 */
	public $name = 'ThemePosts';

	public $theme = null;

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->set(array(
			'testData' => 'Some test data',
			'test2' => 'more data',
			'test3' => 'even more data',
		));
	}

}

/**
 * TestTheme2View class
 *
 * @package       Cake.Test.Case.View
 */
class TestTheme2View extends ThemeView {

/**
 * renderElement method
 *
 * @param string $name
 * @param array $params
 * @return void
 */
	public function renderElement($name, $params = array()) {
		return $name;
	}

/**
 * getViewFileName method
 *
 * @param string $name
 * @return void
 */
	public function getViewFileName($name = null) {
		return $this->_getViewFileName($name);
	}

/**
 * getLayoutFileName method
 *
 * @param string $name
 * @return void
 */
	public function getLayoutFileName($name = null) {
		return $this->_getLayoutFileName($name);
	}

}

/**
 * ThemeViewTest class
 *
 * @package       Cake.Test.Case.View
 */
#[\AllowDynamicProperties]
class ThemeViewTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$request = new CakeRequest('posts/index');
		$this->Controller = new Controller($request);
		$this->PostsController = new ThemePosts2Controller($request);
		$this->PostsController->viewPath = 'posts';
		$this->PostsController->index();
		$this->ThemeView = new ThemeView($this->PostsController);
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		));
		App::objects('plugins', null, false);
		CakePlugin::load(array('TestPlugin'));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->ThemeView);
		unset($this->PostsController);
		unset($this->Controller);
		CakePlugin::unload();
	}

/**
 * testPluginGetTemplate method
 *
 * @return void
 */
	public function testPluginThemedGetTemplate() {
		$this->Controller->plugin = 'TestPlugin';
		$this->Controller->name = 'TestPlugin';
		$this->Controller->viewPath = 'Tests';
		$this->Controller->action = 'index';
		$this->Controller->theme = 'TestTheme';

		$ThemeView = new TestTheme2View($this->Controller);
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Plugin' . DS . 'TestPlugin' . DS . 'Tests' . DS . 'index.ctp';
		$result = $ThemeView->getViewFileName('index');
		$this->assertEquals($expected, $result);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Plugin' . DS . 'TestPlugin' . DS . 'Layouts' . DS . 'plugin_default.ctp';
		$result = $ThemeView->getLayoutFileName('plugin_default');
		$this->assertEquals($expected, $result);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Layouts' . DS . 'default.ctp';
		$result = $ThemeView->getLayoutFileName('default');
		$this->assertEquals($expected, $result);
	}

/**
 * testGetTemplate method
 *
 * @return void
 */
	public function testGetTemplate() {
		$this->Controller->plugin = null;
		$this->Controller->name = 'Pages';
		$this->Controller->viewPath = 'Pages';
		$this->Controller->action = 'display';
		$this->Controller->params['pass'] = array('home');

		$ThemeView = new TestTheme2View($this->Controller);
		$ThemeView->theme = 'TestTheme';
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Pages' . DS . 'home.ctp';
		$result = $ThemeView->getViewFileName('home');
		$this->assertEquals($expected, $result);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Posts' . DS . 'index.ctp';
		$result = $ThemeView->getViewFileName('/Posts/index');
		$this->assertEquals($expected, $result);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Layouts' . DS . 'default.ctp';
		$result = $ThemeView->getLayoutFileName();
		$this->assertEquals($expected, $result);

		$ThemeView->layoutPath = 'rss';
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Layouts' . DS . 'rss' . DS . 'default.ctp';
		$result = $ThemeView->getLayoutFileName();
		$this->assertEquals($expected, $result);

		$ThemeView->layoutPath = 'Emails' . DS . 'html';
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Layouts' . DS . 'Emails' . DS . 'html' . DS . 'default.ctp';
		$result = $ThemeView->getLayoutFileName();
		$this->assertEquals($expected, $result);
	}

/**
 * testMissingView method
 *
 * @return void
 */
	public function testMissingView() {
		$this->Controller->plugin = null;
		$this->Controller->name = 'Pages';
		$this->Controller->viewPath = 'Pages';
		$this->Controller->action = 'display';
		$this->Controller->theme = 'my_theme';

		$this->Controller->params['pass'] = array('home');

		$View = new TestTheme2View($this->Controller);

		try {
			$View->getViewFileName('does_not_exist');
			$this->fail('No exception');
		} catch (MissingViewException $e) {
			$this->assertContains('Pages' . DS . 'does_not_exist.ctp', $e->getMessage());
		}
	}

/**
 * testMissingLayout method
 *
 * @return void
 */
	public function testMissingLayout() {
		$this->Controller->plugin = null;
		$this->Controller->name = 'Posts';
		$this->Controller->viewPath = 'posts';
		$this->Controller->layout = 'whatever';
		$this->Controller->theme = 'my_theme';

		$View = new TestTheme2View($this->Controller);

		try {
			$View->getLayoutFileName();
			$this->fail('No exception');
		} catch (MissingLayoutException $e) {
			$this->assertContains('Layouts' . DS . 'whatever.ctp', $e->getMessage());
		}
	}

/**
 * test memory leaks that existed in _paths at one point.
 *
 * @return void
 */
	public function testMemoryLeakInPaths() {
		$this->Controller->plugin = null;
		$this->Controller->name = 'Posts';
		$this->Controller->viewPath = 'posts';
		$this->Controller->layout = 'whatever';
		$this->Controller->theme = 'TestTheme';

		$View = new ThemeView($this->Controller);
		$View->element('test_element');

		$start = memory_get_usage();
		for ($i = 0; $i < 10; $i++) {
			$View->element('test_element');
		}
		$end = memory_get_usage();
		$this->assertLessThanOrEqual($start + 5000, $end);
	}
}
