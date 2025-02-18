<?php
/**
 * ShellDispatcher file
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
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Shell dispatcher handles dispatching cli commands.
 *
 * @package       Cake.Console
 */
#[\AllowDynamicProperties]
class ShellDispatcher {

/**
 * Contains command switches parsed from the command line.
 *
 * @var array
 */
	public $params = array();

/**
 * Contains arguments parsed from the command line.
 *
 * @var array
 */
	public $args = array();

/**
 * Constructor
 *
 * The execution of the script is stopped after dispatching the request with
 * a status code of either 0 or 1 according to the result of the dispatch.
 *
 * @param array $args the argv from PHP
 * @param bool $bootstrap Should the environment be bootstrapped.
 */
	public function __construct($args = array(), $bootstrap = true) {
		set_time_limit(0);
		$this->parseParams($args);

		if ($bootstrap) {
			$this->_initConstants();
			$this->_initEnvironment();
		}
	}

/**
 * Run the dispatcher
 *
 * @param array $argv The argv from PHP
 * @return void
 */
	public static function run($argv) {
		$dispatcher = new ShellDispatcher($argv);
		return $dispatcher->_stop($dispatcher->dispatch() === false ? 1 : 0);
	}

/**
 * Defines core configuration.
 *
 * @return void
 */
	protected function _initConstants() {
		if (function_exists('ini_set')) {
			ini_set('html_errors', false);
			ini_set('implicit_flush', true);
			ini_set('max_execution_time', 0);
		}

		if (!defined('CAKE_CORE_INCLUDE_PATH')) {
			define('CAKE_CORE_INCLUDE_PATH', dirname(dirname(dirname(__FILE__))));
			define('CAKEPHP_SHELL', true);
			if (!defined('DS')) {
				define('DS', DIRECTORY_SEPARATOR);
			}
			if (!defined('CORE_PATH')) {
				define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
			}
		}
	}

/**
 * Defines current working environment.
 *
 * @return void
 * @throws CakeException
 */
	protected function _initEnvironment() {
		if (!$this->_bootstrap()) {
			$message = "Unable to load CakePHP core.\nMake sure " . DS . 'lib' . DS . 'Cake exists in ' . CAKE_CORE_INCLUDE_PATH;
			throw new CakeException($message);
		}

		if (!isset($this->args[0]) || !isset($this->params['working'])) {
			$message = "This file has been loaded incorrectly and cannot continue.\n" .
				"Please make sure that " . DS . 'lib' . DS . 'Cake' . DS . "Console is in your system path,\n" .
				"and check the cookbook for the correct usage of this command.\n" .
				"(https://book.cakephp.org/)";
			throw new CakeException($message);
		}

		$this->shiftArgs();
	}

/**
 * Initializes the environment and loads the CakePHP core.
 *
 * @return bool Success.
 */
	protected function _bootstrap() {
		if (!defined('ROOT')) {
			define('ROOT', $this->params['root']);
		}
		if (!defined('APP_DIR')) {
			define('APP_DIR', $this->params['app']);
		}
		if (!defined('APP')) {
			define('APP', $this->params['working'] . DS);
		}
		if (!defined('WWW_ROOT')) {
			if (!$this->_isAbsolutePath($this->params['webroot'])) {
				$webroot = realpath(APP . $this->params['webroot']);
			} else {
				$webroot = $this->params['webroot'];
			}
			define('WWW_ROOT', $webroot . DS);
		}
		if (!defined('TMP') && !is_dir(APP . 'tmp')) {
			define('TMP', CAKE_CORE_INCLUDE_PATH . DS . 'Cake' . DS . 'Console' . DS . 'Templates' . DS . 'skel' . DS . 'tmp' . DS);
		}

		if (!defined('CONFIG')) {
			define('CONFIG', ROOT . DS . APP_DIR . DS . 'Config' . DS);
		}
		// $boot is used by Cake/bootstrap.php file
		$boot = file_exists(CONFIG . 'bootstrap.php');
		require CORE_PATH . 'Cake' . DS . 'bootstrap.php';

		if (!file_exists(CONFIG . 'core.php')) {
			include_once CAKE_CORE_INCLUDE_PATH . DS . 'Cake' . DS . 'Console' . DS . 'Templates' . DS . 'skel' . DS . 'Config' . DS . 'core.php';
			App::build();
		}

		$this->setErrorHandlers();

		if (!defined('FULL_BASE_URL')) {
			$url = Configure::read('App.fullBaseUrl');
			define('FULL_BASE_URL', $url ? $url : 'http://localhost');
			Configure::write('App.fullBaseUrl', FULL_BASE_URL);
		}

		return true;
	}

/**
 * Set the error/exception handlers for the console
 * based on the `Error.consoleHandler`, and `Exception.consoleHandler` values
 * if they are set. If they are not set, the default ConsoleErrorHandler will be
 * used.
 *
 * @return void
 */
	public function setErrorHandlers() {
		App::uses('ConsoleErrorHandler', 'Console');
		$error = Configure::read('Error');
		$exception = Configure::read('Exception');

		$errorHandler = new ConsoleErrorHandler();
		if (empty($error['consoleHandler'])) {
			$error['consoleHandler'] = array($errorHandler, 'handleError');
			Configure::write('Error', $error);
		}
		if (empty($exception['consoleHandler'])) {
			$exception['consoleHandler'] = array($errorHandler, 'handleException');
			Configure::write('Exception', $exception);
		}
		set_exception_handler($exception['consoleHandler']);
		set_error_handler($error['consoleHandler'], Configure::read('Error.level'));

		App::uses('Debugger', 'Utility');
		Debugger::getInstance()->output('txt');
	}

/**
 * Dispatches a CLI request
 *
 * @return bool
 * @throws MissingShellMethodException
 */
	public function dispatch() {
		$shell = $this->shiftArgs();

		if (!$shell) {
			$this->help();
			return false;
		}
		if (in_array($shell, array('help', '--help', '-h'))) {
			$this->help();
			return true;
		}

		$Shell = $this->_getShell($shell);

		$command = null;
		if (isset($this->args[0])) {
			$command = $this->args[0];
		}

		if ($Shell instanceof Shell) {
			$Shell->initialize();
			return $Shell->runCommand($command, $this->args);
		}
		$methods = array_diff(get_class_methods($Shell), get_class_methods('Shell'));
		$added = in_array($command, $methods);
		$private = substr((string) $command, 0, 1) === '_' && method_exists($Shell, $command);

		if (!$private) {
			if ($added) {
				$this->shiftArgs();
				$Shell->startup();
				return $Shell->{$command}();
			}
			if (method_exists($Shell, 'main')) {
				$Shell->startup();
				return $Shell->main();
			}
		}

		throw new MissingShellMethodException(array('shell' => $shell, 'method' => $command));
	}

/**
 * Get shell to use, either plugin shell or application shell
 *
 * All paths in the loaded shell paths are searched.
 *
 * @param string $shell Optionally the name of a plugin
 * @return mixed An object
 * @throws MissingShellException when errors are encountered.
 */
	protected function _getShell($shell) {
		list($plugin, $shell) = pluginSplit($shell, true);

		$plugin = Inflector::camelize($plugin);
		$class = Inflector::camelize($shell) . 'Shell';

		App::uses('Shell', 'Console');
		App::uses('AppShell', 'Console/Command');
		App::uses($class, $plugin . 'Console/Command');

		if (!class_exists($class)) {
			$plugin = Inflector::camelize($shell) . '.';
			App::uses($class, $plugin . 'Console/Command');
		}

		if (!class_exists($class)) {
			throw new MissingShellException(array(
				'class' => $class
			));
		}
		$Shell = new $class();
		$Shell->plugin = trim($plugin, '.');
		return $Shell;
	}

/**
 * Parses command line options and extracts the directory paths from $params
 *
 * @param array $args Parameters to parse
 * @return void
 */
	public function parseParams($args) {
		$this->_parsePaths($args);

		$defaults = array(
			'app' => 'app',
			'root' => dirname(dirname(dirname(dirname(__FILE__)))),
			'working' => null,
			'webroot' => 'webroot'
		);
		$params = array_merge($defaults, array_intersect_key($this->params, $defaults));
		$isWin = false;
		foreach ($defaults as $default => $value) {
			if (strpos((string) $params[$default], '\\') !== false) {
				$isWin = true;
				break;
			}
		}
		$params = str_replace('\\', '/', $params);

		if (isset($params['working'])) {
			$params['working'] = trim((string) $params['working']);
		}

		if (!empty($params['working']) && (!isset($this->args[0]) || isset($this->args[0]) && $this->args[0][0] !== '.')) {
			if ($params['working'][0] === '.') {
				$params['working'] = realpath($params['working']);
			}
			if (empty($this->params['app']) && $params['working'] != $params['root']) {
				$params['root'] = dirname((string) $params['working']);
				$params['app'] = basename((string) $params['working']);
			} else {
				$params['root'] = $params['working'];
			}
		}

		if ($this->_isAbsolutePath($params['app'])) {
			$params['root'] = dirname((string) $params['app']);
		} elseif (strpos((string) $params['app'], '/')) {
			$params['root'] .= '/' . dirname((string) $params['app']);
		}
		$isWindowsAppPath = $this->_isWindowsPath($params['app']);
		$params['app'] = basename((string) $params['app']);
		$params['working'] = rtrim((string) $params['root'], '/');
		if (!$isWin || !preg_match('/^[A-Z]:$/i', $params['app'])) {
			$params['working'] .= '/' . $params['app'];
		}

		if ($isWindowsAppPath || !empty($isWin)) {
			$params = str_replace('/', '\\', $params);
		}

		$this->params = $params + $this->params;
	}

/**
 * Checks whether the given path is absolute or relative.
 *
 * @param string $path absolute or relative path.
 * @return bool
 */
	protected function _isAbsolutePath($path) {
		return $path[0] === '/' || $this->_isWindowsPath($path);
	}

/**
 * Checks whether the given path is Window OS path.
 *
 * @param string $path absolute path.
 * @return bool
 */
	protected function _isWindowsPath($path) {
		return preg_match('/([a-z])(:)/i', $path) == 1;
	}

/**
 * Parses out the paths from from the argv
 *
 * @param array $args The argv to parse.
 * @return void
 */
	protected function _parsePaths($args) {
		$parsed = array();
		$keys = array('-working', '--working', '-app', '--app', '-root', '--root', '-webroot', '--webroot');
		$args = (array)$args;
		foreach ($keys as $key) {
			while (($index = array_search($key, $args)) !== false) {
				$keyname = str_replace('-', '', $key);
				$valueIndex = $index + 1;
				$parsed[$keyname] = $args[$valueIndex];
				array_splice($args, $index, 2);
			}
		}
		$this->args = $args;
		$this->params = $parsed;
	}

/**
 * Removes first argument and shifts other arguments up
 *
 * @return mixed Null if there are no arguments otherwise the shifted argument
 */
	public function shiftArgs() {
		return array_shift($this->args);
	}

/**
 * Shows console help. Performs an internal dispatch to the CommandList Shell
 *
 * @return void
 */
	public function help() {
		$this->args = array_merge(array('command_list'), $this->args);
		$this->dispatch();
	}

/**
 * Stop execution of the current script
 *
 * @param int|string $status see http://php.net/exit for values
 * @return void
 */
	protected function _stop($status = 0) {
		exit($status);
	}

}
