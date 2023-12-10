<?php

namespace CaptchaFox\Plugins\Mailchimp;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Helper\Request;
use CaptchaFox\Plugins\Plugin;

use MC4WP_Form;
use MC4WP_Form_Element;

class Forms extends Plugin {

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
		add_filter( 'mc4wp_form_content', [ $this, 'render_captcha' ], 20, 3 );
        add_filter( 'mc4wp_form_errors', [ $this, 'verify' ], 10, 2 );
        add_filter( 'mc4wp_form_messages', [ $this, 'add_error_messages' ], 10, 2 );
    }

    /**
     * Render captcha
     *
     * @param  string             $content Content.
     * @param  MC4WP_Form         $form Form.
     * @param  MC4WP_Form_Element $element Form Element.
     * @return string
     */
    public function render_captcha( string $content, MC4WP_Form $form, MC4WP_Form_Element $element ): string {
        return preg_replace(
            '/(<input.*?type="submit")/',
		CaptchaFox::get_ob_html() . '$1',
		$content
		);
    }

    /**
     * Add error messages
     *
     * @param  mixed      $messages Messages.
     * @param  MC4WP_Form $form Form.
     * @return mixed
     */
    public function add_error_messages( $messages, MC4WP_Form $form ) {
        $messages = (array) $messages;

        $messages['invalid'] = [
            'type' => 'error',
            'text' => __( 'Invalid Captcha', 'captchafox-for-forms' ),
        ];

        return $messages;
    }

    /**
     * Verify Form
     *
     * @param  mixed      $errors Errors.
     * @param  MC4WP_Form $form Form.
     * @return mixed
     */
    public function verify( $errors, MC4WP_Form $form ) {
        $verified = Request::validate_post();

        if ( ! $verified ) {
            $errors     = (array) $errors;
            $errors[]   = 'invalid';
        }

        return $errors;
    }
}
