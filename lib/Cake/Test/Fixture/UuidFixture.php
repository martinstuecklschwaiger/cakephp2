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
 * @since         CakePHP(tm) v 1.2.0.6700
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * UuidFixture.
 *
 * @package       Cake.Test.Fixture
 */
#[\AllowDynamicProperties]
class UuidFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'length' => 36, 'key' => 'primary'),
		'title' => 'string',
		'count' => array('type' => 'integer', 'default' => 0),
		'created' => 'datetime',
		'updated' => 'datetime'
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('id' => '47c36f9c-bc00-4d17-9626-4e183ca6822b', 'title' => 'Unique record 1', 'count' => 2, 'created' => '2008-03-13 01:16:23', 'updated' => '2008-03-13 01:18:31'),
		array('id' => '47c36f9c-f2b0-43f5-b3f7-4e183ca6822b', 'title' => 'Unique record 2', 'count' => 4, 'created' => '2008-03-13 01:18:24', 'updated' => '2008-03-13 01:20:32'),
		array('id' => '47c36f9c-0ffc-4084-9b03-4e183ca6822b', 'title' => 'Unique record 3', 'count' => 5, 'created' => '2008-03-13 01:20:25', 'updated' => '2008-03-13 01:22:33'),
		array('id' => '47c36f9c-2578-4c2e-aeab-4e183ca6822b', 'title' => 'Unique record 4', 'count' => 3, 'created' => '2008-03-13 01:22:26', 'updated' => '2008-03-13 01:24:34'),
	);
}
