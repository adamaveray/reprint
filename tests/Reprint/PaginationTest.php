<?php
namespace tests\Reprint;

use Reprint\Pagination;
use Reprint\Feed;
use Reprint\Post;

/**
 * @coversDefaultClass \Reprint\Pagination
 */
class PaginationTest extends \PHPUnit_Framework_TestCase {
	protected static $classname	= '\\Reprint\\Pagination';

	/**
	 * @covers ::__construct
	 * @covers ::getFeed
	 * @covers ::getPageSize
	 * @covers ::<!public>
	 */
	public function testConstruct(){
		$posts = array(
			$this->buildPost(),
			$this->buildPost(),
			$this->buildPost(),
		);

		$feed	= $this->buildFeed($posts);

		// No page size
		$pagination	= new Pagination($feed);
		$this->assertEquals($feed, $pagination->getFeed(), 'The feed should be stored correctly');
		$this->assertEquals(Pagination::DEFAULT_PAGE_SIZE, $pagination->getPageSize(), 'The page size should use the default if not set');

		// Page size
		$size		= 25;
		$pagination	= new Pagination($feed, $size);
		$this->assertEquals($feed, $pagination->getFeed(), 'The feed should be stored correctly');
		$this->assertEquals($size, $pagination->getPageSize(), 'The page size should use the provided value');
	}


	/**
	 * @covers ::getCurrentPage
	 * @covers ::setCurrentPage
	 * @covers ::<!public>
	 * @dataProvider getCurrentPageData
	 */
	public function testCurrentPage(Feed $feed, $pageSize, $currentPage, $expectedCurrentPage = null){
		$pagination	= new Pagination($feed, $pageSize);

		try {
			$pagination->setCurrentPage($currentPage);
		} catch(\Exception $e){
			// Wait until end
		}

		if(!isset($expectedCurrentPage)){
			$expectedCurrentPage	= $currentPage;
		}
		$this->assertEquals($expectedCurrentPage, $pagination->getCurrentPage(), 'The current page should be stored correctly');

		if(isset($e)){
			throw $e;
		}
	}

	public function getCurrentPageData(){
		$items	= array();

		$feed	= $this->buildFeed($this->generatePosts(30));

		$items[]	= array(
			$feed,
			10,
			2,
		);

		$items[]	= array(
			$feed,
			20,
			1,
		);

		$items[]	= array(
			$feed,
			20,
			'hello',
			1,
		);

		return $items;
	}

	/**
	 * @expectedException \OutOfRangeException
	 * @covers ::getCurrentPage
	 * @covers ::setCurrentPage
	 * @covers ::<!public>
	 */
	public function testInvalidCurrentPage(){
		$feed	= $this->buildFeed($this->generatePosts(30));
		$this->testCurrentPage($feed, 20, 4, 1); // Page 4 does not exist
	}

	/**
	 * @expectedException \OutOfRangeException
	 * @covers ::getCurrentPage
	 * @covers ::setCurrentPage
	 * @covers ::<!public>
	 */
	public function testZeroIndexedCurrentPage(){
		$feed	= $this->buildFeed($this->generatePosts(30));
		$this->testCurrentPage($feed, 20, 0, 1);
	}

	/**
	 * @expectedException \OutOfRangeException
	 * @covers ::getCurrentPage
	 * @covers ::setCurrentPage
	 * @covers ::<!public>
	 */
	public function testNegativeCurrentPage(){
		$feed	= $this->buildFeed($this->generatePosts(30));
		$this->testCurrentPage($feed, 20, -1, 1);
	}


	/**
	 * @covers ::getPageSize
	 * @covers ::setPageSize
	 * @covers ::<!public>
	 * @dataProvider getPageSizeData
	 */
	public function testPageSize($pageSize, $expectedPageSize = null){
		$feed	= $this->buildFeed($this->generatePosts(30));

		$pagination	= new Pagination($feed);

		try {
			$pagination->setPageSize($pageSize);
		} catch(\Exception $e){
			// Wait until end
		}

		if(!isset($expectedPageSize)){
			$expectedPageSize	= $pageSize;
		}
		$this->assertEquals($expectedPageSize, $pagination->getPageSize(), 'The page size should be stored correctly');

		if(isset($e)){
			throw $e;
		}
	}

	public function getPageSizeData(){
		return array(
			array(5),
			array(100),
			array('hello', Pagination::DEFAULT_PAGE_SIZE),
		);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @covers ::getPageSize
	 * @covers ::setPageSize
	 * @covers ::<!public>
	 */
	public function testEmptyPageSize(){
		$this->testPageSize(0, Pagination::DEFAULT_PAGE_SIZE);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @covers ::getPageSize
	 * @covers ::setPageSize
	 * @covers ::<!public>
	 */
	public function testNegativePageSize(){
		$this->testPageSize(-1, Pagination::DEFAULT_PAGE_SIZE);
	}


	/**
	 * @covers ::isFirstPage
	 * @covers ::isLastPage
	 * @covers ::<!public>
	 * @dataProvider getIsPageData
	 */
	public function testIsPage($feedSize, $pageSize, $currentPage, $isFirstPage, $isLastPage){
		$pagination	= new Pagination($this->buildFeed($this->generatePosts($feedSize)), $pageSize);
		$pagination->setCurrentPage($currentPage);

		$this->assertEquals($isFirstPage, $pagination->isFirstPage(), 'The first page state should be determined correctly');
		$this->assertEquals($isLastPage, $pagination->isLastPage(), 'The last page state should be determined correctly');
	}

	public function getIsPageData(){
		$items	= array();

		$items['First & Last']	= array(
			10,
			10,
			1,
			true,
			true
		);

		$items['Neither']	= array(
			30,
			10,
			2,
			false,
			false
		);

		$items['First Only']	= array(
			30,
			10,
			1,
			true,
			false
		);

		$items['Last Only']	= array(
			30,
			10,
			3,
			false,
			true
		);

		return $items;
	}


	/**
	 * @covers ::getPagesCount
	 * @covers ::getPosts
	 * @covers ::count
	 * @covers ::getIterator
	 * @covers ::<!public>
	 * @dataProvider getPaginatingData
	 */
	public function testPaginating($expectedPagesCount, array $expectedPagePosts, Feed $feed, $pageSize, $currentPage){
		$pagination	= new Pagination($feed, $pageSize);

		$this->assertEquals($expectedPagesCount, $pagination->getPagesCount(), 'The number of pages for a feed with '.count($feed).' posts should be correct');
		$pagination->setCurrentPage($currentPage);

		$this->assertEquals(count($expectedPagePosts), count($pagination), 'The correct number of posts for '.$currentPage.' should be selected');
		$this->assertEquals($expectedPagePosts, $pagination->getPosts(), 'The posts for page '.$currentPage.' should be correct');

		foreach($pagination as $post){
			$this->assertEquals(current($expectedPagePosts), $post, 'Posts should be iterable in order');
			next($expectedPagePosts);
		}
	}

	public function getPaginatingData(){
		$items	= array();

		// First page
		$postsCount	= 35;
		$posts		= $this->generatePosts($postsCount);
		$feed		= $this->buildFeed($posts);
		$items[]	= array(
			3,
			array_slice($posts, 0, 15),
			$feed,
			15,
			1,
		);

		// Middle page
		$postsCount	= 123;
		$posts		= $this->generatePosts($postsCount);
		$feed		= $this->buildFeed($posts);
		$items[]	= array(
			6,
			array_slice($posts, 84, 21),
			$feed,
			21,
			4,
		);

		// Last page
		$postsCount	= 46;
		$posts		= $this->generatePosts($postsCount);
		$feed		= $this->buildFeed($posts);
		$items[]	= array(
			4,
			array_slice($posts, 36, 10),
			$feed,
			12,
			4,
		);

		return $items;
	}


	/**
	 * @param int $count
	 * @return Post[]
	 */
	protected function generatePosts($count){
		$posts = array();
		for($i = 0; $i < $count; $i++){
			$posts[]	= $this->buildPost('Post '.$i);
		}
		return $posts;
	}

	/**
	 * @param array|null $properties
	 * @return Pagination|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected function buildPagination(array $properties = null){
		$builder	= $this->getMockBuilder(static::$classname)
						   ->disableOriginalConstructor();
		$mock	= $builder->getMock();

		$mirror	= new \ReflectionClass($mock);
		foreach((array)$properties as $name => $value){
			$property	= $mirror->getProperty($name);
			$property->setAccessible(true);
			$property->setValue($value);
		}

		return $mock;
	}

	/**
	 * @return Feed|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected function buildFeed(array $posts = null){
		$builder	= $this->getMockBuilder('\\Reprint\\Feed')
						   ->setMethods(array('getPosts'))
						   ->disableOriginalConstructor();
		$mock	= $builder->getMock();

		$mock->expects($this->any())
			 ->method('getPosts')
			 ->will($this->returnValue($posts));

		return $mock;
	}

	/**
	 * @return Post|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected function buildPost($title = null){
		$builder	= $this->getMockBuilder('\\Reprint\\Post')
						   ->setMethods(array('getTitle'))
						   ->disableOriginalConstructor();
		$mock	= $builder->getMock();

		$mock->expects($this->any())
			 ->method('getTitle')
			 ->will($this->returnValue($title));

		return $mock;
	}
}
