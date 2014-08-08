<?php
/** @var \Reprint\Feed $feed */
$feed	= require_once(__DIR__.'/_config.php');

$urlBase	= ltrim(str_replace(__DIR__, '', $reprintConfig['outputDir'].'/'), '/');

$posts		= $feed->paginate(10);
if(isset($_GET['p'])){
	$posts->setCurrentPage($_GET['p']);
}

// Example page
header('Content-Type: text/html; charset=utf-8');
?>
<h1>Posts</h1>
<?php if(!count($posts)){ ?>
	<p>No posts could be found</p>
<?php } else { ?>
	<ol class="posts">
	<?php foreach($posts as $post){ ?>
		<li class="post">
			<h2 class="post-title"><a href="<?php echo $urlBase.$post->getURLStub();?>"><?php echo $post->getTitle();?></a></h2>

			<?php echo $post->getSummary();?>

			<a class="see-more" href="<?php echo $urlBase.$post->getURLStub();?>">Read More (<?php echo $urlBase.$post->getURLStub();?></a>
		</li>
	<?php } ?>
	</ol>

	<footer class="posts-navigation">
		<?php if(!$posts->isFirstPage()){ ?>
			<a rel="next" href="">Newest</a>
			<a rel="next" href="?p=<?php echo $posts->getCurrentPage()-1;?>">Newer</a>
		<?php } ?>

		<ol class="posts-pagination">
			<?php for($page = 1, $max = $posts->getPagesCount(); $page <= $max; $page++){ ?>
				<li><a href="?p=<?php $page;?>"><?php echo $page;?></a></li>
			<?php } ?>
		</ol>

		<?php if(!$posts->isLastPage()){ ?>
			<a rel="prev" href="?p=<?php echo $posts->getCurrentPage()+1;?>">Older</a>
			<a rel="prev" href="?p=<?php echo $posts->getPagesCount();?>">Oldest</a>
		<?php } ?>
	</footer>
<?php } ?>
