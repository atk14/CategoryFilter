<?php
class TcUsage extends TcBase {

	function test(){
		$filter_for_cards = new FilterForCards();
	
		$this->assertEquals("Card",$filter_for_cards->getModel());

		$sections = $filter_for_cards->visibleSections();
		$this->assertEquals(1,sizeof($sections));

		$filter_flags_section = $sections[0];
		$this->assertEquals("FilterFlagsSection",get_class($filter_flags_section));

		$fields = $filter_flags_section->getVisibleFields();
		$this->assertEquals(["on_stock","unavailable"],array_keys($fields));

		// --

		$section_flags = $sections[0];

		$this->assertEquals(null,$section_flags->getConditions([]));

		$this->assertEquals([
			"condition" => "((
						NOT products.consider_stockcount OR
						COALESCE(minimum_quantity_to_order,(SELECT minimum_quantity_to_order FROM units WHERE units.id = products.unit_id))<=COALESCE(warehouse_items.stockcount,0)
					))",
			"joins" => [
				"products",
				"warehouse_items"
			],
		],$section_flags->getConditions(["on_stock"]));

		$this->assertEquals([
			"condition" => "((
						NOT products.consider_stockcount OR
						COALESCE(minimum_quantity_to_order,(SELECT minimum_quantity_to_order FROM units WHERE units.id = products.unit_id))<=COALESCE(warehouse_items.stockcount,0)
					) OR (
						products.consider_stockcount AND
						COALESCE(minimum_quantity_to_order,(SELECT minimum_quantity_to_order FROM units WHERE units.id = products.unit_id))>COALESCE(warehouse_items.stockcount,0)
					))",
			"joins" => [
				"products",
				"warehouse_items"
			],
		],$section_flags->getConditions(["on_stock","unavailable"]));

		$this->assertEquals(null,$section_flags->getConditions(["nonsence"]));

		// --

		$filter_for_cards_colours = new FilterForCards_Colours();
		$sections = $filter_for_cards_colours->visibleSections();

		$section = $sections[0];

		$this->assertEquals([
			"condition" => "((color='red'))", // set via force_selected_choices
			"joins" => [],
		],$section->getConditions([]));

		$this->assertEquals([
			"condition" => "((color='red') OR (color='blue'))",
			"joins" => [],
		],$section->getConditions(["blue"]));

		$this->assertEquals([
			"condition" => "((color='red') OR (color='blue') OR (color='green'))",
			"joins" => [],
		],$section->getConditions(["blue","green"]));
	}
}

class FilterForCards extends Filter {

	function __construct($options = []){
		parent::__construct("cards", $options);
		$this->addCondition("cards.visible AND NOT cards.deleted");
		$this->productJoin = $this->addJoin("products")->where("products.card_id = cards.id AND NOT products.deleted AND products.visible")->setJoinBy("left join");

		$this->addJoin("warehouse_items")->where("products.id = warehouse_items.product_id AND warehouse_items.warehouse_id = :eshop_warehouse")->setJoinBy("left join");
		$this->addBind(":eshop_warehouse",1);

		new FilterFlagsSection($this, 'flags', [
			"counts_in_labels" => false,
			'form_choices' => [
				'on_stock' => _('skladem'),
				'unavailable' => _('zobrazit i nedostupné tituly'),
			],
			'field_expressions' => [
				'on_stock' => [
					'condition' => '
						NOT products.consider_stockcount OR
						COALESCE(minimum_quantity_to_order,(SELECT minimum_quantity_to_order FROM units WHERE units.id = products.unit_id))<=COALESCE(warehouse_items.stockcount,0)
					',
					'join' => ['products', 'warehouse_items']
				],
				'unavailable' => [
					'condition' => '
						products.consider_stockcount AND
						COALESCE(minimum_quantity_to_order,(SELECT minimum_quantity_to_order FROM units WHERE units.id = products.unit_id))>COALESCE(warehouse_items.stockcount,0)
					',
					'join' => ['products', 'warehouse_items']
				],
			],
			'join' => ['products', 'warehouse_items']
		]);
	}
}

class FilterForCards_Colours extends Filter {

	function __construct($options = []){
		parent::__construct("cards", $options);
		new FilterFlagsSection($this, 'colours', [
			"counts_in_labels" => false,
			'visible_choices' => ['blue','green'],
			'force_selected_choices' => ['red'],
			'form_choices' => [
				'red' => _('červená'),
				'blue' => _('modrá'),
				'green' => _('green')
			],
			'field_expressions' => [
				'red' => "color='red'",
				'blue' => "color='blue'",
				'green' => "color='green'",
			],
		]);
	}
}
