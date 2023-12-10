<?php

namespace CaptchaFox\Plugins\WPForms;

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
		add_action( 'wpforms_display_submit_before', [ CaptchaFox::class, 'get_html' ] );
        add_action( 'wpforms_process', [ $this, 'verify' ], 10, 3 );
    }

    /**
     * Verify form
     *
     * @param array $fields Form fields.
     * @param array $entry Entry.
     * @param array $form_data Form Data.
     *
     * @return void
     * @noinspection PhpUndefinedFunctionInspection
     * */
    public function verify( array $fields, array $entry, array $form_data ) {
        $verified = Request::validate_post();

        if ( ! $verified ) {
            wpforms()->get( 'process' )->errors[ $form_data['id'] ]['footer'] = __( 'Invalid Captcha', 'captchafox-for-forms' );
        }
    }
}
