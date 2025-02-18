<?php
/**
 * Short description for file.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.6879//Correct version number as needed**
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Short description for file.
 *
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.6879//Correct version number as needed**
 */
#[\AllowDynamicProperties]
class DependencyFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => 'integer',
		'child_id' => 'integer',
		'parent_id' => 'integer'
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('id' => 1, 'child_id' => 1, 'parent_id' => 2),
	);
}
