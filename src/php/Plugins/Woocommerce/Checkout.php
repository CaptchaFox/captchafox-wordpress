<?php

namespace CaptchaFox\Plugins\Woocommerce;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Helper\Request;
use CaptchaFox\Plugins\Plugin;

class Checkout extends Plugin {

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
		add_action( 'woocommerce_review_order_before_submit', [ CaptchaFox::class, 'get_html' ] );
        add_action( 'woocommerce_checkout_process', [ $this, 'verify' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'load_scripts' ] );
    }

    /**
     * Verify form
     *
     * @return void
     */
    public function verify() {
		$verified = Request::validate_post();

        if ( ! $verified ) {
            wc_add_notice( __( 'Invalid Captcha', 'captchafox' ), 'error' );
        }
    }

    /**
     * Load required scripts
     *
     * @return void
     */
    public function load_scripts() {
		wp_enqueue_script(
            'captchafox-woocommerce',
            constant( 'CAPTCHAFOX_BASE_URL' ) . '/assets/js/woocommerce.js',
            [ 'jquery' ],
            '1.0',
            true
        );
    }
}
