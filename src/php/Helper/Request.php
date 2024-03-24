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
        $captcha_success = json_decode( $body );
        if ( $captcha_success->success ) {
            return (object) [
                'success' => true,
                'errors'  => [],
            ];
        }
        return (object) [
            'success' => false,
            'errors'  => $captcha_success->{'error-codes'},
        ];
    }
}
