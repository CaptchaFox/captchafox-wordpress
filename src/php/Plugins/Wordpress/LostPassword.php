<?php

namespace CaptchaFox\Plugins\Wordpress;

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
        add_action( 'lostpassword_form', [ CaptchaFox::class, 'get_html' ] );
        add_action( 'lostpassword_post', [ $this, 'verify' ], 10, 1 );
    }

    /**
     * Verify Form
     *
     * @param  mixed $error Error.
     * @return mixed
     */
    public function verify( $error ) {
        $verified = Request::validate_post();

        if ( ! $verified ) {
            $error = new WP_Error( 'invalid_captcha', __( 'Invalid Captcha', 'captchafox-for-forms' ), 400 );
        }

        return $error;
    }
}
