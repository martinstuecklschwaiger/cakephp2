<?php
/**
 * A class to contain test cases and run them with shared fixtures
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
 * @package       Cake.TestSuite
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Folder', 'Utility');

/**
 * A class to contain test cases and run them with shared fixtures
 *
 * @package       Cake.TestSuite
 */
#[\AllowDynamicProperties]
class CakeTestSuite extends PHPUnit_Framework_TestSuite {

/**
 * Adds all the files in a directory to the test suite. Does not recurse through directories.
 *
 * @param string $directory The directory to add tests from.
 * @return void
 */
	public function addTestDirectory($directory = '.') {
		$Folder = new Folder($directory);
		list(, $files) = $Folder->read(true, true, true);

		foreach ($files as $file) {
			if (substr((string) $file, -4) === '.php') {
				$this->addTestFile($file);
			}
		}
	}

/**
 * Recursively adds all the files in a directory to the test suite.
 *
 * @param string $directory The directory subtree to add tests from.
 * @return void
 */
	public function addTestDirectoryRecursive($directory = '.') {
		$Folder = new Folder($directory);
		$files = $Folder->tree(null, true, 'files');

		foreach ($files as $file) {
			if (substr((string) $file, -4) === '.php') {
				$this->addTestFile($file);
			}
		}
	}

}
