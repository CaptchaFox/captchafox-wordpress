<?php

namespace CaptchaFox\Plugins\BBPress;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Helper\Request;
use CaptchaFox\Plugins\Plugin;

class NewTopic extends Plugin {

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
		add_action( 'bbp_theme_after_topic_form_content', [ CaptchaFox::class, 'get_html' ] );
        add_action( 'bbp_new_topic_pre_extras', [ $this, 'verify' ], 10, 3 );
    }

    /**
     * Verify form
     *
     * @return bool
     * @noinspection PhpUndefinedFunctionInspection
     */
    public function verify() {
		$verified = Request::validate_post();

        if ( ! $verified ) {
            bbp_add_error( 'captchafox_error', __( 'Invalid Captcha', 'captchafox' ) );

            return false;
        }

        return true;
    }
}
