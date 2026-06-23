<?php
/**
 * Tests for the minimum submission time (time trap) spam protection.
 *
 * @package captchafox
 */

namespace CaptchaFox\Tests;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Helper\Request;
use PHPUnit\Framework\TestCase;

class MinTimeTest extends TestCase {

	protected function setUp(): void {
		cf_test_reset();
	}

	protected function tearDown(): void {
		cf_test_reset();
	}

	/**
	 * Set the minimum submission time in seconds.
	 *
	 * @param int $seconds Seconds.
	 */
	private function set_min_time( $seconds ) {
		cf_test_set_option( 'captchafox_security', [ 'field_min_time' => $seconds ] );
	}

	/**
	 * Build a valid signed token for a given timestamp.
	 *
	 * @param int $time Unix timestamp.
	 *
	 * @return string
	 */
	private function token_for( $time ) {
		return $time . '.' . hash_hmac( 'sha256', (string) $time, wp_salt( 'auth' ) );
	}

	public function test_disabled_by_default() {
		$this->assertSame( 0, CaptchaFox::get_min_time() );
	}

	public function test_html_empty_when_disabled() {
		$this->set_min_time( 0 );

		$this->assertSame( '', CaptchaFox::get_timestamp_html() );
	}

	public function test_html_contains_signed_field_when_enabled() {
		$this->set_min_time( 3 );

		$html = CaptchaFox::get_timestamp_html();

		$this->assertStringContainsString( 'name="' . CaptchaFox::TIMESTAMP_NAME . '"', $html );
		$this->assertStringContainsString( 'type="hidden"', $html );
	}

	public function test_passes_when_disabled_even_if_fast() {
		$this->set_min_time( 0 );
		$_POST[ CaptchaFox::TIMESTAMP_NAME ] = $this->token_for( time() );

		$this->assertTrue( Request::passed_min_time() );
	}

	public function test_passes_when_token_absent() {
		$this->set_min_time( 5 );

		$this->assertTrue( Request::passed_min_time() );
	}

	public function test_fails_when_submitted_too_fast() {
		$this->set_min_time( 5 );
		$_POST[ CaptchaFox::TIMESTAMP_NAME ] = $this->token_for( time() );

		$this->assertFalse( Request::passed_min_time() );
	}

	public function test_passes_when_enough_time_elapsed() {
		$this->set_min_time( 5 );
		$_POST[ CaptchaFox::TIMESTAMP_NAME ] = $this->token_for( time() - 10 );

		$this->assertTrue( Request::passed_min_time() );
	}

	public function test_fails_when_signature_forged() {
		$this->set_min_time( 5 );
		// Old enough to pass the elapsed check, but the signature is invalid.
		$_POST[ CaptchaFox::TIMESTAMP_NAME ] = ( time() - 100 ) . '.deadbeef';

		$this->assertFalse( Request::passed_min_time() );
	}

	public function test_fails_when_time_tampered() {
		$this->set_min_time( 5 );
		// Reuse a valid signature but swap the timestamp to fake elapsed time.
		$signed = $this->token_for( time() );
		$parts  = explode( '.', $signed, 2 );
		$_POST[ CaptchaFox::TIMESTAMP_NAME ] = ( time() - 100 ) . '.' . $parts[1];

		$this->assertFalse( Request::passed_min_time() );
	}

	public function test_verify_timestamp_rejects_malformed_token() {
		$this->assertNull( CaptchaFox::verify_timestamp( 'not-a-token' ) );
		$this->assertNull( CaptchaFox::verify_timestamp( '' ) );
	}

	public function test_validate_fails_when_submitted_too_fast() {
		$this->set_min_time( 5 );
		$_POST[ CaptchaFox::TIMESTAMP_NAME ] = $this->token_for( time() );
		$_SERVER['REMOTE_ADDR']              = '203.0.113.10';

		$result = Request::validate( 'some-token' );

		$this->assertFalse( $result->success );
		$this->assertSame( [ 'min_time' ], $result->errors );
	}

	public function test_build_html_includes_timestamp_when_enabled() {
		cf_test_set_option( 'captchafox_options', [ 'field_sitekey' => 'sk_test' ] );
		cf_test_set_option( 'captchafox_security', [ 'field_min_time' => 3 ] );
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';

		$html = CaptchaFox::build_html();

		$this->assertStringContainsString( CaptchaFox::TIMESTAMP_NAME, $html );
	}
}
