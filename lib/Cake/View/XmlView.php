<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('View', 'View');
App::uses('Xml', 'Utility');
App::uses('Hash', 'Utility');

/**
 * A view class that is used for creating XML responses.
 *
 * By setting the '_serialize' key in your controller, you can specify a view variable
 * that should be serialized to XML and used as the response for the request.
 * This allows you to omit views + layouts, if your just need to emit a single view
 * variable as the XML response.
 *
 * In your controller, you could do the following:
 *
 * `$this->set(array('posts' => $posts, '_serialize' => 'posts'));`
 *
 * When the view is rendered, the `$posts` view variable will be serialized
 * into XML.
 *
 * **Note** The view variable you specify must be compatible with Xml::fromArray().
 *
 * You can also define `'_serialize'` as an array. This will create an additional
 * top level element named `<response>` containing all the named view variables:
 *
 * ```
 * $this->set(compact('posts', 'users', 'stuff'));
 * $this->set('_serialize', array('posts', 'users'));
 * ```
 *
 * The above would generate a XML object that looks like:
 *
 * `<response><posts>...</posts><users>...</users></response>`
 *
 * If you don't use the `_serialize` key, you will need a view. You can use extended
 * views to provide layout like functionality.
 *
 * @package       Cake.View
 * @since         CakePHP(tm) v 2.1.0
 */
class XmlView extends View {

/**
 * The subdirectory. XML views are always in xml.
 *
 * @var string
 */
	public $subDir = 'xml';

/**
 * Constructor
 *
 * @param Controller $controller Controller instance.
 */
	public function __construct(Controller $controller = null) {
		parent::__construct($controller);

		if (isset($controller->response) && $controller->response instanceof CakeResponse) {
			$controller->response->type('xml');
		}
	}

/**
 * Skip loading helpers if this is a _serialize based view.
 *
 * @return void
 */
	public function loadHelpers() {
		if (isset($this->viewVars['_serialize'])) {
			return;
		}
		parent::loadHelpers();
	}

/**
 * Render a XML view.
 *
 * Uses the special '_serialize' parameter to convert a set of
 * view variables into a XML response. Makes generating simple
 * XML responses very easy. You can omit the '_serialize' parameter,
 * and use a normal view + layout as well.
 *
 * @param string $view The view being rendered.
 * @param string $layout The layout being rendered.
 * @return string The rendered view.
 */
	public function render($view = null, $layout = null) {
		if (isset($this->viewVars['_serialize'])) {
			return $this->_serialize($this->viewVars['_serialize']);
		}
		if ($view !== false && $this->_getViewFileName($view)) {
			return parent::render($view, false);
		}
	}

/**
 * Serialize view vars.
 *
 * ### Special parameters
 * `_xmlOptions` You can set an array of custom options for Xml::fromArray() this way, e.g.
 *   'format' as 'attributes' instead of 'tags'.
 *
 * @param array $serialize The viewVars that need to be serialized.
 * @return string The serialized data
 */
	protected function _serialize($serialize) {
		$rootNode = isset($this->viewVars['_rootNode']) ? $this->viewVars['_rootNode'] : 'response';

		if (is_array($serialize)) {
			$data = array($rootNode => array());
			foreach ($serialize as $alias => $key) {
				if (is_numeric($alias)) {
					$alias = $key;
				}
				$data[$rootNode][$alias] = $this->viewVars[$key];
			}
		} else {
			$data = isset($this->viewVars[$serialize]) ? $this->viewVars[$serialize] : null;
			if (is_array($data) && Hash::numeric(array_keys($data))) {
				$data = array($rootNode => array($serialize => $data));
			}
		}

		$options = array();
		if (isset($this->viewVars['_xmlOptions'])) {
			$options = $this->viewVars['_xmlOptions'];
		}
		if (Configure::read('debug')) {
			$options['pretty'] = true;
		}

		if (isset($options['return']) && strtolower((string) $options['return']) === 'domdocument') {
			return Xml::fromArray($data, $options)->saveXML();
		}
		return Xml::fromArray($data, $options)->asXML();
	}

}
