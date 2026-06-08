<?php

namespace DuckRace\Services;

defined( 'ABSPATH' ) || exit;

class StripeService {

    /**
     * @return array{session_id:string, checkout_url:string}
     */
    public function create_checkout_session( int $purchase_id, float $amount_gbp, string $description, string $payment_ref = '' ): array {
        $settings = get_option( 'duck_race_settings', [] );
        $secret_key = (string) ( $settings['stripe_secret_key'] ?? '' );

        if ( '' === $secret_key ) {
            return $this->fallback_local_session( $purchase_id );
        }

        $success_url = add_query_arg( [ 'purchase_id' => $purchase_id ], home_url( '/duck-race-success' ) );
        $cancel_url = add_query_arg( [ 'purchase_id' => $purchase_id ], home_url( '/duck-race-failure' ) );

        $body = [
            'mode' => 'payment',
            'success_url' => $success_url,
            'cancel_url' => $cancel_url,
            'client_reference_id' => (string) $purchase_id,
            'metadata[purchase_id]' => (string) $purchase_id,
            'payment_intent_data[description]' => $payment_ref ?: $description,
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => 'gbp',
            'line_items[0][price_data][product_data][name]' => $description,
            'line_items[0][price_data][unit_amount]' => (string) max( 1, (int) round( $amount_gbp * 100 ) ),
        ];

        $response = wp_remote_post(
            'https://api.stripe.com/v1/checkout/sessions',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secret_key,
                ],
                'body' => $body,
                'timeout' => 20,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $this->fallback_local_session( $purchase_id );
        }

        $status = (int) wp_remote_retrieve_response_code( $response );
        $decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $status || ! is_array( $decoded ) || empty( $decoded['id'] ) || empty( $decoded['url'] ) ) {
            return $this->fallback_local_session( $purchase_id );
        }

        return [
            'session_id' => sanitize_text_field( (string) $decoded['id'] ),
            'checkout_url' => esc_url_raw( (string) $decoded['url'] ),
        ];
    }

    public function verify_webhook_signature( string $raw_body, string $provided_signature, string $secret ): bool {
        if ( '' === $secret || '' === $provided_signature || '' === $raw_body ) {
            return false;
        }

        $parts = [];
        foreach ( explode( ',', $provided_signature ) as $segment ) {
            [ $k, $v ] = array_pad( explode( '=', trim( $segment ), 2 ), 2, '' );
            $parts[ $k ] = $v;
        }

        $timestamp = $parts['t'] ?? '';
        $signature = $parts['v1'] ?? '';
        if ( '' === $timestamp || '' === $signature ) {
            return false;
        }

        $signed_payload = $timestamp . '.' . $raw_body;
        $expected = hash_hmac( 'sha256', $signed_payload, $secret );

        return hash_equals( $expected, $signature );
    }

    /**
     * @return array{session_id:string, checkout_url:string}
     */
    private function fallback_local_session( int $purchase_id ): array {
        $session_id = 'dr_sess_' . wp_generate_password( 24, false, false );
        $checkout_url = add_query_arg(
            [ 'purchase_id' => $purchase_id, 'session_id' => $session_id ],
            home_url( '/duck-race-success' )
        );

        return [
            'session_id' => $session_id,
            'checkout_url' => $checkout_url,
        ];
    }
}
