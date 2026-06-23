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
     * Whether the honeypot spam protection is enabled.
     *
     * @return bool
     */
    public static function is_honeypot_enabled() {
        $options = get_option( 'captchafox_options' );
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
     * Get the configured allowlist of IP addresses / CIDR ranges.
     *
     * @return string[]
     */
    public static function get_allowlist() {
        $options = get_option( 'captchafox_options' );
        $raw = isset( $options['field_allowlist'] ) ? $options['field_allowlist'] : '';

        $list = preg_split( '/\r\n|\r|\n/', (string) $raw );
        $list = array_values( array_filter( array_map( 'trim', $list ) ) );

        return apply_filters( 'capf_allowlist', $list );
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

        if ( '' === $ip ) {
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
        $bits = (int) $bits;

        $ip_bin = inet_pton( $ip );
        $subnet_bin = inet_pton( $subnet );

        if ( false === $ip_bin || false === $subnet_bin || strlen( $ip_bin ) !== strlen( $subnet_bin ) ) {
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
     * @return string
     */
    public static function get_ob_html() {
		ob_start();
        self::get_html();

        return ob_get_clean();
    }

    /**
     * Print HTML for widget
     *
     * @return mixed
     */
    public static function get_html() {
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

        print( wp_kses( self::build_html(), $allowed ) );
    }

    /**
     * Create HTML for widget
     *
     * @param array $data Widget data.
     *
     * @return string
     */
    public static function build_html( $data = null ) {
        // Allowlisted visitors skip the captcha entirely, so render nothing and
        // avoid loading the assets. Verification is bypassed accordingly.
        if ( self::is_ip_allowed() ) {
            return '';
        }

        // A widget is being rendered, so make sure the scripts are loaded on
        // this page. Enqueuing here keeps the assets off pages without a form.
        self::enqueue_assets();

        if ( ! $data ) {
            $data = self::get_widget_options();
        }

        $attrs = '';
        foreach ( $data as $attr => $value ) {
            if ( null === $value ) {
                continue;
            }
            $attrs .= "data-{$attr}=\"{$value}\" ";
        }
        $attrs = rtrim( $attrs );

        $widget = sprintf( '<div class="captchafox" %s></div>', wp_kses( $attrs, [
            'data',
        ]) );

        return $widget . self::get_honeypot_html();
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

        $theme = apply_filters( 'capf_theme', $theme_option );
        $mode = apply_filters( 'capf_mode', $mode_option );
        $lang = apply_filters( 'capf_language', $lang_option );

        return [
            'mode'    => $mode,
            'theme'   => $theme,
            'lang'    => $lang,
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
