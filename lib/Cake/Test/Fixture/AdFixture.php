<?php
/**
 * Short description for ad_fixture.php
 *
 * Long description for ad_fixture.php
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          http://www.cakephp.org
 * @package       Cake.Test.Fixture
 * @since         1.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * AdFixture class
 *
 * @package       Cake.Test.Fixture
 */
#[\AllowDynamicProperties]
class AdFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'campaign_id' => array('type' => 'integer'),
		'parent_id' => array('type' => 'integer'),
		'lft' => array('type' => 'integer'),
		'rght' => array('type' => 'integer'),
		'name' => array('type' => 'string', 'length' => 255, 'null' => false)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('parent_id' => null, 'lft' => 1, 'rght' => 2, 'campaign_id' => 1, 'name' => 'Nordover'),
		array('parent_id' => null, 'lft' => 3, 'rght' => 4, 'campaign_id' => 1, 'name' => 'Statbergen'),
		array('parent_id' => null, 'lft' => 5, 'rght' => 6, 'campaign_id' => 1, 'name' => 'Feroy'),
		array('parent_id' => null, 'lft' => 7, 'rght' => 12, 'campaign_id' => 2, 'name' => 'Newcastle'),
		array('parent_id' => null, 'lft' => 8, 'rght' => 9, 'campaign_id' => 2, 'name' => 'Dublin'),
		array('parent_id' => null, 'lft' => 10, 'rght' => 11, 'campaign_id' => 2, 'name' => 'Alborg'),
		array('parent_id' => null, 'lft' => 13, 'rght' => 14, 'campaign_id' => 3, 'name' => 'New York')
	);
}
