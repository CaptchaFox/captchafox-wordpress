<?php

namespace CaptchaFox\Settings;

class General {

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
        $setting_general = 'captchafox_options';
        register_setting( 'captchafox', $setting_general );
        add_settings_section( $setting_general, __( 'General settings', 'captchafox' ), [ $this, 'init_settings_section' ], 'captchafox' );
        add_settings_field('field_sitekey', __( 'Site key', 'captchafox' ), [ $this, 'render_text_field' ], 'captchafox', $setting_general, [
            'label_for' => 'field_sitekey',
            'class'     => 'cf-row',
            'group'     => $setting_general,
        ]);
        add_settings_field('field_secret', __( 'Secret key', 'captchafox' ), [ $this, 'render_text_field' ], 'captchafox', $setting_general, [
            'label_for' => 'field_secret',
            'class'     => 'cf-row',
            'group'     => $setting_general,
            'type'      => 'password',
        ]);
        add_settings_field('field_display_mode', __( 'Display Mode', 'captchafox' ), [ $this, 'render_select_field' ], 'captchafox', $setting_general, [
            'label_for' => 'field_display_mode',
            'class'     => 'cf-row',
            'group'     => $setting_general,
            'options'   => [
                'inline' => __( 'Inline (Default)', 'captchafox' ),
                'popup'  => __( 'Popup', 'captchafox' ),
                'hidden' => __( 'Hidden', 'captchafox' ),
            ],
        ]);
        add_settings_field('field_lang', __( 'Language', 'captchafox' ), [ $this, 'render_select_field' ], 'captchafox', $setting_general, [
            'label_for' => 'field_lang',
            'class'     => 'cf-row',
            'group'     => $setting_general,
            'options'   => [
                'auto'  => __( 'Auto-Detect (Default)', 'captchafox' ),
                'cs'    => __( 'Czech', 'captchafox' ),
                'zh-cn' => __( 'Chinese (simplified)', 'captchafox' ),
                'zh-tw' => __( 'Chinese (traditional)', 'captchafox' ),
                'da'    => __( 'Danish', 'captchafox' ),
                'nl'    => __( 'Dutch', 'captchafox' ),
                'de'    => __( 'German', 'captchafox' ),
                'en'    => __( 'English', 'captchafox' ),
                'fi'    => __( 'Finnish', 'captchafox' ),
                'fr'    => __( 'French', 'captchafox' ),
                'it'    => __( 'Italian', 'captchafox' ),
                'ja'    => __( 'Japanese', 'captchafox' ),
                'ko'    => __( 'Korean', 'captchafox' ),
                'no'    => __( 'Norwegian', 'captchafox' ),
                'pt'    => __( 'Portuguese', 'captchafox' ),
                'pl'    => __( 'Polish', 'captchafox' ),
                'ru'    => __( 'Russian', 'captchafox' ),
                'es'    => __( 'Spanish', 'captchafox' ),
                'sv'    => __( 'Swedish', 'captchafox' ),
                'tr'    => __( 'Turkish', 'captchafox' ),
                'uk'    => __( 'Ukrainian', 'captchafox' ),
                'id'    => __( 'Indonesian', 'captchafox' ),
            ],
        ]);
    }

    /**
     * Init Section
     *
     * @return void
     */
    public function init_settings_section() {                      ?>
        <p><?php esc_html_e( 'Configure the settings for the CaptchaFox widget.', 'captchafox' ); ?> <?php esc_html_e( 'Don\'t have a site key?', 'captchafox' ); ?> <a href="https://portal.captchafox.com/register" target="_blank"><?php esc_html_e( 'Click here to create an account', 'captchafox' ); ?></a></p>
		<?php
    }

    /**
     * Get Tab Content
     *
     * @return void
     */
    public function get_tab_content() {
		?>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'captchafox' );
            do_settings_sections( 'captchafox' );
            submit_button( __( 'Save Settings', 'captchafox' ) );
            ?>
        </form>
		<?php
    }

    /**
     * Text Field
     *
     * @param  mixed $args Args.
     * @return void
     */
    public function render_text_field( $args ) {
        $option_group = $args['group'];
        $options = get_option( $option_group );
        $field_name = esc_attr( $args['label_for'] );
        $field_type = isset( $args['type'] ) ? $args['type'] : 'text';
        $current_value = isset( $options[ $field_name ] ) ? $options[ $field_name ] : '';

        printf(
            '<input id="%s" name="%s[%s]" type="%s" value="%s">
        ',
            esc_attr( $field_name ),
            esc_attr( $option_group ),
            esc_attr( $field_name ),
            esc_attr( $field_type ),
            esc_html( $current_value )
        );
    }

    /**
     * Select Field
     *
     * @param  mixed $args Args.
     * @return void
     */
    public function render_select_field( $args ) {
        $option_group = $args['group'];
        $options = get_option( $option_group );
        $field_name = esc_attr( $args['label_for'] );
        $current_value = isset( $options[ $field_name ] ) ? $options[ $field_name ] : '';

        $select_options = '';

        foreach ( $args['options'] as $attr => $value ) {
            if ( null === $value ) {
                continue;
            }
            $select_options .= sprintf( '<option value="%s" %s>%s</option>', $attr, selected( $attr, $current_value, false ), $value );
        }

        $allowed_html = [
            'option' => [
                'value'    => [],
                'selected' => [],
            ],
        ];

        printf(
            '<select id="%s" name="%s[%s]" />%s</select>
		',
            esc_attr( $field_name ),
            esc_attr( $option_group ),
            esc_attr( $field_name ),
            wp_kses( $select_options, $allowed_html )
        );
    }

}
