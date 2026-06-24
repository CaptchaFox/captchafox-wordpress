<?php
/**
 * Tests for the delayed (on-interaction) script loading.
 *
 * @package captchafox
 */

namespace CaptchaFox\Tests;

use CaptchaFox\Helper\CaptchaFox;
use PHPUnit\Framework\TestCase;

class DelayLoadingTest extends TestCase {

	protected function setUp(): void {
		cf_test_reset();
	}

	protected function tearDown(): void {
		cf_test_reset();
	}

	/**
	 * Set the loading mode.
	 *
	 * @param string $mode Loading mode.
	 */
	private function set_mode( $mode ) {
		cf_test_set_option( 'captchafox_options', [ 'field_loading' => $mode ] );
	}

	public function test_not_delayed_by_default() {
		$this->assertFalse( CaptchaFox::is_delayed() );
	}

	public function test_delayed_when_set_to_interaction() {
		$this->set_mode( 'interaction' );

		$this->assertTrue( CaptchaFox::is_delayed() );
	}

	public function test_not_delayed_when_set_to_instant() {
		$this->set_mode( 'instant' );

		$this->assertFalse( CaptchaFox::is_delayed() );
	}

	public function test_instant_mode_enqueues_api() {
		$this->set_mode( 'instant' );

		CaptchaFox::enqueue_assets();

		$this->assertContains( 'captchafox-form', $GLOBALS['cf_test_enqueued'] );
		$this->assertContains( 'captchafox', $GLOBALS['cf_test_enqueued'] );
	}

	public function test_delayed_mode_does_not_enqueue_api() {
		$this->set_mode( 'interaction' );

		CaptchaFox::enqueue_assets();

		$this->assertContains( 'captchafox-form', $GLOBALS['cf_test_enqueued'] );
		$this->assertNotContains( 'captchafox', $GLOBALS['cf_test_enqueued'] );
	}

	public function test_force_api_enqueues_api_even_when_delayed() {
		$this->set_mode( 'interaction' );

		CaptchaFox::enqueue_assets( true );

		$this->assertContains( 'captchafox', $GLOBALS['cf_test_enqueued'] );
	}
}
