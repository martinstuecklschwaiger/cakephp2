<?php
/**
 * AllComponentsTest file
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
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * AllComponentsTest class
 *
 * This test group will run component class tests
 *
 * @package       Cake.Test.Case
 */
#[\AllowDynamicProperties]
class AllComponentsTest extends PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new CakeTestSuite('All component class tests');

		$suite->addTestFile(CORE_TEST_CASES . DS . 'Controller' . DS . 'ComponentTest.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'Controller' . DS . 'ComponentCollectionTest.php');
		$suite->addTestDirectoryRecursive(CORE_TEST_CASES . DS . 'Controller' . DS . 'Component');
		return $suite;
	}
}
