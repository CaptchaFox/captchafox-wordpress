<?php
/**
 * Integration-style tests for form plugin verification hooks.
 *
 * @package captchafox
 */

namespace CaptchaFox\Tests;

use CaptchaFox\Plugins\ContactForm7\Forms as ContactForm7Forms;
use CaptchaFox\Plugins\GravityForms\Forms as GravityForms;
use CaptchaFox\Plugins\Woocommerce\Login as WoocommerceLogin;
use CaptchaFox\Plugins\WPForms\Forms as WPForms;
use PHPUnit\Framework\TestCase;
use WP_Error;

class PluginIntegrationTest extends TestCase {

	protected function setUp(): void {
		cf_test_reset();
		$GLOBALS['cf_test_wpforms'] = new \CF_Test_WPForms();
		$GLOBALS['cf_test_wpcf7_submission'] = new \WPCF7_Submission();
		$GLOBALS['cf_test_gf_forms'] = [];
		cf_test_set_option( 'captchafox_options', [ 'field_secret' => 'secret' ] );
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
		$_POST['cf-captcha-response'] = 'token';
	}

	protected function tearDown(): void {
		cf_test_reset();
		unset( $GLOBALS['cf_test_wpforms'], $GLOBALS['cf_test_wpcf7_submission'], $GLOBALS['cf_test_gf_forms'] );
	}

	private function reject_captcha() {
		$GLOBALS['cf_test_remote_post_response'] = [
			'body'     => '{"success":false,"error-codes":["invalid-input-response"]}',
			'response' => [ 'code' => 200 ],
		];
	}

	public function test_wpforms_verify_adds_footer_error_on_failed_captcha() {
		$this->reject_captcha();
		$form = new WPForms();

		$form->verify( [], [], [ 'id' => 123 ] );

		$this->assertSame( 'Invalid Captcha', wpforms()->get( 'process' )->errors[123]['footer'] );
	}

	public function test_wpforms_verify_leaves_errors_empty_on_success() {
		$form = new WPForms();

		$form->verify( [], [], [ 'id' => 123 ] );

		$this->assertSame( [], wpforms()->get( 'process' )->errors );
	}

	public function test_contact_form_7_marks_spam_and_logs_reason_on_failed_captcha() {
		$this->reject_captcha();
		$form = new ContactForm7Forms();

		$this->assertTrue( $form->verify( false ) );
		$this->assertSame( 'captchafox', \WPCF7_Submission::get_instance()->spam_log[0]['agent'] );
		$this->assertSame( 'Invalid Captcha', \WPCF7_Submission::get_instance()->spam_log[0]['reason'] );
	}

	public function test_contact_form_7_skips_verification_when_already_spam() {
		$this->reject_captcha();
		$form = new ContactForm7Forms();

		$this->assertTrue( $form->verify( true ) );
		$this->assertSame( [], \WPCF7_Submission::get_instance()->spam_log );
	}

	public function test_woocommerce_login_returns_wp_error_on_failed_captcha() {
		$this->reject_captcha();
		$form = new WoocommerceLogin();

		$result = $form->verify( true );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_captcha', $result->code );
	}

	public function test_gravity_forms_marks_result_invalid_and_adds_error_on_failed_captcha() {
		$this->reject_captcha();
		$_POST['gform_submit'] = '42';
		$GLOBALS['cf_test_gf_forms'][42] = [
			'fields' => [ (object) [ 'type' => 'captchafox' ] ],
		];

		$form = new GravityForms();
		$result = $form->verify( [
			'is_valid' => true,
			'form'     => [],
		], 'form-submit' );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( '1', $result['form']['validationSummary'] );

		$errors = $form->form_validation_errors( [], [] );

		$this->assertSame( 'CaptchaFox', $errors[0]['field_label'] );
		$this->assertSame( 'Invalid Captcha', $errors[0]['message'] );
	}

	public function test_gravity_forms_skips_when_form_has_no_captcha_field() {
		$this->reject_captcha();
		$_POST['gform_submit'] = '42';
		$GLOBALS['cf_test_gf_forms'][42] = [
			'fields' => [ (object) [ 'type' => 'text' ] ],
		];

		$form = new GravityForms();
		$result = $form->verify( [
			'is_valid' => true,
			'form'     => [],
		], 'form-submit' );

		$this->assertTrue( $result['is_valid'] );
		$this->assertSame( [], $form->form_validation_errors( [], [] ) );
	}

}
