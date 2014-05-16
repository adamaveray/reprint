<?php
require_once(__DIR__.'/../src/Reprint/init.php');

$reprintConfig	= array(
	'feedURL'	=> 'http://hellskitsch.com/feed',	// The URL for the feed to download
	'cacheDir'	=> __DIR__.'/../cache',				// The directory to store cache data in
	'template'	=> __DIR__.'/_template_post.php',	// The template to render each post with
	'outputDir'	=> __DIR__.'/blog',					// The directory to output rendered content to
	'secret'	=> 'secretstring',					// A secret key required for rebuilding the site
);

return new Reprint\Feed($reprintConfig['feedURL'], $reprintConfig['cacheDir']);
