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
class ContentAccountFixture extends CakeTestFixture {

	public $table = 'ContentAccounts';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'iContentAccountsId' => array('type' => 'integer', 'key' => 'primary'),
		'iContentId' => array('type' => 'integer'),
		'iAccountId' => array('type' => 'integer')
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('iContentId' => 1, 'iAccountId' => 1),
		array('iContentId' => 2, 'iAccountId' => 2),
		array('iContentId' => 3, 'iAccountId' => 3),
		array('iContentId' => 4, 'iAccountId' => 4),
		array('iContentId' => 1, 'iAccountId' => 2),
		array('iContentId' => 2, 'iAccountId' => 3),
	);
}
