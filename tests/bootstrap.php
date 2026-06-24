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

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
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
 * Remote request responses controlled by the tests.
 *
 * @var mixed
 */
$GLOBALS['cf_test_remote_post_response'] = [
	'body'     => '{"success":true}',
	'response' => [ 'code' => 200 ],
];
$GLOBALS['cf_test_remote_head_response'] = [
	'body'     => '',
	'response' => [ 'code' => 200 ],
];
$GLOBALS['cf_test_remote_head_calls'] = 0;

/**
 * Settings errors captured during tests.
 *
 * @var array<int, array<string, mixed>>
 */
$GLOBALS['cf_test_settings_errors'] = [];

/**
 * Cron events scheduled during tests.
 *
 * @var array<string, int>
 */
$GLOBALS['cf_test_scheduled'] = [];

/**
 * In-memory option store controlled by the tests.
 *
 * @var array<string, mixed>
 */
$GLOBALS['cf_test_options'] = [];

/**
 * Filter overrides controlled by the tests.
 *
 * @var array<string, mixed>
 */
$GLOBALS['cf_test_filters'] = [];

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}

/**
 * Minimal in-memory stand-in for $wpdb covering the queries the Statistics
 * recorder issues. It stores rows in an array and answers the handful of
 * aggregate/select queries by inspecting the SQL.
 */
class CF_Test_WPDB {

	/**
	 * Table prefix.
	 *
	 * @var string
	 */
	public $prefix = 'wp_';

	/**
	 * Stored rows.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public $rows = [];

	/**
	 * Next auto-increment id.
	 *
	 * @var int
	 */
	private $next_id = 1;

	public function get_charset_collate() {
		return '';
	}

	public function prepare( $query, ...$args ) {
		foreach ( $args as $arg ) {
			$replacement = is_int( $arg ) ? (string) $arg : "'" . $arg . "'";
			$query       = preg_replace( '/%[ds]/', $replacement, $query, 1 );
		}

		return $query;
	}

	public function insert( $table, $data, $format = null ) {
		$data['id']   = $this->next_id++;
		$this->rows[] = $data;

		return 1;
	}

	public function get_var( $query ) {
		if ( false !== strpos( $query, 'success = 1' ) ) {
			return (string) count( $this->where_success( 1 ) );
		}

		if ( false !== strpos( $query, 'success = 0' ) ) {
			return (string) count( $this->where_success( 0 ) );
		}

		return (string) count( $this->rows );
	}

	public function get_results( $query, $output = OBJECT ) {
		if ( false !== strpos( $query, 'GROUP BY reason' ) ) {
			$counts = [];
			foreach ( $this->where_success( 0 ) as $row ) {
				$counts[ $row['reason'] ] = ( $counts[ $row['reason'] ] ?? 0 ) + 1;
			}

			$results = [];
			foreach ( $counts as $reason => $total ) {
				$results[] = [
					'reason' => $reason,
					'total'  => (string) $total,
				];
			}

			return $results;
		}

		$rows = ( false !== strpos( $query, 'success = 0' ) ) ? $this->where_success( 0 ) : $this->rows;
		$rows = array_reverse( $rows );

		if ( preg_match( '/LIMIT (\d+)/', $query, $m ) ) {
			$rows = array_slice( $rows, 0, (int) $m[1] );
		}

		return array_map(
			static function ( $row ) {
				return [
					'date_gmt'      => $row['date_gmt'],
					'success'       => $row['success'],
					'reason'        => $row['reason'],
					'source'        => $row['source'] ?? '',
					'form_id'       => $row['form_id'] ?? '',
					'ip'            => $row['ip'],
					'ip_anonymized' => $row['ip_anonymized'] ?? 1,
					'user_agent'    => $row['user_agent'] ?? '',
					'ua_anonymized' => $row['ua_anonymized'] ?? 1,
				];
			},
			$rows
		);
	}

	public function get_col( $query ) {
		return [];
	}

	public function query( $query ) {
		if ( false !== strpos( $query, 'DELETE' ) && preg_match( "/date_gmt < '([^']+)'/", $query, $m ) ) {
			$cutoff     = $m[1];
			$this->rows = array_values(
				array_filter(
					$this->rows,
					static function ( $row ) use ( $cutoff ) {
						return $row['date_gmt'] >= $cutoff;
					}
				)
			);
			return true;
		}

		if ( false !== strpos( $query, 'DELETE' ) ) {
			$this->rows = [];
		}

		return true;
	}

	private function where_success( $value ) {
		return array_values(
			array_filter(
				$this->rows,
				static function ( $row ) use ( $value ) {
					return (int) $row['success'] === $value;
				}
			)
		);
	}
}

$GLOBALS['wpdb'] = new CF_Test_WPDB();

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
	$GLOBALS['cf_test_filters']     = [];
	$GLOBALS['wpdb']                = new CF_Test_WPDB();
	$GLOBALS['cf_test_enqueued']    = [];
	$GLOBALS['cf_test_remote_post_response'] = [
		'body'     => '{"success":true}',
		'response' => [ 'code' => 200 ],
	];
	$GLOBALS['cf_test_remote_head_response'] = [
		'body'     => '',
		'response' => [ 'code' => 200 ],
	];
	$GLOBALS['cf_test_remote_head_calls'] = 0;
	$GLOBALS['cf_test_settings_errors'] = [];
	$GLOBALS['cf_test_scheduled'] = [];
	$GLOBALS['cf_test_logged_in']   = false;
	$GLOBALS['cf_test_user_roles']  = [];
	$_POST                          = [];
	unset( $_SERVER['REMOTE_ADDR'] );
	unset( $_SERVER['HTTP_USER_AGENT'] );
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

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $name, $value, $autoload = null ) {
		$GLOBALS['cf_test_options'][ $name ] = $value;

		return true;
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

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $code;
		public $message;

		public function __construct( $code = '', $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'wp_remote_post' ) ) {
	function wp_remote_post( ...$args ) {
		return $GLOBALS['cf_test_remote_post_response'];
	}
}

if ( ! function_exists( 'wp_remote_head' ) ) {
	function wp_remote_head( ...$args ) {
		$GLOBALS['cf_test_remote_head_calls']++;

		return $GLOBALS['cf_test_remote_head_response'];
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		return is_array( $response ) && isset( $response['body'] ) ? $response['body'] : '';
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) {
		return is_array( $response ) && isset( $response['response']['code'] ) ? (int) $response['response']['code'] : 0;
	}
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
	function wp_next_scheduled( $hook ) {
		return $GLOBALS['cf_test_scheduled'][ $hook ] ?? false;
	}
}

if ( ! function_exists( 'wp_schedule_event' ) ) {
	function wp_schedule_event( $timestamp, $recurrence, $hook ) {
		$GLOBALS['cf_test_scheduled'][ $hook ] = $timestamp;

		return true;
	}
}

if ( ! function_exists( 'wp_unschedule_event' ) ) {
	function wp_unschedule_event( $timestamp, $hook ) {
		unset( $GLOBALS['cf_test_scheduled'][ $hook ] );

		return true;
	}
}

if ( ! function_exists( 'add_settings_error' ) ) {
	function add_settings_error( $setting, $code, $message, $type = 'error' ) {
		$GLOBALS['cf_test_settings_errors'][] = compact( 'setting', 'code', 'message', 'type' );
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( ...$args ) {
		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value, ...$args ) {
		return $GLOBALS['cf_test_filters'][ $tag ] ?? $value;
	}
}

/**
 * Override a filter's return value for the current test.
 *
 * @param string $tag   Filter name.
 * @param mixed  $value Value to return.
 *
 * @return void
 */
function cf_test_set_filter( $tag, $value ) {
	$GLOBALS['cf_test_filters'][ $tag ] = $value;
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
