<?php
/**
 * PHPUnit bootstrap.
 *
 * Provides lightweight stubs for the WordPress functions used by the code under
 * test so the units can be exercised without a full WordPress install.
 *
 * @package captchafox
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! defined( 'CAPTCHAFOX_BASE_URL' ) ) {
	define( 'CAPTCHAFOX_BASE_URL', 'http://example.test/wp-content/plugins/captchafox' );
}

if ( ! defined( 'PLUGIN_VERSION' ) ) {
	define( 'PLUGIN_VERSION', '0.0.0-test' );
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

/**
 * In-memory transient store controlled by the tests.
 *
 * @var array<string, mixed>
 */
$GLOBALS['cf_test_transients'] = [];

/**
 * Handles that have been enqueued during a test.
 *
 * @var string[]
 */
$GLOBALS['cf_test_enqueued'] = [];

/**
 * In-memory option store controlled by the tests.
 *
 * @var array<string, mixed>
 */
$GLOBALS['cf_test_options'] = [];

/**
 * Set an option value for the current test.
 *
 * @param string $name  Option name.
 * @param mixed  $value Option value.
 *
 * @return void
 */
function cf_test_set_option( $name, $value ) {
	$GLOBALS['cf_test_options'][ $name ] = $value;
}

/**
 * Reset the test state (options and superglobals).
 *
 * @return void
 */
function cf_test_reset() {
	$GLOBALS['cf_test_options']     = [];
	$GLOBALS['cf_test_transients']  = [];
	$GLOBALS['cf_test_enqueued']    = [];
	$GLOBALS['cf_test_logged_in']   = false;
	$GLOBALS['cf_test_user_roles']  = [];
	$_POST                          = [];
	unset( $_SERVER['REMOTE_ADDR'] );
}

/**
 * Set the current user's logged-in state and roles for the current test.
 *
 * @param bool     $logged_in Whether the user is logged in.
 * @param string[] $roles     The user's roles.
 *
 * @return void
 */
function cf_test_set_user( $logged_in, $roles = [] ) {
	$GLOBALS['cf_test_logged_in']  = $logged_in;
	$GLOBALS['cf_test_user_roles'] = $roles;
}

if ( ! function_exists( 'is_user_logged_in' ) ) {
	function is_user_logged_in() {
		return ! empty( $GLOBALS['cf_test_logged_in'] );
	}
}

if ( ! function_exists( 'wp_get_current_user' ) ) {
	function wp_get_current_user() {
		return (object) [ 'roles' => $GLOBALS['cf_test_user_roles'] ?? [] ];
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default_value = false ) {
		return $GLOBALS['cf_test_options'][ $name ] ?? $default_value;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $key ) {
		return $GLOBALS['cf_test_transients'][ $key ] ?? false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $key, $value, $expiration = 0 ) {
		$GLOBALS['cf_test_transients'][ $key ] = $value;

		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $key ) {
		unset( $GLOBALS['cf_test_transients'][ $key ] );

		return true;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( ...$args ) {
		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value, ...$args ) {
		return $value;
	}
}

if ( ! function_exists( 'wp_salt' ) ) {
	function wp_salt( $scheme = 'auth' ) {
		return 'captchafox-test-salt';
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $value ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}

		return trim( preg_replace( '/[\r\n\t ]+/', ' ', wp_strip_all_tags( $value ) ) );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $value ) {
		return trim( wp_kses_no_tags( $value ) );
	}
}

if ( ! function_exists( 'wp_kses_no_tags' ) ) {
	function wp_kses_no_tags( $value ) {
		return preg_replace( '/<[^>]*>/', '', (string) $value );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $value ) {
		return htmlspecialchars( (string) $value, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $value ) {
		return htmlspecialchars( (string) $value, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_textarea' ) ) {
	function esc_textarea( $value ) {
		return htmlspecialchars( (string) $value, ENT_QUOTES );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'wp_kses' ) ) {
	function wp_kses( $string, $allowed_html = [], $allowed_protocols = [] ) {
		return $string;
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $string ) {
		return $string;
	}
}

if ( ! function_exists( 'wp_kses_allowed_html' ) ) {
	function wp_kses_allowed_html( $context = '' ) {
		return [];
	}
}

if ( ! function_exists( 'wp_script_is' ) ) {
	function wp_script_is( $handle, $list = 'enqueued' ) {
		return false;
	}
}

if ( ! function_exists( 'wp_register_script' ) ) {
	function wp_register_script( ...$args ) {
		return true;
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( $handle = '', ...$args ) {
		$GLOBALS['cf_test_enqueued'][] = $handle;
	}
}

if ( ! function_exists( 'wp_localize_script' ) ) {
	function wp_localize_script( ...$args ) {
		return true;
	}
}

if ( ! function_exists( 'wp_register_style' ) ) {
	function wp_register_style( ...$args ) {
		return true;
	}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( ...$args ) {}
}

if ( ! function_exists( 'wp_add_inline_style' ) ) {
	function wp_add_inline_style( ...$args ) {
		return true;
	}
}
