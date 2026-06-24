<?php
/**
 * Tests for the honeypot spam protection.
 *
 * @package captchafox
 */

namespace CaptchaFox\Tests;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Helper\Request;
use PHPUnit\Framework\TestCase;

class HoneypotTest extends TestCase {

	protected function setUp(): void {
		cf_test_reset();
	}

	protected function tearDown(): void {
		cf_test_reset();
	}

	/**
	 * Enable or disable the honeypot option.
	 *
	 * @param bool $enabled Whether the honeypot is enabled.
	 */
	private function set_enabled( $enabled ) {
		cf_test_set_option( 'captchafox_security', [ 'field_honeypot' => $enabled ? '1' : '' ] );
	}

	public function test_disabled_by_default() {
		$this->assertFalse( CaptchaFox::is_honeypot_enabled() );
	}

	public function test_passes_when_disabled_even_if_filled() {
		$this->set_enabled( false );
		$_POST[ CaptchaFox::HONEYPOT_NAME ] = 'i am a bot';

		$this->assertTrue( Request::passed_honeypot() );
	}

	public function test_passes_when_enabled_and_empty() {
		$this->set_enabled( true );
		$_POST[ CaptchaFox::HONEYPOT_NAME ] = '';

		$this->assertTrue( Request::passed_honeypot() );
	}

	public function test_passes_when_enabled_and_absent() {
		$this->set_enabled( true );

		$this->assertTrue( Request::passed_honeypot() );
	}

	public function test_fails_when_enabled_and_filled() {
		$this->set_enabled( true );
		$_POST[ CaptchaFox::HONEYPOT_NAME ] = 'i am a bot';

		$this->assertFalse( Request::passed_honeypot() );
	}

	public function test_html_empty_when_disabled() {
		$this->set_enabled( false );

		$this->assertSame( '', CaptchaFox::get_honeypot_html() );
	}

	public function test_html_contains_field_when_enabled() {
		$this->set_enabled( true );

		$html = CaptchaFox::get_honeypot_html();

		$this->assertStringContainsString( 'name="' . CaptchaFox::HONEYPOT_NAME . '"', $html );
		$this->assertStringContainsString( 'autocomplete="off"', $html );
		$this->assertStringContainsString( 'aria-hidden="true"', $html );
	}

	public function test_build_html_includes_honeypot_when_enabled() {
		cf_test_set_option( 'captchafox_options', [ 'field_sitekey' => 'sk_test' ] );
		cf_test_set_option( 'captchafox_security', [ 'field_honeypot' => '1' ] );
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';

		$html = CaptchaFox::build_html();

		$this->assertStringContainsString( 'class="captchafox"', $html );
		$this->assertStringContainsString( CaptchaFox::HONEYPOT_NAME, $html );
	}

	public function test_build_html_omits_honeypot_when_disabled() {
		cf_test_set_option( 'captchafox_options', [ 'field_sitekey' => 'sk_test' ] );
		cf_test_set_option( 'captchafox_security', [ 'field_honeypot' => '' ] );
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';

		$html = CaptchaFox::build_html();

		$this->assertStringContainsString( 'class="captchafox"', $html );
		$this->assertStringNotContainsString( CaptchaFox::HONEYPOT_NAME, $html );
	}

	public function test_build_html_escapes_data_attributes() {
		$html = CaptchaFox::build_html( [
			'sitekey'     => '"><script>alert(1)</script>',
			'theme'       => 'dark',
			'bad attr<>=' => 'ignored-name-is-sanitized',
		] );

		$this->assertStringContainsString( 'data-sitekey="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"', $html );
		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( 'bad attr', $html );
	}

	public function test_validate_fails_when_honeypot_filled() {
		$this->set_enabled( true );
		$_POST[ CaptchaFox::HONEYPOT_NAME ] = 'i am a bot';
		$_SERVER['REMOTE_ADDR']             = '203.0.113.10';

		$result = Request::validate( 'some-token' );

		$this->assertFalse( $result->success );
		$this->assertSame( [ 'honeypot' ], $result->errors );
	}
}
