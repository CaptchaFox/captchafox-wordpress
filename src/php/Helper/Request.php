<?php

namespace CaptchaFox\Helper;

class Request {
    /**
     * Validate POST request
     *
     * @return bool
     */
    public static function validate_post() {
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
        if ( ! self::passed_honeypot() ) {
            return (object) [
                'success' => false,
                'errors'  => [ 'honeypot' ],
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
}
