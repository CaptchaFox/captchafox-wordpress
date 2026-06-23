<?php

namespace CaptchaFox\Plugins\Woocommerce;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Helper\LoginProtection;
use CaptchaFox\Helper\Request;
use CaptchaFox\Plugins\Plugin;
use WP_Error;

class Login extends Plugin {

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
        add_action( 'woocommerce_login_form', [ $this, 'render' ] );
        add_filter( 'woocommerce_process_login_errors', [ $this, 'verify' ] );
    }

    /**
     * Render the widget when the captcha is required.
     *
     * @return void
     */
    public function render() {
        if ( LoginProtection::is_required() ) {
            CaptchaFox::get_html();
        }
    }

    /**
     * Verify Form
     *
     * @param  mixed $validation_error Validation Error.
     * @return mixed
     */
    public function verify( $validation_error ) {
        if ( ! LoginProtection::is_required() ) {
            return $validation_error;
        }

        $verified = Request::validate_post();

        if ( ! $verified ) {
            $validation_error = new WP_Error( 'invalid_captcha', __( 'Invalid Captcha', 'captchafox-for-forms' ), 400 );
        }

        return $validation_error;
    }
}
