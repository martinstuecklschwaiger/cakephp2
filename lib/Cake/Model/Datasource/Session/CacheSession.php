<?php
/**
 * Cache Session save handler. Allows saving session information into Cache.
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

App::uses('Cache', 'Cache');
App::uses('CakeSessionHandlerInterface', 'Model/Datasource/Session');

/**
 * CacheSession provides method for saving sessions into a Cache engine. Used with CakeSession
 *
 * @package       Cake.Model.Datasource.Session
 * @see CakeSession for configuration information.
 */
#[\AllowDynamicProperties]
class CacheSession implements CakeSessionHandlerInterface {

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
 * @param string $id The key of the value to read
 * @return mixed The value of the key or false if it does not exist
 */
	public function read($id) {
		$data = Cache::read($id, Configure::read('Session.handler.config'));

		if (!is_numeric($data) && empty($data)) {
			return '';
		}
		return $data;
	}

/**
 * Helper function called on write for database sessions.
 *
 * @param int $id ID that uniquely identifies session in database
 * @param mixed $data The value of the data to be saved.
 * @return bool True for successful write, false otherwise.
 */
	public function write($id, $data) {
		return (bool)Cache::write($id, $data, Configure::read('Session.handler.config'));
	}

/**
 * Method called on the destruction of a database session.
 *
 * @param int $id ID that uniquely identifies session in cache
 * @return bool True for successful delete, false otherwise.
 */
	public function destroy($id) {
		return (bool)Cache::delete($id, Configure::read('Session.handler.config'));
	}

/**
 * Helper function called on gc for cache sessions.
 *
 * @param int $expires Timestamp (defaults to current time)
 * @return bool Success
 */
	public function gc($expires = null) {
		return (bool)Cache::gc(Configure::read('Session.handler.config'), $expires);
	}

}
