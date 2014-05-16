<!DOCTYPE html>
<?php
function e($string, $echo = true){
	$string	= htmlspecialchars($string, \ENT_QUOTES, 'UTF-8');
	if($echo){
		echo $string;
	}
	return $string;
}
?>
<html>
<head>
	<meta charset="utf-8">
	<title><?php e($post['title']);?></title>
	<meta name="description" content="<?php e($post['summary']);?>">
</head>
<body>

<h1><?php echo $post['title_rendered'];?></h1>
<?php if(isset($post['date'])){?>
	<time datetime="<?php e($post['date']->format('c'));?>"><?php e($post['date']->format('jS M Y'));?></time>
<?php }?>

<section>
<?php echo $post['content'];?>
</section>

</body>
</html>
