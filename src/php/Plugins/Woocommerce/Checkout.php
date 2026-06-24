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
		add_action( 'woocommerce_review_order_before_submit', [ $this, 'render' ] );
        add_action( 'woocommerce_checkout_process', [ $this, 'verify' ] );
    }

    /**
     * Render captcha and load the required scripts
     *
     * @return void
     */
    public function render() {
		$this->load_scripts();
        CaptchaFox::get_html();
    }

    /**
     * Verify form
     *
     * @return void
     */
    public function verify() {
		$verified = Request::validate_post( 'woocommerce-checkout' );

        if ( ! $verified ) {
            wc_add_notice( CaptchaFox::get_error_message(), 'error' );
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
            [ 'jquery', 'captchafox-form' ],
            PLUGIN_VERSION,
            true
        );
    }
}
