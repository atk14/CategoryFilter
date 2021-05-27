<?php
class TcLinter extends TcBase {

	function test_php(){
		$ROOT = __DIR__ . "/../";
		$suffixes = ["php","inc"];
		$forbidden_folders = [
			".git",
			"atk14",
			"vendor",
		];

		$files = Files::FindFiles($ROOT,["pattern" => '/\.('.join('|',$suffixes).')$/']);

		foreach($files as $file){
			$_file = str_replace($ROOT,"",$file);
			if(preg_match('#^('.join('|',$forbidden_folders).')/#',$_file)){
				continue;
			}
			system("php -l ".escapeshellarg($file),$ret_val);
			$this->assertTrue(!$ret_val,"There is syntax error in file $_file");
		}
	}
}
