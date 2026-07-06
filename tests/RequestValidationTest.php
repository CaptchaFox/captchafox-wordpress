<?php
/**
 * Tests for CaptchaFox API request validation.
 *
 * @package captchafox
 */

namespace CaptchaFox\Tests;

use CaptchaFox\Helper\Request;
use CaptchaFox\Helper\Analytics;
use PHPUnit\Framework\TestCase;

class RequestValidationTest extends TestCase {

	protected function setUp(): void {
		cf_test_reset();
		cf_test_set_option( 'captchafox_security', [ 'field_statistics' => '1' ] );
		cf_test_set_option( 'captchafox_options', [ 'field_secret' => 'secret' ] );
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
	}

	protected function tearDown(): void {
		cf_test_reset();
	}

	public function test_validate_returns_success_object_for_api_pass() {
		$GLOBALS['cf_test_remote_post_response'] = [
			'body'     => '{"success":true}',
			'response' => [ 'code' => 200 ],
		];

		$result = Request::validate( 'token', 'test-form' );

		$this->assertTrue( $result->success );
		$this->assertSame( [], $result->errors );
		$this->assertSame( 1, Analytics::get_stats()['passed'] );
	}

	public function test_validate_returns_api_error_object_for_wp_error() {
		$GLOBALS['cf_test_remote_post_response'] = new \WP_Error( 'timeout', 'Timed out' );

		$result = Request::validate( 'token' );

		$this->assertFalse( $result->success );
		$this->assertSame( [ 'api_error' ], $result->errors );
		$this->assertSame( 1, Analytics::get_stats()['reasons']['api_error'] );
	}

	public function test_validate_returns_api_error_object_for_http_failure() {
		$GLOBALS['cf_test_remote_post_response'] = [
			'body'     => '{"success":false}',
			'response' => [ 'code' => 500 ],
		];

		$result = Request::validate( 'token' );

		$this->assertFalse( $result->success );
		$this->assertSame( [ 'api_error' ], $result->errors );
	}

	public function test_validate_returns_api_error_object_for_invalid_json() {
		$GLOBALS['cf_test_remote_post_response'] = [
			'body'     => 'not-json',
			'response' => [ 'code' => 200 ],
		];

		$result = Request::validate( 'token' );

		$this->assertFalse( $result->success );
		$this->assertSame( [ 'api_error' ], $result->errors );
	}

	public function test_validate_returns_captcha_errors_for_api_rejection() {
		$GLOBALS['cf_test_remote_post_response'] = [
			'body'     => '{"success":false,"error-codes":["invalid-input-response"]}',
			'response' => [ 'code' => 200 ],
		];

		$result = Request::validate( 'token' );

		$this->assertFalse( $result->success );
		$this->assertSame( [ 'invalid-input-response' ], $result->errors );
		$this->assertSame( 1, Analytics::get_stats()['reasons']['captcha'] );
	}
}
