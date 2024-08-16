<?php

namespace CaptchaFox\Plugins\GravityForms;

use GFFormsModel;
use CaptchaFox\Helper\Request;
use CaptchaFox\Plugins\Plugin;

class Forms extends Plugin {

	/**
	 * Message.
	 *
	 * @var string|null
	 */
	protected $message;

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
		add_action( 'gform_loaded', [ $this, 'register_field' ], 10, 0 );
		add_filter( 'gform_validation', [ $this, 'verify' ], 10, 2 );
		add_filter( 'gform_form_validation_errors', [ $this, 'form_validation_errors' ], 10, 2 );
		add_action( 'wp_print_footer_scripts', [ $this, 'load_scripts' ], 8 );
	}

	/**
	 * Register CaptchaFox Field
	 *
	 * @return void
	 */
	public function register_field() {
		new CaptchaFoxField();
	}

	/**
	 * Verify form
	 *
	 * @param array|mixed $validation_result Result.
	 * @param string      $context           Context.
	 *
	 * @return array|mixed
	 */
	public function verify( $validation_result, string $context ) {
		if ( ! $this->can_verify() ) {
			return $validation_result;
		}

		$verified = Request::validate_post();

		if ( ! $verified ) {
			$this->message = __( 'Invalid Captcha', 'captchafox-for-forms' );

			$validation_result['is_valid']                  = false;
			$validation_result['form']['validationSummary'] = '1';
        }

		return $validation_result;
	}

	/**
	 * Filter errors
	 *
	 * @param array|mixed $errors Errors.
	 * @param array       $form   Form.
	 *
	 * @return array|mixed
	 */
	public function form_validation_errors( $errors, array $form ) {
		if ( empty( $this->message ) ) {
			return $errors;
		}

		$error['field_selector'] = '';
		$error['field_label']    = 'CaptchaFox';
		$error['message']        = $this->message;

		$errors[] = $error;

		return $errors;
	}

	/**
	 * Check if verification can be started
	 *
	 * @return bool
	 */
	private function can_verify(): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['gform_submit'] ) ) {
			return false;
		}

		$form_id = (int) $_POST['gform_submit'];
		$target_page_name = "gform_target_page_number_$form_id";

		if ( isset( $_POST[ $target_page_name ] ) ) {
			$source_page_name = "gform_source_page_number_$form_id";

			$target_page = (int) $_POST[ $target_page_name ];
			$source_page = isset( $_POST[ $source_page_name ] ) ? (int) $_POST[ $source_page_name ] : 0;

			$form_meta = (array) GFFormsModel::get_form_meta( $form_id );

			$last_page = 0 !== (int) $_POST[ $target_page_name ] &&
			$target_page !== $source_page &&
			isset(
				$form_meta['pagination']['pages'][ $target_page - 1 ],
				$form_meta['pagination']['pages'][ $source_page - 1 ]
			);

			if ( $last_page ) {
				return false;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( $this->is_captcha_on_form( $form_id ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if form contains captcha
	 *
	 * @param int $form_id Form id.
	 *
	 * @return bool
	 */
	private function is_captcha_on_form( int $form_id ): bool {
		$form = GFFormsModel::get_form_meta( $form_id );

		if ( ! $form ) {
			return false;
		}

		$captcha_types = [ 'captcha', 'captchafox' ];

		foreach ( $form['fields'] as $field ) {
			if ( in_array( $field->type, $captcha_types, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Load required scripts
	 *
	 * @return void
	 */
    public function load_scripts() {
		wp_enqueue_script(
			'gravity-forms',
			CAPTCHAFOX_BASE_URL . '/assets/js/gravityForms.js',
			[ 'jquery' ],
			PLUGIN_VERSION,
			true
		);
	}
}
