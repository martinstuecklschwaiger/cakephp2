<?php
/**
 * Methods to display or download any type of file
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
 * @package       Cake.View
 * @since         CakePHP(tm) v 1.2.0.5714
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('View', 'View');
App::uses('CakeRequest', 'Network');

/**
 * Media View provides a custom view implementation for sending files to visitors. Its great
 * for making the response of a controller action be a file that is saved somewhere on the filesystem.
 *
 * An example use comes from the CakePHP internals. MediaView is used to serve plugin and theme assets,
 * as they are not normally accessible from an application's webroot. Unlike other views, MediaView
 * uses several viewVars that have special meaning:
 *
 * - `id` The filename on the server's filesystem, including extension.
 * - `name` The filename that will be sent to the user, specified without the extension.
 * - `download` Set to true to set a `Content-Disposition` header. This is ideal for file downloads.
 * - `path` The absolute path, including the trailing / on the server's filesystem to `id`.
 * - `mimeType` The mime type of the file if CakeResponse doesn't know about it.
 * 	Must be an associative array with extension as key and mime type as value eg. array('ini' => 'text/plain')
 *
 * ### Usage
 *
 * ```
 * class ExampleController extends AppController {
 *		public function download() {
 *			$this->viewClass = 'Media';
 *			$params = array(
 *				'id' => 'example.zip',
 *				'name' => 'example',
 *				'download' => true,
 *				'extension' => 'zip',
 *				'path' => APP . 'files' . DS
 *			);
 *			$this->set($params);
 *		}
 * }
 * ```
 *
 * @package       Cake.View
 * @deprecated 3.0.0 Deprecated since version 2.3, use CakeResponse::file() instead
 */
class MediaView extends View {

/**
 * Display or download the given file
 *
 * @param string $view Not used
 * @param string $layout Not used
 * @return void
 */
	public function render($view = null, $layout = null) {
		$name = $extension = $download = $id = $modified = $path = $cache = $mimeType = $compress = null;
		extract($this->viewVars, EXTR_OVERWRITE);

		$path = $path . $id;

		if (is_array($mimeType)) {
			$this->response->type($mimeType);
		}

		if ($cache) {
			if (!empty($modified) && !is_numeric($modified)) {
				$modified = strtotime($modified, time());
			} else {
				$modified = time();
			}
			$this->response->cache($modified, $cache);
		} else {
			$this->response->disableCache();
		}

		if ($name !== null) {
			if (empty($extension)) {
				$extension = pathinfo((string) $id, PATHINFO_EXTENSION);
			}
			if (!empty($extension)) {
				$name .= '.' . $extension;
			}
		}
		$this->response->file($path, compact('name', 'download'));

		if ($compress) {
			$this->response->compress();
		}
	}

}
