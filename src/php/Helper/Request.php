<?php

namespace CaptchaFox\Helper;

class Request {
    /**
     * Validate POST request
     *
     * @return bool
     */
    public static function validate_post() {
        if ( CaptchaFox::is_ip_allowed() ) {
            return true;
        }

        if ( CaptchaFox::is_ip_denied() ) {
            return false;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing
        if ( ! isset( $_POST['cf-captcha-response'] ) ) {
            return false;
        }

		$response = filter_var( wp_unslash( $_POST['cf-captcha-response'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        return self::validate( $response )->success;
    }

    /**
     * Validate request using response
     *
     * @param string $response Response.
     *
     * @return object
     */
    public static function validate( string $response ) {
        if ( CaptchaFox::is_ip_allowed() ) {
            return (object) [
                'success' => true,
                'errors'  => [],
            ];
        }

        if ( CaptchaFox::is_ip_denied() ) {
            return (object) [
                'success' => false,
                'errors'  => [ 'ip_denied' ],
            ];
        }

        if ( ! self::passed_honeypot() ) {
            return (object) [
                'success' => false,
                'errors'  => [ 'honeypot' ],
            ];
        }

        if ( ! self::passed_min_time() ) {
            return (object) [
                'success' => false,
                'errors'  => [ 'min_time' ],
            ];
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

        if ( is_wp_error( $post_response ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $post_response );
        $result = json_decode( $body );
        if ( $result->success ) {
            return (object) [
                'success' => true,
                'errors'  => [],
            ];
        }
        return (object) [
            'success' => false,
            'errors'  => $result->{'error-codes'},
        ];
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
