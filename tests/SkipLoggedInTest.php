<?php
/**
 * Tests for skipping the captcha for logged-in users / roles.
 *
 * @package captchafox
 */

namespace CaptchaFox\Tests;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Helper\Request;
use PHPUnit\Framework\TestCase;

class SkipLoggedInTest extends TestCase {

	protected function setUp(): void {
		cf_test_reset();
	}

	protected function tearDown(): void {
		cf_test_reset();
	}

	/**
	 * Configure the exemption option.
	 *
	 * @param bool $enabled Whether the exemption is enabled.
	 */
	private function set_option( $enabled ) {
		cf_test_set_option( 'captchafox_security', [
			'field_skip_logged_in' => $enabled ? '1' : '',
		] );
	}

	public function test_disabled_by_default() {
		cf_test_set_user( true );

		$this->assertFalse( CaptchaFox::is_user_exempt() );
	}

	public function test_not_exempt_when_logged_out() {
		$this->set_option( true );
		cf_test_set_user( false );

		$this->assertFalse( CaptchaFox::is_user_exempt() );
	}

	public function test_exempt_when_logged_in() {
		$this->set_option( true );
		cf_test_set_user( true );

		$this->assertTrue( CaptchaFox::is_user_exempt() );
	}

	public function test_should_skip_when_exempt() {
		$this->set_option( true );
		cf_test_set_user( true );

		$this->assertTrue( CaptchaFox::should_skip_captcha() );
	}

	public function test_denylist_wins_over_user_exemption() {
		cf_test_set_option( 'captchafox_security', [
			'field_skip_logged_in' => '1',
			'field_denylist'       => '203.0.113.10',
		] );
		cf_test_set_user( true );
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';

		$this->assertTrue( CaptchaFox::is_user_exempt() );
		$this->assertFalse( CaptchaFox::should_skip_captcha() );
	}

	public function test_validate_bypasses_when_exempt() {
		$this->set_option( true );
		cf_test_set_user( true );

		$result = Request::validate( '' );

		$this->assertTrue( $result->success );
		$this->assertSame( [], $result->errors );
	}

	public function test_build_html_empty_when_exempt() {
		cf_test_set_option( 'captchafox_options', [ 'field_sitekey' => 'sk_test' ] );
		cf_test_set_option( 'captchafox_security', [ 'field_skip_logged_in' => '1' ] );
		cf_test_set_user( true );

		$this->assertSame( '', CaptchaFox::build_html() );
	}
}
