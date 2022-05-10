<?php

/**
 * Section of filter, that has checkboxes
 * abstract, descendant are
 * FilterSection - for filter choices given by possible values of a sql field
 * FilterFlagSection - for filter choices given by boolean (sql) fields
 */
abstract class FilterChoiceSection extends FilterBaseSection {

	function __construct($filter, $name, $options) {
		$options = $options + [
			'force_choices' => false, #If true, choices in form are not generated from DB, but just
																#used as given in options in $this->options['form_field_options']['choices']
			#forms framework support
			'form_field' => 'FilterMultipleChoiceField',                  # name of FormField to be created by createFormFields
			'form_field_options' => [],

			#commonly no need to redefine
			'condition_over_subtable' => null, //whether the condition is oversubtable or not - default is (bool) join
			'multiple' => true,                //user can select multiple values

			'counts_in_labels' => true,
			'label_string' => '%s (%s)',
			'label_string_unselected' => '%s (%+d)',
			'disable_nonlimiting' => false
		];
		$options['form_field_options'] += [ 'label' => $name, 'choices' => [] ]; # options passed to createFormFields

		parent::__construct($filter, $name, $options);
		$this->choiceLabels = $this->options['form_field_options']['choices'];
		$this->forceChoices = $this->options['force_choices'];

		$this->possibleChoices = null;         //cache for choices
		$this->availableChoices = null;         //cache for choices
		$this->availableCounts = null; //cache for availableCounts
		$this->updatedChoices = null;     //real choices with counts and disabled choices

		if($this->options['condition_over_subtable'] === null) {
			$this->options['condition_over_subtable'] = (bool) $this->options['join'];
		}
	}

	/**
	 * Returns array of choices generated by the section
	 * for at least one record exists, where no filter is applied.
	 * $section->getPossibleChoices();
	 * >> [ 1, 6, 15 ]
	 */
	function getPossibleChoices() {
		if(!$this->possibleChoices) {
			$this->possibleChoices = $this->getChoicesOn($this->filter->unfilteredSql());
		}
		return $this->possibleChoices;
	}

	/**
	 * Returns array of choices generated by the section,
	 * for which at least one record matches the currently choosed
	 * filter conditions.
	 * $section->getAvailableChoices();
	 * >> [ 1, 15 ]
	 */
	function getAvailableChoices() {
		if($this->availableChoices === null) {
			if($this->forceChoices || $this->filter->isFilteredExcept($this->name)) {
				$this->availableChoices = $this->getChoicesOn(
					$this->filter->filteredSql
				);
			} else {
				$this->availableChoices = $this->getPossibleChoices();
			}
		}
		return $this->availableChoices;
	}

	/**
	 * Returns array of items count (for empty filter)
	 * $section->getPossibleCount();
	 * >> [ 1 => 10, 15 => 7 ]
	 * There is 10 items for brand with id 1 and 7 for brand 15.
	 */
	function getPossibleCounts() {
		return $this->getCountsOn($this->filter->unfilteredSql()->result());
	}

	/**
	 * Returns array of count of items that match the filter.
	 * (the counts can be limited by other filter's sections)
	 *
	 * If $this->isFiltered() (some option in this filter section is checked)
	 * and the filter is aditive (OR opertator is applied on the choices)
	 * then the result is number of items, that will be shown IN ADDITION
	 * to the current selection.
	 *
	 * $section->getAvailableCount();
	 * >> [ 1 => 4, 15 => 2 ]
	 * There is only 4 items for brand with id 1 and 2 for brand 15,
	 * the others do not fill the filter requirements.
	 */
	function getAvailableCounts() {
		if(!$this->availableCounts) {
			$counts = $this->countAvailable();
		  $this->availableCounts = $counts;
		}
		return $this->availableCounts;
	}

	/** Generate counts for FilterBaseSection::getAvailableCount
	 *  (getAvailableCounts take care of caching and filling missing items
	 *  this method just counts and can be redefined)
	 */
	function countAvailable() {
		$sql = $this->filter->filteredSql;

		if($this->values && $this->isAditive()) {
			$options = $this->sqlOptions(true);
			$result = $sql->result($options);
			$idField = $this->filter->getIdField();
			$notIn = $this->filter->result();
			$notIn = $notIn->select("$idField", ['add_options' => false]);
			$out=$this->getCountsOn($result, "$idField NOT IN ($notIn)");
			if($this->values) {
				#do not disable by user explicitly selected options
				$out += array_fill_keys($this->values, null);
			}
		} else {

			$options=$this->sqlOptions();
			$result = $sql->result($options);
			$out=$this->getCountsOn($result);
		}
		return $out;
	}

	function getChoices() {
		$out = $this->getChoiceLabels();
		if(!$this->forceChoices) {
			$choices = $this->getPossibleChoices();
			$choices = array_combine($choices, $choices);
			$out = array_intersect_key($out + $choices, $choices);
		}
		return $out;
	}

	/**
	 * Return the array of choices, that should be disabled: the choices,
	 * which selection results in empty (filtered) set and not chosen in filter.
   */
	function getDisabledChoices() {
		if(!$this->isParsed()) {
			return [];
		}
		if($this->updatedChoices === null) {
			$this->_computeChoices();
		}
		return $this->updatedChoices['disabled'];
	}

	/**
	 * Choices, that are not checked by user, but they are common to
	 * all items
   **/
	function getImplicitChoices() {
		if(!$this->isParsed()) {
			return [];
		}
		if($this->updatedChoices === null) {
			$this->_computeChoices();
		}
		return $this->updatedChoices['implicit'];
	}

	function getUpdatedChoices() {
		if(!$this->isParsed()) {
			return $this->getChoices();
		}
		if($this->updatedChoices === null) {
			$this->_computeChoices();
		}
		return $this->updatedChoices['labels'];
	}

	function _computeChoices() {
		$labels = $this->getChoices();
		$implicit = [];
		$values = array_flip($this->values);

		if($this->options['counts_in_labels']) {
			$available = $this->getAvailableCounts();
			$disabled = array_diff_key(
				array_flip($this->getPossibleChoices()), $available
			);
			if( $this->options['disable_nonlimiting'] ) {
				$count = $this->filter->getRecordsCount();
			}
			$aditive = $this->isAditive();
			if($this->values && $this->options['multiple'] && $aditive) {
				$str = $this->options['label_string_unselected'];
			} else {
				$str = $this->options['label_string'];
			}
			foreach($available as $k => $v) {
				if($this->options['disable_nonlimiting'] && $v == $count) {
					$implicit[] = $disabled[$k] = $k;
				} elseif($v > 0 && !key_exists($k, $values)) {
					$labels[$k] = sprintf($str,$labels[$k], $v);
				}
			}
		} else {
			$disabled = [];
		}
		$this->updatedChoices = [
			'labels' =>  $labels,
			'disabled' => array_keys($disabled),
			'implicit' => $implicit
		];
	}

	/**
	 * Has any valid value?
	 */
	function isPossible() {
		return $this->getPossibleChoices();
	}

	/**
	 * Set choice labels, used in $this->getChoices()
	 * If the labels is not set, they are readed from database (for FilterSection),
	 * or the ids of choices are used (for FilterFlagsSection)
	 */
	function setChoiceLabels($labels) {
		$this->choiceLabels = $labels;
	}

	/**
	 * Return choice labels, either user defined or already generated
	 */
	function getChoiceLabels() {
		return $this->choiceLabels;
	}

	/**
	 * Parse values (from form)
	 */
	function parseValues($values) {
		$this->availableCounts = null;
		$this->availableChoices = null;
		$this->updatedChoices = null;

		$pname = $this->getParamName();
		if(!key_exists($pname, $values)) {
			$this->values=[];
		} else {
			$this->values = $values[$pname];
		}
		return $this->values;
	}

	/**
	 * @param All params of filter
	 * @return array [ ['params' => (array of filter params with given element removed), 'label' => 'label'] ] of active filters.
	 **/
	function getActiveFilters($params) {
		if(!$this->values) {
			return [];
		}

		$pname = $this->getParamName();
		$myParams = $this->values;

		if(count($this->values) == 1) {
			unset($params[$pname]);
			return [[
				'params' => $params,
				'label' => $this->getChoices(['count' => false])[current($this->values)]
			]];
		}

		$out = array_intersect_key($this->getChoices(['count' => false]), array_fill_keys($this->values, true));
		$copy = $out;
		array_walk( $out, function(&$val, $key) use ($pname, $copy, $params) {
			$mod = $copy;
			unset($mod[$key]);
			$val = [
			'params' => [ $pname => array_keys($mod) ] + $params,
			'label' => $val
		] ; });
		return $out;
	}

	function setFixed($values) {
		if(!is_array($values )) {
			$values = [ $values ];
		}
		parent::setFixed($values);
	}
}
