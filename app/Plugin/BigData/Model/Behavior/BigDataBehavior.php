<?php
/**
 * BigDataBehavior v2.0
 *
 * @author Jarriett K Robinson, jarriett@gmail.com
 * @author (modifications) J. Miller, http://github.com/jmillerdesign
 * @author (modifications) frbuc
 */
class BigDataBehavior extends ModelBehavior {

/**
 * Setup this behavior with the specified configuration settings.
 *
 * @param Model $model Model using this behavior
 * @param array $config Configuration settings for $model
 * @return void
 */
	public function setup(Model $model, $config = array()) {
		// Current model
		$this->_Model = $model;

		// Bundle of items to save
		$this->_Model->_bundle = array();
	}

/**
 * Add an array to the bundle, to be saved later
 *
 * @param Model $model Model using this behavior
 * @param array $modelData Data to be saved
 * @return void
 */
	public function addToBundle(Model $model, $modelData) {
		$this->_Model = $model;

		// Remove model name from array, if it exists
		// Append item to the bundle array
		if (array_key_exists($model->name, $modelData)) {
			$this->_Model->_bundle[] = $this->_prepareItemForSaving($modelData[$model->name]);
		} else {
			$this->_Model->_bundle[] = $this->_prepareItemForSaving($modelData);
		}
	}

/**
 * Save all items in the bundle, then reset the bundle
 *
 * @param Model $model Model using this behavior
 * @param integer $maxPayload Maximum number of items to save per query
 * @param string $replace: 'replace' to replace data if it already exists, 'ignore' to ignore data if it already
 *                          exists, `null` or any other value to disable.
 * @return void
 */
	public function saveBundle(Model $model, $maxPayload = 10000, $replace = "replace") {
		$this->_Model = $model;

		if (count($this->_Model->_bundle) > $maxPayload) {
			$chunks = array_chunk($this->_Model->_bundle, $maxPayload);
			foreach ($chunks as $chunk) {
				$this->_bulkSave($chunk, $replace);
			}
		} else {
			$this->_bulkSave($this->_Model->_bundle, $replace);
		}

		$this->_Model->_bundle = array();
	}

/**
 * Perform the query to save a large bundle of items
 *
 * @param array $bundleItems Items to save
 * @param string $replace: 'replace' to replace data if it already exists, 'ignore' to ignore data if it already
 *                          exists, `null` or any other value to disable.
 * @return void
 */
	protected function _bulkSave(&$bundleItems, $replace = "replace") {
		if (!$bundleItems) {
			return;
		}

		$table = Inflector::tableize($this->_Model->name);
		$fieldNames = array_map(function($n) { return "`$n`"; }, array_keys($this->_Model->schema()));

		$insert_str = "INSERT";
		if ($replace == "ignore") {
		   $insert_str .= " IGNORE";
        }

		$sql = sprintf($insert_str . ' INTO `%s` (%s) VALUES', $table, implode(',', $fieldNames));
		foreach ($bundleItems as $bundleItem) {
			$sql .= sprintf('(%s),', implode(',', array_values($bundleItem)));
		}

		// Remove last comma
		$sql = substr($sql, 0, strlen($sql) - 1);

		if ($replace == "replace") {
			$sql .= ' ON DUPLICATE KEY UPDATE ';
			foreach ($fieldNames as $fieldName) {
				$sql .= sprintf('%s=VALUES(%s),', $fieldName, $fieldName);
			}

			// Remove last comma
			$sql = substr($sql, 0, strlen($sql) - 1);
		}

		$sql .= ';';

		$this->_Model->query($sql);
	}

/**
 * Get the empty value to use for a field
 *
 * @param array $fieldSchema Field schema
 * @return mixed Default value
 */
	protected function _generateEmptyValue($fieldSchema) {
		if (array_key_exists('null', $fieldSchema) && $fieldSchema['null']) {
			return 'NULL';
		}

		if (!array_key_exists('default', $fieldSchema) || !$fieldSchema['default']) {
			switch ($fieldSchema['type']) {
				case 'string':   return '';
				case 'date':     return date('Y-m-d');
				case 'datetime': return date('Y-m-d H:i:s');
				default:         return 0;
			}
		}

		return $fieldSchema['default'];
	}

/**
 * Add default values for keys that exist in the schema but are not set in the data.
 * Will not insert the primary key, since the database will handle that.
 *
 * @param array $modelData Data to be saved
 * @return array Data to be saved
 */
	protected function _prepareItemForSaving($modelData) {
		$formattedData = array();

		foreach ($this->_Model->schema() as $fieldName => $fieldSchema) {
			if (!array_key_exists($fieldName, $modelData)) {
				// Schema exists, but is not set in the model data
				// Insert the default value, unless it is the primary key
				if (!array_key_exists('key', $fieldSchema) || ($fieldSchema['key'] != 'primary')) {
					$value = $this->_generateEmptyValue($fieldSchema);
				} else {
					$value = $modelData[$fieldName];
				}
			} else {
				$value = $modelData[$fieldName];
			}

			// Wrap values in quotes, for SQL string
			if (strtoupper($value) != 'NULL') {
				$value = '"' . $value . '"';
			}

			$formattedData[$fieldName] = $value;
		}

		return $formattedData;
	}

}
