<?php
/**
 * Tests for the login protection (failed attempt threshold).
 *
 * @package captchafox
 */

namespace CaptchaFox\Tests;

use CaptchaFox\Helper\LoginProtection;
use PHPUnit\Framework\TestCase;

class LoginProtectionTest extends TestCase {

	protected function setUp(): void {
		cf_test_reset();
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
	}

	protected function tearDown(): void {
		cf_test_reset();
	}

	/**
	 * Set the login attempt limit.
	 *
	 * @param int      $limit    Limit.
	 * @param int|null $interval Interval in minutes.
	 */
	private function set_limit( $limit, $interval = null ) {
		$option = [ 'field_login_limit' => $limit ];

		if ( null !== $interval ) {
			$option['field_login_interval'] = $interval;
		}

		cf_test_set_option( 'captchafox_security', $option );
	}

	public function test_limit_defaults_to_zero() {
		$this->assertSame( 0, LoginProtection::get_limit() );
	}

	public function test_required_by_default_when_no_limit() {
		$this->set_limit( 0 );

		$this->assertTrue( LoginProtection::is_required() );
	}

	public function test_not_required_below_limit() {
		$this->set_limit( 3 );

		$this->assertFalse( LoginProtection::is_required() );
	}

	public function test_required_once_limit_reached() {
		$this->set_limit( 3 );

		LoginProtection::record_failure();
		LoginProtection::record_failure();
		$this->assertFalse( LoginProtection::is_required() );

		LoginProtection::record_failure();
		$this->assertTrue( LoginProtection::is_required() );
	}

	public function test_attempts_increment() {
		$this->assertSame( 0, LoginProtection::get_attempts() );

		LoginProtection::record_failure();
		LoginProtection::record_failure();

		$this->assertSame( 2, LoginProtection::get_attempts() );
	}

	public function test_clear_failures_resets_counter() {
		$this->set_limit( 2 );
		LoginProtection::record_failure();
		LoginProtection::record_failure();
		$this->assertTrue( LoginProtection::is_required() );

		LoginProtection::clear_failures();

		$this->assertSame( 0, LoginProtection::get_attempts() );
		$this->assertFalse( LoginProtection::is_required() );
	}

	public function test_negative_limit_is_treated_as_always() {
		$this->set_limit( -5 );

		$this->assertSame( 0, LoginProtection::get_limit() );
		$this->assertTrue( LoginProtection::is_required() );
	}

	public function test_attempts_are_tracked_per_ip() {
		$this->set_limit( 1 );

		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
		LoginProtection::record_failure();
		$this->assertTrue( LoginProtection::is_required() );

		$_SERVER['REMOTE_ADDR'] = '198.51.100.4';
		$this->assertSame( 0, LoginProtection::get_attempts() );
		$this->assertFalse( LoginProtection::is_required() );
	}

	public function test_no_attempts_recorded_without_ip() {
		unset( $_SERVER['REMOTE_ADDR'] );
		$this->set_limit( 1 );

		LoginProtection::record_failure();

		$this->assertSame( 0, LoginProtection::get_attempts() );
	}

	public function test_interval_defaults_to_fifteen_minutes() {
		$this->assertSame( 15 * 60, LoginProtection::get_interval() );
	}

	public function test_interval_reads_configured_minutes() {
		$this->set_limit( 1, 30 );

		$this->assertSame( 30 * 60, LoginProtection::get_interval() );
	}

	public function test_interval_minimum_is_one_minute() {
		$this->set_limit( 1, 0 );

		$this->assertSame( 60, LoginProtection::get_interval() );
	}
}
