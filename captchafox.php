<?php
/**
 * CaptchaFox WordPress Plugin
 *
 * @package captchafox
 *
 * Plugin Name:           CaptchaFox for Forms
 * Description:           CaptchaFox plugin for WordPress Forms
 * Version:               1.1.0
 * Requires at least:     5.0
 * Requires PHP:          7.0
 * Author:                CaptchaFox
 * Author URI:            https://captchafox.com/
 * Text Domain:           captchafox
 * Domain Path:           /languages/
 *
 * WC requires at least:  3.0
 * WC tested up to:       8.0
 */

use CaptchaFox\Initializer;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'CAPTCHAFOX_BASE_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

const CAPTCHAFOX_BASE_FILE = __FILE__;

require 'vendor/autoload.php';

/**
 * Initialize plugin
 *
 * @return Initializer
 */
function captchafox(): Initializer {
	static $captchafox;

	if ( ! $captchafox ) {
		$captchafox = new Initializer();
	}

	return $captchafox;
}

captchafox()->setup();
