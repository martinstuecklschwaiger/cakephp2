<?php
/**
 * TreeBehaviorAfterTest file
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
 * TreeBehaviorAfterTest class
 *
 * @package       Cake.Test.Case.Model.Behavior
 */
#[\AllowDynamicProperties]
class TreeBehaviorAfterTest extends CakeTestCase {

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
		'modelClass' => 'AfterTree',
		'leftField' => 'lft',
		'rightField' => 'rght',
		'parentField' => 'parent_id'
	);

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array('core.after_tree');

/**
 * Tests the afterSave callback in the model
 *
 * @return void
 */
	public function testAftersaveCallback() {
		$this->Tree = new AfterTree();
		$this->Tree->order = null;

		$expected = array('AfterTree' => array('name' => 'Six and One Half Changed in AfterTree::afterSave() but not in database', 'parent_id' => 6, 'lft' => 11, 'rght' => 12));
		$result = $this->Tree->save(array('AfterTree' => array('name' => 'Six and One Half', 'parent_id' => 6)));
		$expected['AfterTree']['id'] = $this->Tree->id;
		$this->assertEquals($expected, $result);

		$expected = array('AfterTree' => array('name' => 'Six and One Half', 'parent_id' => 6, 'lft' => 11, 'rght' => 12, 'id' => 8));
		$result = $this->Tree->find('all');
		$this->assertEquals($expected, $result[7]);
	}
}
