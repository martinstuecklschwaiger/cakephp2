<?php
/**
 * SQLite layer for DBO
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
 * @package       Cake.Model.Datasource.Database
 * @since         CakePHP(tm) v 0.9.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('DboSource', 'Model/Datasource');
App::uses('CakeText', 'Utility');

/**
 * DBO implementation for the SQLite3 DBMS.
 *
 * A DboSource adapter for SQLite 3 using PDO
 *
 * @package       Cake.Model.Datasource.Database
 */
#[\AllowDynamicProperties]
class Sqlite extends DboSource {

/**
 * Datasource Description
 *
 * @var string
 */
	public $description = "SQLite DBO Driver";

/**
 * Quote Start
 *
 * @var string
 */
	public $startQuote = '"';

/**
 * Quote End
 *
 * @var string
 */
	public $endQuote = '"';

/**
 * Base configuration settings for SQLite3 driver
 *
 * @var array
 */
	protected $_baseConfig = array(
		'persistent' => false,
		'database' => null,
		'flags' => array()
	);

/**
 * SQLite3 column definition
 *
 * @var array
 * @link https://www.sqlite.org/datatype3.html Datatypes In SQLite Version 3
 */
	public $columns = array(
		'primary_key' => array('name' => 'integer primary key autoincrement'),
		'string' => array('name' => 'varchar', 'limit' => '255'),
		'text' => array('name' => 'text'),
		'integer' => array('name' => 'integer', 'limit' => null, 'formatter' => 'intval'),
		'smallinteger' => array('name' => 'smallint', 'limit' => null, 'formatter' => 'intval'),
		'tinyinteger' => array('name' => 'tinyint', 'limit' => null, 'formatter' => 'intval'),
		'biginteger' => array('name' => 'bigint', 'limit' => 20),
		'float' => array('name' => 'float', 'formatter' => 'floatval'),
		'decimal' => array('name' => 'decimal', 'formatter' => 'floatval'),
		'datetime' => array('name' => 'datetime', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'timestamp' => array('name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'time' => array('name' => 'time', 'format' => 'H:i:s', 'formatter' => 'date'),
		'date' => array('name' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'),
		'binary' => array('name' => 'blob'),
		'boolean' => array('name' => 'boolean')
	);

/**
 * List of engine specific additional field parameters used on table creating
 *
 * @var array
 */
	public $fieldParameters = array(
		'collate' => array(
			'value' => 'COLLATE',
			'quote' => false,
			'join' => ' ',
			'column' => 'Collate',
			'position' => 'afterDefault',
			'options' => array(
				'BINARY', 'NOCASE', 'RTRIM'
			)
		),
	);

/**
 * Connects to the database using config['database'] as a filename.
 *
 * @return bool
 * @throws MissingConnectionException
 */
	public function connect() {
		$config = $this->config;
		$flags = $config['flags'] + array(
			PDO::ATTR_PERSISTENT => $config['persistent'],
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		);
		try {
			$this->_connection = new PDO('sqlite:' . $config['database'], null, null, $flags);
			$this->connected = true;
		} catch(PDOException $e) {
			throw new MissingConnectionException(array(
				'class' => get_class($this),
				'message' => $e->getMessage()
			));
		}
		return $this->connected;
	}

/**
 * Check whether the SQLite extension is installed/loaded
 *
 * @return bool
 */
	public function enabled() {
		return in_array('sqlite', PDO::getAvailableDrivers());
	}

/**
 * Returns an array of tables in the database. If there are no tables, an error is raised and the application exits.
 *
 * @param mixed $data Unused.
 * @return array Array of table names in the database
 */
	public function listSources($data = null) {
		$cache = parent::listSources();
		if ($cache) {
			return $cache;
		}

		$result = $this->fetchAll("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;", false);

		if (!$result || empty($result)) {
			return array();
		}

		$tables = array();
		foreach ($result as $table) {
			$tables[] = $table[0]['name'];
		}
		parent::listSources($tables);
		return $tables;
	}

/**
 * Returns an array of the fields in given table name.
 *
 * @param Model|string $model Either the model or table name you want described.
 * @return array Fields in table. Keys are name and type
 */
	public function describe($model) {
		$table = $this->fullTableName($model, false, false);
		$cache = parent::describe($table);
		if ($cache) {
			return $cache;
		}
		$fields = array();
		$result = $this->_execute(
			'PRAGMA table_info(' . $this->value($table, 'string') . ')'
		);

		foreach ($result as $column) {
			$default = ($column['dflt_value'] === 'NULL') ? null : trim((string) $column['dflt_value'], "'");

			$fields[$column['name']] = array(
				'type' => $this->column($column['type']),
				'null' => !$column['notnull'],
				'default' => $default,
				'length' => $this->length($column['type'])
			);
			if (in_array($fields[$column['name']]['type'], array('timestamp', 'datetime')) && strtoupper($fields[$column['name']]['default']) === 'CURRENT_TIMESTAMP') {
				$fields[$column['name']]['default'] = null;
			}
			if ($column['pk'] == 1) {
				$fields[$column['name']]['key'] = $this->index['PRI'];
				$fields[$column['name']]['null'] = false;
				if (empty($fields[$column['name']]['length'])) {
					$fields[$column['name']]['length'] = 11;
				}
			}
		}

		$result->closeCursor();
		$this->_cacheDescription($table, $fields);
		return $fields;
	}

/**
 * Generates and executes an SQL UPDATE statement for given model, fields, and values.
 *
 * @param Model $model The model instance to update.
 * @param array $fields The fields to update.
 * @param array $values The values to set columns to.
 * @param mixed $conditions array of conditions to use.
 * @return bool
 */
	public function update(Model $model, $fields = array(), $values = null, $conditions = null) {
		if (empty($values) && !empty($fields)) {
			foreach ($fields as $field => $value) {
				if (strpos($field, $model->alias . '.') !== false) {
					unset($fields[$field]);
					$field = str_replace($model->alias . '.', "", $field);
					$field = str_replace($model->alias . '.', "", $field);
					$fields[$field] = $value;
				}
			}
		}
		return parent::update($model, $fields, $values, $conditions);
	}

/**
 * Deletes all the records in a table and resets the count of the auto-incrementing
 * primary key, where applicable.
 *
 * @param string|Model $table A string or model class representing the table to be truncated
 * @return bool SQL TRUNCATE TABLE statement, false if not applicable.
 */
	public function truncate($table) {
		if (in_array('sqlite_sequence', $this->listSources())) {
			$this->_execute('DELETE FROM sqlite_sequence where name=' . $this->startQuote . $this->fullTableName($table, false, false) . $this->endQuote);
		}
		return $this->execute('DELETE FROM ' . $this->fullTableName($table));
	}

/**
 * Converts database-layer column types to basic types
 *
 * @param string $real Real database-layer column type (i.e. "varchar(255)")
 * @return string Abstract column type (i.e. "string")
 */
	public function column($real) {
		if (is_array($real)) {
			$col = $real['name'];
			if (isset($real['limit'])) {
				$col .= '(' . $real['limit'] . ')';
			}
			return $col;
		}

		$col = strtolower(str_replace(')', '', $real));
		if (strpos($col, '(') !== false) {
			list($col) = explode('(', $col);
		}

		$standard = array(
			'text',
			'integer',
			'float',
			'boolean',
			'timestamp',
			'date',
			'datetime',
			'time'
		);
		if (in_array($col, $standard)) {
			return $col;
		}
		if ($col === 'tinyint') {
			return 'tinyinteger';
		}
		if ($col === 'smallint') {
			return 'smallinteger';
		}
		if ($col === 'bigint') {
			return 'biginteger';
		}
		if (strpos($col, 'char') !== false) {
			return 'string';
		}
		if (in_array($col, array('blob', 'clob'))) {
			return 'binary';
		}
		if (strpos($col, 'numeric') !== false || strpos($col, 'decimal') !== false) {
			return 'decimal';
		}
		return 'text';
	}

/**
 * Generate ResultSet
 *
 * @param PDOStatement $results The results to modify.
 * @return void
 */
	public function resultSet($results) {
		$this->results = $results;
		$this->map = array();
		$numFields = $results->columnCount();
		$index = 0;
		$j = 0;

		// PDO::getColumnMeta is experimental and does not work with sqlite3,
		// so try to figure it out based on the querystring
		$querystring = $results->queryString;
		if (stripos($querystring, 'SELECT') === 0 && stripos($querystring, 'FROM') > 0) {
			$selectpart = substr($querystring, 7);
			$selects = array();
			foreach (CakeText::tokenize($selectpart, ',', '(', ')') as $part) {
				$fromPos = stripos((string) $part, ' FROM ');
				if ($fromPos !== false) {
					$selects[] = trim(substr((string) $part, 0, $fromPos));
					break;
				}
				$selects[] = $part;
			}
		} elseif (strpos($querystring, 'PRAGMA table_info') === 0) {
			$selects = array('cid', 'name', 'type', 'notnull', 'dflt_value', 'pk');
		} elseif (strpos($querystring, 'PRAGMA index_list') === 0) {
			$selects = array('seq', 'name', 'unique');
		} elseif (strpos($querystring, 'PRAGMA index_info') === 0) {
			$selects = array('seqno', 'cid', 'name');
		}
		while ($j < $numFields) {
			if (!isset($selects[$j])) {
				$j++;
				continue;
			}
			if (preg_match('/\bAS(?!.*\bAS\b)\s+(.*)/i', (string) $selects[$j], $matches)) {
				$columnName = trim($matches[1], '"');
			} else {
				$columnName = trim(str_replace('"', '', (string) $selects[$j]));
			}

			if (strpos((string) $selects[$j], 'DISTINCT') === 0) {
				$columnName = str_ireplace('DISTINCT', '', $columnName);
			}

			$metaType = false;
			try {
				$metaData = (array)$results->getColumnMeta($j);
				if (!empty($metaData['sqlite:decl_type'])) {
					$metaType = trim((string) $metaData['sqlite:decl_type']);
				}
			} catch (Exception $e) {
			}

			if (strpos($columnName, '.')) {
				$parts = explode('.', $columnName);
				$this->map[$index++] = array(trim($parts[0]), trim($parts[1]), $metaType);
			} else {
				$this->map[$index++] = array(0, $columnName, $metaType);
			}
			$j++;
		}
	}

/**
 * Fetches the next row from the current result set
 *
 * @return mixed array with results fetched and mapped to column names or false if there is no results left to fetch
 */
	public function fetchResult() {
		if ($row = $this->_result->fetch(PDO::FETCH_NUM)) {
			$resultRow = array();
			foreach ($this->map as $col => $meta) {
				list($table, $column, $type) = $meta;
				$resultRow[$table][$column] = $row[$col];
				if ($type === 'boolean' && $row[$col] !== null) {
					$resultRow[$table][$column] = $this->boolean($resultRow[$table][$column]);
				}
			}
			return $resultRow;
		}
		$this->_result->closeCursor();
		return false;
	}

/**
 * Returns a limit statement in the correct format for the particular database.
 *
 * @param int $limit Limit of results returned
 * @param int $offset Offset from which to start results
 * @return string SQL limit/offset statement
 */
	public function limit($limit, $offset = null) {
		if ($limit) {
			$rt = sprintf(' LIMIT %u', $limit);
			if ($offset) {
				$rt .= sprintf(' OFFSET %u', $offset);
			}
			return $rt;
		}
		return null;
	}

/**
 * Generate a database-native column schema string
 *
 * @param array $column An array structured like the following: array('name'=>'value', 'type'=>'value'[, options]),
 *    where options can be 'default', 'length', or 'key'.
 * @return string
 */
	public function buildColumn($column) {
		$name = $type = null;
		$column += array('null' => true);
		extract($column);

		if (empty($name) || empty($type)) {
			trigger_error(__d('cake_dev', 'Column name or type not defined in schema'), E_USER_WARNING);
			return null;
		}

		if (!isset($this->columns[$type])) {
			trigger_error(__d('cake_dev', 'Column type %s does not exist', $type), E_USER_WARNING);
			return null;
		}

		$isPrimary = (isset($column['key']) && $column['key'] === 'primary');
		if ($isPrimary && $type === 'integer') {
			return $this->name($name) . ' ' . $this->columns['primary_key']['name'];
		}
		$out = parent::buildColumn($column);
		if ($isPrimary && $type === 'biginteger') {
			$replacement = 'PRIMARY KEY';
			if ($column['null'] === false) {
				$replacement = 'NOT NULL ' . $replacement;
			}
			return str_replace($this->columns['primary_key']['name'], $replacement, (string) $out);
		}
		return $out;
	}

/**
 * Sets the database encoding
 *
 * @param string $enc Database encoding
 * @return bool
 */
	public function setEncoding($enc) {
		if (!in_array($enc, array("UTF-8", "UTF-16", "UTF-16le", "UTF-16be"))) {
			return false;
		}
		return $this->_execute("PRAGMA encoding = \"{$enc}\"") !== false;
	}

/**
 * Gets the database encoding
 *
 * @return string The database encoding
 */
	public function getEncoding() {
		return $this->fetchRow('PRAGMA encoding');
	}

/**
 * Removes redundant primary key indexes, as they are handled in the column def of the key.
 *
 * @param array $indexes The indexes to build.
 * @param string $table The table name.
 * @return string The completed index.
 */
	public function buildIndex($indexes, $table = null) {
		$join = array();

		$table = str_replace('"', '', $table);
		list($dbname, $table) = explode('.', $table);
		$dbname = $this->name($dbname);

		foreach ($indexes as $name => $value) {

			if ($name === 'PRIMARY') {
				continue;
			}
			$out = 'CREATE ';

			if (!empty($value['unique'])) {
				$out .= 'UNIQUE ';
			}
			if (is_array($value['column'])) {
				$value['column'] = implode(', ', array_map(array(&$this, 'name'), $value['column']));
			} else {
				$value['column'] = $this->name($value['column']);
			}
			$t = trim($table, '"');
			$indexname = $this->name($t . '_' . $name);
			$table = $this->name($table);
			$out .= "INDEX {$dbname}.{$indexname} ON {$table}({$value['column']});";
			$join[] = $out;
		}
		return $join;
	}

/**
 * Overrides DboSource::index to handle SQLite index introspection
 * Returns an array of the indexes in given table name.
 *
 * @param string $model Name of model to inspect
 * @return array Fields in table. Keys are column and unique
 */
	public function index($model) {
		$index = array();
		$table = $this->fullTableName($model, false, false);
		if ($table) {
			$indexes = $this->query('PRAGMA index_list(' . $table . ')');

			if (is_bool($indexes)) {
				return array();
			}
			foreach ($indexes as $info) {
				$key = array_pop($info);
				$keyInfo = $this->query('PRAGMA index_info("' . $key['name'] . '")');
				foreach ($keyInfo as $keyCol) {
					if (!isset($index[$key['name']])) {
						$col = array();
						if (preg_match('/autoindex/', (string) $key['name'])) {
							$key['name'] = 'PRIMARY';
						}
						$index[$key['name']]['column'] = $keyCol[0]['name'];
						$index[$key['name']]['unique'] = (int)$key['unique'] === 1;
					} else {
						if (!is_array($index[$key['name']]['column'])) {
							$col[] = $index[$key['name']]['column'];
						}
						$col[] = $keyCol[0]['name'];
						$index[$key['name']]['column'] = $col;
					}
				}
			}
		}
		return $index;
	}

/**
 * Overrides DboSource::renderStatement to handle schema generation with SQLite-style indexes
 *
 * @param string $type The type of statement being rendered.
 * @param array $data The data to convert to SQL.
 * @return string
 */
	public function renderStatement($type, $data) {
		switch (strtolower($type)) {
			case 'schema':
				extract($data);
				if (is_array($columns)) {
					$columns = "\t" . implode(",\n\t", array_filter($columns));
				}
				if (is_array($indexes)) {
					$indexes = "\t" . implode("\n\t", array_filter($indexes));
				}
				return "CREATE TABLE {$table} (\n{$columns});\n{$indexes}";
			default:
				return parent::renderStatement($type, $data);
		}
	}

/**
 * PDO deals in objects, not resources, so overload accordingly.
 *
 * @return bool
 */
	public function hasResult() {
		return is_object($this->_result);
	}

/**
 * Generate a "drop table" statement for the given table
 *
 * @param type $table Name of the table to drop
 * @return string Drop table SQL statement
 */
	protected function _dropTable($table) {
		return 'DROP TABLE IF EXISTS ' . $this->fullTableName($table) . ";";
	}

/**
 * Gets the schema name
 *
 * @return string The schema name
 */
	public function getSchemaName() {
		return "main"; // Sqlite Datasource does not support multidb
	}

/**
 * Check if the server support nested transactions
 *
 * @return bool
 */
	public function nestedTransactionSupported() {
		return $this->useNestedTransactions && version_compare($this->getVersion(), '3.6.8', '>=');
	}

/**
 * Returns a locking hint for the given mode.
 *
 * Sqlite Datasource doesn't support row-level locking.
 *
 * @param mixed $mode Lock mode
 * @return string|null Null
 */
	public function getLockingHint($mode) {
		return null;
	}
}
