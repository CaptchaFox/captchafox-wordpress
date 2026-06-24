<?php

namespace CaptchaFox\Settings;

use CaptchaFox\Helper\Statistics;

class Events {

    /**
     * Setup. Registered at bootstrap so the reset action is handled before the
     * page is rendered.
     *
     * @return void
     */
    public function setup() {
        add_action( 'admin_init', [ $this, 'handle_reset' ] );
    }

    /**
     * Handle the "clear statistics" request.
     *
     * @return void
     */
    public function handle_reset() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( ! isset( $_POST['captchafox_action'] ) || 'reset_stats' !== $_POST['captchafox_action'] ) {
            return;
        }

        check_admin_referer( 'captchafox_reset_stats' );

        Statistics::reset();

        wp_safe_redirect(
            add_query_arg( 'cf_stats', 'cleared', admin_url( 'admin.php?page=captchafox-stats' ) )
        );
        exit;
    }

    /**
     * Human readable labels for the failure reasons.
     *
     * @return array<string, string>
     */
    private function reason_labels() {
        return [
            'ip_denied' => __( 'IP denylist', 'captchafox-for-forms' ),
            'honeypot'  => __( 'Honeypot', 'captchafox-for-forms' ),
            'min_time'  => __( 'Too fast', 'captchafox-for-forms' ),
            'captcha'   => __( 'Failed captcha', 'captchafox-for-forms' ),
            'api_error' => __( 'API error', 'captchafox-for-forms' ),
        ];
    }

    /**
     * Get Tab Content
     *
     * @return void
     */
    public function get_tab_content() {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $cleared = isset( $_GET['cf_stats'] ) && 'cleared' === $_GET['cf_stats'];
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if ( $cleared ) {
            printf(
                '<div class="notice notice-success"><p>%s</p></div>',
                esc_html__( 'Statistics cleared.', 'captchafox-for-forms' )
            );
        }

        if ( ! Statistics::is_enabled() ) {
            printf(
                '<div class="notice notice-info"><p>%s</p></div>',
                sprintf(
                    /* translators: %s: link to the Security settings tab. */
                    esc_html__( 'Statistics are not being recorded. Enable %s to start collecting data.', 'captchafox-for-forms' ),
                    '<a href="' . esc_url( admin_url( 'admin.php?page=captchafox-security' ) ) . '">' . esc_html__( 'Record Statistics', 'captchafox-for-forms' ) . '</a>'
                )
            );
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $show_all = isset( $_GET['cf_show'] ) && 'all' === $_GET['cf_show'];
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        $stats = Statistics::get_stats();
        $events = Statistics::get_events( Statistics::RECENT_LIMIT, $show_all );
        $total = $stats['passed'] + $stats['failed'];
        $block_rate = $total > 0 ? round( ( $stats['failed'] / $total ) * 100 ) : 0;

        echo '<div class="cf-stats-wrap">';

        $this->render_cards( $total, $stats, $block_rate );
        $this->render_reasons( $stats );
        $this->render_events( $events, $show_all );
        $this->render_reset();

        echo '</div>';
    }

    /**
     * Render the summary cards.
     *
     * @param int   $total      Total verifications.
     * @param array $stats      Aggregate counters.
     * @param int   $block_rate Percentage blocked.
     *
     * @return void
     */
    private function render_cards( $total, $stats, $block_rate ) {
        $cards = [
            [ __( 'Verifications', 'captchafox-for-forms' ), $total ],
            [ __( 'Passed', 'captchafox-for-forms' ), $stats['passed'] ],
            [ __( 'Blocked', 'captchafox-for-forms' ), $stats['failed'] ],
            [ __( 'Block rate', 'captchafox-for-forms' ), $block_rate . '%' ],
        ];

        echo '<div class="cf-stats-cards">';

        foreach ( $cards as $card ) {
            printf(
                '<div class="cf-stats-card"><span class="cf-stats-num">%s</span><span class="cf-stats-label">%s</span></div>',
                esc_html( $card[1] ),
                esc_html( $card[0] )
            );
        }

        echo '</div>';
    }

    /**
     * Render the per-reason breakdown.
     *
     * @param array $stats Aggregate counters.
     *
     * @return void
     */
    private function render_reasons( $stats ) {
        printf( '<h2 class="cf-status-section">%s</h2>', esc_html__( 'Blocked by reason', 'captchafox-for-forms' ) );
        echo '<table class="widefat striped cf-status"><tbody>';

        foreach ( $this->reason_labels() as $key => $label ) {
            printf(
                '<tr><td><strong>%s</strong></td><td>%s</td></tr>',
                esc_html( $label ),
                esc_html( $stats['reasons'][ $key ] )
            );
        }

        echo '</tbody></table>';
    }

    /**
     * Render the recent events log.
     *
     * @param array $events   Recent events.
     * @param bool  $show_all Whether passes are included.
     *
     * @return void
     */
    private function render_events( $events, $show_all ) {
        $labels = $this->reason_labels();

        echo '<div class="cf-stats-events-head">';
        printf( '<h2 class="cf-status-section">%s</h2>', esc_html__( 'Recent events', 'captchafox-for-forms' ) );
        $this->render_events_toggle( $show_all );
        echo '</div>';

        if ( empty( $events ) ) {
            printf( '<p>%s</p>', esc_html__( 'No events recorded yet.', 'captchafox-for-forms' ) );
            return;
        }

        echo '<table class="widefat striped cf-status"><thead><tr>';
        printf( '<th>%s</th>', esc_html__( 'Time', 'captchafox-for-forms' ) );
        printf( '<th>%s</th>', esc_html__( 'Result', 'captchafox-for-forms' ) );
        printf( '<th>%s</th>', esc_html__( 'Form', 'captchafox-for-forms' ) );
        printf( '<th>%s</th>', esc_html__( 'IP address', 'captchafox-for-forms' ) );
        printf( '<th>%s</th>', esc_html__( 'User agent', 'captchafox-for-forms' ) );
        echo '</tr></thead><tbody>';

        $sources = $this->source_labels();
        $passed = __( 'Passed', 'captchafox-for-forms' );

        foreach ( $events as $event ) {
            if ( $event['success'] ) {
                $result = sprintf( '<span class="cf-status-badge cf-status-ok">%s</span>', esc_html( $passed ) );
            } else {
                $reason = isset( $labels[ $event['reason'] ] ) ? $labels[ $event['reason'] ] : $event['reason'];
                $result = sprintf( '<span class="cf-status-badge cf-status-warn">%s</span>', esc_html( $reason ) );
            }

            printf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                esc_html( $this->format_time( $event['time'] ) ),
                wp_kses_post( $result ),
                esc_html( $this->form_label( $event, $sources ) ),
                esc_html( $this->privacy_value( $event['ip'], $event['ip_anonymized'] ) ),
                esc_html( $this->privacy_value( $event['user_agent'], $event['ua_anonymized'] ) )
            );
        }

        echo '</tbody></table>';
    }

    /**
     * Render the blocks-only / all-events toggle.
     *
     * @param bool $show_all Whether passes are currently included.
     *
     * @return void
     */
    private function render_events_toggle( $show_all ) {
        $base = admin_url( 'admin.php?page=captchafox-stats' );
        $url = $show_all ? $base : add_query_arg( 'cf_show', 'all', $base );

        printf(
            '<a class="cf-switch-link%1$s" href="%2$s" role="switch" aria-checked="%3$s"><span class="cf-switch-label">%4$s</span><span class="cf-switch-track"></span></a>',
            esc_attr( $show_all ? ' is-on' : '' ),
            esc_url( $url ),
            esc_attr( $show_all ? 'true' : 'false' ),
            esc_html__( 'Show passes', 'captchafox-for-forms' )
        );
    }

    /**
     * Build the "Form" cell from the event source and form id.
     *
     * @param array                 $event   Event row.
     * @param array<string, string> $sources Source label map.
     *
     * @return string
     */
    private function form_label( $event, $sources ) {
        $source = isset( $sources[ $event['source'] ] ) ? $sources[ $event['source'] ] : $event['source'];

        if ( '' === $source ) {
            $source = __( 'Unknown', 'captchafox-for-forms' );
        }

        return '' !== $event['form_id'] ? $source . ' #' . $event['form_id'] : $source;
    }

    /**
     * Display an IP / user agent value: the stored raw value, or an
     * "Anonymized" placeholder when the row was stored as a hash.
     *
     * @param string $value      Stored value (raw or hash).
     * @param bool   $anonymized Whether the stored value is a hash.
     *
     * @return string
     */
    private function privacy_value( $value, $anonymized ) {
        if ( '' === $value ) {
            return __( 'Unknown', 'captchafox-for-forms' );
        }

        return $anonymized ? __( 'Anonymized', 'captchafox-for-forms' ) : $value;
    }

    /**
     * Human readable labels for the integration sources.
     *
     * @return array<string, string>
     */
    private function source_labels() {
        return [
            'contact-form-7'            => 'Contact Form 7',
            'wpforms'                   => 'WPForms',
            'ninja-forms'               => 'Ninja Forms',
            'gravity-forms'             => 'Gravity Forms',
            'forminator'                => 'Forminator',
            'fluent-forms'              => 'Fluent Forms',
            'elementor'                 => 'Elementor',
            'avada-forms'               => 'Avada',
            'mailchimp'                 => 'Mailchimp',
            'woocommerce-login'         => 'WooCommerce (Login)',
            'woocommerce-register'      => 'WooCommerce (Register)',
            'woocommerce-checkout'      => 'WooCommerce (Checkout)',
            'woocommerce-lost-password' => 'WooCommerce (Lost password)',
            'wordpress-login'           => 'WordPress (Login)',
            'wordpress-register'        => 'WordPress (Register)',
            'wordpress-comment'         => 'WordPress (Comment)',
            'wordpress-lost-password'   => 'WordPress (Lost password)',
            'bbpress-reply'             => 'bbPress (Reply)',
            'bbpress-new-topic'         => 'bbPress (New topic)',
        ];
    }

    /**
     * Render the reset form.
     *
     * @return void
     */
    private function render_reset() {
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=captchafox-stats' ) ); ?>" class="cf-stats-reset">
            <?php wp_nonce_field( 'captchafox_reset_stats' ); ?>
            <input type="hidden" name="captchafox_action" value="reset_stats">
            <button type="submit" class="cf-button"><?php esc_html_e( 'Clear statistics', 'captchafox-for-forms' ); ?></button>
        </form>
        <?php
    }

    /**
     * Format an event timestamp using the site's locale and timezone.
     *
     * @param int $timestamp Unix timestamp.
     *
     * @return string
     */
    private function format_time( $timestamp ) {
        $format = get_option( 'date_format', 'Y-m-d' ) . ' ' . get_option( 'time_format', 'H:i' );

        if ( function_exists( 'wp_date' ) ) {
            return wp_date( $format, $timestamp );
        }

        return gmdate( $format, $timestamp );
    }
}
