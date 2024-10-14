<?php
class FilterLandingPageByChoice {

	var $options;
	var $section;

	function __construct($options = []) {
		$options += [
			'label_string' => ' %s',
			'label_lowercase' => false,
			'label_function' => null,      //Function, that accepts choice key and returns the label for given choice. Possibly, it can accept
			                               //additional argument, passed to label. If it is not given, the label is created
			                               //according to the 'label_string' and 'label_lowercase' options
			'special_label_function' => null, //If defined, the label with non-null argument is built by this function.
		];
		if($options['label_function'] && !$options['special_label_function'] &&
		   (new ReflectionFunction($options['label_function']))->getNumberOfParameters() > 1
		 ) {
			$options['special_label_function'] = $options['label_function'];
		}
		$this->options=$options;
	}

	function setSection($section) {
		$this->section = $section;
	}

	function isLandingPage() {
		$values = $this->section->getValues();
		if(count($values) != 1) {
			return false;
		}
		return true;
	}

	function value() {
		return current($this->section->getValues());
	}

	function label($for=null) {
		return $this->_label($this->value(), $for);
	}

	function _label($id, $for=null) {
		if($for!==null) {
			if(!$this->options['special_label_function']) {
				return null;
			}
			$slf = $this->options['special_label_function'];
			return $slf($id, $for);
		}
		if($this->options['label_function']) {
			$fce=$this->options['label_function'];
			return $fce($id);
		} else {
			$label = $this->section->getChoiceLabels()[$id];
			if($this->options['label_lowercase']) {
				$label = mb_strtolower(mb_substr($label, 0, 1)) . mb_substr($label,1);
			}
			return sprintf($this->options['label_string'], strip_tags($label));
		}
	}

	function enumLandingPages() {
		return array_map(function($id) {
			return [
				'params' => [ $this->section->getParamName() => [ $id ] ],
				'label' => $this->_label($id)
			];
		}, $this->section->getPossibleChoices());
	}
}
