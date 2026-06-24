<?php

namespace CaptchaFox\Settings;

class General {

    use FieldRenderer;

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
        $setting_general = 'captchafox_options';
        register_setting( 'captchafox', $setting_general, [
            'sanitize_callback' => [ $this, 'sanitize_options' ],
        ] );

        $section_keys        = 'captchafox_general_keys';
        $section_appearance  = 'captchafox_general_appearance';
        $section_performance = 'captchafox_general_performance';

        add_settings_section( $section_keys, __( 'API Keys', 'captchafox-for-forms' ), [ $this, 'render_section' ], 'captchafox' );
        add_settings_section( $section_appearance, __( 'Appearance', 'captchafox-for-forms' ), [ $this, 'render_section' ], 'captchafox' );
        add_settings_section( $section_performance, __( 'Performance', 'captchafox-for-forms' ), [ $this, 'render_section' ], 'captchafox' );

        add_settings_field('field_sitekey', __( 'Site key', 'captchafox-for-forms' ), [ $this, 'render_text_field' ], 'captchafox', $section_keys, [
            'label_for' => 'field_sitekey',
            'class'     => 'cf-settings-row',
            'group'     => $setting_general,
        ]);
        add_settings_field('field_secret', __( 'Secret key', 'captchafox-for-forms' ), [ $this, 'render_text_field' ], 'captchafox', $section_keys, [
            'label_for' => 'field_secret',
            'class'     => 'cf-settings-row',
            'group'     => $setting_general,
            'type'      => 'password',
        ]);
        add_settings_field('field_display_mode', __( 'Display Mode', 'captchafox-for-forms' ), [ $this, 'render_select_field' ], 'captchafox', $section_appearance, [
            'label_for' => 'field_display_mode',
            'class'     => 'cf-settings-row',
            'group'     => $setting_general,
            'options'   => [
                'inline' => __( 'Inline (Default)', 'captchafox-for-forms' ),
                'popup'  => __( 'Popup', 'captchafox-for-forms' ),
                'hidden' => __( 'Hidden', 'captchafox-for-forms' ),
            ],
        ]);
        add_settings_field('field_theme', __( 'Theme', 'captchafox-for-forms' ), [ $this, 'render_select_field' ], 'captchafox', $section_appearance, [
            'label_for' => 'field_theme',
            'class'     => 'cf-settings-row',
            'group'     => $setting_general,
            'options'   => [
                'light' => __( 'Light (Default)', 'captchafox-for-forms' ),
                'dark'  => __( 'Dark', 'captchafox-for-forms' ),
            ],
        ]);
        add_settings_field('field_lang', __( 'Language', 'captchafox-for-forms' ), [ $this, 'render_select_field' ], 'captchafox', $section_appearance, [
            'label_for' => 'field_lang',
            'class'     => 'cf-settings-row',
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
        add_settings_field('field_loading', __( 'Script Loading', 'captchafox-for-forms' ), [ $this, 'render_select_field' ], 'captchafox', $section_performance, [
            'label_for'   => 'field_loading',
            'class'       => 'cf-settings-row',
            'group'       => $setting_general,
            'description' => __( 'Delay loading the captcha script until the visitor interacts with the page for better performance.', 'captchafox-for-forms' ),
            'options'     => [
                'instant'     => __( 'Instant (Default)', 'captchafox-for-forms' ),
                'interaction' => __( 'On user interaction', 'captchafox-for-forms' ),
            ],
        ]);
    }

    /**
     * Sanitize general settings before saving.
     *
     * @param mixed $input Raw option value.
     *
     * @return array
     */
    public function sanitize_options( $input ) {
        $input = is_array( $input ) ? $input : [];
        $languages = [ 'auto', 'cs', 'zh-cn', 'zh-tw', 'da', 'nl', 'de', 'en', 'fi', 'fr', 'it', 'ja', 'ko', 'no', 'pt', 'pl', 'ru', 'es', 'sv', 'tr', 'uk', 'id' ];

        return [
            'field_sitekey'      => isset( $input['field_sitekey'] ) ? sanitize_text_field( $input['field_sitekey'] ) : '',
            'field_secret'       => isset( $input['field_secret'] ) ? sanitize_text_field( $input['field_secret'] ) : '',
            'field_display_mode' => isset( $input['field_display_mode'] ) && in_array( $input['field_display_mode'], [ 'inline', 'popup', 'hidden' ], true ) ? $input['field_display_mode'] : 'inline',
            'field_theme'        => isset( $input['field_theme'] ) && in_array( $input['field_theme'], [ 'light', 'dark' ], true ) ? $input['field_theme'] : 'light',
            'field_lang'         => isset( $input['field_lang'] ) && in_array( $input['field_lang'], $languages, true ) ? $input['field_lang'] : 'auto',
            'field_loading'      => isset( $input['field_loading'] ) && in_array( $input['field_loading'], [ 'instant', 'interaction' ], true ) ? $input['field_loading'] : 'instant',
        ];
    }

    /**
     * Render the intro text for a settings section.
     *
     * @param array $args Section arguments, including the section id.
     *
     * @return void
     */
    public function render_section( $args ) {
        if ( 'captchafox_general_keys' === $args['id'] ) {
            printf(
                '<p>%s <a href="https://portal.captchafox.com/register" target="_blank">%s</a></p>',
                esc_html__( 'Enter the site and secret key from your CaptchaFox dashboard.', 'captchafox-for-forms' ),
                esc_html__( 'Need an account?', 'captchafox-for-forms' )
            );
            return;
        }

        $descriptions = [
            'captchafox_general_appearance'  => __( 'Control how the widget looks and which language it uses.', 'captchafox-for-forms' ),
            'captchafox_general_performance' => __( 'Tune when the captcha script is loaded.', 'captchafox-for-forms' ),
        ];

        if ( ! empty( $descriptions[ $args['id'] ] ) ) {
            printf( '<p>%s</p>', esc_html( $descriptions[ $args['id'] ] ) );
        }
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
            $this->render_sections( 'captchafox' );
            submit_button( __( 'Save Settings', 'captchafox-for-forms' ) );
            ?>
        </form>
		<?php
    }

}
