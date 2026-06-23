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
        add_settings_section( $setting_general, __( 'General settings', 'captchafox-for-forms' ), [ $this, 'init_settings_section' ], 'captchafox' );
        add_settings_field('field_sitekey', __( 'Site key', 'captchafox-for-forms' ), [ $this, 'render_text_field' ], 'captchafox', $setting_general, [
            'label_for' => 'field_sitekey',
            'class'     => 'cf-row',
            'group'     => $setting_general,
        ]);
        add_settings_field('field_secret', __( 'Secret key', 'captchafox-for-forms' ), [ $this, 'render_text_field' ], 'captchafox', $setting_general, [
            'label_for' => 'field_secret',
            'class'     => 'cf-row',
            'group'     => $setting_general,
            'type'      => 'password',
        ]);
        add_settings_field('field_display_mode', __( 'Display Mode', 'captchafox-for-forms' ), [ $this, 'render_select_field' ], 'captchafox', $setting_general, [
            'label_for' => 'field_display_mode',
            'class'     => 'cf-row',
            'group'     => $setting_general,
            'options'   => [
                'inline' => __( 'Inline (Default)', 'captchafox-for-forms' ),
                'popup'  => __( 'Popup', 'captchafox-for-forms' ),
                'hidden' => __( 'Hidden', 'captchafox-for-forms' ),
            ],
        ]);
        add_settings_field('field_theme', __( 'Theme', 'captchafox-for-forms' ), [ $this, 'render_select_field' ], 'captchafox', $setting_general, [
            'label_for' => 'field_theme',
            'class'     => 'cf-row',
            'group'     => $setting_general,
            'options'   => [
                'light' => __( 'Light (Default)', 'captchafox-for-forms' ),
                'dark'  => __( 'Dark', 'captchafox-for-forms' ),
            ],
        ]);
        add_settings_field('field_lang', __( 'Language', 'captchafox-for-forms' ), [ $this, 'render_select_field' ], 'captchafox', $setting_general, [
            'label_for' => 'field_lang',
            'class'     => 'cf-row',
            'group'     => $setting_general,
            'options'   => [
                'auto'  => __( 'Auto-Detect (Default)', 'captchafox-for-forms' ),
                'cs'    => __( 'Czech', 'captchafox-for-forms' ),
                'zh-cn' => __( 'Chinese (simplified)', 'captchafox-for-forms' ),
                'zh-tw' => __( 'Chinese (traditional)', 'captchafox-for-forms' ),
                'da'    => __( 'Danish', 'captchafox-for-forms' ),
                'nl'    => __( 'Dutch', 'captchafox-for-forms' ),
                'de'    => __( 'German', 'captchafox-for-forms' ),
                'en'    => __( 'English', 'captchafox-for-forms' ),
                'fi'    => __( 'Finnish', 'captchafox-for-forms' ),
                'fr'    => __( 'French', 'captchafox-for-forms' ),
                'it'    => __( 'Italian', 'captchafox-for-forms' ),
                'ja'    => __( 'Japanese', 'captchafox-for-forms' ),
                'ko'    => __( 'Korean', 'captchafox-for-forms' ),
                'no'    => __( 'Norwegian', 'captchafox-for-forms' ),
                'pt'    => __( 'Portuguese', 'captchafox-for-forms' ),
                'pl'    => __( 'Polish', 'captchafox-for-forms' ),
                'ru'    => __( 'Russian', 'captchafox-for-forms' ),
                'es'    => __( 'Spanish', 'captchafox-for-forms' ),
                'sv'    => __( 'Swedish', 'captchafox-for-forms' ),
                'tr'    => __( 'Turkish', 'captchafox-for-forms' ),
                'uk'    => __( 'Ukrainian', 'captchafox-for-forms' ),
                'id'    => __( 'Indonesian', 'captchafox-for-forms' ),
            ],
        ]);
        add_settings_field('field_honeypot', __( 'Honeypot', 'captchafox-for-forms' ), [ $this, 'render_checkbox_field' ], 'captchafox', $setting_general, [
            'label_for'   => 'field_honeypot',
            'class'       => 'cf-row',
            'group'       => $setting_general,
            'description' => __( 'Add a hidden field that catches bots which auto-fill forms.', 'captchafox-for-forms' ),
        ]);
        add_settings_field('field_allowlist', __( 'IP Allowlist', 'captchafox-for-forms' ), [ $this, 'render_textarea_field' ], 'captchafox', $setting_general, [
            'label_for'   => 'field_allowlist',
            'class'       => 'cf-row',
            'group'       => $setting_general,
            'placeholder' => "203.0.113.5\n192.168.0.0/24",
            'description' => __( 'Trusted IP addresses or CIDR ranges (one per line) that skip the captcha.', 'captchafox-for-forms' ),
        ]);
    }

    /**
     * Init Section
     *
     * @return void
     */
    public function init_settings_section() {
        ?>
        <p><?php esc_html_e( 'Configure the settings for the CaptchaFox widget.', 'captchafox-for-forms' ); ?> <?php esc_html_e( 'Don\'t have a site key?', 'captchafox-for-forms' ); ?> <a href="https://portal.captchafox.com/register" target="_blank"><?php esc_html_e( 'Click here to create an account', 'captchafox-for-forms' ); ?></a></p>
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
            submit_button( __( 'Save Settings', 'captchafox-for-forms' ) );
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
     * Textarea Field
     *
     * @param  mixed $args Args.
     * @return void
     */
    public function render_textarea_field( $args ) {
        $option_group = $args['group'];
        $options = get_option( $option_group );
        $field_name = esc_attr( $args['label_for'] );
        $placeholder = isset( $args['placeholder'] ) ? $args['placeholder'] : '';
        $description = isset( $args['description'] ) ? $args['description'] : '';
        $current_value = isset( $options[ $field_name ] ) ? $options[ $field_name ] : '';

        printf(
            '<textarea id="%1$s" name="%2$s[%1$s]" rows="4" cols="40" placeholder="%3$s">%4$s</textarea>',
            esc_attr( $field_name ),
            esc_attr( $option_group ),
            esc_attr( $placeholder ),
            esc_textarea( $current_value )
        );

        if ( '' !== $description ) {
            printf( '<p class="description">%s</p>', esc_html( $description ) );
        }
    }

    /**
     * Checkbox Field
     *
     * @param  mixed $args Args.
     * @return void
     */
    public function render_checkbox_field( $args ) {
        $option_group = $args['group'];
        $options = get_option( $option_group );
        $field_name = esc_attr( $args['label_for'] );
        $description = isset( $args['description'] ) ? $args['description'] : '';
        $current_value = isset( $options[ $field_name ] ) ? $options[ $field_name ] : '';

        printf(
            '<label><input id="%1$s" name="%2$s[%1$s]" type="checkbox" value="1" %3$s> %4$s</label>',
            esc_attr( $field_name ),
            esc_attr( $option_group ),
            checked( '1', $current_value, false ),
            esc_html( $description )
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
