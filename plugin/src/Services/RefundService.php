<?php

namespace DuckRace\Services;

defined( 'ABSPATH' ) || exit;

class RefundService {

    /**
     * Process a refund for a purchase.
     *
     * For online purchases, calls the Stripe Refunds API.
     * For manual purchases, skips Stripe and marks the purchase as refunded directly.
     *
     * @return array{ok:bool, error?:string}
     */
    public function process( int $purchase_id, string $reason = '' ): array {
        $purchase_service = new PurchaseService();
        $purchase         = $purchase_service->get_by_id( $purchase_id );

        if ( ! is_object( $purchase ) ) {
            return [ 'ok' => false, 'error' => 'purchase_not_found' ];
        }

        if ( 'paid' !== (string) $purchase->payment_status ) {
            return [ 'ok' => false, 'error' => 'not_refundable' ];
        }

        $amount = (float) $purchase->grand_total;

        if ( 'online' === (string) $purchase->purchase_source ) {
            $result = $this->call_stripe_refund( $purchase, $reason );
            if ( ! $result['ok'] ) {
                return $result;
            }
            $refund_id = $result['refund_id'];
        } else {
            $refund_id = 'manual_refund';
        }

        $purchase_service->mark_refunded( $purchase_id, $refund_id, $amount, $reason, 'admin' );

        return [ 'ok' => true ];
    }

    /**
     * @param object $purchase
     * @return array{ok:bool, refund_id?:string, error?:string}
     */
    private function call_stripe_refund( object $purchase, string $reason ): array {
        $settings   = get_option( 'duck_race_settings', [] );
        $secret_key = (string) ( $settings['stripe_secret_key'] ?? '' );

        if ( '' === $secret_key ) {
            return [ 'ok' => false, 'error' => 'stripe_not_configured' ];
        }

        $charge_id  = (string) ( $purchase->stripe_charge_id ?? '' );
        $intent_id  = (string) ( $purchase->stripe_payment_intent_id ?? '' );
        $identifier = $charge_id ?: $intent_id;

        if ( '' === $identifier ) {
            return [ 'ok' => false, 'error' => 'no_stripe_identifier' ];
        }

        $body = $charge_id ? [ 'charge' => $charge_id ] : [ 'payment_intent' => $intent_id ];
        if ( '' !== trim( $reason ) ) {
            $body['reason'] = 'requested_by_customer';
            $body['metadata[reason]'] = sanitize_text_field( $reason );
        }

        $response = wp_remote_post(
            'https://api.stripe.com/v1/refunds',
            [
                'headers' => [ 'Authorization' => 'Bearer ' . $secret_key ],
                'body'    => $body,
                'timeout' => 20,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return [ 'ok' => false, 'error' => 'stripe_request_failed' ];
        }

        $status  = (int) wp_remote_retrieve_response_code( $response );
        $decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $status || ! is_array( $decoded ) || empty( $decoded['id'] ) ) {
            $stripe_error = is_array( $decoded ) ? (string) ( $decoded['error']['message'] ?? 'unknown' ) : 'unknown';
            return [ 'ok' => false, 'error' => $stripe_error ];
        }

        return [ 'ok' => true, 'refund_id' => sanitize_text_field( (string) $decoded['id'] ) ];
    }
}
