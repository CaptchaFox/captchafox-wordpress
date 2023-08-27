<?php

namespace CaptchaFox\Plugins\Wordpress;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Helper\Request;
use CaptchaFox\Plugins\Plugin;
use WP_Error;

class Comment extends Plugin {

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
        add_filter( 'comment_form_submit_field', [ $this, 'render' ], 10, 2 );
        add_filter( 'pre_comment_approved', [ $this, 'verify' ], 20, 2 );
    }

    /**
     * Render captcha
     *
     * @param  mixed $submit_field Submit field.
     * @param  mixed $args Arguments.
     * @return string
     */
    public function render( $submit_field, $args ) {
        return CaptchaFox::get_ob_html() . $submit_field;
    }

    /**
     * Verify form
     *
     * @param  mixed $approved approved.
     * @param  array $commentdata commentdata.
     * @return mixed
     */
    public function verify( $approved, array $commentdata ) {
        if ( is_admin() ) {
            return $approved;
        }

        $verified = Request::validate_post();

        if ( ! $verified ) {
            $approved = new WP_Error( 'invalid_captcha', __( 'Invalid Captcha', 'captchafox' ), 400 );
        }

        return $approved;
    }
}
