<?php

namespace CaptchaFox\Plugins\OtterBlocks;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Plugins\Plugin;

class Forms extends Plugin {

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
		add_filter( 'option_themeisle_google_captcha_api_site_key', [ $this, 'replace_sitekey' ], 10, 2 );
		add_filter( 'default_option_themeisle_google_captcha_api_site_key', [ $this, 'replace_sitekey' ], 99, 3 );
		add_filter( 'option_themeisle_google_captcha_api_secret_key', [ $this, 'replace_secret' ], 10, 2 );
		add_filter( 'default_option_themeisle_google_captcha_api_secret_key', [ $this, 'replace_secret' ], 99, 3 );
		add_filter( 'otter_blocks_recaptcha_verify_url', [ $this, 'replace_siteverify_url' ] );
		add_filter( 'otter_blocks_recaptcha_api_url', [ $this, 'replace_api_url' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'load_scripts' ] );
    }

	/**
	 * Replace sitekey.
	 *
	 * @return string
	 */
	public function replace_sitekey(): string {
		return CaptchaFox::get_sitekey();
	}

	/**
	 * Replace secret.
	 *
	 * @return string
	 */
	public function replace_secret(): string {
		return CaptchaFox::get_secret();
	}

	/**
	 * Replace siteverify URL.
	 *
	 * @return string
	 */
	public function replace_siteverify_url(): string {
		return 'https://api.captchafox.com/siteverify';
	}

	/**
	 * Replace JavaScript API URL.
	 *
	 * @return string
	 */
	public function replace_api_url(): string {
		return 'https://cdn.captchafox.com/api.js?render=explicit&onload=captchaFoxLoadOtter';
	}

    /**
     * Load required scripts
     *
     * @return void
     */
    public function load_scripts() {
		wp_enqueue_script(
            'captchafox-otter',
            constant( 'CAPTCHAFOX_BASE_URL' ) . '/assets/js/otter.js',
            [],
            PLUGIN_VERSION,
            true
        );
    }
}
