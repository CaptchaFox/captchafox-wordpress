<?php

namespace CaptchaFox\Plugins\Woocommerce;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Helper\Request;
use CaptchaFox\Plugins\Plugin;
use WP_Error;

class LostPassword extends Plugin {

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
		add_action( 'woocommerce_lostpassword_form', [ CaptchaFox::class, 'get_html' ] );
        add_action( 'lostpassword_post', [ $this, 'verify' ] );
    }

    /**
     * Verify Form
     *
     * @param  mixed $error Validation Error.
     * @return mixed
     */
    public function verify( $error ) {
        $verified = Request::validate_post();

        if ( ! $verified ) {
            $error = new WP_Error( 'invalid_captcha', __( 'Invalid Captcha', 'captchafox' ), 400 );
        }

        return $error;
    }
}
