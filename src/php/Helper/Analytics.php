<?php

namespace CaptchaFox\Helper;

/**
 * Records spam protection analytics in a dedicated database table.
 */
class Analytics {

    /**
     * Unprefixed table name.
     *
     * @var string
     */
    const TABLE = 'captchafox_events';

    /**
     * Schema version. Bump to trigger an upgrade via maybe_create_table().
     *
     * @var string
     */
    const DB_VERSION = '5';

    /**
     * Option storing the installed schema version.
     *
     * @var string
     */
    const DB_VERSION_OPTION = 'captchafox_db_version';

    /**
     * Number of recent block events shown in the log.
     *
     * @var int
     */
    const RECENT_LIMIT = 50;

    /**
     * Default number of days to keep events.
     *
     * @var int
     */
    const RETENTION_DAYS = 14;

    /**
     * Cron hook used for event retention cleanup.
     *
     * @var string
     */
    const RETENTION_HOOK = 'captchafox_prune_events';

    /**
     * Known failure reasons.
     *
     * @var string[]
     */
    const REASONS = [ 'ip_denied', 'honeypot', 'min_time', 'captcha', 'api_error' ];

    /**
     * Fully qualified table name.
     *
     * @return string
     */
    public static function table_name() {
        global $wpdb;

        return $wpdb->prefix . self::TABLE;
    }

    /**
     * Whether analytics are recorded. Opt-in via the Security settings and can
     * be forced off/on with the `capf_record_events` filter.
     *
     * @return bool
     */
    public static function is_enabled() {
        $options = get_option( 'captchafox_security' );
        $enabled = isset( $options['field_statistics'] ) && '1' === $options['field_statistics'];

        return (bool) apply_filters( 'capf_record_events', $enabled );
    }

    /**
     * Whether raw IP addresses are stored instead of an anonymized hash.
     *
     * @return bool
     */
    public static function is_collect_ip() {
        $options = get_option( 'captchafox_security' );
        $enabled = isset( $options['field_collect_ip'] ) && '1' === $options['field_collect_ip'];

        return (bool) apply_filters( 'capf_collect_ip', $enabled );
    }

    /**
     * Whether raw user agents are stored instead of an anonymized hash.
     *
     * @return bool
     */
    public static function is_collect_user_agent() {
        $options = get_option( 'captchafox_security' );
        $enabled = isset( $options['field_collect_user_agent'] ) && '1' === $options['field_collect_user_agent'];

        return (bool) apply_filters( 'capf_collect_user_agent', $enabled );
    }

    /**
     * Create the events table. Safe to call repeatedly (dbDelta is idempotent).
     *
     * @return bool Whether the table exists after creation.
     */
    public static function create_table() {
        global $wpdb;

        if ( ! isset( $wpdb ) ) {
            return false;
        }

        if ( ! function_exists( 'dbDelta' ) ) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            success       TINYINT(1)      NOT NULL DEFAULT 0,
            reason        VARCHAR(20)     NOT NULL DEFAULT '',
            source        VARCHAR(64)     NOT NULL DEFAULT '',
            form_id       VARCHAR(64)     NOT NULL DEFAULT '',
            ip            VARCHAR(45)     NOT NULL DEFAULT '',
            ip_anonymized TINYINT(1)      NOT NULL DEFAULT 1,
            user_agent    VARCHAR(255)    NOT NULL DEFAULT '',
            ua_anonymized TINYINT(1)      NOT NULL DEFAULT 1,
            date_gmt      DATETIME        NOT NULL,
            PRIMARY KEY (id),
            KEY success (success),
            KEY date_gmt (date_gmt)
        ) $charset_collate";

        dbDelta( $sql );

        if ( ! self::table_exists() ) {
            return false;
        }

        // dbDelta is unreliable at adding columns to a pre-existing table, so
        // reconcile the schema explicitly as a fallback.
        self::ensure_columns();

        update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
        self::schedule_retention();

        return true;
    }

    /**
     * Whether the events table exists.
     *
     * @return bool
     */
    private static function table_exists() {
        global $wpdb;

        if ( ! isset( $wpdb ) ) {
            return false;
        }

        $table = self::table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
    }

    /**
     * Add any expected columns that are missing from the table. Hardcoded
     * definitions, never user input.
     *
     * @return void
     */
    private static function ensure_columns() {
        global $wpdb;

        if ( ! self::table_exists() ) {
            return;
        }

        $table = self::table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $existing = $wpdb->get_col( "SHOW COLUMNS FROM $table" );

        if ( ! is_array( $existing ) ) {
            return;
        }

        $columns = [
            'success'       => 'TINYINT(1) NOT NULL DEFAULT 0',
            'reason'        => "VARCHAR(20) NOT NULL DEFAULT ''",
            'source'        => "VARCHAR(64) NOT NULL DEFAULT ''",
            'form_id'       => "VARCHAR(64) NOT NULL DEFAULT ''",
            'ip'            => "VARCHAR(45) NOT NULL DEFAULT ''",
            'ip_anonymized' => 'TINYINT(1) NOT NULL DEFAULT 1',
            'user_agent'    => "VARCHAR(255) NOT NULL DEFAULT ''",
            'ua_anonymized' => 'TINYINT(1) NOT NULL DEFAULT 1',
            'date_gmt'      => 'DATETIME NOT NULL',
        ];

        foreach ( $columns as $name => $definition ) {
            if ( in_array( $name, $existing, true ) ) {
                continue;
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query( "ALTER TABLE $table ADD COLUMN $name $definition" );
        }
    }

    /**
     * Create or upgrade the table when the schema version changed. Hooked on
     * admin_init so existing installs pick up schema changes without a manual
     * reactivation.
     *
     * @return void
     */
    public static function maybe_create_table() {
        if ( get_option( self::DB_VERSION_OPTION ) === self::DB_VERSION ) {
            return;
        }

        self::create_table();
    }

    /**
     * Schedule the daily retention cleanup if it is not scheduled already.
     *
     * @return void
     */
    public static function schedule_retention() {
        if ( ! function_exists( 'wp_next_scheduled' ) || ! function_exists( 'wp_schedule_event' ) ) {
            return;
        }

        if ( ! wp_next_scheduled( self::RETENTION_HOOK ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::RETENTION_HOOK );
        }
    }

    /**
     * Clear the scheduled retention cleanup.
     *
     * @return void
     */
    public static function clear_retention_schedule() {
        if ( ! function_exists( 'wp_next_scheduled' ) || ! function_exists( 'wp_unschedule_event' ) ) {
            return;
        }

        $timestamp = wp_next_scheduled( self::RETENTION_HOOK );

        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::RETENTION_HOOK );
        }
    }

    /**
     * Get the retention window in days.
     *
     * @return int
     */
    public static function retention_days() {
        return max( 1, (int) apply_filters( 'capf_event_retention_days', self::RETENTION_DAYS ) );
    }

    /**
     * Delete events older than the retention window.
     *
     * @param int|null $days Retention window override.
     *
     * @return void
     */
    public static function prune_old_events( $days = null ) {
        global $wpdb;

        if ( ! isset( $wpdb ) || ! self::table_exists() ) {
            return;
        }

        $days = null === $days ? self::retention_days() : max( 1, (int) $days );
        $cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
        $table = self::table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE date_gmt < %s", $cutoff ) );
    }

    /**
     * Record a successful verification.
     *
     * @param string $source Integration that handled the request.
     *
     * @return void
     */
    public static function record_pass( $source = '' ) {
        self::insert( true, '', $source );
    }

    /**
     * Record a blocked/failed verification.
     *
     * @param string $reason One of self::REASONS.
     * @param string $source Integration that handled the request.
     *
     * @return void
     */
    public static function record_failure( $reason, $source = '' ) {
        $reason = in_array( $reason, self::REASONS, true ) ? $reason : 'captcha';

        self::insert( false, $reason, $source );
    }

    /**
     * Insert an event row.
     *
     * @param bool   $success Whether the verification passed.
     * @param string $reason  Failure reason (empty for a pass).
     * @param string $source  Integration that handled the request.
     *
     * @return void
     */
    private static function insert( $success, $reason, $source = '' ) {
        global $wpdb;

        if ( ! self::is_enabled() || ! isset( $wpdb ) ) {
            return;
        }

        if ( get_option( self::DB_VERSION_OPTION ) !== self::DB_VERSION ) {
            self::create_table();
        }

        if ( ! self::table_exists() ) {
            return;
        }

        $ip = CaptchaFox::get_client_ip();
        $agent = self::user_agent();
        $collect_ip = self::is_collect_ip();
        $collect_ua = self::is_collect_user_agent();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->insert(
            self::table_name(),
            [
                'success'       => $success ? 1 : 0,
                'reason'        => $reason,
                'source'        => substr( (string) $source, 0, 64 ),
                'form_id'       => self::form_id(),
                'ip'            => $collect_ip ? $ip : self::hash_value( $ip ),
                'ip_anonymized' => $collect_ip ? 0 : 1,
                'user_agent'    => $collect_ua ? $agent : self::hash_value( $agent ),
                'ua_anonymized' => $collect_ua ? 0 : 1,
                'date_gmt'      => gmdate( 'Y-m-d H:i:s' ),
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s' ]
        );
    }

    /**
     * Best-effort detection of the submitted form's identifier, covering the
     * common form plugins. Refine or override with the `capf_event_form_id`
     * filter.
     *
     * @return string
     */
    private static function form_id() {
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $candidates = [
            '_wpcf7',          // Contact Form 7.
            'form_id',         // Forminator, Elementor, Fluent Forms, generic.
            'gform_submit',    // Gravity Forms.
        ];

        $form_id = '';

        foreach ( $candidates as $key ) {
            if ( isset( $_POST[ $key ] ) && '' !== $_POST[ $key ] ) {
                $form_id = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
                break;
            }
        }

        // WPForms nests the id under wpforms[id].
        if ( '' === $form_id && isset( $_POST['wpforms']['id'] ) ) {
            $form_id = sanitize_text_field( wp_unslash( $_POST['wpforms']['id'] ) );
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        $form_id = (string) apply_filters( 'capf_event_form_id', $form_id );

        return substr( $form_id, 0, 64 );
    }

    /**
     * Hash an identifying value (IP address, user agent) so events can be
     * correlated without ever storing the raw, personal data.
     *
     * @param string $value Value to hash.
     *
     * @return string
     */
    private static function hash_value( $value ) {
        if ( '' === $value ) {
            return '';
        }

        return function_exists( 'wp_hash' ) ? wp_hash( $value ) : md5( $value );
    }

    /**
     * Get the visitor's user agent string.
     *
     * @return string
     */
    private static function user_agent() {
        if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
            return '';
        }

        return sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
    }

    /**
     * Get the aggregate counters.
     *
     * @return array{passed:int,failed:int,reasons:array<string,int>}
     */
    public static function get_stats() {
        global $wpdb;

        $stats = [
            'passed'  => 0,
            'failed'  => 0,
            'reasons' => array_fill_keys( self::REASONS, 0 ),
        ];

        if ( ! isset( $wpdb ) ) {
            return $stats;
        }

        if ( get_option( self::DB_VERSION_OPTION ) !== self::DB_VERSION ) {
            self::create_table();
        }

        if ( ! self::table_exists() ) {
            return $stats;
        }

        $table = self::table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $stats['passed'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE success = 1" );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $stats['failed'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE success = 0" );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results( "SELECT reason, COUNT(*) AS total FROM $table WHERE success = 0 GROUP BY reason", ARRAY_A );

        if ( is_array( $rows ) ) {
            foreach ( $rows as $row ) {
                if ( isset( $stats['reasons'][ $row['reason'] ] ) ) {
                    $stats['reasons'][ $row['reason'] ] = (int) $row['total'];
                }
            }
        }

        return $stats;
    }

    /**
     * Get the most recent events, newest first.
     *
     * @param int  $limit          Maximum number of events.
     * @param bool $include_passes Whether to include successful verifications.
     *
     * @return array<int, array{time:int,success:bool,reason:string,source:string,form_id:string,ip:string,ip_anonymized:bool,user_agent:string,ua_anonymized:bool}>
     */
    public static function get_events( $limit = self::RECENT_LIMIT, $include_passes = false ) {
        global $wpdb;

        if ( ! isset( $wpdb ) ) {
            return [];
        }

        if ( get_option( self::DB_VERSION_OPTION ) !== self::DB_VERSION ) {
            self::create_table();
        }

        if ( ! self::table_exists() ) {
            return [];
        }

        $table = self::table_name();
        $where = $include_passes ? '' : 'WHERE success = 0';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->prepare( "SELECT date_gmt, success, reason, source, form_id, ip, ip_anonymized, user_agent, ua_anonymized FROM $table $where ORDER BY id DESC LIMIT %d", (int) $limit ),
            ARRAY_A
        );

        if ( ! is_array( $rows ) ) {
            return [];
        }

        return array_map(
            static function ( $row ) {
                return [
                    'time'          => (int) strtotime( $row['date_gmt'] . ' UTC' ),
                    'success'       => (bool) $row['success'],
                    'reason'        => (string) $row['reason'],
                    'source'        => (string) $row['source'],
                    'form_id'       => (string) $row['form_id'],
                    'ip'            => (string) $row['ip'],
                    'ip_anonymized' => (bool) $row['ip_anonymized'],
                    'user_agent'    => (string) $row['user_agent'],
                    'ua_anonymized' => (bool) $row['ua_anonymized'],
                ];
            },
            $rows
        );
    }

    /**
     * Clear all recorded events.
     *
     * @return void
     */
    public static function reset() {
        global $wpdb;

        if ( ! isset( $wpdb ) || ! self::table_exists() ) {
            return;
        }

        $table = self::table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query( "DELETE FROM $table" );
    }
}
