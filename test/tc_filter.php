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

	function test_clone() {
		$filter = new Filter('table', ['sort_by_name' => true]);
		new FilterSection($filter, 'a', ['rank' => 2]);
		new FilterSection($filter, 'c', []);
		new FilterSection($filter, 'b', []);
		foreach($filter as $f) {};

		$f2 = clone $filter;
		$this->assertEquals($f2, $f2->sections['a']->filter);
		foreach($f2 as $section) {
			$this->assertEquals($f2, $section->filter);
			$this->assertFalse(spl_object_hash($filter->sections[$section->name]) == spl_object_hash($section));
		}
	}
}
