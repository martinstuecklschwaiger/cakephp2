<?php
/**
 * Database Session save handler. Allows saving session information into a model.
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
 * @package       Cake.Model.Datasource.Session
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeSessionHandlerInterface', 'Model/Datasource/Session');
App::uses('ClassRegistry', 'Utility');

/**
 * DatabaseSession provides methods to be used with CakeSession.
 *
 * @package       Cake.Model.Datasource.Session
 */
#[\AllowDynamicProperties]
class DatabaseSession implements CakeSessionHandlerInterface {

/**
 * Reference to the model handling the session data
 *
 * @var Model
 */
	protected $_model;

/**
 * Number of seconds to mark the session as expired
 *
 * @var int
 */
	protected $_timeout;

/**
 * Constructor. Looks at Session configuration information and
 * sets up the session model.
 */
	public function __construct() {
		$modelName = Configure::read('Session.handler.model');

		if (empty($modelName)) {
			$settings = array(
				'class' => 'Session',
				'alias' => 'Session',
				'table' => 'cake_sessions',
			);
		} else {
			$settings = array(
				'class' => $modelName,
				'alias' => 'Session',
			);
		}
		$this->_model = ClassRegistry::init($settings);
		$this->_timeout = Configure::read('Session.timeout') * 60;
	}

/**
 * Method called on open of a database session.
 *
 * @return bool Success
 */
	public function open() {
		return true;
	}

/**
 * Method called on close of a database session.
 *
 * @return bool Success
 */
	public function close() {
		return true;
	}

/**
 * Method used to read from a database session.
 *
 * @param int|string $id The key of the value to read
 * @return mixed The value of the key or false if it does not exist
 */
	public function read($id) {
		$row = $this->_model->find('first', array(
			'conditions' => array($this->_model->alias . '.' . $this->_model->primaryKey => $id)
		));

		if (empty($row[$this->_model->alias])) {
			return '';
		}

		if (!is_numeric($row[$this->_model->alias]['data']) && empty($row[$this->_model->alias]['data'])) {
			return '';
		}

		return (string)$row[$this->_model->alias]['data'];
	}

/**
 * Helper function called on write for database sessions.
 *
 * Will retry, once, if the save triggers a PDOException which
 * can happen if a race condition is encountered
 *
 * @param int $id ID that uniquely identifies session in database
 * @param mixed $data The value of the data to be saved.
 * @return bool True for successful write, false otherwise.
 */
	public function write($id, $data) {
		if (!$id) {
			return false;
		}
		$expires = time() + $this->_timeout;
		$record = compact('id', 'data', 'expires');
		$record[$this->_model->primaryKey] = $id;

		$options = array(
			'validate' => false,
			'callbacks' => false,
			'counterCache' => false
		);
		try {
			return (bool)$this->_model->save($record, $options);
		} catch (PDOException $e) {
			return (bool)$this->_model->save($record, $options);
		}
	}

/**
 * Method called on the destruction of a database session.
 *
 * @param int $id ID that uniquely identifies session in database
 * @return bool True for successful delete, false otherwise.
 */
	public function destroy($id) {
		return (bool)$this->_model->delete($id);
	}

/**
 * Helper function called on gc for database sessions.
 *
 * @param int $expires Timestamp (defaults to current time)
 * @return bool Success
 */
	public function gc($expires = null) {
		if (!$expires) {
			$expires = time();
		} else {
			$expires = time() - $expires;
		}
		$this->_model->deleteAll(array($this->_model->alias . ".expires <" => $expires), false, false);
		return true;
	}

}
