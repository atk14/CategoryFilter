<?php
/**
 * Filter section for one YES/NO value, where NO is indicated by nonexistence of given
 * left joined field.
 */

class FilterExistsSection extends FilterBoolSection {
	/*function __construct($filter, $name, $options) {
		parent::__construct($filter, $name, $options + [
		])
	}*/

	function sqlBoolValue() {
		$field = parent::sqlBoolValue();
		return "$field IS NOT NULL";
	}

	function sqlOptions($sql=null) {
		$out = parent::sqlOptions($sql) + ['override_join' => [$this->getMainJoin($sql)->getName() => 'left join']];
		return $out;
	}

	/***
	 * Add conditions to ParsedSqlResult based on given values
	 *
	 *	if($section->addConditions($values)){
	 *		// something was added to the conditions
	 *	}
	 */
	function addConditions($values, $sql = null) {
		if($values === 'yes') {
			$sql = $this->getMainJoin($sql);
			$sql->setJoinBy('JOIN');
			$sql->setActive(true);
			$op = '';
			return true;
		} elseif($values === 'no') {
			$sql = $this->getMainJoin($sql);
			$sql->setActive(true);
			$sql->setJoinBy('LEFT JOIN');
			$this->filter->filteredSql->namedWhere(
				$this->name,
				parent::sqlBoolValue() . ' IS NULL'
			);
			return true;
		}
		return false;
	}
}
