<?php
namespace tests\Reprint;

use Reprint\Cache;

/**
 * @coversDefaultClass \Reprint\Cache
 */
class CacheTest extends \PHPUnit_Framework_TestCase {
	const TEMP_DIR_NAME	= 'reprint-cache-test';

	protected static $classname	= '\\Reprint\\Cache';
	protected $cacheDir;

	public function setUp(){
		$this->cacheDir	= sys_get_temp_dir().'/'.static::TEMP_DIR_NAME.'-'.mt_rand(0, 100000);
	}

	public function tearDown(){
		// Cleanup
		reprint_deleteDir($this->cacheDir);
		$this->cacheDir	= null;
	}


	/**
	 */
	public function testCache(){
		$cache	= $this->buildCache();

		$key	= 'example-key';
		$value	= array('something' => 'value');
		$expiry	= 60*60*24;
		$expiredExpiry	= -60*60;

		// Uncached
		$this->assertFalse($cache->contains($key), 'Uncached items should not be found');
		$this->assertNull($cache->load($key), 'Uncached items should return null');

		// Caching
		$this->assertTrue($cache->save($key, $value, $expiry), 'Cached values should be saved');

		// Retrieving
		$this->assertTrue($cache->contains($key), 'Cached items should be found');
		$this->assertEquals($value, $cache->load($key), 'Cached items should return the same value');

		// Clearing
		$cache->clear($key);
		$this->assertFalse($cache->contains($key), 'Cleared cached items should not be found');

		// Recaching
		$this->assertTrue($cache->save($key, $value, $expiry), 'Recaching items should be valid');
		$this->assertTrue($cache->contains($key), 'Reached items should be valid within expiry window');

		// Expiry
		$this->assertTrue($cache->save($key, $value, $expiredExpiry), 'Overwriting cached values should be valid');
		$this->assertFalse($cache->contains($key), 'Cached items should not be valid after expiry window');
	}


	/**
	 * @return Cache
	 */
	protected function buildCache(){
		$cache	= new Cache($this->cacheDir);
		return $cache;
	}
}
