<?php

namespace DuckRace\Rest;

use DuckRace\Services\PurchaseService;
use DuckRace\Services\StripeService;

defined( 'ABSPATH' ) || exit;

class StripeWebhookRoute {

    public function register(): void {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        register_rest_route(
            'duck-race/v1',
            '/stripe-webhook',
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [ $this, 'handle' ],
                'permission_callback' => '__return_true',
            ]
        );
    }

    public function handle( \WP_REST_Request $request ): \WP_REST_Response {
        $raw = (string) $request->get_body();
        $signature = (string) ( $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '' );
        $settings = get_option( 'duck_race_settings', [] );
        $webhook_secret = (string) ( $settings['stripe_webhook_secret'] ?? '' );

        if ( ! ( new StripeService() )->verify_webhook_signature( $raw, $signature, $webhook_secret ) ) {
            return new \WP_REST_Response( [ 'ok' => false, 'error' => 'invalid_signature' ], 400 );
        }

        $payload = json_decode( $raw, true );
        if ( ! is_array( $payload ) ) {
            return new \WP_REST_Response( [ 'ok' => false, 'error' => 'invalid_payload' ], 400 );
        }

        $event = (string) ( $payload['type'] ?? '' );
        $object = is_array( $payload['data']['object'] ?? null ) ? $payload['data']['object'] : [];
        $purchase_id = (int) ( $object['metadata']['purchase_id'] ?? $object['client_reference_id'] ?? 0 );

        if ( $purchase_id <= 0 || '' === $event ) {
            return new \WP_REST_Response( [ 'ok' => false, 'error' => 'missing_fields' ], 400 );
        }

        $purchase_service = new PurchaseService();
        if ( 'checkout.session.completed' === $event ) {
            $purchase_service->mark_paid(
                $purchase_id,
                (string) ( $object['payment_intent'] ?? '' ),
                (string) ( $object['payment_intent'] ?? '' )
            );
        } elseif ( in_array( $event, [ 'checkout.session.expired', 'payment.failed', 'checkout.session.async_payment_failed' ], true ) ) {
            $purchase_service->release_reservations( $purchase_id, 'failed' );
        }

        return new \WP_REST_Response( [ 'ok' => true ], 200 );
    }
}
