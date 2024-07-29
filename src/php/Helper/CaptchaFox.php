<?php

namespace CaptchaFox\Helper;

class CaptchaFox {
    /**
     * Get styles
     *
     * @return void
     */
    public static function get_styles() {
        ?>
        <style>
            .wpforms-container .captchafox,
            .captchafox {
                margin-bottom: 16px;
            }

            .captchafox[data-mode="hidden"] {
                margin-bottom: 0;
            }
        </style>
		<?php
    }

    /**
     * Load head
     *
     * @return void
     */
    public static function load_head() {
		wp_enqueue_script( 'captchafox-form', constant( 'CAPTCHAFOX_BASE_URL' ) . '/assets/js/form.js', [], PLUGIN_VERSION, true );
        wp_enqueue_script( 'captchafox', self::get_script(), [], PLUGIN_VERSION, true );
        self::get_styles();
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
