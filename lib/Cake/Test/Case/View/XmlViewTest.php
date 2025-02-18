<?php
/**
 * XmlViewTest file
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
 * @since         CakePHP(tm) v 2.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('XmlView', 'View');

/**
 * XmlViewTest
 *
 * @package       Cake.Test.Case.View
 */
#[\AllowDynamicProperties]
class XmlViewTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();
		Configure::write('debug', 0);
	}

/**
 * testRenderWithoutView method
 *
 * @return void
 */
	public function testRenderWithoutView() {
		$Request = new CakeRequest();
		$Response = new CakeResponse();
		$Controller = new Controller($Request, $Response);
		$data = array('users' => array('user' => array('user1', 'user2')));
		$Controller->set(array('users' => $data, '_serialize' => 'users'));
		$View = new XmlView($Controller);
		$output = $View->render(false);

		$this->assertSame(Xml::build($data)->asXML(), $output);
		$this->assertSame('application/xml', $Response->type());

		$data = array(
			array(
				'User' => array(
					'username' => 'user1'
				)
			),
			array(
				'User' => array(
					'username' => 'user2'
				)
			)
		);
		$Controller->set(array('users' => $data, '_serialize' => 'users'));
		$View = new XmlView($Controller);
		$output = $View->render(false);

		$expected = Xml::build(array('response' => array('users' => $data)))->asXML();
		$this->assertSame($expected, $output);

		$Controller->set('_rootNode', 'custom_name');
		$View = new XmlView($Controller);
		$output = $View->render(false);

		$expected = Xml::build(array('custom_name' => array('users' => $data)))->asXML();
		$this->assertSame($expected, $output);
	}

/**
 * Test that rendering with _serialize does not load helpers
 *
 * @return void
 */
	public function testRenderSerializeNoHelpers() {
		$Request = new CakeRequest();
		$Response = new CakeResponse();
		$Controller = new Controller($Request, $Response);
		$Controller->helpers = array('Html');
		$Controller->set(array(
			'_serialize' => 'tags',
			'tags' => array('cakephp', 'framework')
		));
		$View = new XmlView($Controller);
		$View->render();
		$this->assertFalse(isset($View->Html), 'No helper loaded.');
	}

/**
 * Test that rendering with _serialize respects XML options.
 *
 * @return void
 */
	public function testRenderSerializeWithOptions() {
		$Request = new CakeRequest();
		$Response = new CakeResponse();
		$Controller = new Controller($Request, $Response);
		$data = array(
			'_serialize' => array('tags'),
			'_xmlOptions' => array('format' => 'attributes', 'return' => 'domdocument'),
			'tags' => array(
				'tag' => array(
					array(
						'id' => '1',
						'name' => 'defect'
					),
					array(
						'id' => '2',
						'name' => 'enhancement'
					)
				)
			)
		);
		$Controller->set($data);
		$Controller->viewClass = 'Xml';
		$View = new XmlView($Controller);
		$result = $View->render();

		$expected = Xml::build(array('response' => array('tags' => $data['tags'])), $data['_xmlOptions'])->saveXML();
		$this->assertSame($expected, $result);
	}

/**
 * Test that rendering with _serialize can work with string setting.
 *
 * @return void
 */
	public function testRenderSerializeWithString() {
		$Request = new CakeRequest();
		$Response = new CakeResponse();
		$Controller = new Controller($Request, $Response);
		$data = array(
			'_serialize' => 'tags',
			'_xmlOptions' => array('format' => 'attributes'),
			'tags' => array(
				'tags' => array(
					'tag' => array(
						array(
							'id' => '1',
							'name' => 'defect'
						),
						array(
							'id' => '2',
							'name' => 'enhancement'
						)
					)
				)
			)
		);
		$Controller->set($data);
		$Controller->viewClass = 'Xml';
		$View = new XmlView($Controller);
		$result = $View->render();

		$expected = Xml::build($data['tags'], $data['_xmlOptions'])->asXML();
		$this->assertSame($expected, $result);
	}

/**
 * Test render with an array in _serialize
 *
 * @return void
 */
	public function testRenderWithoutViewMultiple() {
		$Request = new CakeRequest();
		$Response = new CakeResponse();
		$Controller = new Controller($Request, $Response);
		$data = array('no' => 'nope', 'user' => 'fake', 'list' => array('item1', 'item2'));
		$Controller->set($data);
		$Controller->set('_serialize', array('no', 'user'));
		$View = new XmlView($Controller);
		$this->assertSame('application/xml', $Response->type());
		$output = $View->render(false);
		$expected = array(
			'response' => array('no' => $data['no'], 'user' => $data['user'])
		);
		$this->assertSame(Xml::build($expected)->asXML(), $output);

		$Controller->set('_rootNode', 'custom_name');
		$View = new XmlView($Controller);
		$output = $View->render(false);
		$expected = array(
			'custom_name' => array('no' => $data['no'], 'user' => $data['user'])
		);
		$this->assertSame(Xml::build($expected)->asXML(), $output);
	}

/**
 * Test render with an array in _serialize and alias
 *
 * @return void
 */
	public function testRenderWithoutViewMultipleAndAlias() {
		$Request = new CakeRequest();
		$Response = new CakeResponse();
		$Controller = new Controller($Request, $Response);
		$data = array('original_name' => 'my epic name', 'user' => 'fake', 'list' => array('item1', 'item2'));
		$Controller->set($data);
		$Controller->set('_serialize', array('new_name' => 'original_name', 'user'));
		$View = new XmlView($Controller);
		$this->assertSame('application/xml', $Response->type());
		$output = $View->render(false);
		$expected = array(
			'response' => array('new_name' => $data['original_name'], 'user' => $data['user'])
		);
		$this->assertSame(Xml::build($expected)->asXML(), $output);

		$Controller->set('_rootNode', 'custom_name');
		$View = new XmlView($Controller);
		$output = $View->render(false);
		$expected = array(
			'custom_name' => array('new_name' => $data['original_name'], 'user' => $data['user'])
		);
		$this->assertSame(Xml::build($expected)->asXML(), $output);
	}

/**
 * testRenderWithView method
 *
 * @return void
 */
	public function testRenderWithView() {
		App::build(array('View' => array(
			CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS
		)));
		$Request = new CakeRequest();
		$Response = new CakeResponse();
		$Controller = new Controller($Request, $Response);
		$Controller->name = $Controller->viewPath = 'Posts';

		$data = array(
			array(
				'User' => array(
					'username' => 'user1'
				)
			),
			array(
				'User' => array(
					'username' => 'user2'
				)
			)
		);
		$Controller->set('users', $data);
		$View = new XmlView($Controller);
		$output = $View->render('index');

		$expected = array(
			'users' => array('user' => array('user1', 'user2'))
		);
		$expected = Xml::build($expected)->asXML();
		$this->assertSame($expected, $output);
		$this->assertSame('application/xml', $Response->type());
		$this->assertInstanceOf('HelperCollection', $View->Helpers);
	}

}
