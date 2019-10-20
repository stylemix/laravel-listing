<?php

namespace Stylemix\Listing\Elastic\Query;

use Elastica\Param;

class Sort extends Param
{

	protected $key;

	protected $fieldName;

	public function __construct($key, $fieldName, $params = null)
	{
		$this->key = $key;
		$this->fieldName = $fieldName;
		if (is_string($params)) {
			$params = ['order' => $params];
		}
		$this->setParams($params);
	}

	/**
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * @inheritDoc
	 */
	protected function _getBaseName()
	{
		return $this->fieldName;
	}

	public function toArray()
	{
		$array = parent::toArray();

		$baseName = $this->_getBaseName();
		if (isset($array[$baseName]['order']) && 1 == count($array[$baseName])) {
			$array[$baseName] = $array[$baseName]['order'];
		}

		return $array;
	}
}
