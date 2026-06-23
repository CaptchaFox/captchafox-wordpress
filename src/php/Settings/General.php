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
        add_settings_field('field_loading', __( 'Script Loading', 'captchafox-for-forms' ), [ $this, 'render_select_field' ], 'captchafox', $setting_general, [
            'label_for'   => 'field_loading',
            'class'       => 'cf-row',
            'group'       => $setting_general,
            'description' => __( 'Delay loading the captcha script until the visitor interacts with the page for better performance.', 'captchafox-for-forms' ),
            'options'     => [
                'instant'     => __( 'Instant (Default)', 'captchafox-for-forms' ),
                'interaction' => __( 'On user interaction', 'captchafox-for-forms' ),
            ],
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

}
