<?php
/**
 * Tests for the spam statistics recorder.
 *
 * @package captchafox
 */

namespace CaptchaFox\Tests;

use CaptchaFox\Helper\Request;
use CaptchaFox\Helper\Statistics;
use PHPUnit\Framework\TestCase;

class StatisticsTest extends TestCase {

	protected function setUp(): void {
		cf_test_reset();
		cf_test_set_option( 'captchafox_security', [ 'field_statistics' => '1' ] );
	}

	protected function tearDown(): void {
		cf_test_reset();
	}

	public function test_disabled_by_default_records_nothing() {
		cf_test_set_option( 'captchafox_security', [] );

		Statistics::record_pass();
		Statistics::record_failure( 'honeypot' );

		$stats = Statistics::get_stats();
		$this->assertSame( 0, $stats['passed'] );
		$this->assertSame( 0, $stats['failed'] );
		$this->assertSame( [], Statistics::get_events() );
	}

	public function test_defaults_are_zeroed() {
		$stats = Statistics::get_stats();

		$this->assertSame( 0, $stats['passed'] );
		$this->assertSame( 0, $stats['failed'] );
		$this->assertSame(
			[ 'ip_denied', 'honeypot', 'min_time', 'captcha' ],
			array_keys( $stats['reasons'] )
		);
		$this->assertSame( [], Statistics::get_events() );
	}

	public function test_record_pass_increments_passed_only() {
		Statistics::record_pass();

		$stats = Statistics::get_stats();
		$this->assertSame( 1, $stats['passed'] );
		$this->assertSame( 0, $stats['failed'] );
		$this->assertSame( [], Statistics::get_events() );
	}

	public function test_record_failure_counts_and_logs_hashed_event() {
		$_SERVER['REMOTE_ADDR']     = '203.0.113.10';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Test Browser)';

		Statistics::record_failure( 'honeypot' );

		$stats = Statistics::get_stats();
		$this->assertSame( 1, $stats['failed'] );
		$this->assertSame( 1, $stats['reasons']['honeypot'] );

		$events = Statistics::get_events();
		$this->assertCount( 1, $events );
		$this->assertSame( 'honeypot', $events[0]['reason'] );

		// The IP is stored as a hash, never the raw address.
		$this->assertNotSame( '', $events[0]['ip'] );
		$this->assertStringNotContainsString( '203.0.113', $events[0]['ip'] );
		$this->assertSame( md5( '203.0.113.10' ), $events[0]['ip'] );

		// The user agent is stored as a hash, never the raw string.
		$this->assertNotSame( '', $events[0]['user_agent'] );
		$this->assertStringNotContainsString( 'Mozilla', $events[0]['user_agent'] );
	}

	public function test_missing_ip_and_user_agent_are_empty() {
		Statistics::record_failure( 'honeypot' );

		$event = Statistics::get_events()[0];
		$this->assertSame( '', $event['ip'] );
		$this->assertSame( '', $event['user_agent'] );
	}

	public function test_raw_ip_and_user_agent_stored_when_collection_enabled() {
		cf_test_set_option( 'captchafox_security', [
			'field_statistics'         => '1',
			'field_collect_ip'         => '1',
			'field_collect_user_agent' => '1',
		] );
		$_SERVER['REMOTE_ADDR']     = '203.0.113.10';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Test Browser)';

		Statistics::record_failure( 'honeypot' );

		$event = Statistics::get_events()[0];
		$this->assertSame( '203.0.113.10', $event['ip'] );
		$this->assertSame( 'Mozilla/5.0 (Test Browser)', $event['user_agent'] );
	}

	public function test_source_is_recorded() {
		Statistics::record_failure( 'honeypot', 'contact-form-7' );

		$this->assertSame( 'contact-form-7', Statistics::get_events()[0]['source'] );
	}

	public function test_anonymization_flags_reflect_storage() {
		$_SERVER['REMOTE_ADDR']     = '203.0.113.10';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Test Browser)';

		Statistics::record_failure( 'honeypot' );

		$event = Statistics::get_events()[0];
		$this->assertTrue( $event['ip_anonymized'] );
		$this->assertTrue( $event['ua_anonymized'] );
	}

	public function test_collected_values_are_not_flagged_anonymized() {
		cf_test_set_option( 'captchafox_security', [
			'field_statistics'         => '1',
			'field_collect_ip'         => '1',
			'field_collect_user_agent' => '1',
		] );
		$_SERVER['REMOTE_ADDR']     = '203.0.113.10';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Test Browser)';

		Statistics::record_failure( 'honeypot' );

		$event = Statistics::get_events()[0];
		$this->assertFalse( $event['ip_anonymized'] );
		$this->assertFalse( $event['ua_anonymized'] );
	}

	public function test_form_id_detected_from_post() {
		$_POST['_wpcf7'] = '42';

		Statistics::record_failure( 'honeypot' );

		$this->assertSame( '42', Statistics::get_events()[0]['form_id'] );
	}

	public function test_form_id_filter_overrides_detection() {
		cf_test_set_filter( 'capf_event_form_id', 'contact-form' );

		Statistics::record_failure( 'honeypot' );

		$this->assertSame( 'contact-form', Statistics::get_events()[0]['form_id'] );
	}

	public function test_unknown_reason_falls_back_to_captcha() {
		Statistics::record_failure( 'bogus' );

		$stats = Statistics::get_stats();
		$this->assertSame( 1, $stats['reasons']['captcha'] );
	}

	public function test_get_events_respects_limit() {
		for ( $i = 0; $i < 10; $i++ ) {
			Statistics::record_failure( 'min_time' );
		}

		$this->assertCount( 10, Statistics::get_events() );
		$this->assertCount( 3, Statistics::get_events( 3 ) );
	}

	public function test_passes_are_logged_but_not_in_events() {
		Statistics::record_pass();
		Statistics::record_pass();
		Statistics::record_failure( 'honeypot' );

		$stats = Statistics::get_stats();
		$this->assertSame( 2, $stats['passed'] );
		$this->assertSame( 1, $stats['failed'] );
		$this->assertCount( 1, Statistics::get_events() );
	}

	public function test_get_events_can_include_passes() {
		Statistics::record_pass();
		Statistics::record_failure( 'honeypot' );

		$all = Statistics::get_events( Statistics::RECENT_LIMIT, true );
		$this->assertCount( 2, $all );
		// Newest first: the failure was recorded last.
		$this->assertFalse( $all[0]['success'] );
		$this->assertTrue( $all[1]['success'] );
	}

	public function test_events_are_newest_first() {
		Statistics::record_failure( 'honeypot' );
		Statistics::record_failure( 'min_time' );

		$events = Statistics::get_events();
		$this->assertSame( 'min_time', $events[0]['reason'] );
		$this->assertSame( 'honeypot', $events[1]['reason'] );
	}

	public function test_reset_clears_everything() {
		Statistics::record_failure( 'honeypot' );
		Statistics::record_pass();

		Statistics::reset();

		$stats = Statistics::get_stats();
		$this->assertSame( 0, $stats['passed'] );
		$this->assertSame( 0, $stats['failed'] );
		$this->assertSame( [], Statistics::get_events() );
	}

	public function test_validate_records_denied_ip_event() {
		cf_test_set_option( 'captchafox_security', [
			'field_statistics' => '1',
			'field_denylist'   => '203.0.113.10',
		] );
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';

		Request::validate( 'token' );

		$stats = Statistics::get_stats();
		$this->assertSame( 1, $stats['reasons']['ip_denied'] );
		$this->assertSame( 'ip_denied', Statistics::get_events()[0]['reason'] );
	}
}
