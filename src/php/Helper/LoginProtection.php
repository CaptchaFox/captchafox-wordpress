<?php

namespace CaptchaFox\Helper;

class LoginProtection {

    /**
     * Prefix for the per-IP attempt transients.
     *
     * @var string
     */
    const TRANSIENT_PREFIX = 'captchafox_login_';

    /**
     * Default interval, in minutes, that failed attempts are remembered.
     *
     * @var int
     */
    const DEFAULT_INTERVAL = 15;

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
        add_action( 'wp_login_failed', [ __CLASS__, 'record_failure' ] );
        add_action( 'wp_login', [ __CLASS__, 'clear_failures' ] );
    }

    /**
     * Number of failed attempts that must occur before the captcha is shown.
     *
     * A value of 0 (or less) means the captcha is always shown.
     *
     * @return int
     */
    public static function get_limit() {
        $options = get_option( 'captchafox_security' );
        $limit = isset( $options['field_login_limit'] ) ? (int) $options['field_login_limit'] : 0;

        return (int) apply_filters( 'capf_login_limit', max( 0, $limit ) );
    }

    /**
     * How long, in seconds, failed attempts are remembered.
     *
     * @return int
     */
    public static function get_interval() {
        $options = get_option( 'captchafox_security' );
        $minutes = isset( $options['field_login_interval'] ) ? (int) $options['field_login_interval'] : self::DEFAULT_INTERVAL;
        $minutes = max( 1, $minutes );

        return (int) apply_filters( 'capf_login_interval', $minutes * MINUTE_IN_SECONDS );
    }

    /**
     * Get the transient key for the current visitor.
     *
     * @return string
     */
    private static function get_key() {
        return self::TRANSIENT_PREFIX . md5( CaptchaFox::get_client_ip() );
    }

    /**
     * Get the number of recent failed attempts for the current visitor.
     *
     * @return int
     */
    public static function get_attempts() {
        if ( '' === CaptchaFox::get_client_ip() ) {
            return 0;
        }

        return (int) get_transient( self::get_key() );
    }

    /**
     * Record a failed login attempt.
     *
     * @return void
     */
    public static function record_failure() {
        if ( '' === CaptchaFox::get_client_ip() ) {
            return;
        }

        set_transient( self::get_key(), self::get_attempts() + 1, self::get_interval() );
    }

    /**
     * Clear the failed attempts after a successful login.
     *
     * @return void
     */
    public static function clear_failures() {
        if ( '' === CaptchaFox::get_client_ip() ) {
            return;
        }

        delete_transient( self::get_key() );
    }

    /**
     * Whether the captcha should be required for the current visitor.
     *
     * @return bool
     */
    public static function is_required() {
        $limit = self::get_limit();

        if ( $limit <= 0 ) {
            return true;
        }

        return self::get_attempts() >= $limit;
    }
}
