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
 * ProductUpdateAllFixture
 *
 * @package       Cake.Test.Fixture
 */
#[\AllowDynamicProperties]
class ProductUpdateAllFixture extends CakeTestFixture {

	public $table = 'product_update_all';

	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => false, 'length' => 29),
		'groupcode' => array('type' => 'integer', 'null' => false, 'length' => 4),
		'group_id' => array('type' => 'integer', 'null' => false, 'length' => 8),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1))
	);

	public $records = array(
		array(
			'id' => 1,
			'name' => 'product one',
			'groupcode' => 120,
			'group_id' => 1
		),
		array(
			'id' => 2,
			'name' => 'product two',
			'groupcode' => 120,
			'group_id' => 1
		),
		array(
			'id' => 3,
			'name' => 'product three',
			'groupcode' => 125,
			'group_id' => 2
		),
		array(
			'id' => 4,
			'name' => 'product four',
			'groupcode' => 135,
			'group_id' => 4
		),
		);
}
