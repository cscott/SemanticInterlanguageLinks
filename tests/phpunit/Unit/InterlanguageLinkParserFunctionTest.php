<?php

namespace SIL\Tests;

use SIL\InterlanguageLinkParserFunction;

/**
 * @covers \SIL\InterlanguageLinkParserFunction
 * @group semantic-interlanguage-links
 *
 * @license GNU GPL v2+
 * @since 1.0
 *
 * @author mwjames
 */
class InterlanguageLinkParserFunctionTest extends \PHPUnit_Framework_TestCase {

	private $languageLinkAnnotator;
	private $siteLanguageLinksParserOutputAppender;

	protected function setUp() {
		parent::setUp();

		$this->languageLinkAnnotator = $this->getMockBuilder( '\SIL\LanguageLinkAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$this->languageLinkAnnotator->expects( $this->any() )
			->method( 'canAddAnnotation' )
			->will( $this->returnValue( true ) );

		$this->siteLanguageLinksParserOutputAppender = $this->getMockBuilder( '\SIL\SiteLanguageLinksParserOutputAppender' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SIL\InterlanguageLinkParserFunction',
			new InterlanguageLinkParserFunction(
				$title,
				$this->languageLinkAnnotator,
				$this->siteLanguageLinksParserOutputAppender
			)
		);
	}

	public function testTryParseThatCausesErrorMessage() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new InterlanguageLinkParserFunction(
			$title,
			$this->languageLinkAnnotator,
			$this->siteLanguageLinksParserOutputAppender
		);

		$instance->setInterlanguageLinksHideState( true );

		$this->assertInternalType(
			'string',
			$instance->parse( 'en', 'Foo' )
		);

		$instance->setInterlanguageLinksHideState( false );

		$this->assertInternalType(
			'string',
			$instance->parse( '%42$', 'Foo' )
		);

		$this->assertContains(
			'-error',
			$instance->parse( '', 'Foo' )
		);

		$this->assertContains(
			'-error',
			$instance->parse( 'en', '{[[:Template:Foo]]' )
		);
	}

	public function testParseToCreateErrorMessageForKnownTarget() {

		$title = \Title::newFromText( __METHOD__ );

		$this->languageLinkAnnotator->expects( $this->never() )
			->method( 'addAnnotationForInterlanguageLink' );

		$this->siteLanguageLinksParserOutputAppender->expects( $this->once() )
			->method( 'getRedirectTargetFor' )
			->with(	$this->equalTo( \Title::newFromText( 'Foo' ) ) )
			->will( $this->returnValue( $title ) );

		$this->siteLanguageLinksParserOutputAppender->expects( $this->once() )
			->method( 'tryAddLanguageTargetLinksToOutput' )
			->with(
				$this->anything(),
				$this->equalTo( $title ) )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new InterlanguageLinkParserFunction(
			$title,
			$this->languageLinkAnnotator,
			$this->siteLanguageLinksParserOutputAppender
		);

		$instance->setInterlanguageLinksHideState( false );

		$this->assertContains(
			'-error',
			$instance->parse( 'en', 'Foo' )
		);
	}

	public function testMultipleParseCalls() {

		$title = \Title::newFromText( __METHOD__ );

		$this->siteLanguageLinksParserOutputAppender->expects( $this->any() )
			->method( 'getRedirectTargetFor' )
			->will( $this->returnValue( $title ) );

		$instance = new InterlanguageLinkParserFunction(
			$title,
			$this->languageLinkAnnotator,
			$this->siteLanguageLinksParserOutputAppender
		);

		$this->assertContains(
			'div class="sil-interlanguagelink"',
			$instance->parse( 'en', 'Foo' )
		);

		$this->assertContains(
			'div class="sil-interlanguagelink"',
			$instance->parse( 'en', 'Foo' )
		);
	}

	public function testMultipleParseCallsWithDifferentLanguagesTriggersErrorMessage() {

		$title = \Title::newFromText( __METHOD__ );

		$this->languageLinkAnnotator->expects( $this->any() )
			->method( 'hasDifferentLanguageAnnotation' )
			->will( $this->onConsecutiveCalls( false, true ) );

		$this->siteLanguageLinksParserOutputAppender->expects( $this->any() )
			->method( 'getRedirectTargetFor' )
			->will( $this->returnValue( $title ) );

		$instance = new InterlanguageLinkParserFunction(
			$title,
			$this->languageLinkAnnotator,
			$this->siteLanguageLinksParserOutputAppender
		);

		$instance->parse( 'en', 'Foo' );

		$this->assertContains(
			'-error',
			$instance->parse( 'fr', 'Foo' )
		);
	}

	public function testParse() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->languageLinkAnnotator->expects( $this->once() )
			->method( 'addAnnotationForInterlanguageLink' );

		$this->siteLanguageLinksParserOutputAppender->expects( $this->any() )
			->method( 'getRedirectTargetFor' )
			->will( $this->returnValue( $title ) );

		$instance = new InterlanguageLinkParserFunction(
			$title,
			$this->languageLinkAnnotator,
			$this->siteLanguageLinksParserOutputAppender
		);

		$instance->setInterlanguageLinksHideState( false );

		$this->assertContains(
			'div class="sil-interlanguagelink"',
			$instance->parse( 'en', 'Foo' )
		);
	}

	public function testRevisionMode() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->siteLanguageLinksParserOutputAppender->expects( $this->any() )
			->method( 'getRedirectTargetFor' )
			->will( $this->returnValue( $title ) );

		$instance = new InterlanguageLinkParserFunction(
			$title,
			$this->languageLinkAnnotator,
			$this->siteLanguageLinksParserOutputAppender
		);

		$instance->setRevisionModeState( true );

		$this->assertEmpty(
			$instance->parse( 'en', 'Foo' )
		);
	}

	public function testAnnotationModeIsDisabled() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$languageLinkAnnotator = $this->getMockBuilder( '\SIL\LanguageLinkAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$languageLinkAnnotator->expects( $this->once() )
			->method( 'canAddAnnotation' )
			->will( $this->returnValue( false ) );

		$instance = new InterlanguageLinkParserFunction(
			$title,
			$languageLinkAnnotator,
			$this->siteLanguageLinksParserOutputAppender
		);

		$this->assertEmpty(
			$instance->parse( 'en', 'Foo' )
		);
	}

}
