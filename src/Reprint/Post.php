<?php
namespace Reprint;

use SimplePie_Item;
use SimplePie_Category;
use TruncateHTML;
use phpTypography;

class Post {
	const DEFAULT_URL_PATTERN	= '{{year}}/{{month}}/{{slug}}';
	const ALLOWED_TAGS			= '<p><em><strong><h1><h2><h3><h4><h5><ul><ol><li><blockquote><code><pre><span>';
	const DEFAULT_SUMMARY_LENGTH	= 120;

	protected static $imageExtensions	= array('png','jpg','jpeg','gif','bmp');

	protected $url;
	protected $title;
	protected $content;
	protected $slug;
	protected $date;
	protected $categories	= array();
	protected $images		= array();

	protected $meta	= array();
	protected $renderers	= array();

	public function __construct(array $details){
		$defaults	= array(
			'url'			=> null,
			'title'			=> null,
			'content'		=> null,
			'date'			=> null,
			'categories'	=> array(),
			'images'		=> array(),
		);
		$details	= array_intersect_key(array_merge($defaults, $details), $defaults);

		// Process special values
		if(isset($details['date'])){
			if(!($details['date'] instanceof \DateTime)){
				$details['date']	= new \DateTime($details['date']);
			}
		}
		if(isset($details['categories'])){
			$details['categories']	= (array)$details['categories'];
		}
		if(isset($details['images'])){
			$details['images']		= (array)$details['images'];
		}

		// Store values
		foreach($details as $key => $value){
			if(is_callable(array($this, 'set'.$key))){
				$this->{'set'.$key}($value);
			} else {
				$this->{$key} = $value;
			}
		}
	}


	/**
	 * @return string|null
	 */
	public function getOriginalURL(){
		return $this->url;
	}

	/**
	 * @param bool $render	Whether to style the title for styled output
	 * @return string		The post's title
	 */
	public function getTitle($render = true){
		$title	= $this->title;
		if($render && isset($title)){
			$title	= $this->renderText($title);
		}
		return $title;
	}

	/**
	 * @param int|null $length	The maximum number of characters to return (excluding HTML tags)
	 * @param bool $render		Whether to process the content for styled output
	 * @param bool $allowHTML	Whether to keep HTML in the content
	 * @param string $ending	The text to append to the end of the truncated summary
	 * @return string			The post summary
	 */
	public function getSummary($length = null, $render = true, $allowHTML = true, $ending = null){
		if(!$length){
			$length	= static::DEFAULT_SUMMARY_LENGTH;
		}
		if(!isset($ending)){
			$ending = '...';
		}

		$summary	= $this->getContent(false, $render);

		if($render){
			$summary	= $this->renderText($summary);
		}

		$summary	= str_replace('</p>', '</p> ', $summary); // Force space between paragraphs
		$summary	= strip_tags($summary, ($allowHTML ? static::ALLOWED_TAGS : null));

		return TruncateHTML::truncate($summary, $length, $ending);
	}

	/**
	 * @param bool $includeTitle	Whether to include the post title in the page's content
	 * @param bool $render			Whether to process the content for styled output
	 * @return string				The post's content
	 */
	public function getContent($includeTitle = true, $render = true){
		$content	= $this->content;
		if($render && isset($content)){
			$content	= $this->renderText($content);
		}

		if($includeTitle){
			$title		= $this->getTitle($render);
			if(isset($title)){
				$content = '<h1>'.Utilities::escape($title).'</h1>'.$content;
			}
		}

		return $content;
	}

	/**
	 * @return array
	 */
	public function getImages(){
		if(!$this->images){
			// Determine images from source
			$this->images	= $this->parseImages();
		}

		return $this->images;
	}

	/**
	 * @return bool
	 */
	public function hasImages(){
		return (count($this->getImages()) > 0);
	}

	/**
	 * @return \DateTime|null
	 */
	public function getDate(){
		return $this->date;
	}

	/**
	 * @return array
	 */
	public function getCategories(){
		return $this->categories;
	}

	/**
	 * @param array $categories
	 */
	public function setCategories(array $categories){
		$processedCategories	= array();
		foreach($categories as $category){
			$processedCategories[Utilities::stringToSlug($category)]	= $category;
		}

		$this->categories	= $processedCategories;
	}

	/**
	 * Removes the given categories from the post
	 *
	 * @param array $removedCategories	Categories to remove
	 * @param bool $slugify				Whether to convert the given category names to slugs for matching
	 */
	public function removeCategories(array $removedCategories, $slugify = true){
		foreach($removedCategories as $category){
			if($slugify){
				$category	= Utilities::stringToSlug($category);
			}

			unset($this->categories[$category]);
		}
	}

	/**
	 * @return string
	 */
	public function getSlug(){
		if(!isset($this->slug)){
			// Build slug
			$slug	= null;
			if(isset($this->url)){
				// Use slug from original URL
				$slug = trim(strtolower(pathinfo(rtrim($this->url, '/'), \PATHINFO_FILENAME)));
			}

			if(!$slug || substr($slug, 0, 1) === '?'){
				// Generate slug from title
				$slug	= Utilities::stringToSlug($this->getTitle());
			}
			$this->slug	= $slug;
		}

		return $this->slug;
	}

	/**
	 * @param string|null $pattern	An alternate pattern to use for the URL slub
	 * @return string|null			The URL stub
	 *
	 * @see Post::DEFAULT_URL_PATTERN
	 */
	public function getURLStub($pattern = null){
		$slug	= $this->getSlug();
		$date	= $this->getDate();
		if(!isset($slug) || !isset($date)){
			return null;
		}

		if(!isset($pattern)){
			$pattern	= static::DEFAULT_URL_PATTERN;
		}

		$replace	= array(
			'year'	=> $date->format('Y'),
			'month'	=> $date->format('m'),
			'day'	=> $date->format('d'),
			'slug'	=> $slug,
		);

		return Utilities::renderTemplate($pattern, $replace);
	}


	/**
	 * @param string $name			The name of the meta value to retrieve
	 * @param mixed|null $default	The value to return if the meta value is not set
	 * @return mixed|null			The meta value, or $default if not set
	 */
	public function getMeta($name, $default = null){
		if(!isset($this->meta[$name])){
			return $default;
		}

		return $this->meta[$name];
	}

	/**
	 * @param string $name	The name of the meta value to store
	 * @param mixed $value	The value for the meta value
	 */
	public function setMeta($name, $value){
		$this->meta[$name]	= $value;
	}


	/**
	 * Locates inline images within the post's content
	 *
	 * @return array	The URLs for images found within the post
	 */
	protected function parseImages(){
		$images	= array();
		$content	= strip_tags($this->content, '<img>'); // Remove all non-image tags

		if(preg_match_all('~<img [^>]*?src="([^"]*?)"~', $content, $matches)){
			$images	= $matches[1];
		}

		return $images;
	}

	/**
	 * Styles the given text, for example converting basic characters to styled versions (e.g. simple quotes to angled quotes)
	 *
	 * @param string $text			The text to process
	 * @param bool $useTypography	Whether to process the post's typography
	 * @return string				The processed text
	 */
	protected function renderText($text, $useTypography = true){
		// Typography
		if($useTypography){
			if(!isset($this->renderers['typography'])){
				$this->renderers['typography']	= new phpTypography();
			}
			$text	= @$this->renderers['typography']->process($text);
		}

		return $text;
	}



	/**
	 * @param SimplePie_Item $item
	 * @return self
	 */
	public static function loadFromSimplePieItem(SimplePie_Item $item){
		$details	= static::processSimplePieItem($item);

		return new static($details);
	}

	/**
	 * @param SimplePie_Item $item
	 * @return array
	 */
	protected static function processSimplePieItem(SimplePie_Item $item){
		$details	= array();
		$details['title']	= $item->get_title();
		$details['content']	= $item->get_content();
		$details['date']	= new \DateTime($item->get_date('c'));

		// Process URL
		if($item->get_permalink()){
			$details['url']		= $item->get_permalink();
		}

		// Load images
		$details['images']	= array();
		foreach((array)$item->get_enclosures() as $enclosure){
			/** @var \SimplePie_Enclosure $enclosure */
			if(!is_null($enclosure->get_thumbnail())){
				$details['images'][]	= $enclosure->get_thumbnail();
			} else if(in_array(strtolower(pathinfo($enclosure->get_link(), \PATHINFO_EXTENSION)), static::$imageExtensions)){
				$details['images'][]	= $enclosure->get_link();
			}
		}
		$details['images']	= array_unique($details['images']);

		$categories	= $item->get_categories();
		if($categories){
			$details['categories']	= array_map(function(SimplePie_Category $simplePieCategory){
				return $simplePieCategory->get_term();
			}, $categories);
		} else {
			$details['categories']	= array();
		}

		return $details;
	}
}
