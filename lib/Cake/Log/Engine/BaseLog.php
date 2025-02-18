<?php
/**
 * Base Log Engine class
 *
 * CakePHP(tm) :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       Cake.Log.Engine
 * @since         CakePHP(tm) v 2.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeLogInterface', 'Log');

/**
 * Base log engine class.
 *
 * @package       Cake.Log.Engine
 */
#[\AllowDynamicProperties]
abstract class BaseLog implements CakeLogInterface {

/**
 * Engine config
 *
 * @var string
 */
	protected $_config = array();

/**
 * Constructor
 *
 * @param array $config Configuration array
 */
	public function __construct($config = array()) {
		$this->config($config);
	}

/**
 * Sets instance config. When $config is null, returns config array
 *
 * Config
 *
 * - `types` string or array, levels the engine is interested in
 * - `scopes` string or array, scopes the engine is interested in
 *
 * @param array $config engine configuration
 * @return array
 */
	public function config($config = array()) {
		if (!empty($config)) {
			foreach (array('types', 'scopes') as $option) {
				if (isset($config[$option]) && is_string($config[$option])) {
					$config[$option] = array($config[$option]);
				}
			}
			$this->_config = $config;
		}
		return $this->_config;
	}

}
