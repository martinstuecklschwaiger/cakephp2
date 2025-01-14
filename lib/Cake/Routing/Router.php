<?php
/**
 * Parses the request URL into controller, action, and parameters.
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
 * @package       Cake.Routing
 * @since         CakePHP(tm) v 0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeRequest', 'Network');
App::uses('CakeRoute', 'Routing/Route');

/**
 * Parses the request URL into controller, action, and parameters. Uses the connected routes
 * to match the incoming URL string to parameters that will allow the request to be dispatched. Also
 * handles converting parameter lists into URL strings, using the connected routes. Routing allows you to decouple
 * the way the world interacts with your application (URLs) and the implementation (controllers and actions).
 *
 * ### Connecting routes
 *
 * Connecting routes is done using Router::connect(). When parsing incoming requests or reverse matching
 * parameters, routes are enumerated in the order they were connected. You can modify the order of connected
 * routes using Router::promote(). For more information on routes and how to connect them see Router::connect().
 *
 * ### Named parameters
 *
 * Named parameters allow you to embed key:value pairs into path segments. This allows you create hash
 * structures using URLs. You can define how named parameters work in your application using Router::connectNamed()
 *
 * @package       Cake.Routing
 */
#[\AllowDynamicProperties]
class Router {

/**
 * Array of routes connected with Router::connect()
 *
 * @var array
 */
	public static $routes = array();

/**
 * Have routes been loaded
 *
 * @var bool
 */
	public static $initialized = false;

/**
 * Contains the base string that will be applied to all generated URLs
 * For example `https://example.com`
 *
 * @var string
 */
	protected static $_fullBaseUrl;

/**
 * List of action prefixes used in connected routes.
 * Includes admin prefix
 *
 * @var array
 */
	protected static $_prefixes = array();

/**
 * Directive for Router to parse out file extensions for mapping to Content-types.
 *
 * @var bool
 */
	protected static $_parseExtensions = false;

/**
 * List of valid extensions to parse from a URL. If null, any extension is allowed.
 *
 * @var array
 */
	protected static $_validExtensions = array();

/**
 * Regular expression for action names
 *
 * @var string
 */
	const ACTION = 'index|show|add|create|edit|update|remove|del|delete|view|item';

/**
 * Regular expression for years
 *
 * @var string
 */
	const YEAR = '[12][0-9]{3}';

/**
 * Regular expression for months
 *
 * @var string
 */
	const MONTH = '0[1-9]|1[012]';

/**
 * Regular expression for days
 *
 * @var string
 */
	const DAY = '0[1-9]|[12][0-9]|3[01]';

/**
 * Regular expression for auto increment IDs
 *
 * @var string
 */
	const ID = '[0-9]+';

/**
 * Regular expression for UUIDs
 *
 * @var string
 */
	const UUID = '[A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12}';

/**
 * Named expressions
 *
 * @var array
 */
	protected static $_namedExpressions = array(
		'Action' => Router::ACTION,
		'Year' => Router::YEAR,
		'Month' => Router::MONTH,
		'Day' => Router::DAY,
		'ID' => Router::ID,
		'UUID' => Router::UUID
	);

/**
 * Stores all information necessary to decide what named arguments are parsed under what conditions.
 *
 * @var string
 */
	protected static $_namedConfig = array(
		'default' => array('page', 'fields', 'order', 'limit', 'recursive', 'sort', 'direction', 'step'),
		'greedyNamed' => true,
		'separator' => ':',
		'rules' => false,
	);

/**
 * The route matching the URL of the current request
 *
 * @var array
 */
	protected static $_currentRoute = array();

/**
 * Default HTTP request method => controller action map.
 *
 * @var array
 */
	protected static $_resourceMap = array(
		array('action' => 'index', 'method' => 'GET', 'id' => false),
		array('action' => 'view', 'method' => 'GET', 'id' => true),
		array('action' => 'add', 'method' => 'POST', 'id' => false),
		array('action' => 'edit', 'method' => 'PUT', 'id' => true),
		array('action' => 'delete', 'method' => 'DELETE', 'id' => true),
		array('action' => 'edit', 'method' => 'POST', 'id' => true)
	);

/**
 * List of resource-mapped controllers
 *
 * @var array
 */
	protected static $_resourceMapped = array();

/**
 * Maintains the request object stack for the current request.
 * This will contain more than one request object when requestAction is used.
 *
 * @var array
 */
	protected static $_requests = array();

/**
 * Initial state is populated the first time reload() is called which is at the bottom
 * of this file. This is a cheat as get_class_vars() returns the value of static vars even if they
 * have changed.
 *
 * @var array
 */
	protected static $_initialState = array();

/**
 * Default route class to use
 *
 * @var string
 */
	protected static $_routeClass = 'CakeRoute';

/**
 * Set the default route class to use or return the current one
 *
 * @param string $routeClass The route class to set as default.
 * @return string|null The default route class.
 * @throws RouterException
 */
	public static function defaultRouteClass($routeClass = null) {
		if ($routeClass === null) {
			return static::$_routeClass;
		}

		static::$_routeClass = static::_validateRouteClass($routeClass);
	}

/**
 * Validates that the passed route class exists and is a subclass of CakeRoute
 *
 * @param string $routeClass Route class name
 * @return string
 * @throws RouterException
 */
	protected static function _validateRouteClass($routeClass) {
		if ($routeClass !== 'CakeRoute' &&
			(!class_exists($routeClass) || !is_subclass_of($routeClass, 'CakeRoute'))
		) {
			throw new RouterException(__d('cake_dev', 'Route class not found, or route class is not a subclass of CakeRoute'));
		}
		return $routeClass;
	}

/**
 * Sets the Routing prefixes.
 *
 * @return void
 */
	protected static function _setPrefixes() {
		$routing = Configure::read('Routing');
		if (!empty($routing['prefixes'])) {
			static::$_prefixes = array_merge(static::$_prefixes, (array)$routing['prefixes']);
		}
	}

/**
 * Gets the named route elements for use in app/Config/routes.php
 *
 * @return array Named route elements
 * @see Router::$_namedExpressions
 */
	public static function getNamedExpressions() {
		return static::$_namedExpressions;
	}

/**
 * Resource map getter & setter.
 *
 * @param array $resourceMap Resource map
 * @return mixed
 * @see Router::$_resourceMap
 */
	public static function resourceMap($resourceMap = null) {
		if ($resourceMap === null) {
			return static::$_resourceMap;
		}
		static::$_resourceMap = $resourceMap;
	}

/**
 * Connects a new Route in the router.
 *
 * Routes are a way of connecting request URLs to objects in your application. At their core routes
 * are a set of regular expressions that are used to match requests to destinations.
 *
 * Examples:
 *
 * `Router::connect('/:controller/:action/*');`
 *
 * The first token ':controller' will be used as a controller name while the second is used as the action name.
 * the '/*' syntax makes this route greedy in that it will match requests like `/posts/index` as well as requests
 * like `/posts/edit/1/foo/bar`.
 *
 * `Router::connect('/home-page', array('controller' => 'pages', 'action' => 'display', 'home'));`
 *
 * The above shows the use of route parameter defaults, and providing routing parameters for a static route.
 *
 * ```
 * Router::connect(
 *   '/:lang/:controller/:action/:id',
 *   array(),
 *   array('id' => '[0-9]+', 'lang' => '[a-z]{3}')
 * );
 * ```
 *
 * Shows connecting a route with custom route parameters as well as providing patterns for those parameters.
 * Patterns for routing parameters do not need capturing groups, as one will be added for each route params.
 *
 * $defaults is merged with the results of parsing the request URL to form the final routing destination and its
 * parameters. This destination is expressed as an associative array by Router. See the output of {@link parse()}.
 *
 * $options offers four 'special' keys. `pass`, `named`, `persist` and `routeClass`
 * have special meaning in the $options array.
 *
 * - `pass` is used to define which of the routed parameters should be shifted into the pass array. Adding a
 *   parameter to pass will remove it from the regular route array. Ex. `'pass' => array('slug')`
 * - `persist` is used to define which route parameters should be automatically included when generating
 *   new URLs. You can override persistent parameters by redefining them in a URL or remove them by
 *   setting the parameter to `false`. Ex. `'persist' => array('lang')`
 * - `routeClass` is used to extend and change how individual routes parse requests and handle reverse routing,
 *   via a custom routing class. Ex. `'routeClass' => 'SlugRoute'`
 * - `named` is used to configure named parameters at the route level. This key uses the same options
 *   as Router::connectNamed()
 *
 * You can also add additional conditions for matching routes to the $defaults array.
 * The following conditions can be used:
 *
 * - `[type]` Only match requests for specific content types.
 * - `[method]` Only match requests with specific HTTP verbs.
 * - `[server]` Only match when $_SERVER['SERVER_NAME'] matches the given value.
 *
 * Example of using the `[method]` condition:
 *
 * `Router::connect('/tasks', array('controller' => 'tasks', 'action' => 'index', '[method]' => 'GET'));`
 *
 * The above route will only be matched for GET requests. POST requests will fail to match this route.
 *
 * @param string $route A string describing the template of the route
 * @param array $defaults An array describing the default route parameters. These parameters will be used by default
 *   and can supply routing parameters that are not dynamic. See above.
 * @param array $options An array matching the named elements in the route to regular expressions which that
 *   element should match. Also contains additional parameters such as which routed parameters should be
 *   shifted into the passed arguments, supplying patterns for routing parameters and supplying the name of a
 *   custom routing class.
 * @see routes
 * @see parse().
 * @return array Array of routes
 * @throws RouterException
 */
	public static function connect($route, $defaults = array(), $options = array()) {
		static::$initialized = true;

		foreach (static::$_prefixes as $prefix) {
			if (isset($defaults[$prefix])) {
				if ($defaults[$prefix]) {
					$defaults['prefix'] = $prefix;
				} else {
					unset($defaults[$prefix]);
				}
				break;
			}
		}
		if (isset($defaults['prefix']) && !in_array($defaults['prefix'], static::$_prefixes)) {
			static::$_prefixes[] = $defaults['prefix'];
		}
		$defaults += array('plugin' => null);
		if (empty($options['action'])) {
			$defaults += array('action' => 'index');
		}
		$routeClass = static::$_routeClass;
		if (isset($options['routeClass'])) {
			if (strpos((string) $options['routeClass'], '.') === false) {
				$routeClass = $options['routeClass'];
			} else {
				list(, $routeClass) = pluginSplit($options['routeClass'], true);
			}
			$routeClass = static::_validateRouteClass($routeClass);
			unset($options['routeClass']);
		}
		if ($routeClass === 'RedirectRoute' && isset($defaults['redirect'])) {
			$defaults = $defaults['redirect'];
		}
		static::$routes[] = new $routeClass($route, $defaults, $options);
		return static::$routes;
	}

/**
 * Connects a new redirection Route in the router.
 *
 * Redirection routes are different from normal routes as they perform an actual
 * header redirection if a match is found. The redirection can occur within your
 * application or redirect to an outside location.
 *
 * Examples:
 *
 * `Router::redirect('/home/*', array('controller' => 'posts', 'action' => 'view'), array('persist' => true));`
 *
 * Redirects /home/* to /posts/view and passes the parameters to /posts/view. Using an array as the
 * redirect destination allows you to use other routes to define where a URL string should be redirected to.
 *
 * `Router::redirect('/posts/*', 'http://google.com', array('status' => 302));`
 *
 * Redirects /posts/* to http://google.com with a HTTP status of 302
 *
 * ### Options:
 *
 * - `status` Sets the HTTP status (default 301)
 * - `persist` Passes the params to the redirected route, if it can. This is useful with greedy routes,
 *   routes that end in `*` are greedy. As you can remap URLs and not loose any passed/named args.
 *
 * @param string $route A string describing the template of the route
 * @param array $url A URL to redirect to. Can be a string or a CakePHP array-based URL
 * @param array $options An array matching the named elements in the route to regular expressions which that
 *   element should match. Also contains additional parameters such as which routed parameters should be
 *   shifted into the passed arguments. As well as supplying patterns for routing parameters.
 * @see routes
 * @return array Array of routes
 */
	public static function redirect($route, $url, $options = array()) {
		App::uses('RedirectRoute', 'Routing/Route');
		$options['routeClass'] = 'RedirectRoute';
		if (is_string($url)) {
			$url = array('redirect' => $url);
		}
		return static::connect($route, $url, $options);
	}

/**
 * Specifies what named parameters CakePHP should be parsing out of incoming URLs. By default
 * CakePHP will parse every named parameter out of incoming URLs. However, if you want to take more
 * control over how named parameters are parsed you can use one of the following setups:
 *
 * Do not parse any named parameters:
 *
 * ``` Router::connectNamed(false); ```
 *
 * Parse only default parameters used for CakePHP's pagination:
 *
 * ``` Router::connectNamed(false, array('default' => true)); ```
 *
 * Parse only the page parameter if its value is a number:
 *
 * ``` Router::connectNamed(array('page' => '[\d]+'), array('default' => false, 'greedy' => false)); ```
 *
 * Parse only the page parameter no matter what.
 *
 * ``` Router::connectNamed(array('page'), array('default' => false, 'greedy' => false)); ```
 *
 * Parse only the page parameter if the current action is 'index'.
 *
 * ```
 * Router::connectNamed(
 *    array('page' => array('action' => 'index')),
 *    array('default' => false, 'greedy' => false)
 * );
 * ```
 *
 * Parse only the page parameter if the current action is 'index' and the controller is 'pages'.
 *
 * ```
 * Router::connectNamed(
 *    array('page' => array('action' => 'index', 'controller' => 'pages')),
 *    array('default' => false, 'greedy' => false)
 * );
 * ```
 *
 * ### Options
 *
 * - `greedy` Setting this to true will make Router parse all named params. Setting it to false will
 *    parse only the connected named params.
 * - `default` Set this to true to merge in the default set of named parameters.
 * - `reset` Set to true to clear existing rules and start fresh.
 * - `separator` Change the string used to separate the key & value in a named parameter. Defaults to `:`
 *
 * @param array $named A list of named parameters. Key value pairs are accepted where values are
 *    either regex strings to match, or arrays as seen above.
 * @param array $options Allows to control all settings: separator, greedy, reset, default
 * @return array
 */
	public static function connectNamed($named, $options = array()) {
		if (isset($options['separator'])) {
			static::$_namedConfig['separator'] = $options['separator'];
			unset($options['separator']);
		}

		if ($named === true || $named === false) {
			$options += array('default' => $named, 'reset' => true, 'greedy' => $named);
			$named = array();
		} else {
			$options += array('default' => false, 'reset' => false, 'greedy' => true);
		}

		if ($options['reset'] || static::$_namedConfig['rules'] === false) {
			static::$_namedConfig['rules'] = array();
		}

		if ($options['default']) {
			$named = array_merge($named, static::$_namedConfig['default']);
		}

		foreach ($named as $key => $val) {
			if (is_numeric($key)) {
				static::$_namedConfig['rules'][$val] = true;
			} else {
				static::$_namedConfig['rules'][$key] = $val;
			}
		}
		static::$_namedConfig['greedyNamed'] = $options['greedy'];
		return static::$_namedConfig;
	}

/**
 * Gets the current named parameter configuration values.
 *
 * @return array
 * @see Router::$_namedConfig
 */
	public static function namedConfig() {
		return static::$_namedConfig;
	}

/**
 * Creates REST resource routes for the given controller(s). When creating resource routes
 * for a plugin, by default the prefix will be changed to the lower_underscore version of the plugin
 * name. By providing a prefix you can override this behavior.
 *
 * ### Options:
 *
 * - 'id' - The regular expression fragment to use when matching IDs. By default, matches
 *    integer values and UUIDs.
 * - 'prefix' - URL prefix to use for the generated routes. Defaults to '/'.
 * - 'connectOptions' – Custom options for connecting the routes.
 *
 * @param string|array $controller A controller name or array of controller names (i.e. "Posts" or "ListItems")
 * @param array $options Options to use when generating REST routes
 * @return array Array of mapped resources
 */
	public static function mapResources($controller, $options = array()) {
		$hasPrefix = isset($options['prefix']);
		$options += array(
			'connectOptions' => array(),
			'prefix' => '/',
			'id' => static::ID . '|' . static::UUID
		);

		$prefix = $options['prefix'];
		$connectOptions = $options['connectOptions'];
		unset($options['connectOptions']);
		if (strpos((string) $prefix, '/') !== 0) {
			$prefix = '/' . $prefix;
		}
		if (substr((string) $prefix, -1) !== '/') {
			$prefix .= '/';
		}

		foreach ((array)$controller as $name) {
			list($plugin, $name) = pluginSplit($name);
			$urlName = Inflector::underscore($name);
			$plugin = Inflector::underscore($plugin);
			if ($plugin && !$hasPrefix) {
				$prefix = '/' . $plugin . '/';
			}

			foreach (static::$_resourceMap as $params) {
				$url = $prefix . $urlName . (($params['id']) ? '/:id' : '');

				Router::connect($url,
					array(
						'plugin' => $plugin,
						'controller' => $urlName,
						'action' => $params['action'],
						'[method]' => $params['method']
					),
					array_merge(
						array('id' => $options['id'], 'pass' => array('id')),
						$connectOptions
					)
				);
			}
			static::$_resourceMapped[] = $urlName;
		}
		return static::$_resourceMapped;
	}

/**
 * Returns the list of prefixes used in connected routes
 *
 * @return array A list of prefixes used in connected routes
 */
	public static function prefixes() {
		return static::$_prefixes;
	}

/**
 * Parses given URL string. Returns 'routing' parameters for that URL.
 *
 * @param string $url URL to be parsed
 * @return array Parsed elements from URL
 */
	public static function parse($url) {
		if (!static::$initialized) {
			static::_loadRoutes();
		}

		$ext = null;
		$out = array();

		if (strlen($url) && strpos($url, '/') !== 0) {
			$url = '/' . $url;
		}
		if (strpos($url, '?') !== false) {
			list($url, $queryParameters) = explode('?', $url, 2);
			parse_str($queryParameters, $queryParameters);
		}

		extract(static::_parseExtension($url));

		foreach (static::$routes as $route) {
			if (($r = $route->parse($url)) !== false) {
				static::$_currentRoute[] = $route;
				$out = $r;
				break;
			}
		}
		if (isset($out['prefix'])) {
			$out['action'] = $out['prefix'] . '_' . $out['action'];
		}

		if (!empty($ext) && !isset($out['ext'])) {
			$out['ext'] = $ext;
		}

		if (!empty($queryParameters) && !isset($out['?'])) {
			$out['?'] = $queryParameters;
		}
		return $out;
	}

/**
 * Parses a file extension out of a URL, if Router::parseExtensions() is enabled.
 *
 * @param string $url URL.
 * @return array Returns an array containing the altered URL and the parsed extension.
 */
	protected static function _parseExtension($url) {
		$ext = null;

		if (static::$_parseExtensions) {
			if (preg_match('/\.[0-9a-zA-Z]*$/', $url, $match) === 1) {
				$match = substr($match[0], 1);
				if (empty(static::$_validExtensions)) {
					$url = substr($url, 0, strpos($url, '.' . $match));
					$ext = $match;
				} else {
					foreach (static::$_validExtensions as $name) {
						if (strcasecmp((string) $name, $match) === 0) {
							$url = substr($url, 0, strpos($url, '.' . $name));
							$ext = $match;
							break;
						}
					}
				}
			}
		}
		return compact('ext', 'url');
	}

/**
 * Takes parameter and path information back from the Dispatcher, sets these
 * parameters as the current request parameters that are merged with URL arrays
 * created later in the request.
 *
 * Nested requests will create a stack of requests. You can remove requests using
 * Router::popRequest(). This is done automatically when using CakeObject::requestAction().
 *
 * Will accept either a CakeRequest object or an array of arrays. Support for
 * accepting arrays may be removed in the future.
 *
 * @param CakeRequest|array $request Parameters and path information or a CakeRequest object.
 * @return void
 */
	public static function setRequestInfo($request) {
		if ($request instanceof CakeRequest) {
			static::$_requests[] = $request;
		} else {
			$requestObj = new CakeRequest();
			$request += array(array(), array());
			$request[0] += array('controller' => false, 'action' => false, 'plugin' => null);
			$requestObj->addParams($request[0])->addPaths($request[1]);
			static::$_requests[] = $requestObj;
		}
	}

/**
 * Pops a request off of the request stack. Used when doing requestAction
 *
 * @return CakeRequest The request removed from the stack.
 * @see Router::setRequestInfo()
 * @see Object::requestAction()
 */
	public static function popRequest() {
		return array_pop(static::$_requests);
	}

/**
 * Gets the current request object, or the first one.
 *
 * @param bool $current True to get the current request object, or false to get the first one.
 * @return CakeRequest|null Null if stack is empty.
 */
	public static function getRequest($current = false) {
		if ($current) {
			$i = count(static::$_requests) - 1;
			return isset(static::$_requests[$i]) ? static::$_requests[$i] : null;
		}
		return isset(static::$_requests[0]) ? static::$_requests[0] : null;
	}

/**
 * Gets parameter information
 *
 * @param bool $current Get current request parameter, useful when using requestAction
 * @return array Parameter information
 */
	public static function getParams($current = false) {
		if ($current && static::$_requests) {
			return static::$_requests[count(static::$_requests) - 1]->params;
		}
		if (isset(static::$_requests[0])) {
			return static::$_requests[0]->params;
		}
		return array();
	}

/**
 * Gets URL parameter by name
 *
 * @param string $name Parameter name
 * @param bool $current Current parameter, useful when using requestAction
 * @return string|null Parameter value
 */
	public static function getParam($name = 'controller', $current = false) {
		$params = Router::getParams($current);
		if (isset($params[$name])) {
			return $params[$name];
		}
		return null;
	}

/**
 * Gets path information
 *
 * @param bool $current Current parameter, useful when using requestAction
 * @return array
 */
	public static function getPaths($current = false) {
		if ($current) {
			return static::$_requests[count(static::$_requests) - 1];
		}
		if (!isset(static::$_requests[0])) {
			return array('base' => null);
		}
		return array('base' => static::$_requests[0]->base);
	}

/**
 * Reloads default Router settings. Resets all class variables and
 * removes all connected routes.
 *
 * @return void
 */
	public static function reload() {
		if (empty(static::$_initialState)) {
			static::$_initialState = get_class_vars('Router');
			static::_setPrefixes();
			return;
		}
		foreach (static::$_initialState as $key => $val) {
			if ($key !== '_initialState') {
				static::${$key} = $val;
			}
		}
		static::_setPrefixes();
	}

/**
 * Promote a route (by default, the last one added) to the beginning of the list
 *
 * @param int $which A zero-based array index representing the route to move. For example,
 *    if 3 routes have been added, the last route would be 2.
 * @return bool Returns false if no route exists at the position specified by $which.
 */
	public static function promote($which = null) {
		if ($which === null) {
			$which = count(static::$routes) - 1;
		}
		if (!isset(static::$routes[$which])) {
			return false;
		}
		$route =& static::$routes[$which];
		unset(static::$routes[$which]);
		array_unshift(static::$routes, $route);
		return true;
	}

/**
 * Finds URL for specified action.
 *
 * Returns a URL pointing to a combination of controller and action. Param
 * $url can be:
 *
 * - Empty - the method will find address to actual controller/action.
 * - '/' - the method will find base URL of application.
 * - A combination of controller/action - the method will find URL for it.
 *
 * There are a few 'special' parameters that can change the final URL string that is generated
 *
 * - `base` - Set to false to remove the base path from the generated URL. If your application
 *   is not in the root directory, this can be used to generate URLs that are 'cake relative'.
 *   cake relative URLs are required when using requestAction.
 * - `?` - Takes an array of query string parameters
 * - `#` - Allows you to set URL hash fragments.
 * - `full_base` - If true the `Router::fullBaseUrl()` value will be prepended to generated URLs.
 *
 * @param string|array $url Cake-relative URL, like "/products/edit/92" or "/presidents/elect/4"
 *   or an array specifying any of the following: 'controller', 'action',
 *   and/or 'plugin', in addition to named arguments (keyed array elements),
 *   and standard URL arguments (indexed array elements)
 * @param bool|array $full If (bool) true, the full base URL will be prepended to the result.
 *   If an array accepts the following keys
 *    - escape - used when making URLs embedded in html escapes query string '&'
 *    - full - if true the full base URL will be prepended.
 * @return string Full translated URL with base path.
 */
	public static function url($url = null, $full = false) {
		if (!static::$initialized) {
			static::_loadRoutes();
		}

		$params = array('plugin' => null, 'controller' => null, 'action' => 'index');

		if (is_bool($full)) {
			$escape = false;
		} else {
			extract($full + array('escape' => false, 'full' => false));
		}

		$path = array('base' => null);
		if (!empty(static::$_requests)) {
			$request = static::$_requests[count(static::$_requests) - 1];
			$params = $request->params;
			$path = array('base' => $request->base, 'here' => $request->here);
		}
		if (empty($path['base'])) {
			$path['base'] = Configure::read('App.base');
		}

		$base = $path['base'];
		$extension = $output = $q = $frag = null;

		if (empty($url)) {
			$output = isset($path['here']) ? $path['here'] : '/';
			if ($full) {
				$output = static::fullBaseUrl() . $output;
			}
			return $output;
		} elseif (is_array($url)) {
			if (isset($url['base']) && $url['base'] === false) {
				$base = null;
				unset($url['base']);
			}
			if (isset($url['full_base']) && $url['full_base'] === true) {
				$full = true;
				unset($url['full_base']);
			}
			if (isset($url['?'])) {
				$q = $url['?'];
				unset($url['?']);
			}
			if (isset($url['#'])) {
				$frag = '#' . $url['#'];
				unset($url['#']);
			}
			if (isset($url['ext'])) {
				$extension = '.' . $url['ext'];
				unset($url['ext']);
			}
			if (empty($url['action'])) {
				if (empty($url['controller']) || $params['controller'] === $url['controller']) {
					$url['action'] = $params['action'];
				} else {
					$url['action'] = 'index';
				}
			}

			$prefixExists = (array_intersect_key($url, array_flip(static::$_prefixes)));
			foreach (static::$_prefixes as $prefix) {
				if (!empty($params[$prefix]) && !$prefixExists) {
					$url[$prefix] = true;
				} elseif (isset($url[$prefix]) && !$url[$prefix]) {
					unset($url[$prefix]);
				}
				if (isset($url[$prefix]) && strpos((string) $url['action'], $prefix . '_') === 0) {
					$url['action'] = substr((string) $url['action'], strlen((string) $prefix) + 1);
				}
			}

			$url += array('controller' => $params['controller'], 'plugin' => $params['plugin']);

			$match = false;

			foreach (static::$routes as $route) {
				$originalUrl = $url;

				$url = $route->persistParams($url, $params);

				if ($match = $route->match($url)) {
					$output = trim((string) $match, '/');
					break;
				}
				$url = $originalUrl;
			}
			if ($match === false) {
				$output = static::_handleNoRoute($url);
			}
		} else {
			if (preg_match('/^([a-z][a-z0-9.+\-]+:|:?\/\/|[#?])/i', $url)) {
				return $url;
			}
			if (substr($url, 0, 1) === '/') {
				$output = substr($url, 1);
			} else {
				foreach (static::$_prefixes as $prefix) {
					if (isset($params[$prefix])) {
						$output .= $prefix . '/';
						break;
					}
				}
				if (!empty($params['plugin']) && $params['plugin'] !== $params['controller']) {
					$output .= Inflector::underscore($params['plugin']) . '/';
				}
				$output .= Inflector::underscore($params['controller']) . '/' . $url;
			}
		}
		$protocol = preg_match('#^[a-z][a-z0-9+\-.]*\://#i', $output);
		if ($protocol === 0) {
			$output = str_replace('//', '/', $base . '/' . $output);

			if ($full) {
				$output = static::fullBaseUrl() . $output;
			}
			if (!empty($extension)) {
				$output = rtrim($output, '/');
			}
		}
		return $output . $extension . static::queryString($q, array(), $escape) . $frag;
	}

/**
 * Sets the full base URL that will be used as a prefix for generating
 * fully qualified URLs for this application. If no parameters are passed,
 * the currently configured value is returned.
 *
 * ## Note:
 *
 * If you change the configuration value ``App.fullBaseUrl`` during runtime
 * and expect the router to produce links using the new setting, you are
 * required to call this method passing such value again.
 *
 * @param string $base the prefix for URLs generated containing the domain.
 * For example: ``http://example.com``
 * @return string
 */
	public static function fullBaseUrl($base = null) {
		if ($base !== null) {
			static::$_fullBaseUrl = $base;
			Configure::write('App.fullBaseUrl', $base);
		}
		if (empty(static::$_fullBaseUrl)) {
			static::$_fullBaseUrl = Configure::read('App.fullBaseUrl');
		}
		return static::$_fullBaseUrl;
	}

/**
 * A special fallback method that handles URL arrays that cannot match
 * any defined routes.
 *
 * @param array $url A URL that didn't match any routes
 * @return string A generated URL for the array
 * @see Router::url()
 */
	protected static function _handleNoRoute($url) {
		$named = $args = array();
		$skip = array_merge(
			array('bare', 'action', 'controller', 'plugin', 'prefix'),
			static::$_prefixes
		);

		$keys = array_values(array_diff(array_keys($url), $skip));

		// Remove this once parsed URL parameters can be inserted into 'pass'
		foreach ($keys as $key) {
			if (is_numeric($key)) {
				$args[] = $url[$key];
			} else {
				$named[$key] = $url[$key];
			}
		}

		list($args, $named) = array(Hash::filter($args), Hash::filter($named));
		foreach (static::$_prefixes as $prefix) {
			$prefixed = $prefix . '_';
			if (!empty($url[$prefix]) && strpos((string) $url['action'], $prefixed) === 0) {
				$url['action'] = substr((string) $url['action'], strlen($prefixed) * -1);
				break;
			}
		}

		if (empty($named) && empty($args) && (!isset($url['action']) || $url['action'] === 'index')) {
			$url['action'] = null;
		}

		$urlOut = array_filter(array($url['controller'], $url['action']));

		if (isset($url['plugin'])) {
			array_unshift($urlOut, $url['plugin']);
		}

		foreach (static::$_prefixes as $prefix) {
			if (isset($url[$prefix])) {
				array_unshift($urlOut, $prefix);
				break;
			}
		}
		$output = implode('/', $urlOut);

		if (!empty($args)) {
			$output .= '/' . implode('/', array_map('rawurlencode', $args));
		}

		if (!empty($named)) {
			foreach ($named as $name => $value) {
				if (is_array($value)) {
					$flattend = Hash::flatten($value, '%5D%5B');
					foreach ($flattend as $namedKey => $namedValue) {
						$output .= '/' . $name . "%5B{$namedKey}%5D" . static::$_namedConfig['separator'] . rawurlencode((string) $namedValue);
					}
				} else {
					$output .= '/' . $name . static::$_namedConfig['separator'] . rawurlencode((string) $value);
				}
			}
		}
		return $output;
	}

/**
 * Generates a well-formed querystring from $q
 *
 * @param string|array $q Query string Either a string of already compiled query string arguments or
 *    an array of arguments to convert into a query string.
 * @param array $extra Extra querystring parameters.
 * @param bool $escape Whether or not to use escaped &
 * @return string|null
 */
	public static function queryString($q, $extra = array(), $escape = false) {
		if (empty($q) && empty($extra)) {
			return null;
		}
		$join = '&';
		if ($escape === true) {
			$join = '&amp;';
		}
		$out = '';

		if (is_array($q)) {
			$q = array_merge($q, $extra);
		} else {
			$out = $q;
			$q = $extra;
		}
		$addition = http_build_query($q, '', $join);

		if ($out && $addition && substr($out, strlen($join) * -1, strlen($join)) !== $join) {
			$out .= $join;
		}

		$out .= $addition;

		if (isset($out[0]) && $out[0] !== '?') {
			$out = '?' . $out;
		}
		return $out;
	}

/**
 * Reverses a parsed parameter array into an array.
 *
 * Works similarly to Router::url(), but since parsed URL's contain additional
 * 'pass' and 'named' as well as 'url.url' keys. Those keys need to be specially
 * handled in order to reverse a params array into a string URL.
 *
 * This will strip out 'autoRender', 'bare', 'requested', and 'return' param names as those
 * are used for CakePHP internals and should not normally be part of an output URL.
 *
 * @param CakeRequest|array $params The params array or CakeRequest object that needs to be reversed.
 * @return array The URL array ready to be used for redirect or HTML link.
 */
	public static function reverseToArray($params) {
		if ($params instanceof CakeRequest) {
			$url = $params->query;
			$params = $params->params;
		} else {
			$url = $params['url'];
		}
		$pass = isset($params['pass']) ? $params['pass'] : array();
		$named = isset($params['named']) ? $params['named'] : array();
		unset(
			$params['pass'], $params['named'], $params['paging'], $params['models'], $params['url'], $url['url'],
			$params['autoRender'], $params['bare'], $params['requested'], $params['return'],
			$params['_Token']
		);
		$params = array_merge($params, $pass, $named);
		if (!empty($url)) {
			$params['?'] = $url;
		}
		return $params;
	}

/**
 * Reverses a parsed parameter array into a string.
 *
 * Works similarly to Router::url(), but since parsed URL's contain additional
 * 'pass' and 'named' as well as 'url.url' keys. Those keys need to be specially
 * handled in order to reverse a params array into a string URL.
 *
 * This will strip out 'autoRender', 'bare', 'requested', and 'return' param names as those
 * are used for CakePHP internals and should not normally be part of an output URL.
 *
 * @param CakeRequest|array $params The params array or CakeRequest object that needs to be reversed.
 * @param bool $full Set to true to include the full URL including the protocol when reversing
 *     the URL.
 * @return string The string that is the reversed result of the array
 */
	public static function reverse($params, $full = false) {
		$params = Router::reverseToArray($params, $full);
		return Router::url($params, $full);
	}

/**
 * Normalizes a URL for purposes of comparison.
 *
 * Will strip the base path off and replace any double /'s.
 * It will not unify the casing and underscoring of the input value.
 *
 * @param array|string $url URL to normalize Either an array or a string URL.
 * @return string Normalized URL
 */
	public static function normalize($url = '/') {
		if (is_array($url)) {
			$url = Router::url($url);
		}
		if (preg_match('/^[a-z\-]+:\/\//', $url)) {
			return $url;
		}
		$request = Router::getRequest();

		if (!empty($request->base) && stristr($url, $request->base)) {
			$url = preg_replace('/^' . preg_quote($request->base, '/') . '/', '', $url, 1);
		}
		$url = '/' . $url;

		while (strpos($url, '//') !== false) {
			$url = str_replace('//', '/', $url);
		}
		$url = preg_replace('/(?:(\/$))/', '', $url);

		if (empty($url)) {
			return '/';
		}
		return $url;
	}

/**
 * Returns the route matching the current request URL.
 *
 * @return CakeRoute Matching route object.
 */
	public static function requestRoute() {
		return static::$_currentRoute[0];
	}

/**
 * Returns the route matching the current request (useful for requestAction traces)
 *
 * @return CakeRoute Matching route object.
 */
	public static function currentRoute() {
		$count = count(static::$_currentRoute) - 1;
		return ($count >= 0) ? static::$_currentRoute[$count] : false;
	}

/**
 * Removes the plugin name from the base URL.
 *
 * @param string $base Base URL
 * @param string $plugin Plugin name
 * @return string base URL with plugin name removed if present
 */
	public static function stripPlugin($base, $plugin = null) {
		if ($plugin) {
			$base = preg_replace('/(?:' . $plugin . ')/', '', $base);
			$base = str_replace('//', '', $base);
			$pos1 = strrpos($base, '/');
			$char = strlen($base) - 1;

			if ($pos1 === $char) {
				$base = substr($base, 0, $char);
			}
		}
		return $base;
	}

/**
 * Instructs the router to parse out file extensions from the URL.
 *
 * For example, http://example.com/posts.rss would yield a file extension of "rss".
 * The file extension itself is made available in the controller as
 * `$this->params['ext']`, and is used by the RequestHandler component to
 * automatically switch to alternate layouts and templates, and load helpers
 * corresponding to the given content, i.e. RssHelper. Switching layouts and helpers
 * requires that the chosen extension has a defined mime type in `CakeResponse`
 *
 * A list of valid extension can be passed to this method, i.e. Router::parseExtensions('rss', 'xml');
 * If no parameters are given, anything after the first . (dot) after the last / in the URL will be
 * parsed, excluding querystring parameters (i.e. ?q=...).
 *
 * @return void
 * @see RequestHandler::startup()
 */
	public static function parseExtensions() {
		static::$_parseExtensions = true;
		if (func_num_args() > 0) {
			static::setExtensions(func_get_args(), false);
		}
	}

/**
 * Get the list of extensions that can be parsed by Router.
 *
 * To initially set extensions use `Router::parseExtensions()`
 * To add more see `setExtensions()`
 *
 * @return array Array of extensions Router is configured to parse.
 */
	public static function extensions() {
		if (!static::$initialized) {
			static::_loadRoutes();
		}

		return static::$_validExtensions;
	}

/**
 * Set/add valid extensions.
 *
 * To have the extensions parsed you still need to call `Router::parseExtensions()`
 *
 * @param array $extensions List of extensions to be added as valid extension
 * @param bool $merge Default true will merge extensions. Set to false to override current extensions
 * @return array
 */
	public static function setExtensions($extensions, $merge = true) {
		if (!is_array($extensions)) {
			return static::$_validExtensions;
		}
		if (!$merge) {
			return static::$_validExtensions = $extensions;
		}
		return static::$_validExtensions = array_merge(static::$_validExtensions, $extensions);
	}

/**
 * Loads route configuration
 *
 * @return void
 */
	protected static function _loadRoutes() {
		static::$initialized = true;
		include CONFIG . 'routes.php';
	}

}

//Save the initial state
Router::reload();
