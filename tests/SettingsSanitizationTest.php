<?php
/**
 * Tests for settings sanitization.
 *
 * @package captchafox
 */

namespace CaptchaFox\Tests;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Settings\General;
use CaptchaFox\Settings\Security;
use PHPUnit\Framework\TestCase;

class SettingsSanitizationTest extends TestCase {

	protected function setUp(): void {
		cf_test_reset();
	}

	protected function tearDown(): void {
		cf_test_reset();
	}

	public function test_general_options_are_sanitized_and_clamped_to_known_values() {
		$settings = new General();

		$sanitized = $settings->sanitize_options( [
			'field_sitekey'      => '<b>site</b>',
			'field_secret'       => " secret\nkey ",
			'field_display_mode' => 'bogus',
			'field_theme'        => 'dark',
			'field_lang'         => 'bogus',
			'field_loading'      => 'interaction',
		] );

		$this->assertSame( 'site', $sanitized['field_sitekey'] );
		$this->assertSame( 'secret key', $sanitized['field_secret'] );
		$this->assertSame( 'inline', $sanitized['field_display_mode'] );
		$this->assertSame( 'dark', $sanitized['field_theme'] );
		$this->assertSame( 'auto', $sanitized['field_lang'] );
		$this->assertSame( 'interaction', $sanitized['field_loading'] );
	}

	public function test_security_options_drop_invalid_ip_entries() {
		$settings = new Security();

		$sanitized = $settings->sanitize_options( [
			'field_honeypot'       => '1',
			'field_min_time'       => '-10',
			'field_allowlist'      => "203.0.113.10\nnot-an-ip\n192.168.0.0/24\n192.168.0.0/33",
			'field_denylist'       => "2001:db8::/32\n2001:db8::/129",
			'field_login_limit'    => '-1',
			'field_login_interval' => '0',
		] );

		$this->assertSame( '1', $sanitized['field_honeypot'] );
		$this->assertSame( 0, $sanitized['field_min_time'] );
		$this->assertSame( "203.0.113.10\n192.168.0.0/24", $sanitized['field_allowlist'] );
		$this->assertSame( '2001:db8::/32', $sanitized['field_denylist'] );
		$this->assertSame( 0, $sanitized['field_login_limit'] );
		$this->assertSame( 1, $sanitized['field_login_interval'] );
		$this->assertNotEmpty( $GLOBALS['cf_test_settings_errors'] );
	}

	public function test_invalid_cidr_entries_do_not_match() {
		cf_test_set_option( 'captchafox_security', [ 'field_allowlist' => "0.0.0.0/33\n2001:db8::/129" ] );
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';

		$this->assertFalse( CaptchaFox::is_ip_allowed() );
	}
}
