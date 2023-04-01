<?php
class TcFilter extends TcBase {

	function test_sort(){
		$filter = new Filter('table', ['sort_by_name' => true]);
		new FilterSection($filter, 'a', ['rank' => 2]);
		new FilterSection($filter, 'c', []);
		new FilterSection($filter, 'b', []);
		$this->assertEquals(['b', 'c', 'a'], array_map(function($v) {return $v->name;}, $filter->visibleSections() ));

		$filter = new Filter('table', ['sort_by_name' => false]);
		new FilterSection($filter, 'a', ['rank' => 2]);
		new FilterSection($filter, 'c', []);
		new FilterSection($filter, 'b', []);
		$this->assertEquals(['c', 'b', 'a'], array_map(function($v) {return $v->name;}, $filter->visibleSections() ));

	}
}
