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
     * Create HTML for widget
     *
     * @return void
     */
    public static function get_html() {
        $data = self::get_widget_options();

        $attrs = '';
        foreach ( $data as $attr => $value ) {
            if ( null === $value ) {
                continue;
            }
            $attrs .= "data-{$attr}=\"{$value}\" ";
        }
        $attrs = rtrim( $attrs );

        printf( '<div class="captchafox" %s></div>', wp_kses( $attrs, [
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
        $field_lang = isset( $options['field_lang'] ) ? $options['field_lang'] : null;
        $lang = 'auto' !== $field_lang ? $field_lang : null;
        $sitekey = isset( $options['field_sitekey'] ) ? $options['field_sitekey'] : '';
        $mode = isset( $options['field_display_mode'] ) ? $options['field_display_mode'] : 'inline';
        $theme = isset( $options['field_theme'] ) ? $options['field_theme'] : 'light';
        $secret = isset( $options['field_secret'] ) ? $options['field_secret'] : '';

        return [
            'sitekey' => $sitekey,
            'mode'    => $mode,
            'theme'   => $theme,
            'lang'    => $lang,
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
