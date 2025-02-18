<?php
/**
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.TestSuite.Fixture
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeSchema', 'Model');

/**
 * CakeTestFixture is responsible for building and destroying tables to be used
 * during testing.
 *
 * @package       Cake.TestSuite.Fixture
 */
#[\AllowDynamicProperties]
class CakeTestFixture {

/**
 * Name of the object
 *
 * @var string
 */
	public $name = null;

/**
 * CakePHP's DBO driver (e.g: DboMysql).
 *
 * @var object
 */
	public $db = null;

/**
 * Fixture Datasource
 *
 * @var string
 */
	public $useDbConfig = 'test';

/**
 * Full Table Name
 *
 * @var string
 */
	public $table = null;

/**
 * List of datasources where this fixture has been created
 *
 * @var array
 */
	public $created = array();

/**
 * Fields / Schema for the fixture.
 * This array should match the output of Model::schema()
 *
 * @var array
 */
	public $fields = array();

/**
 * Fixture records to be inserted.
 *
 * @var array
 */
	public $records = array();

/**
 * The primary key for the table this fixture represents.
 *
 * @var string
 */
	public $primaryKey = null;

/**
 * Fixture data can be stored in memory by default.
 * When table is created for a fixture the MEMORY engine is used
 * where possible. Set $canUseMemory to false if you don't want this.
 *
 * @var bool
 */
	public $canUseMemory = true;

/**
 * Instantiate the fixture.
 *
 * @throws CakeException on invalid datasource usage.
 */
	public function __construct() {
		if ($this->name === null) {
			if (preg_match('/^(.*)Fixture$/', get_class($this), $matches)) {
				$this->name = $matches[1];
			} else {
				$this->name = get_class($this);
			}
		}
		$connection = 'test';
		if (!empty($this->useDbConfig)) {
			$connection = $this->useDbConfig;
			if (strpos($connection, 'test') !== 0) {
				$message = __d(
					'cake_dev',
					'Invalid datasource name "%s" for "%s" fixture. Fixture datasource names must begin with "test".',
					$connection,
					$this->name
				);
				throw new CakeException($message);
			}
		}
		$this->Schema = new CakeSchema(array('name' => 'TestSuite', 'connection' => $connection));
		$this->init();
	}

/**
 * Initialize the fixture.
 *
 * @return void
 * @throws MissingModelException Whe importing from a model that does not exist.
 */
	public function init() {
		if (isset($this->import) && (is_string($this->import) || is_array($this->import))) {
			$import = array_merge(
				array('connection' => 'default', 'records' => false),
				is_array($this->import) ? $this->import : array('model' => $this->import)
			);

			$this->Schema->connection = $import['connection'];
			if (isset($import['model'])) {
				list($plugin, $modelClass) = pluginSplit($import['model'], true);
				App::uses($modelClass, $plugin . 'Model');
				if (!class_exists($modelClass)) {
					throw new MissingModelException(array('class' => $modelClass));
				}
				$model = new $modelClass(null, null, $import['connection']);
				$db = $model->getDataSource();
				if (empty($model->tablePrefix)) {
					$model->tablePrefix = $db->config['prefix'];
				}
				$this->fields = $model->schema(true);
				$this->fields[$model->primaryKey]['key'] = 'primary';
				$this->table = $db->fullTableName($model, false, false);
				$this->primaryKey = $model->primaryKey;
				ClassRegistry::config(array('ds' => 'test'));
				ClassRegistry::flush();
			} elseif (isset($import['table'])) {
				$model = new Model(null, $import['table'], $import['connection']);
				$db = ConnectionManager::getDataSource($import['connection']);
				$db->cacheSources = false;
				$model->useDbConfig = $import['connection'];
				$model->name = Inflector::camelize(Inflector::singularize($import['table']));
				$model->table = $import['table'];
				$model->tablePrefix = $db->config['prefix'];
				$this->fields = $model->schema(true);
				$this->primaryKey = $model->primaryKey;
				ClassRegistry::flush();
			}

			if (!empty($db->config['prefix']) && strpos($this->table, (string) $db->config['prefix']) === 0) {
				$this->table = str_replace($db->config['prefix'], '', $this->table);
			}

			if (isset($import['records']) && $import['records'] !== false && isset($model) && isset($db)) {
				$this->records = array();
				$query = array(
					'fields' => $db->fields($model, null, array_keys($this->fields)),
					'table' => $db->fullTableName($model),
					'alias' => $model->alias,
					'conditions' => array(),
					'order' => null,
					'limit' => null,
					'group' => null
				);
				$records = $db->fetchAll($db->buildStatement($query, $model), false, $model->alias);

				if ($records !== false && !empty($records)) {
					$this->records = Hash::extract($records, '{n}.' . $model->alias);
				}
			}
		}

		if (!isset($this->table)) {
			$this->table = Inflector::underscore(Inflector::pluralize($this->name));
		}

		if (!isset($this->primaryKey) && isset($this->fields['id'])) {
			$this->primaryKey = 'id';
		}
	}

/**
 * Run before all tests execute, should return SQL statement to create table for this fixture could be executed successfully.
 *
 * @param DboSource $db An instance of the database object used to create the fixture table
 * @return bool True on success, false on failure
 */
	public function create($db) {
		if (!isset($this->fields) || empty($this->fields)) {
			return false;
		}

		if (empty($this->fields['tableParameters']['engine'])) {
			$canUseMemory = $this->canUseMemory;
			foreach ($this->fields as $args) {

				if (is_string($args)) {
					$type = $args;
				} elseif (!empty($args['type'])) {
					$type = $args['type'];
				} else {
					continue;
				}

				if (in_array($type, array('blob', 'text', 'binary'))) {
					$canUseMemory = false;
					break;
				}
			}

			if ($canUseMemory) {
				$this->fields['tableParameters']['engine'] = 'MEMORY';
			}
		}
		$this->Schema->build(array($this->table => $this->fields));
		try {
			$db->execute($db->createSchema($this->Schema), array('log' => false));
			$this->created[] = $db->configKeyName;
		} catch (Exception $e) {
			$msg = __d(
				'cake_dev',
				'Fixture creation for "%s" failed "%s"',
				$this->table,
				$e->getMessage()
			);
			CakeLog::error($msg);
			trigger_error($msg, E_USER_WARNING);
			return false;
		}
		return true;
	}

/**
 * Run after all tests executed, should return SQL statement to drop table for this fixture.
 *
 * @param DboSource $db An instance of the database object used to create the fixture table
 * @return bool True on success, false on failure
 */
	public function drop($db) {
		if (empty($this->fields)) {
			return false;
		}
		$this->Schema->build(array($this->table => $this->fields));
		try {

			$db->execute($db->dropSchema($this->Schema), array('log' => false));
			$this->created = array_diff($this->created, array($db->configKeyName));
		} catch (Exception $e) {
			return false;
		}
		return true;
	}

/**
 * Run before each tests is executed, should return a set of SQL statements to insert records for the table
 * of this fixture could be executed successfully.
 *
 * @param DboSource $db An instance of the database into which the records will be inserted
 * @return bool on success or if there are no records to insert, or false on failure
 * @throws CakeException if counts of values and fields do not match.
 */
	public function insert($db) {
		if (!isset($this->_insert)) {
			$values = array();
			if (isset($this->records) && !empty($this->records)) {
				$fields = array();
				foreach ($this->records as $record) {
					$fields = array_merge($fields, array_keys(array_intersect_key($record, $this->fields)));
				}
				$fields = array_unique($fields);
				$default = array_fill_keys($fields, null);
				foreach ($this->records as $record) {
					$mergeData = array_merge($default, $record);
					$merge = array_values($mergeData);
					if (count($fields) !== count($merge)) {

						$mergeFields = array_diff_key(array_keys($mergeData), $fields);

						$message = 'Fixture invalid: Count of fields does not match count of values in ' . get_class($this) . "\n";
						foreach ($mergeFields as $field) {
							$message .= "The field '" . $field . "' is in the data fixture but not in the schema." . "\n";
						}

						throw new CakeException($message);
					}
					$values[] = $merge;
				}
				$nested = $db->useNestedTransactions;
				$db->useNestedTransactions = false;
				$result = $db->insertMulti($this->table, $fields, $values);
				if ($this->primaryKey &&
					isset($this->fields[$this->primaryKey]['type']) &&
					in_array($this->fields[$this->primaryKey]['type'], array('integer', 'biginteger'))
				) {
					$db->resetSequence($this->table, $this->primaryKey);
				}
				$db->useNestedTransactions = $nested;
				return $result;
			}
			return true;
		}
	}

/**
 * Truncates the current fixture. Can be overwritten by classes extending
 * CakeFixture to trigger other events before / after truncate.
 *
 * @param DboSource $db A reference to a db instance
 * @return bool
 */
	public function truncate($db) {
		$fullDebug = $db->fullDebug;
		$db->fullDebug = false;
		$return = $db->truncate($this->table);
		$db->fullDebug = $fullDebug;
		return $return;
	}

}
