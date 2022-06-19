<?php
/**
 * Tests for TimeUnits
 * @author Santhosh Thottingal
 * @copyright Copyright © 2007-2013
 * @license GPL-2.0-or-later
 *
 * @covers \MediaWiki\Extension\CLDR\TimeUnits
 */
class TimeUnitsTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider providerTimeUnit
	 * @param string $language
	 * @param string $tsTime The timestamp to format
	 * @param string $currentTime The time to consider "now"
	 * @param string $expectedOutput The expected output
	 * @param string $desc Description
	 */
	public function testTimeUnit( $language, $tsTime, $currentTime, $expectedOutput, $desc ) {
		$lang = Language::factory( $language );
		$tsTime = new MWTimestamp( $tsTime );
		$currentTime = new MWTimestamp( $currentTime );
		$this->assertEquals(
			$expectedOutput,
			$lang->getHumanTimestamp( $tsTime, $currentTime ),
			$desc
		);
	}

	public static function providerTimeUnit() {
		return [
			[
				'en',
				'20111231170000',
				'20120101000000',
				'7 hours ago',
				'"Yesterday" across years',
			],
			[
				'en',
				'20120717190900',
				'20120717190929',
				'29 seconds ago',
				'"Just now"',
			],
			[
				'en',
				'20120717190900',
				'20120717191530',
				'6 minutes ago',
				'X minutes ago',
			],
			[
				'en',
				'20121006173100',
				'20121006173200',
				'1 minute ago',
				'"1 minute ago"',
			],
			[
				'en',
				'20120617190900',
				'20120717190900',
				'1 month ago',
				'Month difference'
			],
			[
				'en',
				'19910130151500',
				'20120716193700',
				'21 years ago',
				'Different year',
			],
			[
				'en',
				'20120714184300',
				'20120715040000',
				'9 hours ago',
				'Today at another time',
			],
			[
				'en',
				'20120617190900',
				'20120717190900',
				'1 month ago',
				'Another month'
			],
			[
				'en',
				'19910130151500',
				'20120716193700',
				'21 years ago',
				'Different year',
			],
			[
				'ml',
				'20111231170000',
				'20120101000000',
				'7 മണിക്കൂർ മുമ്പ്',
				'"Yesterday" across years',
			],
			[
				'ml',
				'20120717190900',
				'20120717190929',
				'29 സെക്കൻഡ് മുമ്പ്',
				'"Just now"',
			],
			[
				'ml',
				'20120717190900',
				'20120717191530',
				'6 മിനിറ്റ് മുമ്പ്',
				'X minutes ago',
			],
			[
				'ml',
				'20121006173100',
				'20121006173200',
				'1 മിനിറ്റ് മുമ്പ്',
				'"1 minute ago"',
			],
			[
				'ml',
				'20120617190900',
				'20120717190900',
				'1 മാസം മുമ്പ്',
				'Month difference'
			],
			[
				'ml',
				'19910130151500',
				'20120716193700',
				'21 വർഷം മുമ്പ്',
				'Different year',
			],
			[
				'ml',
				'20120714184300',
				'20120715040000',
				'9 മണിക്കൂർ മുമ്പ്',
				'Today at another time',
			],
			[
				'ml',
				'20120617190900',
				'20120717190900',
				'1 മാസം മുമ്പ്',
				'Another month'
			],
			[
				'ml',
				'19910130151500',
				'20120716193700',
				'21 വർഷം മുമ്പ്',
				'Different year',
			],
		];
	}
}
