<?php
/**
 * TestRunner for CakePHP Test suite.
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

if (!class_exists('PHPUnit_TextUI_Command')) {
	require_once 'PHPUnit/TextUI/Command.php';
}

App::uses('CakeTestRunner', 'TestSuite');
App::uses('CakeTestLoader', 'TestSuite');
App::uses('CakeTestSuite', 'TestSuite');
App::uses('CakeTestCase', 'TestSuite');
App::uses('ControllerTestCase', 'TestSuite');
App::uses('CakeTestModel', 'TestSuite/Fixture');

/**
 * Class to customize loading of test suites from CLI
 *
 * @package       Cake.TestSuite
 */
#[\AllowDynamicProperties]
class CakeTestSuiteCommand extends PHPUnit_TextUI_Command {

/**
 * Construct method
 *
 * @param mixed $loader The loader instance to use.
 * @param array $params list of options to be used for this run
 * @throws MissingTestLoaderException When a loader class could not be found.
 */
	public function __construct($loader, $params = array()) {
		if ($loader && !class_exists($loader)) {
			throw new MissingTestLoaderException(array('class' => $loader));
		}
		$this->arguments['loader'] = $loader;
		$this->arguments['test'] = $params['case'];
		$this->arguments['testFile'] = $params;
		$this->_params = $params;

		$this->longOptions['fixture='] = 'handleFixture';
		$this->longOptions['output='] = 'handleReporter';
	}

/**
 * Ugly hack to get around PHPUnit having a hard coded class name for the Runner. :(
 *
 * @param array $argv The command arguments
 * @param bool $exit The exit mode.
 * @return void
 */
	public function run(array $argv, $exit = true) {
		$this->handleArguments($argv);

		$runner = $this->getRunner($this->arguments['loader']);

		if (is_object($this->arguments['test']) &&
			$this->arguments['test'] instanceof PHPUnit_Framework_Test) {
			$suite = $this->arguments['test'];
		} else {
			$suite = $runner->getTest(
				$this->arguments['test'],
				$this->arguments['testFile']
			);
		}

		if ($this->arguments['listGroups']) {
			PHPUnit_TextUI_TestRunner::printVersionString();

			print "Available test group(s):\n";

			$groups = $suite->getGroups();
			sort($groups);

			foreach ($groups as $group) {
				print " - $group\n";
			}

			exit(PHPUnit_TextUI_TestRunner::SUCCESS_EXIT);
		}

		unset($this->arguments['test']);
		unset($this->arguments['testFile']);

		try {
			$result = $runner->doRun($suite, $this->arguments, false);
		} catch (PHPUnit_Framework_Exception $e) {
			print $e->getMessage() . "\n";
		}

		if ($exit) {
			if (!isset($result) || $result->errorCount() > 0) {
				exit(PHPUnit_TextUI_TestRunner::EXCEPTION_EXIT);
			}
			if ($result->failureCount() > 0) {
				exit(PHPUnit_TextUI_TestRunner::FAILURE_EXIT);
			}

			if (!empty($this->arguments["failOnWarning"]) && $result->warningCount() > 0) {
				exit(PHPUnit_TextUI_TestRunner::FAILURE_EXIT);
			}

			if (!empty($this->arguments["failOnRisky"]) && !$result->allHarmless()) {
				exit(PHPUnit_TextUI_TestRunner::FAILURE_EXIT);
			}

			// Default to success even if there are warnings to match phpunit's behavior
			exit(PHPUnit_TextUI_TestRunner::SUCCESS_EXIT);
		}
	}

/**
 * Create a runner for the command.
 *
 * @param mixed $loader The loader to be used for the test run.
 * @return CakeTestRunner
 */
	public function getRunner($loader) {
		return new CakeTestRunner($loader, $this->_params);
	}

/**
 * Handler for customizing the FixtureManager class/
 *
 * @param string $class Name of the class that will be the fixture manager
 * @return void
 */
	public function handleFixture($class) {
		$this->arguments['fixtureManager'] = $class;
	}

/**
 * Handles output flag used to change printing on webrunner.
 *
 * @param string $reporter The reporter class to use.
 * @return void
 */
	public function handleReporter($reporter) {
		$object = null;

		$reporter = ucwords($reporter);
		$coreClass = 'Cake' . $reporter . 'Reporter';
		App::uses($coreClass, 'TestSuite/Reporter');

		$appClass = $reporter . 'Reporter';
		App::uses($appClass, 'TestSuite/Reporter');

		if (!class_exists($appClass)) {
			$object = new $coreClass(null, $this->_params);
		} else {
			$object = new $appClass(null, $this->_params);
		}
		return $this->arguments['printer'] = $object;
	}

}
