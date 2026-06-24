<?php

namespace CaptchaFox\Settings;

class Security {

    use FieldRenderer;

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
        $setting_security = 'captchafox_security';
        register_setting( 'captchafox_security', $setting_security );
        add_settings_section( $setting_security, __( 'Security settings', 'captchafox-for-forms' ), [ $this, 'init_settings_section' ], 'captchafox-security' );

        add_settings_field('field_honeypot', __( 'Honeypot', 'captchafox-for-forms' ), [ $this, 'render_checkbox_field' ], 'captchafox-security', $setting_security, [
            'label_for'   => 'field_honeypot',
            'class'       => 'cf-row',
            'group'       => $setting_security,
            'description' => __( 'Add a hidden field that catches bots which auto-fill forms.', 'captchafox-for-forms' ),
        ]);
        add_settings_field('field_min_time', __( 'Minimum Submission Time', 'captchafox-for-forms' ), [ $this, 'render_number_field' ], 'captchafox-security', $setting_security, [
            'label_for'   => 'field_min_time',
            'class'       => 'cf-row',
            'group'       => $setting_security,
            'min'         => 0,
            'default'     => 0,
            'description' => __( 'Reject submissions completed faster than this many seconds (0 = disabled). Bots usually submit instantly.', 'captchafox-for-forms' ),
        ]);
        add_settings_field('field_allowlist', __( 'IP Allowlist', 'captchafox-for-forms' ), [ $this, 'render_textarea_field' ], 'captchafox-security', $setting_security, [
            'label_for'   => 'field_allowlist',
            'class'       => 'cf-row',
            'group'       => $setting_security,
            'placeholder' => "203.0.113.5\n192.168.0.0/24",
            'description' => __( 'Trusted IP addresses or CIDR ranges (one per line) that skip the captcha.', 'captchafox-for-forms' ),
        ]);
        add_settings_field('field_denylist', __( 'IP Denylist', 'captchafox-for-forms' ), [ $this, 'render_textarea_field' ], 'captchafox-security', $setting_security, [
            'label_for'   => 'field_denylist',
            'class'       => 'cf-row',
            'group'       => $setting_security,
            'placeholder' => "203.0.113.6\n10.0.0.0/8",
            'description' => __( 'Blocked IP addresses or CIDR ranges (one per line). Submissions from these are always rejected, even if also allowlisted.', 'captchafox-for-forms' ),
        ]);
        add_settings_field('field_skip_logged_in', __( 'Skip for Logged-in Users', 'captchafox-for-forms' ), [ $this, 'render_checkbox_field' ], 'captchafox-security', $setting_security, [
            'label_for'   => 'field_skip_logged_in',
            'class'       => 'cf-row',
            'group'       => $setting_security,
            'description' => __( 'Do not show the captcha to users who are signed in.', 'captchafox-for-forms' ),
        ]);
        add_settings_field('field_login_limit', __( 'Login Attempts Before Captcha', 'captchafox-for-forms' ), [ $this, 'render_number_field' ], 'captchafox-security', $setting_security, [
            'label_for'   => 'field_login_limit',
            'class'       => 'cf-row',
            'group'       => $setting_security,
            'min'         => 0,
            'default'     => 0,
            'description' => __( 'Failed login attempts before the captcha is shown on login forms (0 = always show).', 'captchafox-for-forms' ),
        ]);
        add_settings_field('field_login_interval', __( 'Failed Login Attempts Interval', 'captchafox-for-forms' ), [ $this, 'render_number_field' ], 'captchafox-security', $setting_security, [
            'label_for'   => 'field_login_interval',
            'class'       => 'cf-row',
            'group'       => $setting_security,
            'min'         => 1,
            'default'     => 15,
            'description' => __( 'How long, in minutes, failed login attempts are counted before the captcha is shown.', 'captchafox-for-forms' ),
        ]);
        add_settings_field('field_statistics', __( 'Record Statistics', 'captchafox-for-forms' ), [ $this, 'render_checkbox_field' ], 'captchafox-security', $setting_security, [
            'label_for'   => 'field_statistics',
            'class'       => 'cf-row',
            'group'       => $setting_security,
            'description' => __( 'Log anonymized verification results in the Statistics tab.', 'captchafox-for-forms' ),
        ]);
        add_settings_field('field_collect_ip', __( 'Store IP Addresses', 'captchafox-for-forms' ), [ $this, 'render_checkbox_field' ], 'captchafox-security', $setting_security, [
            'label_for'   => 'field_collect_ip',
            'class'       => 'cf-row',
            'group'       => $setting_security,
            'description' => __( 'Store the visitor IP address with each event so it is shown instead of an anonymized value.', 'captchafox-for-forms' ),
        ]);
        add_settings_field('field_collect_user_agent', __( 'Store User Agents', 'captchafox-for-forms' ), [ $this, 'render_checkbox_field' ], 'captchafox-security', $setting_security, [
            'label_for'   => 'field_collect_user_agent',
            'class'       => 'cf-row',
            'group'       => $setting_security,
            'description' => __( 'Store the visitor user agent with each event so it is shown instead of an anonymized value.', 'captchafox-for-forms' ),
        ]);
    }

    /**
     * Init Section
     *
     * @return void
     */
    public function init_settings_section() {
        ?>
        <p><?php esc_html_e( 'Configure additional spam and abuse protection.', 'captchafox-for-forms' ); ?></p>
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
            settings_fields( 'captchafox_security' );
            do_settings_sections( 'captchafox-security' );
            submit_button( __( 'Save Settings', 'captchafox-for-forms' ) );
            ?>
        </form>
		<?php
    }
}
