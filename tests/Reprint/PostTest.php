<?php
namespace tests\Reprint;

use Reprint\Post;
use SimplePie_Item;

/**
 * @coversDefaultClass \Reprint\Post
 */
class PostTest extends \PHPUnit_Framework_TestCase {
	protected static $classname	= '\\Reprint\\Post';

	/**
	 * @covers ::__construct
	 * @dataProvider getConstructData
	 */
	public function testConstruct($data, $checks = null){
		$post	= new Post($data);

		if(isset($checks)){
			if($checks === true){
				$checks	= $data;
			}

			foreach($checks as $property => $expected){
				$this->assertEquals($expected, $post->{'get'.$property}(), 'Property "'.$property.'" should have correct value');
			}
		}
	}

	public function getConstructData(){
		$items	= array();

		// Defaults
		$items[]	= array(
			array(),
			array(
				'originalURL'	=> null,
				'title'			=> null,
				'content'		=> null,
				'date'			=> null,
				'categories'	=> array(),
				'images'		=> array(),
			),
		);

		// Properties
		$items[]	= array(
			array(
				'url'	=> 'http://www.example.com',
			),
			array(
				'originalURL'	=> 'http://www.example.com',
			),
		);
		$items[]	= array(
			array(
				'title'	=> 'Post Title',
			),
			true,
		);
		$items[]	= array(
			array(
				'date'	=> new \DateTime('2014/01/31'),
			),
			true,
		);
		$items[]	= array(
			array(
				'date'	=> '2014/01/31',
			),
			array(
				'date'	=> new \DateTime('2014/01/31')
			),
		);
		$items[]	= array(
			array(
				'categories'	=> array(
					'Category One',
					'Category Two',
				),
			),
			array(
				'categories'	=> array(
					'category-one'	=> 'Category One',
					'category-two'	=> 'Category Two',
				),
			),
			true,
		);
		$items[]	= array(
			array(
				'images'	=> array(
					'image-1.jpg',
				),
			),
			true,
		);

		return $items;
	}

	/**
	 * @expectedException \Exception
	 */
	public function testConstructWithInvalidDate(){
		$this->testConstruct([
			'date'	=> 'not a date',
		]);
	}


	/**
	 * @covers ::processSimplePieItem
	 * @dataProvider getProcessSimplePieItemData
	 */
	public function testProcessSimplePieItem($expected, $item, $message = null){
		$method	= new \ReflectionMethod(static::$classname, 'processSimplePieItem');
		$method->setAccessible(true);
		$result	= $method->invoke(null, $item);

		$this->assertEquals($expected, $result, (isset($message) ? $message : 'The item should be processed correctly'));
	}

	public function getProcessSimplePieItemData(){
		$defaultValues		= array(
			'title'			=> 'The Post Title',
			'content'		=> 'Content for the post...',
			'date'			=> new \DateTime('-1 week'),
			'permalink'		=> 'http://www.example.com/2014/01/title-of-the-post',
			'enclosures'	=> array(
				array('link'		=> 'http://www.example.com/path/to/image.jpg'),
				array('link'		=> 'http://www.example.com/path/to/file.txt'),
				array('thumbnail'	=> 'http://www.example.com/path/to/other.png'),
			),
			'categories'	=> array('Category One', 'Category Two'),
		);
		$defaultExpected	= array(
			'title'		=> $defaultValues['title'],
			'content'	=> $defaultValues['content'],
			'date'		=> $defaultValues['date'],
			'url'		=> $defaultValues['permalink'],
			'images'	=> array(
				'http://www.example.com/path/to/image.jpg',
				'http://www.example.com/path/to/other.png',
			),
			'categories'	=> $defaultValues['categories'],
		);

		$items	= array();

		// Normal
		$values		= $defaultValues;
		$expected	= $defaultExpected;
		$items[]	= array(
			'expected'	=> $expected,
			'item'		=> $this->buildMockSimplePieItem($values),
		);

		// No URL
		$values		= $defaultValues;
		unset($values['permalink']);
		$expected	= $defaultExpected;
		unset($expected['url']);
		$items[]	= array(
			'expected'	=> $expected,
			'item'		=> $this->buildMockSimplePieItem($values),
		);

		// No images
		$values		= $defaultValues;
		unset($values['enclosures']);
		$expected	= $defaultExpected;
		$expected['images']	= array();
		$items[]	= array(
			'expected'	=> $expected,
			'item'		=> $this->buildMockSimplePieItem($values),
		);

		// No categories
		$values		= $defaultValues;
		unset($values['categories']);
		$expected	= $defaultExpected;
		$expected['categories']	= array();
		$items[]	= array(
			'expected'	=> $expected,
			'item'		=> $this->buildMockSimplePieItem($values),
		);

		return $items;
	}


	public function testBasicGetters(){
		$url	= 'http://www.example.com/path/to/post';
		$properties	= array(
			'date'	=> new \DateTime('-2 weeks'),
			'url'	=> $url,
		);

		$post	= $this->buildPost($properties);

		unset($properties['url']);
		foreach($properties as $name => $value){
			$this->assertEquals($value, $post->{'get'.$name}(), 'The property '.$name.' should provide the correct value');
		}

		$this->assertEquals($url, $post->getOriginalURL(), 'The original URL getter should provide the URL');
	}

	/**
	 * @covers ::getTitle
	 * @covers ::<!public>
	 */
	public function testGetTitle(){
		$rawTitle		= 'The "Title" of the Post';
		$post	= $this->buildPost(array(
			'title'	=> $rawTitle,
		));

		$this->assertEquals($rawTitle, $post->getTitle(false), 'The non-rendered title should be unchanged');
		$this->assertEquals('The “Title” of the Post', $post->getTitle(), 'The rendered title should be processed correctly');
	}

	/**
	 * @covers ::getSummary
	 * @covers ::<!public>
	 */
	public function testGetSummary(){
		$content	= <<<HTML
<p>Cras<img src="hello" /> "consectetur" odio ac dolor tincidunt, et tincidunt nulla auctor. Vivamus vitae mollis nunc. Donec condimentum fringilla neque et adipiscing. Proin mi urna, elementum vitae dapibus at, facilisis sed erat.</p><p>In consequat lacus neque, ac viverra magna posuere vel. Praesent varius pretium aliquam. In hendrerit euismod diam, nec convallis est feugiat ac. Ut eget elementum dolor. Donec in sapien mauris. Phasellus malesuada eget est et scelerisque. Integer quis aliquet augue.</p>
HTML;
		$post	= $this->buildPost(array(
			'content'	=> $content,
		));

		$this->assertEquals('Cras "consectetur" odio ac dolor tincidunt, et tincidunt nulla auctor. Vivamus vitae mollis nunc. Donec condimentum...',
							$post->getSummary(null, false, false),
							'The summary should be truncated by default');

		$this->assertEquals('Cras "consectetur" odio ac dolor...',
							$post->getSummary(45, false, false),
							'The summary should be truncated to the given amount');

		$this->assertEquals('Cras "consectetur" odio ac dolor!?!',
							$post->getSummary(45, false, false, '!?!'),
							'The truncation should use the custom ending characters');

		$this->assertEquals('Cras “con­secte­tur” odio ac dolor...', // There are hidden characters in "consectetur"
							$post->getSummary(45, true, false),
							'The truncation should be rendered');

		$this->assertEquals('<p>Cras "consectetur" odio ac dolor...</p>',
							$post->getSummary(45, false, true),
							'The truncation should retain allowed HTML tags');
	}

	/**
	 * @covers ::getContent
	 * @covers ::<!public>
	 */
	public function testGetContent(){
		$title		= 'The <strong>Post</strong> Title';
		$content	= <<<HTML
<p>Cras<img src="hello" /> "consectetur" odio ac dolor tincidunt, et tincidunt nulla auctor. Vivamus vitae mollis nunc. Donec condimentum fringilla neque et adipiscing. Proin mi urna, elementum vitae dapibus at, facilisis sed erat.</p><p>In consequat lacus neque, ac viverra magna posuere vel. Praesent varius pretium aliquam. In hendrerit euismod diam, nec convallis est feugiat ac. Ut eget elementum dolor. Donec in sapien mauris. Phasellus malesuada eget est et scelerisque. Integer quis aliquet augue.</p>
HTML;
		$post	= $this->buildPost(array(
			'title'		=> $title,
			'content'	=> $content,
		));

		$this->assertEquals($content,
							$post->getContent(false, false),
							'The raw content should be accessible');

		$this->assertEquals('<h1>The &lt;strong&gt;Post&lt;/strong&gt; Title</h1>'.$content,
							$post->getContent(true, false),
							'The page title should be escaped and prepended to the content');

		$renderedContent	= <<<HTML
<p>Cras<img src="hello" /> “con­secte­tur” odio ac dolor tin­cidunt, et tin­cidunt nulla auc­tor. Viva­mus vitae mol­lis nunc. Donec condi­men­tum fringilla neque et adip­isc­ing. Proin mi urna, ele­men­tum vitae dapibus at, facil­i­sis sed erat.</p><p>In con­se­quat lacus neque, ac viverra magna posuere vel. Prae­sent var­ius pretium ali­quam. In hen­drerit euis­mod diam, nec con­va­l­lis est feu­giat ac. Ut eget ele­men­tum dolor. Donec in sapien mau­ris. Phasel­lus male­suada eget est et scelerisque. Inte­ger quis ali­quet augue.</p>
HTML;
		$this->assertEquals($renderedContent,
							$post->getContent(false, true),
							'The page content should be rendered correctly');
	}

	/**
	 * @covers ::getImages
	 * @covers ::hasImages
	 * @covers ::<!public>
	 */
	public function testImages(){
		// Preset
		$images	= array(
			'http://www.example.com/path/to/image.jpg',
			'http://www.example.com/path/to/other.png',
		);
		$post	= $this->buildPost(array(
			'images'	=> $images,
		));

		$this->assertEquals($images,
							$post->getImages(),
							'Pre-loaded images should be used if set');
		$this->assertTrue($post->hasImages(), 'Images should be recognised');

		// Generated
		$content	= <<<HTML
<p>This is the content of the image with images</p>
<img alt="A test image" src="http://www.example.com/path/to/image.jpg" class="image" />
<p>More content with an inline image <img alt="A test image" src="http://www.example.com/path/to/other.png" /></p>
HTML;
		$post	= $this->buildPost(array(
			'images'	=> array(),
			'content'	=> $content
		));
		$this->assertEquals($images,
							$post->getImages(),
							'Images should be parsed from content if not set');
		$this->assertTrue($post->hasImages(), 'Images should be recognised');


		// Empty
		$post	= $this->buildPost(array(
			'content'	=> '<p>Imageless HTML</p>',
		));
		$this->assertEmpty($post->getImages(), 'An empty array should be returned when no images are found');
		$this->assertFalse($post->hasImages(), 'No images found should be recognised');
	}

	/**
	 * @covers ::getCategories
	 * @covers ::setCategories
	 * @covers ::removeCategories
	 * @covers ::<!public>
	 */
	public function testCategories(){
		$categories	= array(
			'category-one'	=> 'Category One',
			'category-two'	=> 'Category Two',
		);
		$post	= $this->buildPost(array(
			'categories'	=> $categories,
		));

		$this->assertEquals($categories,
							$post->getCategories(),
							'Processed categories should not be changed');


		$post	= $this->buildPost();
		$post->setCategories(array_values($categories));

		$this->assertEquals($categories,
							$post->getCategories(),
							'Slugs for categories should be generated');

		$post->removeCategories(array('category-one'), false);
		$this->assertEquals(array('category-two' => 'Category Two'),
							$post->getCategories(),
							'Filtered categories should be removed');

		$post->removeCategories(array('Category Two'));
		$this->assertEquals(array(),
							$post->getCategories(),
							'Filtered categories should be removed');
	}

	/**
	 * @covers ::getSlug
	 * @covers ::<!public>
	 * @dataProvider getSlugData
	 */
	public function testGetSlug($expected, array $postData, $message = null){
		$post	= $this->buildPost($postData);
		$this->assertEquals($expected, $post->getSlug(), $message);
	}

	public function getSlugData(){
		return array(
			array(
				'an-example-slug',
				array(
					'slug'	=> 'an-example-slug',
				),
				'Pre-loaded slugs should be used',
			),

			array(
				'post-slug',
				array(
					'url'	=> 'http://www.example.com/2014/01/post-slug',
				),
				'The slug from the URL should be used if set',
			),

			array(
				'the-post-title',
				array(
					'title'	=> 'The Post Title',
				),
				'The slug should be generated from the title if no URL is set',
			),

			array(
				'the-post-title',
				array(
					'url'	=> 'http://www.example.com/?p=100',
					'title'	=> 'The Post Title',
				),
				'The slug should be generated from the title if no slug is in the URL',
			),
		);
	}

	/**
	 * @covers ::getURLStub
	 * @covers ::<!public>
	 * @dataProvider getURLStubData
	 */
	public function testGetURLStub($expected, $params, $format = null, $message = null){
		$post	= $this->buildPost($params);
		$this->assertEquals($expected, $post->getURLStub($format), $message);
	}

	public function getURLStubData(){
		return array(
			array(
				null,
				null,
				null,
				'No stub should be generated without a slug or date',
			),
			array(
				'2014/01/the-post-slug',
				array(
					'slug'	=> 'the-post-slug',
					'date'	=> new \DateTime('2014/01/31'),
				),
				null,
				'The default URL stub format should be generated correctly',
			),
			array(
				'the-post-slug',
				array(
					'slug'	=> 'the-post-slug',
					'date'	=> new \DateTime('2014/01/31'),
				),
				'{{slug}}',
				'Custom slug formats should be processed correctly',
			),
			array(
				'2014/01/31/the-post-slug',
				array(
					'slug'	=> 'the-post-slug',
					'date'	=> new \DateTime('2014/01/31'),
				),
				'{{year}}/{{month}}/{{day}}/{{slug}}',
				'Custom slug formats should be processed correctly',
			),
		);
	}

	/**
	 * @covers ::getMeta
	 * @covers ::setMeta
	 * @covers ::<!public>
	 */
	public function testMeta(){
		$post	= $this->buildPost();

		$key	= 'some-meta';
		$value	= 'some meta value';

		$this->assertEmpty($post->getMeta($key), 'No meta should be defined by default');
		$post->setMeta($key, $value);
		$this->assertEquals($value, $post->getMeta($key), 'Meta should be retrieved after setting');

		$otherValue	= (object)array('something' => 'value');
		$this->assertEquals($otherValue, $post->getMeta('undefined-meta', $otherValue), 'Default values should be returned for undefined meta values');
	}




	/**
	 * @param array|null $properties
	 * @param array|null $methods
	 * @return Post|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected function buildPost(array $properties = null, array $methods = null){
		$builder	= $this->getMockBuilder(static::$classname)
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
	 * @return SimplePie_Item|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected function buildMockSimplePieItem(array $values, $convertItems = true){
		$methods	= array(
			'get_title',
			'get_content',
			'get_date',
			'get_permalink',
			'get_categories',
			'get_enclosures',
		);

		$builder	= $this->getMockBuilder('\\SimplePie_Item');
		$builder->disableOriginalConstructor()
				->setMethods($methods);

		$mock	= $builder->getMock();

		foreach($values as $key => $value){
			$method	= $mock->expects($this->any())
						   ->method('get_'.$key);

			$return	= null;
			if($convertItems){
				// Convert raw PHP values, etc to SimplePie mocks
				switch($key){
					case 'date':
						$return	= $this->returnCallback(function($format) use($value){
							return $value->format($format);
						});
						break;

					case 'categories':
						$value	= (array)$value;
						if(!$value || ($value[0] instanceof \SimplePie_Category)){
							// Already processed
							break;
						}

						$value	= $this->buildMockSimplePieCategories($value);
						break;

					case 'enclosures':
						$value	= (array)$value;
						if(!$value || ($value[0] instanceof \SimplePie_Enclosure)){
							// Already processed
							break;
						}

						$value	= $this->buildMockSimplePieEnclosures($value);
						break;
				}
			}

			$method->will((isset($return) ? $return : $this->returnValue($value)));
		}

		return $mock;
	}

	/**
	 * @param \SimplePie_Category[]|\PHPUnit_Framework_MockObject_MockObject[] $categories
	 *
	 * @return array
	 */
	protected function buildMockSimplePieCategories(array $categories){
		$mocks	= array();

		foreach($categories as $category){
			$mock	= $this->getMockBuilder('\\SimplePie_Category')
						   ->disableOriginalConstructor()
						   ->setMethods(array('get_term'))
						   ->getMock();

			$mock->expects($this->any())
				 ->method('get_term')
				 ->will($this->returnValue($category));

			$mocks[]	= $mock;
		}

		return $mocks;
	}

	/**
	 * @param \SimplePie_Enclosure[]|\PHPUnit_Framework_MockObject_MockObject[] $enclosures
	 *
	 * @return array
	 */
	protected function buildMockSimplePieEnclosures(array $enclosures){
		$mocks	= array();

		foreach($enclosures as $enclosure){
			$mock	= $this->getMockBuilder('\\SimplePie_Enclosure')
						   ->disableOriginalConstructor()
						   ->setMethods(array('get_thumbnail', 'get_link'))
						   ->getMock();

			if(isset($enclosure['thumbnail'])){
				$mock->expects($this->any())
					 ->method('get_thumbnail')
					 ->will($this->returnValue($enclosure['thumbnail']));
			}
			if(isset($enclosure['link'])){
				$mock->expects($this->any())
					 ->method('get_link')
					 ->will($this->returnValue($enclosure['link']));
			}

			$mocks[]	= $mock;
		}

		return $mocks;
	}
}
