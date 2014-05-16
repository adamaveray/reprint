<?php
/** @var \Reprint\Feed $feed */
$feed	= require_once(__DIR__.'/_config.php');

if(!isset($_GET['secret']) || $_GET['secret'] !== $reprintConfig['secret']){
	// Not authorised
	header('HTTP/1.1 401 Unauthorized', true, 401);
	exit;
}

try {
	$result = $feed->renderFeed($reprintConfig['outputDir'], file_get_contents($reprintConfig['template']), true);
	if(!$result){
		throw new \RuntimeException('Rendering failed');
	}
	echo 'Reloaded';

} catch(\Exception $e){
	echo 'Failed: '.$e->getMessage();
}
