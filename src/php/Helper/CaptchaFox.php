<?php

namespace CaptchaFox\Helper;

class CaptchaFox {

    /**
     * Name of the honeypot field.
     *
     * @var string
     */
    const HONEYPOT_NAME = 'cf-captcha-hp';

    /**
     * Name of the signed timestamp field used by the time trap.
     *
     * @var string
     */
    const TIMESTAMP_NAME = 'cf-captcha-ts';

    /**
     * Whether the honeypot spam protection is enabled.
     *
     * @return bool
     */
    public static function is_honeypot_enabled() {
        $options = get_option( 'captchafox_security' );
        $enabled = isset( $options['field_honeypot'] ) && '1' === $options['field_honeypot'];

        return (bool) apply_filters( 'capf_honeypot', $enabled );
    }

    /**
     * Build the honeypot field markup.
     *
     * A hidden field that real users never fill in. Bots that auto fill form
     * fields will populate it, which lets us reject the submission.
     *
     * @return string
     */
    public static function get_honeypot_html() {
        if ( ! self::is_honeypot_enabled() ) {
            return '';
        }

        return sprintf(
            '<div class="cf-hp-field" aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;height:1px;width:1px;overflow:hidden;">' .
            '<label for="%1$s">%2$s</label>' .
            '<input type="text" id="%1$s" name="%1$s" value="" autocomplete="off" tabindex="-1">' .
            '</div>',
            esc_attr( self::HONEYPOT_NAME ),
            esc_html__( 'Leave this field empty', 'captchafox-for-forms' )
        );
    }

    /**
     * Minimum number of seconds a visitor must spend on the form before the
     * submission is accepted (0 disables the check).
     *
     * @return int
     */
    public static function get_min_time() {
        $options = get_option( 'captchafox_security' );
        $seconds = isset( $options['field_min_time'] ) ? (int) $options['field_min_time'] : 0;

        return (int) apply_filters( 'capf_min_time', max( 0, $seconds ) );
    }

    /**
     * Secret key used to sign the timestamp token so it cannot be forged.
     *
     * @return string
     */
    private static function get_signing_key() {
        if ( function_exists( 'wp_salt' ) ) {
            return wp_salt( 'auth' );
        }

        return defined( 'AUTH_SALT' ) ? AUTH_SALT : 'captchafox-fallback-key';
    }

    /**
     * Build a signed "time.signature" token for the current request.
     *
     * @return string
     */
    private static function create_timestamp_token() {
        $time = (string) time();

        return $time . '.' . hash_hmac( 'sha256', $time, self::get_signing_key() );
    }

    /**
     * Verify a timestamp token and return the embedded time, or null when the
     * token is missing/invalid (tampered).
     *
     * @param string $token Token from the request.
     *
     * @return int|null
     */
    public static function verify_timestamp( $token ) {
        $parts = explode( '.', (string) $token, 2 );

        if ( count( $parts ) !== 2 || ! ctype_digit( $parts[0] ) ) {
            return null;
        }

        list( $time, $signature ) = $parts;
        $expected = hash_hmac( 'sha256', $time, self::get_signing_key() );

        if ( ! hash_equals( $expected, $signature ) ) {
            return null;
        }

        return (int) $time;
    }

    /**
     * Build the hidden signed timestamp field used by the time trap.
     *
     * @return string
     */
    public static function get_timestamp_html() {
        if ( self::get_min_time() <= 0 ) {
            return '';
        }

        return sprintf(
            '<input type="hidden" name="%1$s" value="%2$s" autocomplete="off">',
            esc_attr( self::TIMESTAMP_NAME ),
            esc_attr( self::create_timestamp_token() )
        );
    }

    /**
     * Get the configured allowlist of IP addresses / CIDR ranges.
     *
     * @return string[]
     */
    public static function get_allowlist() {
        $options = get_option( 'captchafox_security' );
        $raw = isset( $options['field_allowlist'] ) ? $options['field_allowlist'] : '';

        $list = preg_split( '/\r\n|\r|\n/', (string) $raw );
        $list = array_values( array_filter( array_map( 'trim', $list ) ) );

        return apply_filters( 'capf_allowlist', $list );
    }

    /**
     * Get the configured denylist of IP addresses / CIDR ranges.
     *
     * @return string[]
     */
    public static function get_denylist() {
        $options = get_option( 'captchafox_security' );
        $raw = isset( $options['field_denylist'] ) ? $options['field_denylist'] : '';

        $list = preg_split( '/\r\n|\r|\n/', (string) $raw );
        $list = array_values( array_filter( array_map( 'trim', $list ) ) );

        return apply_filters( 'capf_denylist', $list );
    }

    /**
     * Get the visitor's IP address.
     *
     * @return string
     */
    public static function get_client_ip() {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ?
            sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) :
            '';

        $ip = apply_filters( 'capf_client_ip', $ip );

        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
    }

    /**
     * Whether the visitor's IP is allowlisted and should skip the captcha.
     *
     * @return bool
     */
    public static function is_ip_allowed() {
        $ip = self::get_client_ip();

        // A denied IP is never treated as allowed; blocking takes precedence.
        if ( '' === $ip || self::is_ip_denied() ) {
            return (bool) apply_filters( 'capf_ip_allowed', false, $ip );
        }

        $allowed = false;
        foreach ( self::get_allowlist() as $entry ) {
            if ( self::ip_matches( $ip, $entry ) ) {
                $allowed = true;
                break;
            }
        }

        return (bool) apply_filters( 'capf_ip_allowed', $allowed, $ip );
    }

    /**
     * Whether logged-in users skip the captcha.
     *
     * @return bool
     */
    public static function is_user_exempt() {
        $options = get_option( 'captchafox_security' );
        $enabled = isset( $options['field_skip_logged_in'] ) && '1' === $options['field_skip_logged_in'];
        $enabled = (bool) apply_filters( 'capf_skip_logged_in', $enabled );

        if ( ! $enabled || ! function_exists( 'is_user_logged_in' ) || ! is_user_logged_in() ) {
            return false;
        }

        return (bool) apply_filters( 'capf_user_exempt', true );
    }

    /**
     * Whether the captcha should be skipped for the current request, either
     * because the IP is allowlisted or the user is exempt. A denylisted IP is
     * never skipped.
     *
     * @return bool
     */
    public static function should_skip_captcha() {
        return self::is_ip_allowed() || ( ! self::is_ip_denied() && self::is_user_exempt() );
    }

    /**
     * Whether the visitor's IP is denylisted and should be blocked.
     *
     * @return bool
     */
    public static function is_ip_denied() {
        $ip = self::get_client_ip();

        if ( '' === $ip ) {
            return (bool) apply_filters( 'capf_ip_denied', false, $ip );
        }

        $denied = false;
        foreach ( self::get_denylist() as $entry ) {
            if ( self::ip_matches( $ip, $entry ) ) {
                $denied = true;
                break;
            }
        }

        return (bool) apply_filters( 'capf_ip_denied', $denied, $ip );
    }

    /**
     * Match an IP against an allowlist entry (exact match or CIDR range).
     *
     * Supports both IPv4 and IPv6.
     *
     * @param string $ip    Visitor IP address.
     * @param string $entry Allowlist entry.
     *
     * @return bool
     */
    private static function ip_matches( $ip, $entry ) {
        if ( $ip === $entry ) {
            return true;
        }

        if ( false === strpos( $entry, '/' ) ) {
            return false;
        }

        list( $subnet, $bits ) = explode( '/', $entry, 2 );

        if ( ! ctype_digit( (string) $bits ) ) {
            return false;
        }

        $bits = (int) $bits;

        $ip_bin = inet_pton( $ip );
        $subnet_bin = inet_pton( $subnet );

        if ( false === $ip_bin || false === $subnet_bin || strlen( $ip_bin ) !== strlen( $subnet_bin ) ) {
            return false;
        }

        $max_bits = strlen( $ip_bin ) * 8;

        if ( $bits < 0 || $bits > $max_bits ) {
            return false;
        }

        $bytes = intdiv( $bits, 8 );
        $remainder = $bits % 8;

        if ( $bytes > 0 && 0 !== substr_compare( $ip_bin, $subnet_bin, 0, $bytes ) ) {
            return false;
        }

        if ( $remainder > 0 ) {
            $mask = chr( ( 0xff << ( 8 - $remainder ) ) & 0xff );

            return ( ord( $ip_bin[ $bytes ] ) & ord( $mask ) ) === ( ord( $subnet_bin[ $bytes ] ) & ord( $mask ) );
        }

        return true;
    }

    /**
     * Whether a configured IP access entry is valid.
     *
     * @param string $entry IP address or CIDR range.
     *
     * @return bool
     */
    public static function is_valid_ip_entry( $entry ) {
        $entry = trim( (string) $entry );

        if ( filter_var( $entry, FILTER_VALIDATE_IP ) ) {
            return true;
        }

        if ( false === strpos( $entry, '/' ) ) {
            return false;
        }

        list( $subnet, $bits ) = explode( '/', $entry, 2 );

        if ( ! filter_var( $subnet, FILTER_VALIDATE_IP ) || ! ctype_digit( (string) $bits ) ) {
            return false;
        }

        $subnet_bin = inet_pton( $subnet );

        if ( false === $subnet_bin ) {
            return false;
        }

        $bits = (int) $bits;

        return $bits >= 0 && $bits <= strlen( $subnet_bin ) * 8;
    }

    /**
     * Get the widget styles as a CSS string.
     *
     * @return string
     */
    public static function get_styles_css() {
        return '
            .wpforms-container .captchafox,
            .captchafox {
                margin-bottom: 16px;
            }

            .captchafox[data-mode="hidden"] {
                margin-bottom: 0;
            }
        ';
    }

    /**
     * Register the frontend assets without enqueuing them.
     *
     * @return void
     */
    public static function register_assets() {
        if ( wp_script_is( 'captchafox-form', 'registered' ) ) {
            return;
        }

        // form.js defines window.captchaFoxOnLoad, which the CDN api.js invokes
        // via its onload parameter, so form.js must be loaded before the api
        // script.
        wp_register_script( 'captchafox-form', constant( 'CAPTCHAFOX_BASE_URL' ) . '/assets/js/form.js', [], PLUGIN_VERSION, true );
        wp_register_script( 'captchafox', self::get_script(), [ 'captchafox-form' ], PLUGIN_VERSION, true );

        wp_localize_script( 'captchafox-form', 'captchaFoxConfig', [
            'api'   => self::get_script(),
            'delay' => self::is_delayed() ? '1' : '0',
        ] );

        wp_register_style( 'captchafox', false, [], PLUGIN_VERSION );
        wp_add_inline_style( 'captchafox', self::get_styles_css() );
    }

    /**
     * Get the message shown when captcha verification fails. Customise it with
     * the `capf_error_message` filter.
     *
     * @return string
     */
    public static function get_error_message() {
        return apply_filters( 'capf_error_message', __( 'Invalid Captcha', 'captchafox-for-forms' ) );
    }

    /**
     * Whether the api script loading is delayed until the first user
     * interaction.
     *
     * @return bool
     */
    public static function is_delayed() {
        $options = get_option( 'captchafox_options' );
        $loading = isset( $options['field_loading'] ) ? $options['field_loading'] : 'instant';

        return (bool) apply_filters( 'capf_delay', 'interaction' === $loading );
    }

    /**
     * Enqueue the assets.
     *
     * Called whenever a widget is rendered so the scripts are only loaded on
     * pages that contain a CaptchaFox widget.
     *
     * @param bool $force_api Always enqueue the api script, even when loading
     *                        is delayed. Used by integrations that need the api
     *                        available immediately.
     *
     * @return void
     */
    public static function enqueue_assets( $force_api = false ) {
        self::register_assets();

        wp_enqueue_script( 'captchafox-form' );

        // When loading is delayed, form.js injects the api script on the first
        // user interaction instead of it being enqueued up front.
        if ( $force_api || ! self::is_delayed() ) {
            wp_enqueue_script( 'captchafox' );
        }

        wp_enqueue_style( 'captchafox' );
    }

    /**
     * Load head. Used on the login pages, where the captcha is always present.
     *
     * @return void
     */
    public static function load_head() {
        self::enqueue_assets();
    }

    /**
     * Get script
     *
     * @return string
     */
    public static function get_script() {
		return 'https://cdn.captchafox.com/api.js?render=explicit&onload=captchaFoxOnLoad';
    }

    /**
     * Get Output Buffer HTML
     *
     * @param array $overrides Per-instance widget option overrides (e.g. 'start').
     *
     * @return string
     */
    public static function get_ob_html( $overrides = [] ) {
		ob_start();
        self::print_html( $overrides );

        return ob_get_clean();
    }

    /**
     * Print HTML for widget.
     *
     *
     * @return mixed
     */
    public static function get_html() {
        self::print_html();
    }

    /**
     * Sanitize and print the widget markup.
     *
     * @param array $overrides Per-instance widget option overrides.
     *
     * @return void
     */
    private static function print_html( $overrides = [] ) {
        $allowed = wp_kses_allowed_html( 'post' );

        // Allow the honeypot input field, which is not part of the default
        // post allowed tags.
        $allowed['input'] = [
            'type'         => true,
            'id'           => true,
            'name'         => true,
            'value'        => true,
            'autocomplete' => true,
            'tabindex'     => true,
            'class'        => true,
            'aria-hidden'  => true,
        ];

        print( wp_kses( self::build_html( $overrides ), $allowed ) );
    }

    /**
     * Create HTML for widget
     *
     * @param array $overrides Per-instance widget option overrides.
     *
     * @return string
     */
    public static function build_html( $overrides = [] ) {
        // Allowlisted/exempt visitors skip the captcha entirely, so render
        // nothing and avoid loading the assets. Verification is bypassed too.
        if ( self::should_skip_captcha() ) {
            return '';
        }

        // A widget is being rendered, so make sure the scripts are loaded on
        // this page. Enqueuing here keeps the assets off pages without a form.
        self::enqueue_assets();

        $data = self::get_widget_options();

        if ( is_array( $overrides ) && ! empty( $overrides ) ) {
            $data = self::apply_widget_overrides( $data, $overrides );
        }

        $attrs = '';
        foreach ( $data as $attr => $value ) {
            if ( null === $value ) {
                continue;
            }

            $attr = preg_replace( '/[^a-z0-9_-]/i', '', (string) $attr );

            if ( '' === $attr ) {
                continue;
            }

            $attrs .= sprintf( 'data-%s="%s" ', esc_attr( $attr ), esc_attr( $value ) );
        }
        $attrs = rtrim( $attrs );

        $widget = sprintf( '<div class="captchafox"%s></div>', '' !== $attrs ? ' ' . $attrs : '' );

        return $widget . self::get_honeypot_html() . self::get_timestamp_html();
    }

    /**
     * Apply per-instance overrides on top of the global widget options.
     *
     *
     * @param array $data      Resolved global widget options.
     * @param array $overrides Per-instance overrides.
     *
     * @return array
     */
    private static function apply_widget_overrides( array $data, array $overrides ) {
        if ( isset( $overrides['start'] ) ) {
            $start = $overrides['start'];

            // 'inherit' (or an empty value) keeps the global setting, an
            // explicit choice overrides it. Anything unknown falls back to the
            // 'none' default.
            if ( 'inherit' !== $start && '' !== $start ) {
                $data['start'] = in_array( $start, [ 'auto', 'focus' ], true ) ? $start : null;
            }
        }

        return $data;
    }

    /**
     * Get saved options for widget
     *
     * @return array
     */
    public static function get_widget_options() {
        $options = self::get_options();

        return [
            'sitekey' => $options['sitekey'],
            'mode'    => $options['mode'],
            'theme'   => $options['theme'],
            'lang'    => $options['lang'],
            'start'   => $options['start'],
        ];
    }

    /**
     * Get saved options
     *
     * @return array
     */
    public static function get_options() {
        $options = get_option( 'captchafox_options' );
        $secret = isset( $options['field_secret'] ) ? $options['field_secret'] : '';
        $sitekey = isset( $options['field_sitekey'] ) ? $options['field_sitekey'] : '';
        $lang_option = isset( $options['field_lang'] ) ? $options['field_lang'] : null;
        $lang_option = 'auto' !== $lang_option ? $lang_option : null;

        $mode_option = isset( $options['field_display_mode'] ) ? $options['field_display_mode'] : 'inline';
        $theme_option = isset( $options['field_theme'] ) ? $options['field_theme'] : 'light';
        $start_option = isset( $options['field_start'] ) ? $options['field_start'] : 'none';
        $start_option = 'none' !== $start_option ? $start_option : null;

        $theme = apply_filters( 'capf_theme', $theme_option );
        $mode = apply_filters( 'capf_mode', $mode_option );
        $lang = apply_filters( 'capf_language', $lang_option );
        $start = apply_filters( 'capf_start', $start_option );

        return [
            'mode'    => $mode,
            'theme'   => $theme,
            'lang'    => $lang,
            'start'   => $start,
            'sitekey' => $sitekey,
            'secret'  => $secret,
        ];
    }

    /**
     * Get secret key
     *
     * @return string
     */
    public static function get_secret() {
        $options = self::get_options();

        return $options['secret'];
    }

    /**
     * Get site key
     *
     * @return string
     */
    public static function get_sitekey() {
        $options = self::get_options();

        return $options['sitekey'];
    }
}
