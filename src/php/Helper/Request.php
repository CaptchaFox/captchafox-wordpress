<?php

namespace CaptchaFox\Helper;

class Request {

    /**
     * Build a stable validation result object.
     *
     * @param bool     $success Whether validation passed.
     * @param string[] $errors  Error codes.
     *
     * @return object
     */
    private static function result( $success, array $errors = [] ) {
        return (object) [
            'success' => (bool) $success,
            'errors'  => $errors,
        ];
    }

    /**
     * Validate POST request
     *
     * @param string $source Integration that handled the request.
     *
     * @return bool
     */
    public static function validate_post( $source = '' ) {
        if ( CaptchaFox::should_skip_captcha() ) {
            return true;
        }

        if ( CaptchaFox::is_ip_denied() ) {
            Analytics::record_failure( 'ip_denied', $source );
            return false;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing
        if ( ! isset( $_POST['cf-captcha-response'] ) ) {
            return false;
        }

		$response = filter_var( wp_unslash( $_POST['cf-captcha-response'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        return self::validate( $response, $source )->success;
    }

    /**
     * Validate request using response
     *
     * @param string $response Response.
     * @param string $source   Integration that handled the request.
     *
     * @return object
     */
    public static function validate( string $response, $source = '' ) {
        if ( CaptchaFox::should_skip_captcha() ) {
            return self::result( true );
        }

        if ( CaptchaFox::is_ip_denied() ) {
            Analytics::record_failure( 'ip_denied', $source );
            return self::result( false, [ 'ip_denied' ] );
        }

        if ( ! self::passed_honeypot() ) {
            Analytics::record_failure( 'honeypot', $source );
            return self::result( false, [ 'honeypot' ] );
        }

        if ( ! self::passed_min_time() ) {
            Analytics::record_failure( 'min_time', $source );
            return self::result( false, [ 'min_time' ] );
        }

        $response = sanitize_text_field( $response );
        $secret = CaptchaFox::get_secret();
        $url = 'https://api.captchafox.com/siteverify';
        $data = [
            'secret'   => $secret,
            'response' => $response,
        ];

        $post_response = wp_remote_post( $url, array(
            'body' => $data,
        ) );

        if ( is_wp_error( $post_response ) || ! is_array( $post_response ) ) {
            Analytics::record_failure( 'api_error', $source );
            return self::result( false, [ 'api_error' ] );
        }

        $status_code = (int) wp_remote_retrieve_response_code( $post_response );

        if ( $status_code >= 400 ) {
            Analytics::record_failure( 'api_error', $source );
            return self::result( false, [ 'api_error' ] );
        }

        $body = wp_remote_retrieve_body( $post_response );
        $result = json_decode( $body );

        if ( ! is_object( $result ) || ! property_exists( $result, 'success' ) ) {
            Analytics::record_failure( 'api_error', $source );
            return self::result( false, [ 'api_error' ] );
        }

        if ( $result->success ) {
            Analytics::record_pass( $source );
            return self::result( true );
        }

        $errors = [];

        if ( isset( $result->{'error-codes'} ) && is_array( $result->{'error-codes'} ) ) {
            $errors = array_map( 'sanitize_text_field', $result->{'error-codes'} );
        }

        if ( empty( $errors ) ) {
            $errors = [ 'captcha' ];
        }

        Analytics::record_failure( 'captcha', $source );
        return self::result( false, $errors );
    }

    /**
     * Check that the honeypot field was not filled in.
     *
     * Returns true when the honeypot is disabled or empty (the request is
     * allowed to continue), and false when it was filled in (likely a bot).
     *
     * @return bool
     */
    public static function passed_honeypot() {
        if ( ! CaptchaFox::is_honeypot_enabled() ) {
            return true;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $value = isset( $_POST[ CaptchaFox::HONEYPOT_NAME ] ) ?
            sanitize_text_field( wp_unslash( $_POST[ CaptchaFox::HONEYPOT_NAME ] ) ) :
            '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        return '' === $value;
    }

    /**
     * Check that the form was not submitted faster than the configured minimum.
     *
     * Returns true when the time trap is disabled, when the signed timestamp is
     * absent (integrations that do not render it are not penalised), or when
     * enough time has elapsed. Returns false for forged tokens or submissions
     * that arrived too quickly (likely a bot).
     *
     * @return bool
     */
    public static function passed_min_time() {
        $min = CaptchaFox::get_min_time();

        if ( $min <= 0 ) {
            return true;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $value = isset( $_POST[ CaptchaFox::TIMESTAMP_NAME ] ) ?
            sanitize_text_field( wp_unslash( $_POST[ CaptchaFox::TIMESTAMP_NAME ] ) ) :
            '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        if ( '' === $value ) {
            return true;
        }

        $timestamp = CaptchaFox::verify_timestamp( $value );

        if ( null === $timestamp ) {
            return false;
        }

        return ( time() - $timestamp ) >= $min;
    }
}
