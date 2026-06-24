<?php
/**
 * Tests for the IP allowlist behaviour.
 *
 * @package captchafox
 */

namespace CaptchaFox\Tests;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Helper\Request;
use PHPUnit\Framework\TestCase;

class IpAllowlistTest extends TestCase {

	protected function setUp(): void {
		cf_test_reset();
	}

	protected function tearDown(): void {
		cf_test_reset();
	}

	/**
	 * Configure the allowlist and the visitor IP.
	 *
	 * @param string $allowlist Newline separated allowlist.
	 * @param string $ip        Visitor IP.
	 */
	private function configure( $allowlist, $ip ) {
		cf_test_set_option( 'captchafox_security', [ 'field_allowlist' => $allowlist ] );
		$_SERVER['REMOTE_ADDR'] = $ip;
	}

	public function test_empty_allowlist_never_allows() {
		$this->configure( '', '203.0.113.10' );

		$this->assertFalse( CaptchaFox::is_ip_allowed() );
	}

	public function test_exact_ipv4_match() {
		$this->configure( "203.0.113.10\n198.51.100.4", '203.0.113.10' );

		$this->assertTrue( CaptchaFox::is_ip_allowed() );
	}

	public function test_exact_ipv4_no_match() {
		$this->configure( "203.0.113.10\n198.51.100.4", '203.0.113.11' );

		$this->assertFalse( CaptchaFox::is_ip_allowed() );
	}

	public function test_ipv4_cidr_match() {
		$this->configure( '192.168.0.0/24', '192.168.0.55' );

		$this->assertTrue( CaptchaFox::is_ip_allowed() );
	}

	public function test_ipv4_cidr_no_match() {
		$this->configure( '192.168.0.0/24', '192.168.1.55' );

		$this->assertFalse( CaptchaFox::is_ip_allowed() );
	}

	public function test_ipv4_cidr_boundary() {
		$this->configure( '10.0.0.0/8', '10.255.255.255' );

		$this->assertTrue( CaptchaFox::is_ip_allowed() );
	}

	public function test_ipv6_exact_match() {
		$this->configure( '2001:db8::1', '2001:db8::1' );

		$this->assertTrue( CaptchaFox::is_ip_allowed() );
	}

	public function test_ipv6_cidr_match() {
		$this->configure( '2001:db8::/32', '2001:db8:1234::abcd' );

		$this->assertTrue( CaptchaFox::is_ip_allowed() );
	}

	public function test_ipv6_cidr_no_match() {
		$this->configure( '2001:db8::/32', '2001:dead::1' );

		$this->assertFalse( CaptchaFox::is_ip_allowed() );
	}

	public function test_mixed_family_does_not_match() {
		$this->configure( '192.168.0.0/24', '2001:db8::1' );

		$this->assertFalse( CaptchaFox::is_ip_allowed() );
	}

	public function test_invalid_remote_addr_returns_false() {
		$this->configure( '203.0.113.10', 'not-an-ip' );

		$this->assertFalse( CaptchaFox::is_ip_allowed() );
	}

	public function test_get_allowlist_trims_and_ignores_blanks() {
		cf_test_set_option( 'captchafox_security', [ 'field_allowlist' => "  203.0.113.10  \n\n   \n198.51.100.4\n" ] );

		$this->assertSame(
			[ '203.0.113.10', '198.51.100.4' ],
			CaptchaFox::get_allowlist()
		);
	}

	public function test_validate_bypasses_api_when_allowed() {
		$this->configure( '203.0.113.10', '203.0.113.10' );

		$result = Request::validate( '' );

		$this->assertTrue( $result->success );
		$this->assertSame( [], $result->errors );
	}

	public function test_validate_post_bypasses_when_allowed() {
		$this->configure( '203.0.113.10', '203.0.113.10' );

		$this->assertTrue( Request::validate_post() );
	}
}
