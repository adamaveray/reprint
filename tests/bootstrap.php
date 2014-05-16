<?php
require_once(__DIR__.'/../src/Reprint/init.php');

// Testing utilities
function reprint_deleteDir($dir){
	if(!is_dir($dir)){
		return;
	}

	$files = array_diff(scandir($dir), array('.', '..'));

	foreach($files as $file){
		$path = $dir.'/'.$file;
		if(is_dir($path)){
			reprint_deleteDir($path);
		} else {
			unlink($path);
		}
	}

	rmdir($dir);
}
