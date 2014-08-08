<?php
namespace tests\Reprint;

use Reprint\Cache;
use Reprint\Feed;
use Reprint\Post;

/**
 * @coversDefaultClass \Reprint\Feed
 */
class FeedTest extends \PHPUnit_Framework_TestCase {
	const TEMP_DIR_NAME	= 'reprint-feed-test';

	protected static $classname	= '\\Reprint\\Feed';
	protected $cacheDir;
	protected $outputDir;

	public function setUp(){
		$this->cacheDir		= sys_get_temp_dir().'/'.static::TEMP_DIR_NAME.'-cache-'.mt_rand(0, 100000);
		$this->outputDir	= sys_get_temp_dir().'/'.static::TEMP_DIR_NAME.'-output-'.mt_rand(0, 100000);
	}

	public function tearDown(){
		// Cleanup
		reprint_deleteDir($this->cacheDir);
		reprint_deleteDir($this->outputDir);
		$this->cacheDir		= null;
		$this->outputDir	= null;
	}


	/**
	 * @covers ::__construct
	 * @covers ::getPosts
	 * @covers ::<!public>
	 * @dataProvider getPostsData
	 */
	public function testGetPosts($expected, $feedContent, $feedURL, $message = null){
		$self	= $this; // PHP 5.3 compatibility
		$buildFeed	= function($cache, $shouldGetItems = true) use(&$self, $feedContent, $expected, $feedURL){
			// Build mock feed data
			$dataSource	= $self->getMockBuilder('\\SimplePie')
							   ->setMethods(array('get_items'))
							   ->getMock();
			$dataSource->expects($shouldGetItems ? $self->once() : $self->never())
					   ->method('get_items')
					   ->will($self->returnValue($feedContent));

			// Build feed
			$feed	= $self->buildFeed(array(), array('buildRawFeed', 'buildPost'));
			$feed->expects($shouldGetItems ? $self->once() : $self->never())
				 ->method('buildRawFeed')
				 ->will($self->returnValue($dataSource));

			if($shouldGetItems){
				for($i = 0, $max = count($feedContent); $i < $max; $i++){
					$feed->expects($self->at($i + 1)) // 1-indexed
						 ->method('buildPost')
						 ->with($feedContent[$i])
						 ->will($self->returnValue($expected[$i]));
				}
			}

			$feed->__construct($feedURL, $self->cacheDir);
			$self->setFeedProperty($feed, 'cache', $cache);
			return $feed;
		};

		// Without cache
		$cache	= $this->buildCache(array('load', 'save', 'contains'));
		$cache->expects($this->once())
			  ->method('save')
			  ->with(Feed::CACHE_KEY_POSTS, $expected, $this->anything())
			  ->will($this->returnValue(true));

		/** @var Feed $feed */
		$feed	= $buildFeed($cache);
		$posts	= $feed->getPosts(true);
		$this->assertEquals($expected, $posts, $message);

		// With empty cache
		$cache	= $this->buildCache(array('load', 'save', 'contains'));
		$cache->expects($this->once())
			  ->method('contains')
			  ->with(Feed::CACHE_KEY_POSTS)
			  ->will($this->returnValue(false));
		$cache->expects($this->never())
			  ->method('load');
		$cache->expects($this->once())
			  ->method('save')
			  ->with(Feed::CACHE_KEY_POSTS, $expected, $this->anything())
			  ->will($this->returnValue(true));

		/** @var Feed $feed */
		$feed	= $buildFeed($cache);
		$feed->getPosts();

		// With full cache
		$cache	= $this->buildCache(array('load', 'save', 'contains'));
		$cache->expects($this->once())
			  ->method('contains')
			  ->with(Feed::CACHE_KEY_POSTS)
			  ->will($this->returnValue(true));
		$cache->expects($this->once())
			  ->method('load')
			  ->with(Feed::CACHE_KEY_POSTS)
			  ->will($this->returnValue(true));
		$cache->expects($this->never())
			  ->method('save');

		/** @var Feed $feed */
		$feed	= $buildFeed($cache, false);
		$feed->getPosts();
	}

	public function getPostsData(){
		$items	= array();

		$buildFeedEntry	= function(array $data){
			return new \SimplePie_Item(null, $data);
		};

		// Empty feed
		$items[]	= array(
			array(),
			array(),
			'http://www.example.com/feed.xml',
			'An empty feed should return no posts',
		);

		// Normal feed
		$items[]	= array(
			array(
				$this->buildPost(array(
					'title'	=> 'Post One',
					'date'	=> new \DateTime('2014/01/31'),
				)),
				$this->buildPost(array(
					'title'	=> 'Post Two',
					'date'	=> new \DateTime('2014/02/15'),
				)),
			),
			array(
				$buildFeedEntry(array(
					'title'	=> 'Post One',
					'date'	=> new \DateTime('2014/01/31'),
				)),
				$buildFeedEntry(array(
					'title'	=> 'Post Two',
					'date'	=> new \DateTime('2014/02/15'),
				)),
			),
			'http://www.example.com/feed.xml',
			'An empty feed should return no posts',
		);

		return $items;
	}


	/**
	 * @covers ::renderFeed
	 * @covers ::<!public>
	 * @dataProvider getRenderFeedData
	 */
	public function testRenderFeed($posts = array(), $files = array(), $template = null, $filename = null){
		$feed	= $this->buildFeed(array(
			'posts'	=> $posts,
		));

		$feed->renderFeed($this->outputDir, $template, false, $filename);

		foreach($files as $path => $content){
			$fullPath	= $this->outputDir.'/'.$path.'/'.(isset($filename) ? $filename : Feed::DEFAULT_RENDERED_FILENAME);
			$this->assertFileExists($fullPath, 'Each post should be rendered to a file');
			$this->assertEquals($content, file_get_contents($fullPath), 'Each post should be rendered correctly');
		}
	}

	public function getRenderFeedData(){
		$items	= array();

		$template	= <<<EOD
Title: "{{title}}"
Date: "{{date}}"
Content: "{{content}}"
EOD;

		$tz	= new \DateTimeZone('UTC');

		$items['Default Filename']	= array(
			array(
				$this->buildPost(array(
					'title'		=> 'Post One',
					'date'		=> new \DateTime('2014/01/31', $tz),
					'content'	=> 'The first post content',
					'slug'		=> 'post-one',
				)),
				$this->buildPost(array(
					'title'		=> 'Post Two',
					'date'		=> new \DateTime('2014/02/15', $tz),
					'content'	=> 'The second post content',
					'slug'		=> 'post-two',
				)),
			),
			array(
				'2014/01/post-one'	=>
					'Title: "Post One"'."\n"
					.'Date: "2014-01-31T00:00:00+00:00"'."\n"
					.'Content: "The first post content"',
				'2014/02/post-two'	=>
					'Title: "Post Two"'."\n"
					.'Date: "2014-02-15T00:00:00+00:00"'."\n"
					.'Content: "The sec­ond post content"',
			),
			$template,
			null
		);

		$items['Custom Filename']	= array(
			array(
				$this->buildPost(array(
					'title'		=> 'Post One',
					'date'		=> new \DateTime('2014/01/31', $tz),
					'content'	=> 'The first post content',
					'slug'		=> 'post-one',
				)),
				$this->buildPost(array(
					'title'		=> 'Post Two',
					'date'		=> new \DateTime('2014/02/15', $tz),
					'content'	=> 'The second post content',
					'slug'		=> 'post-two',
				)),
			),
			array(
				'2014/01/post-one'	=>
					'Title: "Post One"'."\n"
					.'Date: "2014-01-31T00:00:00+00:00"'."\n"
					.'Content: "The first post content"',
				'2014/02/post-two'	=>
					'Title: "Post Two"'."\n"
					.'Date: "2014-02-15T00:00:00+00:00"'."\n"
					.'Content: "The sec­ond post content"',
			),
			$template,
			'alt.php'
		);

		return $items;
	}


	/**
	 * @covers ::count
	 * @covers ::getIterator
	 * @covers ::offsetExists
	 * @covers ::offsetGet
	 * @covers ::offsetSet
	 * @covers ::offsetUnset
	 * @dataProvider
	 */
	public function testInterfaces(){
		$feed	= $this->buildFeed(array(
			'posts'	=> array('post1', 'post2', 'post3'),
		));

		// Countable
		$this->assertEquals(count($feed->getPosts()), count($feed), 'Countable interface should be correctly implemented');

		// Iterable
		$posts	= array();
		foreach($feed as $post){
			$posts[]	= $post;
		}
		$this->assertEquals($feed->getPosts(), $posts, 'Iterator interface should be correctly implemented');

		// Array Access
		$this->assertEquals($posts[1], $feed[1], 'Posts should be accessible through array access');
		$this->assertTrue(isset($feed[1]), 'Post existence should be checkable through array access');
		$this->assertFalse(isset($feed[4]), 'Post existence should be checkable through array access');
		try {
			$feed[1] = 'hello';
			$this->fail('Feed should not be editable through array-access');
		} catch(\BadMethodCallException $e){}

		try {
			unset($feed[1]);
			$this->fail('Feed should not be editable through array-access');
		} catch(\BadMethodCallException $e){}
	}


	/**
	 * @covers ::paginate
	 * @covers ::<!public>
	 */
	public function testPaginate(){
		$feed	= $this->buildFeed(array(
			'posts'	=> array('post1', 'post2', 'post3'),
		));

		$pageSize	= 20;

		$pagination	= $feed->paginate($pageSize);
		$this->assertInstanceOf('\\Reprint\\Pagination', $pagination, 'Pagination should be returned');
		$this->assertEquals($pageSize, $pagination->getPageSize(), 'Page size should be passed to paginator correctly');
	}


	/**
	 * @param array|null $properties
	 * @param array|null $methods
	 * @return Feed|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected function buildFeed(array $properties = null, array $methods = null){
		$builder	= $this->getMockBuilder(static::$classname)
						   ->disableOriginalConstructor()
						   ->setMethods($methods);
		/** @var Feed|\PHPUnit_Framework_MockObject_MockObject $mock */
		$mock	= $builder->getMock();

		// Set properties
		foreach((array)$properties as $name => $value){
			$this->setFeedProperty($mock, $name, $value);
		}

		return $mock;
	}

	/**
	 * @param Feed $feed
	 * @param string $name
	 * @param mixed $value
	 */
	protected function setFeedProperty(Feed $feed, $name, $value){
		$property	= new \ReflectionProperty($feed, $name);
		$property->setAccessible(true);
		$property->setValue($feed, $value);
	}

	/**
	 * @param array|null $properties
	 * @param array|null $methods
	 * @return Post|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected function buildPost(array $properties = null, array $methods = null){
		$builder	= $this->getMockBuilder('\\Reprint\\Post')
						   ->disableOriginalConstructor()
						   ->setMethods($methods);
		$mock	= $builder->getMock();

		// Set properties
		$mirror	= new \ReflectionClass($mock);
		foreach((array)$properties as $name => $value){
			$property	= $mirror->getProperty($name);
			$property->setAccessible(true);
			$property->setValue($mock, $value);
		}

		return $mock;
	}

	/**
	 * @param array|null $methods
	 * @return Cache|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected function buildCache(array $methods = null){
		$builder	= $this->getMockBuilder('\\Reprint\\Cache')
						   ->disableOriginalConstructor()
						   ->setMethods($methods);
		$mock	= $builder->getMock();

		return $mock;
	}
}
