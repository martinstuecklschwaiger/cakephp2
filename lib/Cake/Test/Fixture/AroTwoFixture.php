<?php
/**
 * Short description for file.
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
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
#[\AllowDynamicProperties]
class AroTwoFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'parent_id' => array('type' => 'integer', 'length' => 10, 'null' => true),
		'model' => array('type' => 'string', 'null' => true),
		'foreign_key' => array('type' => 'integer', 'length' => 10, 'null' => true),
		'alias' => array('type' => 'string', 'default' => ''),
		'lft' => array('type' => 'integer', 'length' => 10, 'null' => true),
		'rght' => array('type' => 'integer', 'length' => 10, 'null' => true)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('parent_id' => null, 'model' => null, 'foreign_key' => null, 'alias' => 'root', 'lft' => '1', 'rght' => '20'),
		array('parent_id' => 1, 'model' => 'Group', 'foreign_key' => '1', 'alias' => 'admin', 'lft' => '2', 'rght' => '5'),
		array('parent_id' => 1, 'model' => 'Group', 'foreign_key' => '2', 'alias' => 'managers', 'lft' => '6', 'rght' => '9'),
		array('parent_id' => 1, 'model' => 'Group', 'foreign_key' => '3', 'alias' => 'users', 'lft' => '10', 'rght' => '19'),
		array('parent_id' => 2, 'model' => 'User', 'foreign_key' => '1', 'alias' => 'Bobs', 'lft' => '3', 'rght' => '4'),
		array('parent_id' => 3, 'model' => 'User', 'foreign_key' => '2', 'alias' => 'Lumbergh', 'lft' => '7', 'rght' => '8'),
		array('parent_id' => 4, 'model' => 'User', 'foreign_key' => '3', 'alias' => 'Samir', 'lft' => '11', 'rght' => '12'),
		array('parent_id' => 4, 'model' => 'User', 'foreign_key' => '4', 'alias' => 'Micheal', 'lft' => '13', 'rght' => '14'),
		array('parent_id' => 4, 'model' => 'User', 'foreign_key' => '5', 'alias' => 'Peter', 'lft' => '15', 'rght' => '16'),
		array('parent_id' => 4, 'model' => 'User', 'foreign_key' => '6', 'alias' => 'Milton', 'lft' => '17', 'rght' => '18'),
	);
}
