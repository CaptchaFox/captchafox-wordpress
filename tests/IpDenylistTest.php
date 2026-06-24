<?php
/**
 * Tests for the IP denylist behaviour.
 *
 * @package captchafox
 */

namespace CaptchaFox\Tests;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Helper\Request;
use PHPUnit\Framework\TestCase;

class IpDenylistTest extends TestCase {

	protected function setUp(): void {
		cf_test_reset();
	}

	protected function tearDown(): void {
		cf_test_reset();
	}

	/**
	 * Configure the denylist and the visitor IP.
	 *
	 * @param string $denylist Newline separated denylist.
	 * @param string $ip       Visitor IP.
	 */
	private function configure( $denylist, $ip ) {
		cf_test_set_option( 'captchafox_security', [ 'field_denylist' => $denylist ] );
		$_SERVER['REMOTE_ADDR'] = $ip;
	}

	public function test_empty_denylist_never_denies() {
		$this->configure( '', '203.0.113.10' );

		$this->assertFalse( CaptchaFox::is_ip_denied() );
	}

	public function test_exact_ipv4_match() {
		$this->configure( "203.0.113.10\n198.51.100.4", '203.0.113.10' );

		$this->assertTrue( CaptchaFox::is_ip_denied() );
	}

	public function test_ipv4_cidr_match() {
		$this->configure( '10.0.0.0/8', '10.1.2.3' );

		$this->assertTrue( CaptchaFox::is_ip_denied() );
	}

	public function test_ipv6_cidr_match() {
		$this->configure( '2001:db8::/32', '2001:db8:1234::abcd' );

		$this->assertTrue( CaptchaFox::is_ip_denied() );
	}

	public function test_no_match() {
		$this->configure( '203.0.113.10', '198.51.100.4' );

		$this->assertFalse( CaptchaFox::is_ip_denied() );
	}

	public function test_get_denylist_trims_and_ignores_blanks() {
		cf_test_set_option( 'captchafox_security', [ 'field_denylist' => "  203.0.113.10  \n\n   \n198.51.100.4\n" ] );

		$this->assertSame(
			[ '203.0.113.10', '198.51.100.4' ],
			CaptchaFox::get_denylist()
		);
	}

	public function test_denylist_wins_over_allowlist() {
		cf_test_set_option( 'captchafox_security', [
			'field_allowlist' => '203.0.113.10',
			'field_denylist'  => '203.0.113.10',
		] );
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';

		$this->assertTrue( CaptchaFox::is_ip_denied() );
		$this->assertFalse( CaptchaFox::is_ip_allowed() );
	}

	public function test_validate_blocks_denied_ip() {
		$this->configure( '203.0.113.10', '203.0.113.10' );

		$result = Request::validate( 'some-token' );

		$this->assertFalse( $result->success );
		$this->assertSame( [ 'ip_denied' ], $result->errors );
	}

	public function test_validate_post_blocks_denied_ip() {
		$this->configure( '203.0.113.10', '203.0.113.10' );
		$_POST['cf-captcha-response'] = 'some-token';

		$this->assertFalse( Request::validate_post() );
	}
}
