<?php

namespace CaptchaFox\Plugins\Forminator;

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
		add_filter( 'forminator_render_button_markup', [ $this, 'render_captcha' ], 10, 2 );
        add_filter( 'forminator_cform_form_is_submittable', [ $this, 'verify' ], 10, 3 );
    }

    /**
     * Render captcha
     *
     * @param  string $html Html.
     * @param  string $button Button.
     * @return string
     */
    public function render_captcha( string $html, string $button ): string {
        return str_replace(
            '<button ',
            CaptchaFox::get_ob_html() .
                '<button ',
            (string) $html
        );
    }

    /**
     * Verify Form
     *
     * @param  bool  $can_show Can Show.
     * @param  int   $id ID.
     * @param  array $form_settings Settings.
     * @return mixed
     */
    public function verify( $can_show, int $id, array $form_settings ) {
        $verified = Request::validate_post();

        if ( ! $verified ) {
            return [
                'can_submit' => false,
                'error'      => __( 'Invalid Captcha', 'captchafox' ),
            ];
        }

        return $can_show;
    }
}
