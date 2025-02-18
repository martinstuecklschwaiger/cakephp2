<?php
/**
 * AllDatabaseTest file
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
 * AllDatabaseTest class
 *
 * This test group will run database tests not in model or behavior group.
 *
 * @package       Cake.Test.Case
 */
#[\AllowDynamicProperties]
class AllDatabaseTest extends PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('Datasources, Schema and DbAcl tests');

		$path = CORE_TEST_CASES . DS . 'Model' . DS;
		$tasks = array(
			'AclNode',
			'CakeSchema',
			'ConnectionManager',
			'Datasource' . DS . 'DboSource',
			'Datasource' . DS . 'Database' . DS . 'Mysql',
			'Datasource' . DS . 'Database' . DS . 'Postgres',
			'Datasource' . DS . 'Database' . DS . 'Sqlite',
			'Datasource' . DS . 'Database' . DS . 'Sqlserver',
			'Datasource' . DS . 'CakeSession',
			'Datasource' . DS . 'Session' . DS . 'CacheSession',
			'Datasource' . DS . 'Session' . DS . 'DatabaseSession',
		);
		foreach ($tasks as $task) {
			$suite->addTestFile($path . $task . 'Test.php');
		}
		return $suite;
	}
}
