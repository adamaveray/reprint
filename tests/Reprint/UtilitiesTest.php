<?php
namespace tests\Reprint;

use Reprint\Utilities;

/**
 * @coversDefaultClass \Reprint\Utilities
 */
class UtilitiesTest extends \PHPUnit_Framework_TestCase {
	protected static $classname	= '\\Reprint\\Utilities';

	/**
	 * @covers ::stringToSlug
	 * @covers ::<!public>
	 * @dataProvider getStringToSlugData
	 */
	public function testStringToSlug($expected, $string, $message = null){
		$this->assertEquals($expected, Utilities::stringToSlug($string), $message);
	}

	public function getStringToSlugData(){
		return array(
			array('example-string', 'Example String', 'Slugs should be generated from basic strings'),
			array('an-example-string-slug', '"An Example" String Slug', 'Non-alphanumeric characters should be removed from slugs'),
			array('cant-keep-apostrophes', 'Can\'t keep apostrophes', 'Apostrophes should be removed'),
			array('example-string', ' (Example)  &   "String" ', 'Multiple consecutive non-whitespace characters should be reduced to one'),
		);
	}


	/**
	 * @covers ::escape
	 * @covers ::<!public>
	 * @dataProvider getEscapeData
	 */
	public function testEscape($expected, $string, $message = null){
		$this->assertEquals($expected, Utilities::escape($string), $message);
	}

	public function getEscapeData(){
		return array(
			array('Text then a &lt;tag&gt;', 'Text then a <tag>', 'HTML should be correctly escaped'),
			array('&#039;Text&#039; with &quot;quotes&quot;', '\'Text\' with "quotes"', 'Quotes should be correctly escaped'),
		);
	}


	/**
	 * @covers ::renderTemplate
	 * @covers ::<!public>
	 * @dataProvider getRenderTemplateData
	 */
	public function testRenderTemplate($expected, $content, array $args = null, $message = null){
		$this->assertEquals($expected, Utilities::renderTemplate($content, $args), $message);
	}

	public function getRenderTemplateData(){
		return array(
			array(
				'An example {{template}}',
				'An example {{template}}',
				null,
				'Argument-less template should not be modified',
			),
			array(
				'An example template',
				'An example {{type}}',
				array(
					'type'	=> 'template',
				),
				'Template tags should be replaced',
			),
			array(
				'An example template with {{something}}',
				'An example {{type}} with {{something}}',
				array(
					'type'	=> 'template',
				),
				'Unknown tags should not be modified',
			),
		);
	}
}
