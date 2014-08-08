<?php
namespace Reprint;

use SimplePie;
use SimplePie_File;
use SimplePie_Item;

class Feed implements \Countable, \IteratorAggregate, \ArrayAccess {
	const CACHE_KEY_POSTS	= 'posts';
	const DEFAULT_RENDERED_FILENAME	= 'index.php';

	protected $feedURL;
	protected $cache;
	protected $posts;

	public function __construct($feedURL, $cacheDir){
		$this->feedURL	= $feedURL;
		$this->cache	= new Cache($cacheDir.'/'.'feed-'.sha1($feedURL));
	}


	/**
	 * @param string|bool $bypassCache
	 * @return Post[]|null
	 */
	public function getPosts($bypassCache = false){
		if(!isset($this->posts) || $bypassCache){
			$this->posts	= $this->loadPosts($bypassCache);
		}

		return $this->posts;
	}

	/**
	 * @param string $outputDir	The path to output rendered templates to
	 * @param string $template	The template to render posts with *as a string*. Use `file_get_contents('path/to/template')` if the template is a file.
	 * @param bool $bypassCache Whether to bypass the cache for loading feeds
	 * @param string $filename A different filename to use for the created file within the post's directory. Will default to ::DEFAULT_RENDERED_FILENAME
	 * @return bool	Whether rendering was successful. If only some posts are correctly rendered, this will still be false.
	 */
	public function renderFeed($outputDir, $template, $bypassCache = false, $filename = null){
		if(!isset($filename)){
			$filename	= static::DEFAULT_RENDERED_FILENAME;
		}

		$success	= true;
		foreach($this->getPosts($bypassCache) as $post){
			$postFile = $outputDir.'/'.$post->getURLStub().'/'.$filename;

			// Build file
			$values		= $this->getRenderDataForPost($post);
			$content	= Utilities::renderTemplate($template, $values);

			// Save file
			$postDir	= dirname($postFile);
			if(!is_dir($postDir)){
				// Create directory hierarchy
				$result = mkdir($postDir, 0777, true);
				if(!$result){
					throw new \RuntimeException('Could not create directory "'.$postDir.'"');
				}
			}
			$result = @file_put_contents($postFile, $content);
			if(!$result){
				$success	= false;
			}
		}

		return $success;
	}

	protected function getRenderDataForPost(Post $post){
		$values  = array(
			'title'				=> $post->getTitle(false),
			'title_rendered'	=> $post->getTitle(),
			'date'				=> $post->getDate(),
			'summary'			=> $post->getSummary(150, false, false),
			'summary_rendered'	=> $post->getSummary(150),
			'content'			=> $post->getContent(false),
		);

		// NULL-out empty values
		foreach($values as &$value){
			if($value === ''){
				$value = null;
			}
		}

		// Include PHP-readable data
		$values['data']	= var_export($values, true);

		// Special post-export changes
		$values['date']	= $values['date']->format('c');

		return $values;
	}

	/**
	 * @param int|null $pageSize
	 * @return Pagination
	 */
	public function paginate($pageSize = null){
		return new Pagination($this, $pageSize);
	}


	/**
	 * @param bool $bypassCache
	 * @return Post[]|null
	 */
	protected function loadPosts($bypassCache = false){
		if(!$bypassCache){
			// Try loading from cache
			$result	= $this->loadCachedPosts();
			if(isset($result)){
				// Cached results found
				return $result;
			}
		}

		$feed	= $this->buildRawFeed();

		// Process feed
		$posts	= array();
		foreach($feed->get_items() as $item){
			$posts[]	= $this->buildPost($item);
		}

		// Save to cache
		$this->cachePosts($posts);

		return $posts;
	}

	/**
	 * @return Post[]|null
	 */
	protected function loadCachedPosts(){
		$key	= static::CACHE_KEY_POSTS;

		if(!$this->cache->contains($key)){
			// Cannot load
			return null;
		}

		$posts	= $this->cache->load($key);
		return $posts;
	}

	/**
	 * @param Post[] $posts
	 */
	protected function cachePosts(array $posts){
		$key	= static::CACHE_KEY_POSTS;
		$expiry	= 60*60*12;	// 12 hours

		$this->cache->save($key, $posts, $expiry);
	}

	/**
	 * @return SimplePie
	 */
	protected function buildRawFeed(){
		$feed = new SimplePie();
		$feed->enable_cache(false); // Caching handled at a higher level
		$feed->set_file(new SimplePie_File($this->feedURL));

		// Initialize parser
		$success	= $feed->init();
		if(!$success){
			throw new \RuntimeException('Could not load feed');
		}

		return $feed;
	}

	/**
	 * @param SimplePie_Item $item
	 * @return Post
	 */
	protected function buildPost(SimplePie_Item $item){
		return Post::loadFromSimplePieItem($item);
	}

// Interfaces
	// Countable
	public function count(){
		return count($this->getPosts());
	}

	// IteratorAggregate
	public function getIterator(){
		return new \ArrayIterator($this->getPosts());
	}

	// ArrayAccess
	public function offsetExists($offset){
		$posts	= $this->getPosts();
		return isset($posts[$offset]);
	}
	public function offsetGet($offset){
		$posts	= $this->getPosts();
		return (isset($posts[$offset]) ? $posts[$offset] : null);
	}
	public function offsetUnset($offset){
		throw new \BadMethodCallException('Cannot modify feed');
	}
	public function offsetSet($offset, $value){
		throw new \BadMethodCallException('Cannot modify feed');
	}
}
