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
 * @since         CakePHP(tm) v 1.2.0.6317
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * JoinAFixture
 *
 * @package       Cake.Test.Fixture
 */
#[\AllowDynamicProperties]
class JoinAFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'name' => array('type' => 'string', 'default' => ''),
		'body' => array('type' => 'text'),
		'created' => array('type' => 'datetime', 'null' => true),
		'updated' => array('type' => 'datetime', 'null' => true)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('name' => 'Join A 1', 'body' => 'Join A 1 Body', 'created' => '2008-01-03 10:54:23', 'updated' => '2008-01-03 10:54:23'),
		array('name' => 'Join A 2', 'body' => 'Join A 2 Body', 'created' => '2008-01-03 10:54:24', 'updated' => '2008-01-03 10:54:24'),
		array('name' => 'Join A 3', 'body' => 'Join A 2 Body', 'created' => '2008-01-03 10:54:25', 'updated' => '2008-01-03 10:54:24')
	);
}
