<?php

namespace CaptchaFox\Plugins\ContactForm7;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Helper\Request;
use CaptchaFox\Plugins\Plugin;

use WPCF7_Submission;

class Forms extends Plugin {

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
		add_filter( 'wpcf7_form_elements', [ $this, 'render_captcha' ], 20, 1 );
		add_filter( 'wpcf7_spam', [ $this, 'verify' ], 9, 1 );
        add_action( 'wp_print_footer_scripts', [ $this, 'load_scripts' ], 9 );
    }

    /**
     * Render captcha
     *
     * @param  string $elements Elements.
     * @return string
     */
    public function render_captcha( string $elements ) {
		return preg_replace(
        '/(<input.*?type="submit")/',
		CaptchaFox::get_ob_html() . '$1',
		$elements
		);
	}

    /**
     * Verify Form
     *
     * @param  bool $spam Spam status.
     * @return bool
     */
    public function verify( bool $spam ) {
		if ( $spam ) {
			return $spam;
		}

		$submission = WPCF7_Submission::get_instance();
		$verified = Request::validate_post();

		if ( ! $verified ) {
			$spam = true;
			$submission->add_spam_log(array(
				'agent'  => 'captchafox',
				'reason' => __( 'Invalid Captcha', 'captchafox' ),
			));
		}

		return $spam;
	}

    /**
     * Load required scripts
     *
     * @return void
     */
    public function load_scripts() {
		wp_enqueue_script(
            'captchafox-cf7',
            constant( 'CAPTCHAFOX_BASE_URL' ) . '/assets/js/contactForm7.js',
            [],
            '1.0',
            true
        );
    }
}
