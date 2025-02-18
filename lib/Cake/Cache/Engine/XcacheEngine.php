<?php
/**
 * Xcache storage engine for cache.
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
 * @package       Cake.Cache.Engine
 * @since         CakePHP(tm) v 1.2.0.4947
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Xcache storage engine for cache
 *
 * @link          http://trac.lighttpd.net/xcache/ Xcache
 * @package       Cake.Cache.Engine
 */
#[\AllowDynamicProperties]
class XcacheEngine extends CacheEngine {

/**
 * Settings
 *
 *  - PHP_AUTH_USER = xcache.admin.user, default cake
 *  - PHP_AUTH_PW = xcache.admin.password, default cake
 *
 * @var array
 */
	public $settings = array();

/**
 * Initialize the Cache Engine
 *
 * Called automatically by the cache frontend
 * To reinitialize the settings call Cache::engine('EngineName', [optional] settings = array());
 *
 * @param array $settings array of setting for the engine
 * @return bool True if the engine has been successfully initialized, false if not
 */
	public function init($settings = array()) {
		if (PHP_SAPI !== 'cli') {
			parent::init(array_merge(array(
				'engine' => 'Xcache',
				'prefix' => Inflector::slug(APP_DIR) . '_',
				'PHP_AUTH_USER' => 'user',
				'PHP_AUTH_PW' => 'password'
				), $settings)
			);
			return function_exists('xcache_info');
		}
		return false;
	}

/**
 * Write data for key into cache
 *
 * @param string $key Identifier for the data
 * @param mixed $value Data to be cached
 * @param int $duration How long to cache the data, in seconds
 * @return bool True if the data was successfully cached, false on failure
 */
	public function write($key, $value, $duration) {
		$expires = time() + $duration;
		xcache_set($key . '_expires', $expires, $duration);
		return xcache_set($key, $value, $duration);
	}

/**
 * Read a key from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 */
	public function read($key) {
		if (xcache_isset($key)) {
			$time = time();
			$cachetime = (int)xcache_get($key . '_expires');
			if ($cachetime < $time || ($time + $this->settings['duration']) < $cachetime) {
				return false;
			}
			return xcache_get($key);
		}
		return false;
	}

/**
 * Increments the value of an integer cached key
 * If the cache key is not an integer it will be treated as 0
 *
 * @param string $key Identifier for the data
 * @param int $offset How much to increment
 * @return New incremented value, false otherwise
 */
	public function increment($key, $offset = 1) {
		return xcache_inc($key, $offset);
	}

/**
 * Decrements the value of an integer cached key.
 * If the cache key is not an integer it will be treated as 0
 *
 * @param string $key Identifier for the data
 * @param int $offset How much to subtract
 * @return New decremented value, false otherwise
 */
	public function decrement($key, $offset = 1) {
		return xcache_dec($key, $offset);
	}

/**
 * Delete a key from the cache
 *
 * @param string $key Identifier for the data
 * @return bool True if the value was successfully deleted, false if it didn't exist or couldn't be removed
 */
	public function delete($key) {
		return xcache_unset($key);
	}

/**
 * Delete all keys from the cache
 *
 * @param bool $check If true no deletes will occur and instead CakePHP will rely
 *   on key TTL values.
 * @return bool True if the cache was successfully cleared, false otherwise
 */
	public function clear($check) {
		$this->_auth();
		$max = xcache_count(XC_TYPE_VAR);
		for ($i = 0; $i < $max; $i++) {
			xcache_clear_cache(XC_TYPE_VAR, $i);
		}
		$this->_auth(true);
		return true;
	}

/**
 * Returns the `group value` for each of the configured groups
 * If the group initial value was not found, then it initializes
 * the group accordingly.
 *
 * @return array
 */
	public function groups() {
		$result = array();
		foreach ($this->settings['groups'] as $group) {
			$value = xcache_get($this->settings['prefix'] . $group);
			if (!$value) {
				$value = 1;
				xcache_set($this->settings['prefix'] . $group, $value, 0);
			}
			$result[] = $group . $value;
		}
		return $result;
	}

/**
 * Increments the group value to simulate deletion of all keys under a group
 * old values will remain in storage until they expire.
 *
 * @param string $group The group to clear.
 * @return bool success
 */
	public function clearGroup($group) {
		return (bool)xcache_inc($this->settings['prefix'] . $group, 1);
	}

/**
 * Populates and reverses $_SERVER authentication values
 * Makes necessary changes (and reverting them back) in $_SERVER
 *
 * This has to be done because xcache_clear_cache() needs to pass Basic Http Auth
 * (see xcache.admin configuration settings)
 *
 * @param bool $reverse Revert changes
 * @return void
 */
	protected function _auth($reverse = false) {
		static $backup = array();
		$keys = array('PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => 'password');
		foreach ($keys as $key => $setting) {
			if ($reverse) {
				if (isset($backup[$key])) {
					$_SERVER[$key] = $backup[$key];
					unset($backup[$key]);
				} else {
					unset($_SERVER[$key]);
				}
			} else {
				$value = env($key);
				if (!empty($value)) {
					$backup[$key] = $value;
				}
				if (!empty($this->settings[$setting])) {
					$_SERVER[$key] = $this->settings[$setting];
				} elseif (!empty($this->settings[$key])) {
					$_SERVER[$key] = $this->settings[$key];
				} else {
					$_SERVER[$key] = $value;
				}
			}
		}
	}

/**
 * Write data for key into cache if it doesn't exist already.
 * If it already exists, it fails and returns false.
 *
 * @param string $key Identifier for the data.
 * @param mixed $value Data to be cached.
 * @param int $duration How long to cache the data, in seconds.
 * @return bool True if the data was successfully cached, false on failure.
 */
	public function add($key, $value, $duration) {
		$cachedValue = $this->read($key);
		if ($cachedValue === false) {
			return $this->write($key, $value, $duration);
		}
		return false;
	}
}
