<?php
/**
 * built-in Server Shell
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
 * @since         CakePHP(tm) v 2.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppShell', 'Console/Command');

/**
 * built-in Server Shell
 *
 * @package       Cake.Console.Command
 */
#[\AllowDynamicProperties]
class ServerShell extends AppShell {

/**
 * Default ServerHost
 *
 * @var string
 */
	const DEFAULT_HOST = 'localhost';

/**
 * Default ListenPort
 *
 * @var int
 */
	const DEFAULT_PORT = 80;

/**
 * server host
 *
 * @var string
 */
	protected $_host = null;

/**
 * listen port
 *
 * @var string
 */
	protected $_port = null;

/**
 * document root
 *
 * @var string
 */
	protected $_documentRoot = null;

/**
 * Override initialize of the Shell
 *
 * @return void
 */
	public function initialize() {
		$this->_host = static::DEFAULT_HOST;
		$this->_port = static::DEFAULT_PORT;
		$this->_documentRoot = WWW_ROOT;
	}

/**
 * Starts up the Shell and displays the welcome message.
 * Allows for checking and configuring prior to command or main execution
 *
 * Override this method if you want to remove the welcome information,
 * or otherwise modify the pre-command flow.
 *
 * @return void
 * @link https://book.cakephp.org/2.0/en/console-and-shells.html#Shell::startup
 */
	public function startup() {
		if (!empty($this->params['host'])) {
			$this->_host = $this->params['host'];
		}
		if (!empty($this->params['port'])) {
			$this->_port = $this->params['port'];
		}
		if (!empty($this->params['document_root'])) {
			$this->_documentRoot = $this->params['document_root'];
		}

		// for Windows
		if (substr($this->_documentRoot, -1, 1) === DIRECTORY_SEPARATOR) {
			$this->_documentRoot = substr($this->_documentRoot, 0, strlen($this->_documentRoot) - 1);
		}
		if (preg_match("/^([a-z]:)[\\\]+(.+)$/i", $this->_documentRoot, $m)) {
			$this->_documentRoot = $m[1] . '\\' . $m[2];
		}

		parent::startup();
	}

/**
 * Displays a header for the shell
 *
 * @return void
 */
	protected function _welcome() {
		$this->out();
		$this->out(__d('cake_console', '<info>Welcome to CakePHP %s Console</info>', 'v' . Configure::version()));
		$this->hr();
		$this->out(__d('cake_console', 'App : %s', APP_DIR));
		$this->out(__d('cake_console', 'Path: %s', APP));
		$this->out(__d('cake_console', 'DocumentRoot: %s', $this->_documentRoot));
		$this->hr();
	}

/**
 * Override main() to handle action
 *
 * @return void
 */
	public function main() {
		if (version_compare(PHP_VERSION, '5.4.0') < 0) {
			$this->out(__d('cake_console', '<warning>This command is available on %s or above</warning>', 'PHP5.4'));
			return;
		}

		$command = sprintf("php -S %s:%d -t %s %s",
			$this->_host,
			$this->_port,
			escapeshellarg($this->_documentRoot),
			escapeshellarg($this->_documentRoot . '/index.php')
		);

		$port = ($this->_port == static::DEFAULT_PORT) ? '' : ':' . $this->_port;
		$this->out(__d('cake_console', 'built-in server is running in http://%s%s/', $this->_host, $port));
		system($command);
	}

/**
 * Gets the option parser instance and configures it.
 *
 * @return ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$parser->description(array(
			__d('cake_console', 'PHP Built-in Server for CakePHP'),
			__d('cake_console', '<warning>[WARN] Don\'t use this at the production environment</warning>')
		))->addOption('host', array(
			'short' => 'H',
			'help' => __d('cake_console', 'ServerHost')
		))->addOption('port', array(
			'short' => 'p',
			'help' => __d('cake_console', 'ListenPort')
		))->addOption('document_root', array(
			'short' => 'd',
			'help' => __d('cake_console', 'DocumentRoot')
		));

		return $parser;
	}
}
