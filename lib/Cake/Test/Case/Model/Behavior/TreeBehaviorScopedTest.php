<?php
/**
 * TreeBehaviorScopedTest file
 *
 * A tree test using scope
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
 * @package       Cake.Test.Case.Model.Behavior
 * @since         CakePHP(tm) v 1.2.0.5330
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');

require_once dirname(dirname(__FILE__)) . DS . 'models.php';

/**
 * TreeBehaviorScopedTest class
 *
 * @package       Cake.Test.Case.Model.Behavior
 */
#[\AllowDynamicProperties]
class TreeBehaviorScopedTest extends CakeTestCase {

/**
 * Whether backup global state for each test method or not
 *
 * @var bool
 */
	public $backupGlobals = false;

/**
 * settings property
 *
 * @var array
 */
	public $settings = array(
		'modelClass' => 'FlagTree',
		'leftField' => 'lft',
		'rightField' => 'rght',
		'parentField' => 'parent_id'
	);

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array('core.flag_tree', 'core.ad', 'core.campaign', 'core.translate', 'core.number_tree_two');

/**
 * testStringScope method
 *
 * @return void
 */
	public function testStringScope() {
		$this->Tree = new FlagTree();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 3);

		$this->Tree->id = 1;
		$this->Tree->saveField('flag', 1);
		$this->Tree->id = 2;
		$this->Tree->saveField('flag', 1);

		$result = $this->Tree->children();
		$expected = array(
			array('FlagTree' => array('id' => '3', 'name' => '1.1.1', 'parent_id' => '2', 'lft' => '3', 'rght' => '4', 'flag' => '0')),
			array('FlagTree' => array('id' => '4', 'name' => '1.1.2', 'parent_id' => '2', 'lft' => '5', 'rght' => '6', 'flag' => '0')),
			array('FlagTree' => array('id' => '5', 'name' => '1.1.3', 'parent_id' => '2', 'lft' => '7', 'rght' => '8', 'flag' => '0'))
		);
		$this->assertEquals($expected, $result);

		$this->Tree->Behaviors->load('Tree', array('scope' => 'FlagTree.flag = 1'));
		$this->assertEquals(array(), $this->Tree->children());

		$this->Tree->id = 1;
		$this->Tree->Behaviors->load('Tree', array('scope' => 'FlagTree.flag = 1'));

		$result = $this->Tree->children();
		$expected = array(array('FlagTree' => array('id' => '2', 'name' => '1.1', 'parent_id' => '1', 'lft' => '2', 'rght' => '9', 'flag' => '1')));
		$this->assertEquals($expected, $result);

		$this->assertTrue($this->Tree->delete());
		$this->assertEquals(11, $this->Tree->find('count'));
	}

/**
 * testArrayScope method
 *
 * @return void
 */
	public function testArrayScope() {
		$this->Tree = new FlagTree();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 3);

		$this->Tree->id = 1;
		$this->Tree->saveField('flag', 1);
		$this->Tree->id = 2;
		$this->Tree->saveField('flag', 1);

		$result = $this->Tree->children();
		$expected = array(
			array('FlagTree' => array('id' => '3', 'name' => '1.1.1', 'parent_id' => '2', 'lft' => '3', 'rght' => '4', 'flag' => '0')),
			array('FlagTree' => array('id' => '4', 'name' => '1.1.2', 'parent_id' => '2', 'lft' => '5', 'rght' => '6', 'flag' => '0')),
			array('FlagTree' => array('id' => '5', 'name' => '1.1.3', 'parent_id' => '2', 'lft' => '7', 'rght' => '8', 'flag' => '0'))
		);
		$this->assertEquals($expected, $result);

		$this->Tree->Behaviors->load('Tree', array('scope' => array('FlagTree.flag' => 1)));
		$this->assertEquals(array(), $this->Tree->children());

		$this->Tree->id = 1;
		$this->Tree->Behaviors->load('Tree', array('scope' => array('FlagTree.flag' => 1)));

		$result = $this->Tree->children();
		$expected = array(array('FlagTree' => array('id' => '2', 'name' => '1.1', 'parent_id' => '1', 'lft' => '2', 'rght' => '9', 'flag' => '1')));
		$this->assertEquals($expected, $result);

		$this->assertTrue($this->Tree->delete());
		$this->assertEquals(11, $this->Tree->find('count'));
	}

/**
 * testSaveWithParentAndInvalidScope method
 *
 * Attempting to save an invalid data should not trigger an `Undefined offset`
 * error
 *
 * @return void
 */
	public function testSaveWithParentAndInvalidScope() {
		$this->Tree = new FlagTree();
		$this->Tree->order = null;
		$data = $this->Tree->create(array(
			'name' => 'Flag',
		));
		$tree = $this->Tree->save($data);
		$this->Tree->Behaviors->load('Tree', array(
			'scope' => array('FlagTree.flag' => 100)
		));
		$tree['FlagTree']['parent_id'] = 1;
		$result = $this->Tree->save($tree);
		$this->assertFalse($result);
	}

/**
 * testMoveUpWithScope method
 *
 * @return void
 */
	public function testMoveUpWithScope() {
		$this->Ad = new Ad();
		$this->Ad->order = null;
		$this->Ad->Behaviors->load('Tree', array('scope' => 'Campaign'));
		$this->Ad->moveUp(6);

		$this->Ad->id = 4;
		$result = $this->Ad->children();
		$this->assertEquals(array(6, 5), Hash::extract($result, '{n}.Ad.id'));
		$this->assertEquals(array(2, 2), Hash::extract($result, '{n}.Campaign.id'));
	}

/**
 * testMoveDownWithScope method
 *
 * @return void
 */
	public function testMoveDownWithScope() {
		$this->Ad = new Ad();
		$this->Ad->order = null;
		$this->Ad->Behaviors->load('Tree', array('scope' => 'Campaign'));
		$this->Ad->moveDown(6);

		$this->Ad->id = 4;
		$result = $this->Ad->children();
		$this->assertEquals(array(5, 6), Hash::extract($result, '{n}.Ad.id'));
		$this->assertEquals(array(2, 2), Hash::extract($result, '{n}.Campaign.id'));
	}

/**
 * Tests the interaction (non-interference) between TreeBehavior and other behaviors with respect
 * to callback hooks
 *
 * @return void
 */
	public function testTranslatingTree() {
		$this->Tree = new FlagTree();
		$this->Tree->order = null;
		$this->Tree->cacheQueries = false;
		$this->Tree->Behaviors->load('Translate', array('title'));

		//Save
		$this->Tree->create();
		$this->Tree->locale = 'eng';
		$data = array('FlagTree' => array(
			'title' => 'name #1',
			'name' => 'test',
			'locale' => 'eng',
			'parent_id' => null,
		));
		$this->Tree->save($data);
		$result = $this->Tree->find('all');
		$expected = array(array('FlagTree' => array(
			'id' => 1,
			'title' => 'name #1',
			'name' => 'test',
			'parent_id' => null,
			'lft' => 1,
			'rght' => 2,
			'flag' => 0,
			'locale' => 'eng',
		)));
		$this->assertEquals($expected, $result);

		// update existing record, same locale
		$this->Tree->create();
		$data['FlagTree']['title'] = 'Named 2';
		$this->Tree->id = 1;
		$this->Tree->save($data);
		$result = $this->Tree->find('all');
		$expected = array(array('FlagTree' => array(
			'id' => 1,
			'title' => 'Named 2',
			'name' => 'test',
			'parent_id' => null,
			'lft' => 1,
			'rght' => 2,
			'flag' => 0,
			'locale' => 'eng',
		)));
		$this->assertEquals($expected, $result);

		// update different locale, same record
		$this->Tree->create();
		$this->Tree->locale = 'deu';
		$this->Tree->id = 1;
		$data = array('FlagTree' => array(
			'id' => 1,
			'parent_id' => null,
			'title' => 'namen #1',
			'name' => 'test',
			'locale' => 'deu',
		));
		$this->Tree->save($data);

		$this->Tree->locale = 'deu';
		$result = $this->Tree->find('all');
		$expected = array(
			array(
				'FlagTree' => array(
					'id' => 1,
					'title' => 'namen #1',
					'name' => 'test',
					'parent_id' => null,
					'lft' => 1,
					'rght' => 2,
					'flag' => 0,
					'locale' => 'deu',
				)
			)
		);
		$this->assertEquals($expected, $result);

		// Save with bindTranslation
		$this->Tree->locale = 'eng';
		$data = array(
			'title' => array('eng' => 'New title', 'spa' => 'Nuevo leyenda'),
			'name' => 'test',
			'parent_id' => null
		);
		$this->Tree->create($data);
		$this->Tree->save();

		$this->Tree->unbindTranslation();
		$translations = array('title' => 'Title');
		$this->Tree->bindTranslation($translations, false);
		$this->Tree->locale = array('eng', 'spa');

		$result = $this->Tree->read();
		$expected = array(
			'FlagTree' => array(
				'id' => 2,
				'parent_id' => null,
				'locale' => 'eng',
				'name' => 'test',
				'title' => 'New title',
				'flag' => 0,
				'lft' => 3,
				'rght' => 4
			),
			'Title' => array(
				array('id' => 21, 'locale' => 'eng', 'model' => 'FlagTree', 'foreign_key' => 2, 'field' => 'title', 'content' => 'New title'),
				array('id' => 22, 'locale' => 'spa', 'model' => 'FlagTree', 'foreign_key' => 2, 'field' => 'title', 'content' => 'Nuevo leyenda')
			),
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testGenerateTreeListWithSelfJoin method
 *
 * @return void
 */
	public function testAliasesWithScopeInTwoTreeAssociations() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$this->TreeTwo = new NumberTreeTwo();
		$this->TreeTwo->order = null;

		$record = $this->Tree->find('first');

		$this->Tree->bindModel(array(
			'hasMany' => array(
				'SecondTree' => array(
					'className' => 'NumberTreeTwo',
					'foreignKey' => 'number_tree_id'
				)
			)
		));
		$this->TreeTwo->bindModel(array(
			'belongsTo' => array(
				'FirstTree' => array(
					'className' => $modelClass,
					'foreignKey' => 'number_tree_id'
				)
			)
		));
		$this->TreeTwo->Behaviors->load('Tree', array(
			'scope' => 'FirstTree'
		));

		$data = array(
			'NumberTreeTwo' => array(
				'name' => 'First',
				'number_tree_id' => $record['FlagTree']['id']
			)
		);
		$this->TreeTwo->create();
		$result = $this->TreeTwo->save($data);
		$this->assertFalse(empty($result));

		$result = $this->TreeTwo->find('first');
		$expected = array('NumberTreeTwo' => array(
			'id' => 1,
			'name' => 'First',
			'number_tree_id' => $record['FlagTree']['id'],
			'parent_id' => null,
			'lft' => 1,
			'rght' => 2
		));
		$this->assertEquals($expected, $result);
	}

/**
 * testGenerateTreeListWithScope method
 *
 * @return void
 */
	public function testGenerateTreeListWithScope() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 3);

		$this->Tree->id = 1;
		$this->Tree->saveField('flag', 1);
		$this->Tree->id = 2;
		$this->Tree->saveField('flag', 1);

		$this->Tree->Behaviors->load('Tree', array('scope' => array('FlagTree.flag' => 1)));

		$result = $this->Tree->generateTreeList();
		$expected = array(
			1 => '1. Root',
			2 => '_1.1'
		);
		$this->assertEquals($expected, $result);

		// As string.
		$this->Tree->Behaviors->load('Tree', array('scope' => 'FlagTree.flag = 1'));

		$result = $this->Tree->generateTreeList();
		$this->assertEquals($expected, $result);

		// Merging conditions.
		$result = $this->Tree->generateTreeList(array('FlagTree.id >' => 1));
		$expected = array(
			2 => '1.1'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testRecoverUsingParentMode method
 *
 * @return void
 */
	public function testRecoverUsingParentMode() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 3);

		$this->Tree->Behaviors->load('Tree', array('scope' => 'FlagTree.flag = 1'));
		$this->Tree->Behaviors->disable('Tree');

		$this->Tree->create();
		$this->Tree->save(array('name' => 'Main', $parentField => null, $leftField => 0, $rightField => 0, 'flag' => 1));
		$node1 = $this->Tree->id;

		$this->Tree->create();
		$this->Tree->save(array('name' => 'About Us', $parentField => $node1, $leftField => 0, $rightField => 0, 'flag' => 1));
		$node11 = $this->Tree->id;

		$this->Tree->create();
		$this->Tree->save(array('name' => 'Programs', $parentField => $node1, $leftField => 0, $rightField => 0, 'flag' => 1));
		$node12 = $this->Tree->id;

		$this->Tree->create();
		$this->Tree->save(array('name' => 'Mission and History', $parentField => $node11, $leftField => 0, $rightField => 0, 'flag' => 1));

		$this->Tree->create();
		$this->Tree->save(array('name' => 'Overview', $parentField => $node12, $leftField => 0, $rightField => 0, 'flag' => 1));

		$this->Tree->Behaviors->enable('Tree');

		$result = $this->Tree->verify();
		$this->assertNotSame(true, $result);

		$result = $this->Tree->recover();
		$this->assertTrue($result);

		$result = $this->Tree->verify();
		$this->assertTrue($result);

		$result = $this->Tree->find('first', array(
			'fields' => array('name', $parentField, $leftField, $rightField, 'flag'),
			'conditions' => array('name' => 'Main'),
			'recursive' => -1
		));
		$expected = array(
			$modelClass => array(
				'name' => 'Main',
				$parentField => null,
				$leftField => 1,
				$rightField => 10,
				'flag' => 1
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testRecoverFromMissingParent method
 *
 * @return void
 */
	public function testRecoverFromMissingParent() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$this->Tree->id = 1;
		$this->Tree->saveField('flag', 1);
		$this->Tree->id = 2;
		$this->Tree->saveField('flag', 1);

		$this->Tree->Behaviors->load('Tree', array('scope' => array('FlagTree.flag' => 1)));

		$result = $this->Tree->findByName('1.1');
		$this->Tree->updateAll(array($parentField => 999999), array('id' => $result[$modelClass]['id']));

		$result = $this->Tree->verify();
		$this->assertNotSame(true, $result);

		$result = $this->Tree->recover();
		$this->assertTrue($result);

		$result = $this->Tree->verify();
		$this->assertTrue($result);
	}

/**
 * testDetectInvalidParents method
 *
 * @return void
 */
	public function testDetectInvalidParents() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$this->Tree->id = 1;
		$this->Tree->saveField('flag', 1);
		$this->Tree->id = 2;
		$this->Tree->saveField('flag', 1);

		$this->Tree->Behaviors->load('Tree', array('scope' => array('FlagTree.flag' => 1)));

		$this->Tree->updateAll(array($parentField => null));

		$result = $this->Tree->verify();
		$this->assertNotSame(true, $result);

		$result = $this->Tree->recover();
		$this->assertTrue($result);

		$result = $this->Tree->verify();
		$this->assertTrue($result);
	}

/**
 * testDetectInvalidLftsRghts method
 *
 * @return void
 */
	public function testDetectInvalidLftsRghts() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$this->Tree->id = 1;
		$this->Tree->saveField('flag', 1);
		$this->Tree->id = 2;
		$this->Tree->saveField('flag', 1);

		$this->Tree->Behaviors->load('Tree', array('scope' => array('FlagTree.flag' => 1)));

		$this->Tree->updateAll(array($leftField => 0, $rightField => 0));

		$result = $this->Tree->verify();
		$this->assertNotSame(true, $result);

		$this->Tree->recover();

		$result = $this->Tree->verify();
		$this->assertTrue($result);
	}

/**
 * Reproduces a situation where a single node has lft= rght, and all other lft and rght fields follow sequentially
 *
 * @return void
 */
	public function testDetectEqualLftsRghts() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(1, 3);

		$this->Tree->id = 1;
		$this->Tree->saveField('flag', 1);
		$this->Tree->id = 2;
		$this->Tree->saveField('flag', 1);

		$this->Tree->Behaviors->load('Tree', array('scope' => array('FlagTree.flag' => 1)));

		$result = $this->Tree->findByName('1.1');
		$this->Tree->updateAll(array($rightField => $result[$modelClass][$leftField]), array('id' => $result[$modelClass]['id']));
		$this->Tree->updateAll(array($leftField => $this->Tree->escapeField($leftField) . ' -1'),
			array($leftField . ' >' => $result[$modelClass][$leftField]));
		$this->Tree->updateAll(array($rightField => $this->Tree->escapeField($rightField) . ' -1'),
			array($rightField . ' >' => $result[$modelClass][$leftField]));

		$result = $this->Tree->verify();
		$this->assertNotSame(true, $result);

		$result = $this->Tree->recover();
		$this->assertTrue($result);

		$result = $this->Tree->verify();
		$this->assertTrue($result);
	}

}
