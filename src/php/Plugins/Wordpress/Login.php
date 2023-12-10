<?php

namespace CaptchaFox\Plugins\Wordpress;

use CaptchaFox\Helper\CaptchaFox;
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
		add_action( 'login_form', [ CaptchaFox::class, 'get_html' ] );
        add_action( 'login_head', [ CaptchaFox::class, 'load_head' ] );
        add_filter( 'wp_authenticate_user', [ $this, 'verify' ], 10, 2 );
    }

    /**
     * Verify Form
     *
     * @param  mixed  $user User.
     * @param  string $password Password.
     * @return mixed
     */
    public function verify( $user, string $password ) {
        $verified = Request::validate_post();

        if ( ! $verified ) {
            return new WP_Error( 'invalid_captcha', __( 'Invalid Captcha', 'captchafox-for-forms' ), 400 );
        }

        return $user;
    }
}
