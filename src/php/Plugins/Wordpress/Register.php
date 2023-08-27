<?php

namespace CaptchaFox\Plugins\Wordpress;

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
		add_action( 'register_form', [ CaptchaFox::class, 'get_html' ] );
        add_action( 'register_head', [ CaptchaFox::class, 'load_head' ] );
        add_filter( 'registration_errors', [ $this, 'verify' ], 10, 3 );
    }

    /**
     * Verify Form
     *
     * @param  mixed  $errors Errors.
     * @param  string $sanitized_user_login User Login.
     * @param  string $user_email User Email.
     * @return mixed
     */
    public function verify( $errors, string $sanitized_user_login, string $user_email ) {
        $verified = Request::validate_post();

        if ( ! $verified ) {
            $errors = new WP_Error( 'invalid_captcha', __( 'Invalid Captcha', 'captchafox' ), 400 );
        }

        return $errors;
    }
}
