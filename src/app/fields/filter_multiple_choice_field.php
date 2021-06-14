<?php
class FilterMultipleChoiceField extends MultipleChoiceField implements IFilterFormField {

	var $disabled_choices = array();

	function __construct($options = []){
		$options += [
			"widget" => new FilterCheckboxSelectMultiple([
				'filter_section' => $options['filter_section']
			]),
			"required" => false,
			"ignore_invalid_choices" => 'partial',   //How to treat invalid values given by user
							//true - do not consider as error if an invalid value is given
							//'partial - don not cosider as error if an invalid value is given, if at least one given value is a valid choice
							//false invalid value is error
		];
		$this->section = $options['filter_section'];
		if(!key_exists('choices', $options) || $options['choices'] === null) {
				$options["choices"] = $this->section->getChoices();
		}
		$this->ignore_invalid_choices = $options["ignore_invalid_choices"];
		unset($options["ignore_invalid_choices"]);
		parent::__construct($options);
	}

	function clean($values){
		// Odfiltruji se pryc hodnoty, ktere ve filtru nejsou nebo jsou disablovane.
		// Nam to totiz nevadi. Naopak. Kdyz se z filtru ztrati nejaka option, tak neprestanou fungovat zaindexovana URL.
		if($values && $this->ignore_invalid_choices) {
			$_values = array_flip(array_intersect_key(
					array_flip($values), $this->choices
			));
			if($this->ignore_invalid_choices !== 'partial' || $_values) {
				$values = $_values;
			}
		}
		return parent::clean($values);
	}

	function get_possible_choices(){
		return array_keys($this->choices);
	}

	/**
	 * $field->set_possible_choices([123,124,125]);
	 */
	function set_possible_choices($possible_choices){
		$choices = $this->choices;
		$choices = array_intersect_key($choices, array_flip($possible_choices));
		$this->set_choices($choices);
		return $this->get_possible_choices();
	}

	/**
	 * $field->set_disabled_choices([124,125]);
	 */
	function set_disabled_choices($disabled_choices){
		$this->disabled_choices = $disabled_choices;
		$this->widget->set_disabled_choices($disabled_choices);
	}

	function update_by_filter($form, $key) {
		$choices = $this->section->getUpdatedChoices();
		if(!$choices) {
			unset($form->fields[$key]);
			return;
		}
		$this->set_choices($choices);
		$this->widget->set_implicit_choices($this->section->getImplicitChoices());
		$this->set_disabled_choices($this->section->getDisabledChoices());
	}
}
