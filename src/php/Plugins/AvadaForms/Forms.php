<?php

namespace CaptchaFox\Plugins\AvadaForms;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Helper\Request;
use CaptchaFox\Plugins\Plugin;

class Forms extends Plugin {

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
		add_filter( 'fusion_form_demo_mode', [ $this, 'verify' ] );
		add_action( 'fusion_element_button_content', [ $this, 'render_captcha' ], 10, 2 );
    }

    /**
     * Render captcha
     *
	 * @param string $html Content HTML.
	 * @param array  $args Args.
     * @return string
     */
    public function render_captcha( string $html, array $args ) {
        if ( false === strpos( $html, '<button type="submit"' ) ) {
			return $html;
		}

		return CaptchaFox::build_html() . $html;
	}

    /**
     * Verify form. Utilizes a demo filter in Avada, that allows us to hook into the submission process.
     *
     * @param array $demo_mode Demo mode.
     *
     * @return mixed
     * */
    public function verify( $demo_mode ) {

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$form_data = isset( $_POST['formData'] ) ?
            filter_var( wp_unslash( $_POST['formData'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS ) :
            [];

		$form_data = wp_parse_args( str_replace( '&amp;', '&', $form_data ) );
		$response = $form_data['cf-captcha-response'] ?? '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing

		$verification = Request::validate( $response );

		if ( $verification->success ) {
			return $demo_mode;
		}

		wp_die(wp_json_encode(
            [
                'status' => 'error',
                'info'   => [ 'captchafox' => __( 'Invalid Captcha', 'captchafox-for-forms' ) ],
			]));
    }
}
