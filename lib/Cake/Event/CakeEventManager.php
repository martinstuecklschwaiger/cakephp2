<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright	  Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link		  https://cakephp.org CakePHP(tm) Project
 * @package		  Cake.Event
 * @since		  CakePHP(tm) v 2.1
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeEventListener', 'Event');
App::uses('CakeEvent', 'Event');

/**
 * The event manager is responsible for keeping track of event listeners, passing the correct
 * data to them, and firing them in the correct order, when associated events are triggered. You
 * can create multiple instances of this object to manage local events or keep a single instance
 * and pass it around to manage all events in your app.
 *
 * @package Cake.Event
 */
#[\AllowDynamicProperties]
class CakeEventManager {

/**
 * The default priority queue value for new, attached listeners
 *
 * @var int
 */
	public static $defaultPriority = 10;

/**
 * The globally available instance, used for dispatching events attached from any scope
 *
 * @var CakeEventManager
 */
	protected static $_generalManager = null;

/**
 * List of listener callbacks associated to
 *
 * @var object
 */
	protected $_listeners = array();

/**
 * Internal flag to distinguish a common manager from the singleton
 *
 * @var bool
 */
	protected $_isGlobal = false;

/**
 * Returns the globally available instance of a CakeEventManager
 * this is used for dispatching events attached from outside the scope
 * other managers were created. Usually for creating hook systems or inter-class
 * communication
 *
 * If called with the first parameter, it will be set as the globally available instance
 *
 * @param CakeEventManager $manager Optional event manager instance.
 * @return CakeEventManager the global event manager
 */
	public static function instance($manager = null) {
		if ($manager instanceof CakeEventManager) {
			static::$_generalManager = $manager;
		}
		if (empty(static::$_generalManager)) {
			static::$_generalManager = new CakeEventManager();
		}

		static::$_generalManager->_isGlobal = true;
		return static::$_generalManager;
	}

/**
 * Adds a new listener to an event. Listeners
 *
 * @param callable|CakeEventListener $callable PHP valid callback type or instance of CakeEventListener to be called
 * when the event named with $eventKey is triggered. If a CakeEventListener instance is passed, then the `implementedEvents`
 * method will be called on the object to register the declared events individually as methods to be managed by this class.
 * It is possible to define multiple event handlers per event name.
 *
 * @param string $eventKey The event unique identifier name with which the callback will be associated. If $callable
 * is an instance of CakeEventListener this argument will be ignored
 *
 * @param array $options used to set the `priority` and `passParams` flags to the listener.
 * Priorities are handled like queues, and multiple attachments added to the same priority queue will be treated in
 * the order of insertion. `passParams` means that the event data property will be converted to function arguments
 * when the listener is called. If $called is an instance of CakeEventListener, this parameter will be ignored
 *
 * @return void
 * @throws InvalidArgumentException When event key is missing or callable is not an
 *   instance of CakeEventListener.
 */
	public function attach($callable, $eventKey = null, $options = array()) {
		if (!$eventKey && !($callable instanceof CakeEventListener)) {
			throw new InvalidArgumentException(__d('cake_dev', 'The eventKey variable is required'));
		}
		if ($callable instanceof CakeEventListener) {
			$this->_attachSubscriber($callable);
			return;
		}
		$options = $options + array('priority' => static::$defaultPriority, 'passParams' => false);
		$this->_listeners[$eventKey][$options['priority']][] = array(
			'callable' => $callable,
			'passParams' => $options['passParams'],
		);
	}

/**
 * Auxiliary function to attach all implemented callbacks of a CakeEventListener class instance
 * as individual methods on this manager
 *
 * @param CakeEventListener $subscriber Event listener.
 * @return void
 */
	protected function _attachSubscriber(CakeEventListener $subscriber) {
		foreach ((array)$subscriber->implementedEvents() as $eventKey => $function) {
			$options = array();
			$method = $function;
			if (is_array($function) && isset($function['callable'])) {
				list($method, $options) = $this->_extractCallable($function, $subscriber);
			} elseif (is_array($function) && is_numeric(key($function))) {
				foreach ($function as $f) {
					list($method, $options) = $this->_extractCallable($f, $subscriber);
					$this->attach($method, $eventKey, $options);
				}
				continue;
			}
			if (is_string($method)) {
				$method = array($subscriber, $function);
			}
			$this->attach($method, $eventKey, $options);
		}
	}

/**
 * Auxiliary function to extract and return a PHP callback type out of the callable definition
 * from the return value of the `implementedEvents` method on a CakeEventListener
 *
 * @param array $function the array taken from a handler definition for an event
 * @param CakeEventListener $object The handler object
 * @return callable
 */
	protected function _extractCallable($function, $object) {
		$method = $function['callable'];
		$options = $function;
		unset($options['callable']);
		if (is_string($method)) {
			$method = array($object, $method);
		}
		return array($method, $options);
	}

/**
 * Removes a listener from the active listeners.
 *
 * @param callable|CakeEventListener $callable any valid PHP callback type or an instance of CakeEventListener
 * @param string $eventKey The event unique identifier name with which the callback has been associated
 * @return void
 */
	public function detach($callable, $eventKey = null) {
		if ($callable instanceof CakeEventListener) {
			return $this->_detachSubscriber($callable, $eventKey);
		}
		if (empty($eventKey)) {
			foreach (array_keys($this->_listeners) as $eventKey) {
				$this->detach($callable, $eventKey);
			}
			return;
		}
		if (empty($this->_listeners[$eventKey])) {
			return;
		}
		foreach ($this->_listeners[$eventKey] as $priority => $callables) {
			foreach ($callables as $k => $callback) {
				if ($callback['callable'] === $callable) {
					unset($this->_listeners[$eventKey][$priority][$k]);
					break;
				}
			}
		}
	}

/**
 * Auxiliary function to help detach all listeners provided by an object implementing CakeEventListener
 *
 * @param CakeEventListener $subscriber the subscriber to be detached
 * @param string $eventKey optional event key name to unsubscribe the listener from
 * @return void
 */
	protected function _detachSubscriber(CakeEventListener $subscriber, $eventKey = null) {
		$events = (array)$subscriber->implementedEvents();
		if (!empty($eventKey) && empty($events[$eventKey])) {
			return;
		} elseif (!empty($eventKey)) {
			$events = array($eventKey => $events[$eventKey]);
		}
		foreach ($events as $key => $function) {
			if (is_array($function)) {
				if (is_numeric(key($function))) {
					foreach ($function as $handler) {
						$handler = isset($handler['callable']) ? $handler['callable'] : $handler;
						$this->detach(array($subscriber, $handler), $key);
					}
					continue;
				}
				$function = $function['callable'];
			}
			$this->detach(array($subscriber, $function), $key);
		}
	}

/**
 * Dispatches a new event to all configured listeners
 *
 * @param string|CakeEvent $event the event key name or instance of CakeEvent
 * @return CakeEvent
 * @triggers $event
 */
	public function dispatch($event) {
		if (is_string($event)) {
			$event = new CakeEvent($event);
		}

		$listeners = $this->listeners($event->name());
		if (empty($listeners)) {
			return $event;
		}

		foreach ($listeners as $listener) {
			if ($event->isStopped()) {
				break;
			}
			if ($listener['passParams'] === true) {
				$result = call_user_func_array($listener['callable'], $event->data);
			} else {
				$result = call_user_func($listener['callable'], $event);
			}
			if ($result === false) {
				$event->stopPropagation();
			}
			if ($result !== null) {
				$event->result = $result;
			}
		}
		return $event;
	}

/**
 * Returns a list of all listeners for an eventKey in the order they should be called
 *
 * @param string $eventKey Event key.
 * @return array
 */
	public function listeners($eventKey) {
		$localListeners = array();
		$priorities = array();
		if (!$this->_isGlobal) {
			$localListeners = $this->prioritisedListeners($eventKey);
			$localListeners = empty($localListeners) ? array() : $localListeners;
		}
		$globalListeners = static::instance()->prioritisedListeners($eventKey);
		$globalListeners = empty($globalListeners) ? array() : $globalListeners;

		$priorities = array_merge(array_keys($globalListeners), array_keys($localListeners));
		$priorities = array_unique($priorities);
		asort($priorities);

		$result = array();
		foreach ($priorities as $priority) {
			if (isset($globalListeners[$priority])) {
				$result = array_merge($result, $globalListeners[$priority]);
			}
			if (isset($localListeners[$priority])) {
				$result = array_merge($result, $localListeners[$priority]);
			}
		}
		return $result;
	}

/**
 * Returns the listeners for the specified event key indexed by priority
 *
 * @param string $eventKey Event key.
 * @return array
 */
	public function prioritisedListeners($eventKey) {
		if (empty($this->_listeners[$eventKey])) {
			return array();
		}
		return $this->_listeners[$eventKey];
	}
}
