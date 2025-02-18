<?php
/**
 * Fixture to test be tested exclusively with InnoDB tables
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/view/1196/Testing>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 2.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * InnoFixture
 *
 * @package       Cake.Test.Fixture
 */
#[\AllowDynamicProperties]
class InnoFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => true),
		'tableParameters' => array(
			'engine' => 'InnoDB'
		)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('name' => 'Name 1'),
		array('name' => 'Name 2'),
	);

}
