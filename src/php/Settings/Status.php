<?php

namespace CaptchaFox\Settings;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Helper\LoginProtection;

class Status {

    /**
     * Supported form integrations, keyed by display name.
     *
     * @var array<string, string|callable>
     */
    const INTEGRATIONS = [
        'WooCommerce'    => 'woocommerce/woocommerce.php',
        'Elementor Pro'  => 'elementor-pro/elementor-pro.php',
        'WPForms'        => 'wpforms/wpforms.php',
        'WPForms Lite'   => 'wpforms-lite/wpforms.php',
        'Mailchimp'      => 'mailchimp-for-wp/mailchimp-for-wp.php',
        'Forminator'     => 'forminator/forminator.php',
        'bbPress'        => 'bbpress/bbpress.php',
        'Contact Form 7' => 'contact-form-7/wp-contact-form-7.php',
        'Ninja Forms'    => 'ninja-forms/ninja-forms.php',
        'Gravity Forms'  => 'gravityforms/gravityforms.php',
        'Otter Blocks'   => 'otter-blocks/otter-blocks.php',
        'Fluent Forms'   => 'fluentform/fluentform.php',
    ];

    /**
     * Setup. The status page is read-only, so there is nothing to register.
     *
     * @return void
     */
    public function setup() {}

    /**
     * Get Tab Content
     *
     * @return void
     */
    public function get_tab_content() {
        echo '<div class="cf-status-wrap">';

        foreach ( $this->get_sections() as $title => $rows ) {
            printf( '<h2 class="cf-status-section">%s</h2>', esc_html( $title ) );
            echo '<table class="widefat striped cf-status"><tbody>';

            foreach ( $rows as $row ) {
                // The badge markup is built from a fixed, pre-escaped allowlist.
                printf(
                    '<tr><td><strong>%s</strong></td><td>%s %s</td></tr>',
                    esc_html( $row['label'] ),
                    esc_html( $row['value'] ),
                    $this->badge( $row['status'] ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                );
            }

            echo '</tbody></table>';
        }

        echo '</div>';
    }

    /**
     * Render a status badge.
     *
     * @param string $status One of 'ok', 'warn' or '' for none.
     *
     * @return string
     */
    private function badge( $status ) {
        $labels = [
            'ok'   => __( 'OK', 'captchafox-for-forms' ),
            'warn' => __( 'Check', 'captchafox-for-forms' ),
        ];

        if ( ! isset( $labels[ $status ] ) ) {
            return '';
        }

        return sprintf(
            '<span class="cf-status-badge cf-status-%s">%s</span>',
            esc_attr( $status ),
            esc_html( $labels[ $status ] )
        );
    }

    /**
     * Build all status sections.
     *
     * @return array<string, array<int, array<string, string>>>
     */
    private function get_sections() {
        return [
            __( 'Environment', 'captchafox-for-forms' )   => $this->environment_rows(),
            __( 'Configuration', 'captchafox-for-forms' ) => $this->configuration_rows(),
            __( 'Spam protection', 'captchafox-for-forms' ) => $this->security_rows(),
            __( 'Integrations', 'captchafox-for-forms' )  => $this->integration_rows(),
            __( 'Connectivity', 'captchafox-for-forms' )  => $this->connectivity_rows(),
        ];
    }

    /**
     * Environment rows.
     *
     * @return array<int, array<string, string>>
     */
    private function environment_rows() {
        $wp_version = get_bloginfo( 'version' );

        return [
            [
                'label'  => __( 'Plugin version', 'captchafox-for-forms' ),
                'value'  => PLUGIN_VERSION,
                'status' => '',
            ],
            [
                'label'  => __( 'PHP version', 'captchafox-for-forms' ),
                'value'  => PHP_VERSION,
                'status' => version_compare( PHP_VERSION, '7.0', '>=' ) ? 'ok' : 'warn',
            ],
            [
                'label'  => __( 'WordPress version', 'captchafox-for-forms' ),
                'value'  => $wp_version,
                'status' => version_compare( $wp_version, '5.0', '>=' ) ? 'ok' : 'warn',
            ],
        ];
    }

    /**
     * Configuration rows.
     *
     * @return array<int, array<string, string>>
     */
    private function configuration_rows() {
        $has_sitekey = '' !== CaptchaFox::get_sitekey();
        $has_secret = '' !== CaptchaFox::get_secret();

        return [
            [
                'label'  => __( 'Site key', 'captchafox-for-forms' ),
                'value'  => $has_sitekey ? __( 'Set', 'captchafox-for-forms' ) : __( 'Not set', 'captchafox-for-forms' ),
                'status' => $has_sitekey ? 'ok' : 'warn',
            ],
            [
                'label'  => __( 'Secret key', 'captchafox-for-forms' ),
                'value'  => $has_secret ? __( 'Set', 'captchafox-for-forms' ) : __( 'Not set', 'captchafox-for-forms' ),
                'status' => $has_secret ? 'ok' : 'warn',
            ],
            [
                'label'  => __( 'Script loading', 'captchafox-for-forms' ),
                'value'  => CaptchaFox::is_delayed() ?
                    __( 'On first interaction', 'captchafox-for-forms' ) :
                    __( 'Immediately', 'captchafox-for-forms' ),
                'status' => '',
            ],
        ];
    }

    /**
     * Spam protection rows.
     *
     * @return array<int, array<string, string>>
     */
    private function security_rows() {
        $options = get_option( 'captchafox_security' );
        $min_time = CaptchaFox::get_min_time();
        $limit = LoginProtection::get_limit();
        $skip_logged_in = isset( $options['field_skip_logged_in'] ) && '1' === $options['field_skip_logged_in'];

        return [
            [
                'label'  => __( 'Honeypot', 'captchafox-for-forms' ),
                'value'  => $this->enabled_label( CaptchaFox::is_honeypot_enabled() ),
                'status' => '',
            ],
            [
                'label'  => __( 'Minimum submission time', 'captchafox-for-forms' ),
                'value'  => $min_time > 0 ?
                    sprintf(
                        /* translators: %d: number of seconds. */
                        _n( '%d second', '%d seconds', $min_time, 'captchafox-for-forms' ),
                        $min_time
                    ) :
                    __( 'Disabled', 'captchafox-for-forms' ),
                'status' => '',
            ],
            [
                'label'  => __( 'IP allowlist', 'captchafox-for-forms' ),
                'value'  => $this->entries_label( count( CaptchaFox::get_allowlist() ) ),
                'status' => '',
            ],
            [
                'label'  => __( 'IP denylist', 'captchafox-for-forms' ),
                'value'  => $this->entries_label( count( CaptchaFox::get_denylist() ) ),
                'status' => '',
            ],
            [
                'label'  => __( 'Login protection', 'captchafox-for-forms' ),
                'value'  => $limit > 0 ?
                    sprintf(
                        /* translators: %d: number of failed login attempts. */
                        _n( 'After %d attempt', 'After %d attempts', $limit, 'captchafox-for-forms' ),
                        $limit
                    ) :
                    __( 'Captcha always shown', 'captchafox-for-forms' ),
                'status' => '',
            ],
            [
                'label'  => __( 'Skip for logged-in users', 'captchafox-for-forms' ),
                'value'  => $this->enabled_label( $skip_logged_in ),
                'status' => '',
            ],
        ];
    }

    /**
     * Integration rows.
     *
     * @return array<int, array<string, string>>
     */
    private function integration_rows() {
        $active = $this->active_integrations();

        return [
            [
                'label'  => __( 'Detected form plugins', 'captchafox-for-forms' ),
                'value'  => empty( $active ) ?
                    __( 'None detected', 'captchafox-for-forms' ) :
                    implode( ', ', $active ),
                'status' => '',
            ],
        ];
    }

    /**
     * Connectivity rows.
     *
     * @return array<int, array<string, string>>
     */
    private function connectivity_rows() {
        $reachable = $this->api_reachable();

        return [
            [
                'label'  => __( 'CaptchaFox API', 'captchafox-for-forms' ),
                'value'  => $reachable ?
                    __( 'Reachable', 'captchafox-for-forms' ) :
                    __( 'Unreachable', 'captchafox-for-forms' ),
                'status' => $reachable ? 'ok' : 'warn',
            ],
        ];
    }

    /**
     * Get the names of the active supported form plugins.
     *
     * @return string[]
     */
    private function active_integrations() {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $active = [];

        foreach ( self::INTEGRATIONS as $name => $basename ) {
            if ( is_plugin_active( $basename ) ) {
                $active[] = $name;
            }
        }

        if ( 'Avada' === get_template() ) {
            $active[] = 'Avada';
        }

        return $active;
    }

    /**
     * Whether the CaptchaFox CDN is reachable from the server.
     *
     * @return bool
     */
    private function api_reachable() {
        $response = wp_remote_head( 'https://cdn.captchafox.com/api.js', [ 'timeout' => 5 ] );

        return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) < 400;
    }

    /**
     * Enabled / Disabled label.
     *
     * @param bool $enabled Whether the feature is enabled.
     *
     * @return string
     */
    private function enabled_label( $enabled ) {
        return $enabled ?
            __( 'Enabled', 'captchafox-for-forms' ) :
            __( 'Disabled', 'captchafox-for-forms' );
    }

    /**
     * "N entries" label.
     *
     * @param int $count Number of entries.
     *
     * @return string
     */
    private function entries_label( $count ) {
        return sprintf(
            /* translators: %d: number of entries. */
            _n( '%d entry', '%d entries', $count, 'captchafox-for-forms' ),
            $count
        );
    }
}
