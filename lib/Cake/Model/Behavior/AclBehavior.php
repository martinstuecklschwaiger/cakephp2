<?php
/**
 * ACL behavior class.
 *
 * Enables objects to easily tie into an ACL system
 *
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Model.Behavior
 * @since         CakePHP v 1.2.0.4487
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('ModelBehavior', 'Model');
App::uses('AclNode', 'Model');
App::uses('Hash', 'Utility');

/**
 * ACL behavior
 *
 * Enables objects to easily tie into an ACL system
 *
 * @package       Cake.Model.Behavior
 * @link https://book.cakephp.org/2.0/en/core-libraries/behaviors/acl.html
 */
#[\AllowDynamicProperties]
class AclBehavior extends ModelBehavior {

/**
 * Maps ACL type options to ACL models
 *
 * @var array
 */
	protected $_typeMaps = array('requester' => 'Aro', 'controlled' => 'Aco', 'both' => array('Aro', 'Aco'));

/**
 * Sets up the configuration for the model, and loads ACL models if they haven't been already
 *
 * @param Model $model Model using this behavior.
 * @param array $config Configuration options.
 * @return void
 */
	public function setup(Model $model, $config = array()) {
		if (isset($config[0])) {
			$config['type'] = $config[0];
			unset($config[0]);
		}
		$this->settings[$model->name] = array_merge(array('type' => 'controlled'), $config);
		$this->settings[$model->name]['type'] = strtolower((string) $this->settings[$model->name]['type']);

		$types = $this->_typeMaps[$this->settings[$model->name]['type']];

		if (!is_array($types)) {
			$types = array($types);
		}
		foreach ($types as $type) {
			$model->{$type} = ClassRegistry::init($type);
		}
		if (!method_exists($model, 'parentNode')) {
			trigger_error(__d('cake_dev', 'Callback %s not defined in %s', 'parentNode()', $model->alias), E_USER_WARNING);
		}
	}

/**
 * Retrieves the Aro/Aco node for this model
 *
 * @param Model $model Model using this behavior.
 * @param string|array|Model $ref Array with 'model' and 'foreign_key', model object, or string value
 * @param string $type Only needed when Acl is set up as 'both', specify 'Aro' or 'Aco' to get the correct node
 * @return array
 * @link https://book.cakephp.org/2.0/en/core-libraries/behaviors/acl.html#node
 */
	public function node(Model $model, $ref = null, $type = null) {
		if (empty($type)) {
			$type = $this->_typeMaps[$this->settings[$model->name]['type']];
			if (is_array($type)) {
				trigger_error(__d('cake_dev', 'AclBehavior is setup with more then one type, please specify type parameter for node()'), E_USER_WARNING);
				return array();
			}
		}
		if (empty($ref)) {
			$ref = array('model' => $model->name, 'foreign_key' => $model->id);
		}
		return $model->{$type}->node($ref);
	}

/**
 * Creates a new ARO/ACO node bound to this record
 *
 * @param Model $model Model using this behavior.
 * @param bool $created True if this is a new record
 * @param array $options Options passed from Model::save().
 * @return void
 */
	public function afterSave(Model $model, $created, $options = array()) {
		$types = $this->_typeMaps[$this->settings[$model->name]['type']];
		if (!is_array($types)) {
			$types = array($types);
		}
		foreach ($types as $type) {
			$parent = $model->parentNode($type);
			if (!empty($parent)) {
				$parent = $this->node($model, $parent, $type);
			}
			$data = array(
				'parent_id' => isset($parent[0][$type]['id']) ? $parent[0][$type]['id'] : null,
				'model' => $model->name,
				'foreign_key' => $model->id
			);
			if (!$created) {
				$node = $this->node($model, null, $type);
				$data['id'] = isset($node[0][$type]['id']) ? $node[0][$type]['id'] : null;
			}
			$model->{$type}->create();
			$model->{$type}->save($data);
		}
	}

/**
 * Destroys the ARO/ACO node bound to the deleted record
 *
 * @param Model $model Model using this behavior.
 * @return void
 */
	public function afterDelete(Model $model) {
		$types = $this->_typeMaps[$this->settings[$model->name]['type']];
		if (!is_array($types)) {
			$types = array($types);
		}
		foreach ($types as $type) {
			$node = Hash::extract($this->node($model, null, $type), "0.{$type}.id");
			if (!empty($node)) {
				$model->{$type}->delete($node);
			}
		}
	}

}
