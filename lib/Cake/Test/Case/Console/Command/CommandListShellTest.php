<?php
/**
 * CommandListShellTest file
 *
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CommandListShell', 'Console/Command');
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('Shell', 'Console');
App::uses('CommandTask', 'Console/Command/Task');

/**
 * TestStringOutput
 *
 * @package       Cake.Test.Case.Console.Command
 */
#[\AllowDynamicProperties]
class TestStringOutput extends ConsoleOutput {

	public $output = '';

	protected function _write($message) {
		$this->output .= $message;
	}

}

/**
 * CommandListShellTest
 *
 * @package       Cake.Test.Case.Console.Command
 */
#[\AllowDynamicProperties]
class CommandListShellTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		App::build(array(
			'Plugin' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS
			),
			'Console/Command' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'Console' . DS . 'Command' . DS
			)
		), App::RESET);
		CakePlugin::load(array('TestPlugin', 'TestPluginTwo'));

		$out = new TestStringOutput();
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Shell = $this->getMock(
			'CommandListShell',
			array('in', '_stop', 'clear'),
			array($out, $out, $in)
		);

		$this->Shell->Command = $this->getMock(
			'CommandTask',
			array('in', '_stop', 'clear'),
			array($out, $out, $in)
		);
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Shell);
		CakePlugin::unload();
	}

/**
 * test that main finds core shells.
 *
 * @return void
 */
	public function testMain() {
		$this->Shell->main();
		$output = $this->Shell->stdout->output;

		$expected = "/\[.*TestPlugin.*\] example/";
		$this->assertRegExp($expected, $output);

		$expected = "/\[.*TestPluginTwo.*\] example, welcome/";
		$this->assertRegExp($expected, $output);

		$expected = "/\[.*CORE.*\] acl, api, bake, command_list, completion, console, i18n, schema, server, test, testsuite, upgrade/";
		$this->assertRegExp($expected, $output);

		$expected = "/\[.*app.*\] sample/";
		$this->assertRegExp($expected, $output);
	}

/**
 * test xml output.
 *
 * @return void
 */
	public function testMainXml() {
		$this->Shell->params['xml'] = true;
		$this->Shell->main();

		$output = $this->Shell->stdout->output;

		$find = '<shell name="sample" call_as="sample" provider="app" help="sample -h"/>';
		$this->assertContains($find, $output);

		$find = '<shell name="bake" call_as="bake" provider="CORE" help="bake -h"/>';
		$this->assertContains($find, $output);

		$find = '<shell name="welcome" call_as="TestPluginTwo.welcome" provider="TestPluginTwo" help="TestPluginTwo.welcome -h"/>';
		$this->assertContains($find, $output);
	}
}
