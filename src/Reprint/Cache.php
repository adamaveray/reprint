<?php
namespace Reprint;

class Cache {
	protected $cacheDir;

	/**
	 * @param string $cacheDir
	 */
	public function __construct($cacheDir){
		$this->cacheDir	= $cacheDir;
		if(!is_dir($this->cacheDir)){
			$result	= mkdir($this->cacheDir, 0777, true);
			if($result === false){
				throw new \RuntimeException('Could not create cache dir');
			}
		}
	}


	/**
	 * @return string
	 */
	public function getCacheDir(){
		return $this->cacheDir;
	}


	/**
	 * @param string $key	The key the data was cached with
	 * @return mixed|null	The data saved to the cache, or null if no data found
	 * @see saveCache
	 */
	public function load($key){
		if(!$this->contains($key)){
			return null;
		}

		$path   = $this->getPathForKey($key);
		return unserialize(file_get_contents($path));
   	}

	/**
	 * @param string $key	The key to cache the data under
	 * @param mixed $data	The cache data
	 * @param int $expiry A number of seconds the data is considered invalid after
	 * @return bool	Whether saving was successful
	 * @see loadCache
	 */
	public function save($key, $data, $expiry){
		$this->clear($key);
		$path	= $this->getPathForKey($key);
		$result	= file_put_contents($path, serialize($data));
		if(!$result){
			return false;
		}

		// Set expiry time
		touch($path, $expiry + time());

		return true;
	}

	/**
	 * @param string $key		The key the data was cached with
	 * @return bool	Whether the cache is available
	 */
	public function contains($key){
		$path   = $this->getPathForKey($key);

		if(!file_exists($path)){
			// Cache empty
			return false;
		}

		if(filemtime($path) < time()){
			// Cache expired
			return false;
		}

		// Cache valid
		return true;
	}

	/**
	 * @param string $key		The key the data was cached with
	 */
	public function clear($key){
		$path	= $this->getPathForKey($key);
		if(file_exists($path)){
			unlink($path);
		}
	}

	/**
	 * @param string $key	The key for an item in the cache
	 * @return string		The filesystem path for the cached item
	 */
	protected function getPathForKey($key){
        return $this->getCacheDir().'/'.sha1($key);
	}
}
