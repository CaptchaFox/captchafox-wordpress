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
		wp_enqueue_script( 'captchafox-js', constant( 'CAPTCHAFOX_BASE_URL' ) . '/assets/js/form.js', [], '1.0', true );
        wp_enqueue_script( 'captchafox', self::get_script(), [], '1.0', true );
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
     * Get_ob_html
     *
     * @return string
     */
    public static function get_ob_html() {
		ob_start();
        self::get_html();

        return ob_get_clean();
    }

    /**
     * Get_html
     *
     * @return void
     */
    public static function get_html() {
		$data = [];
        $options = get_option( 'captchafox_options' );
        $lang = isset( $options['field_lang'] ) ? $options['field_lang'] : null;
        $data['sitekey'] = isset( $options['field_sitekey'] ) ? $options['field_sitekey'] : '';
        $data['mode'] = isset( $options['field_display_mode'] ) ? $options['field_display_mode'] : 'inline';
        $data['lang'] = 'auto' !== $lang ? $options['field_lang'] : null;

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
}
