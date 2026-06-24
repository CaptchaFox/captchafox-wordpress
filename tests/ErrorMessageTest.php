<?php
/**
 * Tests for the failure message helper.
 *
 * @package captchafox
 */

namespace CaptchaFox\Tests;

use CaptchaFox\Helper\CaptchaFox;
use PHPUnit\Framework\TestCase;

class ErrorMessageTest extends TestCase {

	protected function setUp(): void {
		cf_test_reset();
	}

	protected function tearDown(): void {
		cf_test_reset();
	}

	public function test_returns_default_message() {
		$this->assertSame( 'Invalid Captcha', CaptchaFox::get_error_message() );
	}
}
