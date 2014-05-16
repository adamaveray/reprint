<?php
namespace Reprint;

class Pagination implements \IteratorAggregate, \Countable {
	const DEFAULT_PAGE_SIZE	= 10;

	protected $pageSize		= self::DEFAULT_PAGE_SIZE;
	protected $currentPage	= 1;

	protected $postsCount;
	protected $pagesCount;

	/**
	 * @param Feed $feed
	 * @param int|null $pageSize
	 */
	public function __construct(Feed $feed, $pageSize = null){
		$this->feed			= $feed;
		$this->postsCount	= count($feed);

		if(isset($pageSize)){
			$this->setPageSize($pageSize);
		}
	}


	/**
	 * @return Feed
	 */
	public function getFeed(){
		return $this->feed;
	}

	/**
	 * @return int
	 */
	public function getPagesCount(){
		return ceil($this->getPostsCount() / $this->getPageSize());
	}

	/**
	 * @return int
	 */
	protected function getPostsCount(){
		return $this->postsCount;
	}

	/**
	 * @return int
	 */
	public function getPageSize(){
		return $this->pageSize;
	}

	/**
	 * @param int $pageSize
	 */
	public function setPageSize($pageSize){
		if(!is_numeric($pageSize)){
			return;
		}

		if($pageSize < 1){
			throw new \InvalidArgumentException('Page size must be at least 1');
		}

		$this->pageSize	= (int)$pageSize;
	}

	/**
	 * @return int
	 */
	public function getCurrentPage(){
		return $this->currentPage;
	}

	/**
	 * @param int $currentPage
	 */
	public function setCurrentPage($currentPage){
		if(!is_numeric($currentPage)){
			return;
		}

		$currentPage	= (int)$currentPage;

		if($currentPage < 1 || $currentPage > $this->getPagesCount()){
			throw new \OutOfRangeException('Page must be between 1 and '.$this->getPagesCount());
		}

		$this->currentPage	= $currentPage;
	}

	/**
	 * @return Post[]
	 */
	public function getPosts(){
		$offset	= (($this->currentPage - 1) * $this->pageSize);

		$posts	= array();
		for($i = 0; $i < $this->pageSize; $i++){
			$position	= $i + $offset;
			if(!isset($this->feed[$position])){
				// End of page
				break;
			}

			$posts[]	= $this->feed[$position];
		}
		return $posts;
	}


// Interfaces
	/**
	 * @return int
	 */
	public function count(){
		return count($this->getPosts());
	}

	/**
	 * @return Post[]|\ArrayIterator|\Traversable
	 */
	public function getIterator(){
		return new \ArrayIterator($this->getPosts());
	}
}
