<?php
/**
 * Tests for the spam analytics recorder.
 *
 * @package captchafox
 */

namespace CaptchaFox\Tests;

use CaptchaFox\Helper\Request;
use CaptchaFox\Helper\Analytics;
use PHPUnit\Framework\TestCase;

class AnalyticsTest extends TestCase {

	protected function setUp(): void {
		cf_test_reset();
		cf_test_set_option( 'captchafox_security', [ 'field_statistics' => '1' ] );
	}

	protected function tearDown(): void {
		cf_test_reset();
	}

	public function test_disabled_by_default_records_nothing() {
		cf_test_set_option( 'captchafox_security', [] );

		Analytics::record_pass();
		Analytics::record_failure( 'honeypot' );

		$stats = Analytics::get_stats();
		$this->assertSame( 0, $stats['passed'] );
		$this->assertSame( 0, $stats['failed'] );
		$this->assertSame( [], Analytics::get_events() );
	}

	public function test_defaults_are_zeroed() {
		$stats = Analytics::get_stats();

		$this->assertSame( 0, $stats['passed'] );
		$this->assertSame( 0, $stats['failed'] );
		$this->assertSame(
			[ 'ip_denied', 'honeypot', 'min_time', 'captcha', 'api_error' ],
			array_keys( $stats['reasons'] )
		);
		$this->assertSame( [], Analytics::get_events() );
	}

	public function test_record_pass_increments_passed_only() {
		Analytics::record_pass();

		$stats = Analytics::get_stats();
		$this->assertSame( 1, $stats['passed'] );
		$this->assertSame( 0, $stats['failed'] );
		$this->assertSame( [], Analytics::get_events() );
	}

	public function test_record_failure_counts_and_logs_hashed_event() {
		$_SERVER['REMOTE_ADDR']     = '203.0.113.10';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Test Browser)';

		Analytics::record_failure( 'honeypot' );

		$stats = Analytics::get_stats();
		$this->assertSame( 1, $stats['failed'] );
		$this->assertSame( 1, $stats['reasons']['honeypot'] );

		$events = Analytics::get_events();
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
		Analytics::record_failure( 'honeypot' );

		$event = Analytics::get_events()[0];
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

		Analytics::record_failure( 'honeypot' );

		$event = Analytics::get_events()[0];
		$this->assertSame( '203.0.113.10', $event['ip'] );
		$this->assertSame( 'Mozilla/5.0 (Test Browser)', $event['user_agent'] );
	}

	public function test_source_is_recorded() {
		Analytics::record_failure( 'honeypot', 'contact-form-7' );

		$this->assertSame( 'contact-form-7', Analytics::get_events()[0]['source'] );
	}

	public function test_anonymization_flags_reflect_storage() {
		$_SERVER['REMOTE_ADDR']     = '203.0.113.10';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Test Browser)';

		Analytics::record_failure( 'honeypot' );

		$event = Analytics::get_events()[0];
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

		Analytics::record_failure( 'honeypot' );

		$event = Analytics::get_events()[0];
		$this->assertFalse( $event['ip_anonymized'] );
		$this->assertFalse( $event['ua_anonymized'] );
	}

	public function test_form_id_detected_from_post() {
		$_POST['_wpcf7'] = '42';

		Analytics::record_failure( 'honeypot' );

		$this->assertSame( '42', Analytics::get_events()[0]['form_id'] );
	}

	public function test_form_id_filter_overrides_detection() {
		cf_test_set_filter( 'capf_event_form_id', 'contact-form' );

		Analytics::record_failure( 'honeypot' );

		$this->assertSame( 'contact-form', Analytics::get_events()[0]['form_id'] );
	}

	public function test_unknown_reason_falls_back_to_captcha() {
		Analytics::record_failure( 'bogus' );

		$stats = Analytics::get_stats();
		$this->assertSame( 1, $stats['reasons']['captcha'] );
	}

	public function test_get_events_respects_limit() {
		for ( $i = 0; $i < 10; $i++ ) {
			Analytics::record_failure( 'min_time' );
		}

		$this->assertCount( 10, Analytics::get_events() );
		$this->assertCount( 3, Analytics::get_events( 3 ) );
	}

	public function test_passes_are_logged_but_not_in_events() {
		Analytics::record_pass();
		Analytics::record_pass();
		Analytics::record_failure( 'honeypot' );

		$stats = Analytics::get_stats();
		$this->assertSame( 2, $stats['passed'] );
		$this->assertSame( 1, $stats['failed'] );
		$this->assertCount( 1, Analytics::get_events() );
	}

	public function test_get_events_can_include_passes() {
		Analytics::record_pass();
		Analytics::record_failure( 'honeypot' );

		$all = Analytics::get_events( Analytics::RECENT_LIMIT, true );
		$this->assertCount( 2, $all );
		// Newest first: the failure was recorded last.
		$this->assertFalse( $all[0]['success'] );
		$this->assertTrue( $all[1]['success'] );
	}

	public function test_events_are_newest_first() {
		Analytics::record_failure( 'honeypot' );
		Analytics::record_failure( 'min_time' );

		$events = Analytics::get_events();
		$this->assertSame( 'min_time', $events[0]['reason'] );
		$this->assertSame( 'honeypot', $events[1]['reason'] );
	}

	public function test_reset_clears_everything() {
		Analytics::record_failure( 'honeypot' );
		Analytics::record_pass();

		Analytics::reset();

		$stats = Analytics::get_stats();
		$this->assertSame( 0, $stats['passed'] );
		$this->assertSame( 0, $stats['failed'] );
		$this->assertSame( [], Analytics::get_events() );
	}

	public function test_create_table_does_not_mark_version_when_table_is_missing() {
		global $wpdb;

		$wpdb->table_exists = false;

		$this->assertFalse( Analytics::create_table() );
		$this->assertNotSame( Analytics::DB_VERSION, get_option( Analytics::DB_VERSION_OPTION ) );
	}

	public function test_missing_table_returns_empty_stats_and_ignores_inserts() {
		global $wpdb;

		$wpdb->table_exists = false;

		Analytics::record_failure( 'honeypot' );

		$stats = Analytics::get_stats();
		$this->assertSame( 0, $stats['passed'] );
		$this->assertSame( 0, $stats['failed'] );
		$this->assertSame( [], Analytics::get_events() );
		$this->assertSame( [], $wpdb->rows );
	}

	public function test_validate_records_denied_ip_event() {
		cf_test_set_option( 'captchafox_security', [
			'field_statistics' => '1',
			'field_denylist'   => '203.0.113.10',
		] );
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';

		Request::validate( 'token' );

		$stats = Analytics::get_stats();
		$this->assertSame( 1, $stats['reasons']['ip_denied'] );
		$this->assertSame( 'ip_denied', Analytics::get_events()[0]['reason'] );
	}

	public function test_prune_old_events_keeps_default_fourteen_day_window() {
		global $wpdb;

		$wpdb->rows = [
			[
				'id'            => 1,
				'success'       => 0,
				'reason'        => 'captcha',
				'source'        => '',
				'form_id'       => '',
				'ip'            => '',
				'ip_anonymized' => 1,
				'user_agent'    => '',
				'ua_anonymized' => 1,
				'date_gmt'      => gmdate( 'Y-m-d H:i:s', time() - ( 15 * DAY_IN_SECONDS ) ),
			],
			[
				'id'            => 2,
				'success'       => 0,
				'reason'        => 'honeypot',
				'source'        => '',
				'form_id'       => '',
				'ip'            => '',
				'ip_anonymized' => 1,
				'user_agent'    => '',
				'ua_anonymized' => 1,
				'date_gmt'      => gmdate( 'Y-m-d H:i:s', time() - ( 13 * DAY_IN_SECONDS ) ),
			],
		];

		Analytics::prune_old_events();

		$this->assertCount( 1, $wpdb->rows );
		$this->assertSame( 'honeypot', $wpdb->rows[0]['reason'] );
	}
}
