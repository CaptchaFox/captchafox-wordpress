<?php

namespace CaptchaFox;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Settings\Settings;

class Initializer {

	/**
	 * Setup
	 *
	 * @return void
	 */
	public function setup() {
		$settings = new Settings();
		$settings->setup();

		add_action( 'init', [ $this, 'init' ] );
		add_action( 'wp_head', [ CaptchaFox::class, 'load_head' ] );

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$this->load_enabled_plugins();
	}

	/**
	 * Load plugins that are enabled in the config
	 *
	 * @return void
	 */
	private function load_enabled_plugins() {
		$plugin_settings = get_option( 'captchafox_plugins' );
		$plugins = [
			[
				'group'   => 'wordpress',
				'action'  => 'login',
				'plugins' => [],
				'class'   => Plugins\Wordpress\Login::class,
			],
			[
				'group'   => 'wordpress',
				'action'  => 'register',
				'plugins' => [],
				'class'   => Plugins\Wordpress\Register::class,
			],
			[
				'group'   => 'wordpress',
				'action'  => 'comment',
				'plugins' => [],
				'class'   => Plugins\Wordpress\Comment::class,
			],
			[
				'group'   => 'wordpress',
				'action'  => 'lost_password',
				'plugins' => [],
				'class'   => Plugins\Wordpress\LostPassword::class,
			],
			[
				'group'   => 'wpforms',
				'action'  => 'form',
				'plugins' => [ 'wpforms-lite/wpforms.php', 'wpforms/wpforms.php' ],
				'class'   => Plugins\WPForms\Forms::class,
			],
			[
				'group'   => 'mailchimp',
				'action'  => 'form',
				'plugins' => [ 'mailchimp-for-wp/mailchimp-for-wp.php' ],
				'class'   => Plugins\Mailchimp\Forms::class,
			],
			[
				'group'   => 'woocommerce',
				'action'  => 'login',
				'plugins' => [ 'woocommerce/woocommerce.php' ],
				'class'   => Plugins\Woocommerce\Login::class,
			],
			[
				'group'   => 'woocommerce',
				'action'  => 'register',
				'plugins' => [ 'woocommerce/woocommerce.php' ],
				'class'   => Plugins\Woocommerce\Register::class,
			],
			[
				'group'   => 'woocommerce',
				'action'  => 'checkout',
				'plugins' => [ 'woocommerce/woocommerce.php' ],
				'class'   => Plugins\Woocommerce\Checkout::class,
			],
			[
				'group'   => 'woocommerce',
				'action'  => 'lost_password',
				'plugins' => [ 'woocommerce/woocommerce.php' ],
				'class'   => Plugins\Woocommerce\LostPassword::class,
			],
			[
				'group'   => 'forminator',
				'action'  => 'form',
				'plugins' => [ 'forminator/forminator.php' ],
				'class'   => Plugins\Forminator\Forms::class,
			],
			[
				'group'   => 'bbpress',
				'action'  => 'reply',
				'plugins' => [ 'bbpress/bbpress.php' ],
				'class'   => Plugins\BBPress\Reply::class,
			],
			[
				'group'   => 'bbpress',
				'action'  => 'new_topic',
				'plugins' => [ 'bbpress/bbpress.php' ],
				'class'   => Plugins\BBPress\NewTopic::class,
			],
			[
				'group'   => 'cf7',
				'action'  => 'form',
				'plugins' => [ 'contact-form-7/wp-contact-form-7.php' ],
				'class'   => Plugins\ContactForm7\Forms::class,
			],
			[
				'group'   => 'ninja-forms',
				'action'  => 'form',
				'plugins' => [ 'ninja-forms/ninja-forms.php' ],
				'class'   => Plugins\NinjaForms\Forms::class,
			],
		];

		foreach ( $plugins as $data ) {
			$files = $data['plugins'];
			$action = $data['action'];
			$group = $data['group'];
			$class = $data['class'];
			$plugin_setting = isset( $plugin_settings[ $group ] ) ? $plugin_settings[ $group ] : [];
			$plugin_enabled = in_array( $action, $plugin_setting, true );

			if ( $this->is_plugin_active( $files ) && $plugin_enabled ) {
				if ( isset( $class ) ) {
					new $class();
				}
			}
		}
	}

	/**
	 * Check if plugins are active
	 *
	 * @param  mixed $plugins Plugins to check.
	 * @return bool
	 */
	public function is_plugin_active( $plugins ) {
		if ( count( $plugins ) === 0 ) {
			return true;
		}

		foreach ( $plugins as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init() {
        load_plugin_textdomain( 'captchafox', false, constant( 'CAPTCHAFOX_BASE_URL' ) . '/languages' );
	}
}
