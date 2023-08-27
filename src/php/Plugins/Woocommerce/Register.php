<?php

namespace CaptchaFox\Plugins\Woocommerce;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Helper\Request;
use CaptchaFox\Plugins\Plugin;
use WP_Error;

class Register extends Plugin {

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
		add_action( 'woocommerce_register_form', [ CaptchaFox::class, 'get_html' ] );
        add_filter( 'woocommerce_process_registration_errors', [ $this, 'verify' ] );
    }

    /**
     * Verify Form
     *
     * @param  mixed $validation_error Validation Error.
     * @return mixed
     */
    public function verify( $validation_error ) {
        $verified = Request::validate_post();

        if ( ! $verified ) {
            $validation_error = new WP_Error( 'invalid_captcha', __( 'Invalid Captcha', 'captchafox' ), 400 );
        }

        return $validation_error;
    }
}
