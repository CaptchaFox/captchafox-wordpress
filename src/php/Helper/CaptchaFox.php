<?php

namespace CaptchaFox\Helper;

class CaptchaFox {

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
     * Registering keeps the scripts available so they can be enqueued lazily
     * (or pulled in as dependencies) only on pages that actually contain a
     * CaptchaFox widget. This avoids loading the third party script on every
     * page for data privacy reasons.
     *
     * @return void
     */
    public static function register_assets() {
        if ( wp_script_is( 'captchafox-form', 'registered' ) ) {
            return;
        }

        // form.js defines window.captchaFoxOnLoad, which the CDN api.js invokes
        // via its onload parameter, so form.js must be loaded before the api
        // script. The dependency guarantees that order.
        wp_register_script( 'captchafox-form', constant( 'CAPTCHAFOX_BASE_URL' ) . '/assets/js/form.js', [], PLUGIN_VERSION, true );
        wp_register_script( 'captchafox', self::get_script(), [ 'captchafox-form' ], PLUGIN_VERSION, true );

        wp_register_style( 'captchafox', false, [], PLUGIN_VERSION );
        wp_add_inline_style( 'captchafox', self::get_styles_css() );
    }

    /**
     * Enqueue the assets.
     *
     * Called whenever a widget is rendered so the scripts are only loaded on
     * pages that contain a CaptchaFox widget.
     *
     * @return void
     */
    public static function enqueue_assets() {
        self::register_assets();

        wp_enqueue_script( 'captchafox-form' );
        wp_enqueue_script( 'captchafox' );
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
        print( wp_kses_post( self::build_html() ) );
    }

    /**
     * Create HTML for widget
     *
     * @param array $data Widget data.
     *
     * @return string
     */
    public static function build_html( $data = null ) {
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

        return sprintf( '<div class="captchafox" %s></div>', wp_kses( $attrs, [
            'data',
        ]) );
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
