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
 * SampleFixture
 *
 * @package       Cake.Test.Fixture
 */
#[\AllowDynamicProperties]
class SampleFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'apple_id' => array('type' => 'integer', 'null' => false),
		'name' => array('type' => 'string', 'length' => 40, 'null' => false)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('apple_id' => 3, 'name' => 'sample1'),
		array('apple_id' => 2, 'name' => 'sample2'),
		array('apple_id' => 4, 'name' => 'sample3'),
		array('apple_id' => 5, 'name' => 'sample4')
	);
}
