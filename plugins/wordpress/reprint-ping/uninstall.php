<?php
global $wpdb;

$isMultisite	= is_multisite();
if($isMultisite){
	$oldBlogID	= $wpdb->blogid;
	$blogIDs	= $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
} else {
	$blogIDs	= array(null);
}

foreach($blogIDs as $blogID){
	if(isset($blogID)){
		switch_to_blog($blogID);
	}

	delete_option('reprint_url');
	delete_option('reprint_secret');
}

if($isMultisite){
	// Restore blog
	switch_to_blog($oldBlogID);
}
