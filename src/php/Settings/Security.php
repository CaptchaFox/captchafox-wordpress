<?php

namespace CaptchaFox\Settings;

use CaptchaFox\Helper\CaptchaFox;

class Security {

    use FieldRenderer;

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
        $setting_security = 'captchafox_security';
        register_setting( 'captchafox_security', $setting_security, [
            'sanitize_callback' => [ $this, 'sanitize_options' ],
        ] );

        $section_spam  = 'captchafox_security_spam';
        $section_ip    = 'captchafox_security_ip';
        $section_login = 'captchafox_security_login';
        $section_stats = 'captchafox_security_stats';

        add_settings_section( $section_spam, __( 'Spam Protection', 'captchafox-for-forms' ), [ $this, 'render_section' ], 'captchafox-security' );
        add_settings_section( $section_ip, __( 'IP Access', 'captchafox-for-forms' ), [ $this, 'render_section' ], 'captchafox-security' );
        add_settings_section( $section_login, __( 'Login Protection', 'captchafox-for-forms' ), [ $this, 'render_section' ], 'captchafox-security' );
        add_settings_section( $section_stats, __( 'Analytics', 'captchafox-for-forms' ), [ $this, 'render_section' ], 'captchafox-security' );

        add_settings_field('field_honeypot', __( 'Honeypot', 'captchafox-for-forms' ), [ $this, 'render_checkbox_field' ], 'captchafox-security', $section_spam, [
            'label_for'   => 'field_honeypot',
            'class'       => 'cf-settings-row',
            'group'       => $setting_security,
            'description' => __( 'Add a hidden field that catches bots which auto-fill forms.', 'captchafox-for-forms' ),
        ]);
        add_settings_field('field_min_time', __( 'Minimum Submission Time', 'captchafox-for-forms' ), [ $this, 'render_number_field' ], 'captchafox-security', $section_spam, [
            'label_for'   => 'field_min_time',
            'class'       => 'cf-settings-row',
            'group'       => $setting_security,
            'min'         => 0,
            'default'     => 0,
            'description' => __( 'Reject submissions completed faster than this many seconds (0 = disabled). Bots usually submit instantly.', 'captchafox-for-forms' ),
        ]);
        add_settings_field('field_skip_logged_in', __( 'Skip for Logged-in Users', 'captchafox-for-forms' ), [ $this, 'render_checkbox_field' ], 'captchafox-security', $section_spam, [
            'label_for'   => 'field_skip_logged_in',
            'class'       => 'cf-settings-row',
            'group'       => $setting_security,
            'description' => __( 'Do not show the captcha to users who are signed in.', 'captchafox-for-forms' ),
        ]);
        add_settings_field('field_allowlist', __( 'IP Allowlist', 'captchafox-for-forms' ), [ $this, 'render_textarea_field' ], 'captchafox-security', $section_ip, [
            'label_for'   => 'field_allowlist',
            'class'       => 'cf-settings-row',
            'group'       => $setting_security,
            'placeholder' => "203.0.113.5\n192.168.0.0/24",
            'description' => __( 'Trusted IP addresses or CIDR ranges (one per line) that skip the captcha.', 'captchafox-for-forms' ),
        ]);
        add_settings_field('field_denylist', __( 'IP Denylist', 'captchafox-for-forms' ), [ $this, 'render_textarea_field' ], 'captchafox-security', $section_ip, [
            'label_for'   => 'field_denylist',
            'class'       => 'cf-settings-row',
            'group'       => $setting_security,
            'placeholder' => "203.0.113.6\n10.0.0.0/8",
            'description' => __( 'Blocked IP addresses or CIDR ranges (one per line). Submissions from these are always rejected, even if also allowlisted.', 'captchafox-for-forms' ),
        ]);
        add_settings_field('field_login_limit', __( 'Login Attempts Before Captcha', 'captchafox-for-forms' ), [ $this, 'render_number_field' ], 'captchafox-security', $section_login, [
            'label_for'   => 'field_login_limit',
            'class'       => 'cf-settings-row',
            'group'       => $setting_security,
            'min'         => 0,
            'default'     => 0,
            'description' => __( 'Failed login attempts before the captcha is shown on login forms (0 = always show).', 'captchafox-for-forms' ),
        ]);
        add_settings_field('field_login_interval', __( 'Failed Login Attempts Interval', 'captchafox-for-forms' ), [ $this, 'render_number_field' ], 'captchafox-security', $section_login, [
            'label_for'   => 'field_login_interval',
            'class'       => 'cf-settings-row',
            'group'       => $setting_security,
            'min'         => 1,
            'default'     => 15,
            'description' => __( 'How long, in minutes, failed login attempts are counted before the captcha is shown.', 'captchafox-for-forms' ),
        ]);
        add_settings_field('field_statistics', __( 'Record Analytics', 'captchafox-for-forms' ), [ $this, 'render_checkbox_field' ], 'captchafox-security', $section_stats, [
            'label_for'   => 'field_statistics',
            'class'       => 'cf-settings-row',
            'group'       => $setting_security,
            'description' => __( 'Log anonymized verification results.', 'captchafox-for-forms' ),
        ]);
        add_settings_field('field_collect_ip', __( 'Store IP Addresses', 'captchafox-for-forms' ), [ $this, 'render_checkbox_field' ], 'captchafox-security', $section_stats, [
            'label_for'   => 'field_collect_ip',
            'class'       => 'cf-settings-row',
            'group'       => $setting_security,
            'description' => __( 'Store the visitor IP address with each event.', 'captchafox-for-forms' ),
        ]);
        add_settings_field('field_collect_user_agent', __( 'Store User Agents', 'captchafox-for-forms' ), [ $this, 'render_checkbox_field' ], 'captchafox-security', $section_stats, [
            'label_for'   => 'field_collect_user_agent',
            'class'       => 'cf-settings-row',
            'group'       => $setting_security,
            'description' => __( 'Store the visitor user agent with each event.', 'captchafox-for-forms' ),
        ]);
    }

    /**
     * Sanitize security settings before saving.
     *
     * @param mixed $input Raw option value.
     *
     * @return array
     */
    public function sanitize_options( $input ) {
        $input = is_array( $input ) ? $input : [];

        return [
            'field_honeypot'           => ! empty( $input['field_honeypot'] ) ? '1' : '',
            'field_min_time'           => isset( $input['field_min_time'] ) ? max( 0, (int) $input['field_min_time'] ) : 0,
            'field_skip_logged_in'     => ! empty( $input['field_skip_logged_in'] ) ? '1' : '',
            'field_allowlist'          => $this->sanitize_ip_list( $input['field_allowlist'] ?? '', 'field_allowlist' ),
            'field_denylist'           => $this->sanitize_ip_list( $input['field_denylist'] ?? '', 'field_denylist' ),
            'field_login_limit'        => isset( $input['field_login_limit'] ) ? max( 0, (int) $input['field_login_limit'] ) : 0,
            'field_login_interval'     => isset( $input['field_login_interval'] ) ? max( 1, (int) $input['field_login_interval'] ) : 15,
            'field_statistics'         => ! empty( $input['field_statistics'] ) ? '1' : '',
            'field_collect_ip'         => ! empty( $input['field_collect_ip'] ) ? '1' : '',
            'field_collect_user_agent' => ! empty( $input['field_collect_user_agent'] ) ? '1' : '',
        ];
    }

    /**
     * Sanitize an IP/CIDR textarea, dropping invalid entries.
     *
     * @param string $raw   Raw textarea value.
     * @param string $field Field name for settings errors.
     *
     * @return string
     */
    private function sanitize_ip_list( $raw, $field ) {
        $lines = preg_split( '/\r\n|\r|\n/', (string) $raw );
        $valid = [];
        $invalid = [];

        foreach ( $lines as $line ) {
            $entry = trim( sanitize_text_field( $line ) );

            if ( '' === $entry ) {
                continue;
            }

            if ( CaptchaFox::is_valid_ip_entry( $entry ) ) {
                $valid[] = $entry;
                continue;
            }

            $invalid[] = $entry;
        }

        if ( ! empty( $invalid ) && function_exists( 'add_settings_error' ) ) {
            add_settings_error(
                'captchafox_security',
                $field . '_invalid',
                sprintf(
                    /* translators: %s: comma separated invalid IP entries. */
                    __( 'Invalid IP entries were ignored: %s', 'captchafox-for-forms' ),
                    implode( ', ', $invalid )
                )
            );
        }

        return implode( "\n", array_unique( $valid ) );
    }

    /**
     * Render the intro text for a settings section.
     *
     * @param array $args Section arguments, including the section id.
     *
     * @return void
     */
    public function render_section( $args ) {
        $descriptions = [
            'captchafox_security_spam'  => __( 'Catch bots before they submit your forms.', 'captchafox-for-forms' ),
            'captchafox_security_ip'    => __( 'Always allow or block specific IP addresses or ranges.', 'captchafox-for-forms' ),
            'captchafox_security_login' => __( 'Show the captcha on login forms only after repeated failed attempts.', 'captchafox-for-forms' ),
            'captchafox_security_stats' => __( 'Record anonymized verification results for the Analytics tab.', 'captchafox-for-forms' ),
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
            settings_fields( 'captchafox_security' );
            $this->render_sections( 'captchafox-security' );
            submit_button( __( 'Save Settings', 'captchafox-for-forms' ) );
            ?>
        </form>
		<?php
    }
}
