<?php
namespace Reprint;

defined('¶') || define('¶', "\n");

date_default_timezone_set('Australia/Sydney');

$files	= array(
	'Feed.php',
	'Cache.php',
	'Post.php',
	'Utilities.php',
	'Pagination.php',
	'libs/php-typography/php-typography.php',
	'libs/TruncateHTML/TruncateHTML.php',
	'libs/SimplePie/simplepie.mini.php',
);
foreach($files as $file){
	require(__DIR__.'/'.$file);
}
