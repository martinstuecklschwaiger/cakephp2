<?php
/**
 * AllDbRelatedTest file
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
 * @package       Cake.Test.Case
 * @since         CakePHP(tm) v 2.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * AllDbRelatedTest class
 *
 * This test group will run db related tests.
 *
 * @package       Cake.Test.Case
 */
#[\AllowDynamicProperties]
class AllDbRelatedTest extends PHPUnit_Framework_TestSuite {

/**
 * Suite define the tests for this suite
 *
 * @return void
 */
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('All Db Related Tests');

		$path = CORE_TEST_CASES . DS;

		$suite->addTestFile($path . 'AllBehaviorsTest.php');
		$suite->addTestFile($path . 'Controller' . DS . 'Component' . DS . 'PaginatorComponentTest.php');
		$suite->addTestFile($path . 'AllDatabaseTest.php');
		$suite->addTestFile($path . 'Model' . DS . 'ModelTest.php');
		$suite->addTestFile($path . 'View' . DS . 'ViewTest.php');
		$suite->addTestFile($path . 'View' . DS . 'ScaffoldViewTest.php');
		$suite->addTestFile($path . 'View' . DS . 'HelperTest.php');
		$suite->addTestFile($path . 'View' . DS . 'Helper' . DS . 'FormHelperTest.php');
		$suite->addTestFile($path . 'View' . DS . 'Helper' . DS . 'PaginatorHelperTest.php');
		return $suite;
	}
}
