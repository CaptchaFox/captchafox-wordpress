<?php

namespace CaptchaFox\Plugins\Wordpress;

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
		add_action( 'login_form', [ $this, 'render' ] );
        add_action( 'login_head', [ $this, 'load_head' ] );
        add_filter( 'wp_authenticate_user', [ $this, 'verify' ], 10, 2 );
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
     * Load the assets when the captcha is required.
     *
     * @return void
     */
    public function load_head() {
        if ( LoginProtection::is_required() ) {
            CaptchaFox::load_head();
        }
    }

    /**
     * Verify Form
     *
     * @param  mixed  $user User.
     * @param  string $password Password.
     * @return mixed
     */
    public function verify( $user, string $password ) {
        if ( ! LoginProtection::is_required() ) {
            return $user;
        }

        $verified = Request::validate_post();

        if ( ! $verified ) {
            return new WP_Error( 'invalid_captcha', __( 'Invalid Captcha', 'captchafox-for-forms' ), 400 );
        }

        return $user;
    }
}
