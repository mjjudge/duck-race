<?php

namespace DuckRace\Rest;

use DuckRace\Security\RequestGuard;
use DuckRace\Services\PurchaseService;
use DuckRace\Services\StripeWebhookProcessor;
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
        RequestGuard::send_nocache_headers();

        $raw       = (string) $request->get_body();
        // Use the WP_REST_Request header accessor — more reliable across server/proxy configs
        // than reading directly from $_SERVER['HTTP_STRIPE_SIGNATURE'].
        $signature = (string) ( $request->get_header( 'stripe-signature' ) ?? '' );
        $settings  = get_option( 'duck_race_settings', [] );
        $webhook_secret = (string) ( $settings['stripe_webhook_secret'] ?? '' );

        if ( ! ( new StripeService() )->verify_webhook_signature( $raw, $signature, $webhook_secret ) ) {
            $reason = '' === $webhook_secret
                ? 'Webhook secret not configured in Duck Race Settings.'
                : 'Stripe signature verification failed — check the webhook secret in Duck Race Settings matches the Stripe Dashboard.';
            $this->log_error( $reason, [ 'sig_present' => '' !== $signature, 'body_len' => strlen( $raw ) ] );
            return new \WP_REST_Response( [ 'ok' => false, 'error' => 'invalid_signature' ], 400 );
        }

        $payload = json_decode( $raw, true );
        if ( ! is_array( $payload ) ) {
            $this->log_error( 'Invalid JSON payload received from Stripe.' );
            return new \WP_REST_Response( [ 'ok' => false, 'error' => 'invalid_payload' ], 400 );
        }

        $result = ( new StripeWebhookProcessor() )->process( $payload, new PurchaseService() );
        if ( ! $result['ok'] ) {
            $this->log_error(
                'Webhook processing error: ' . (string) ( $result['error'] ?? 'unknown' ),
                [ 'event' => (string) ( $payload['type'] ?? '' ), 'status' => (int) $result['status'] ]
            );
            return new \WP_REST_Response( [ 'ok' => false, 'error' => (string) ( $result['error'] ?? 'webhook_processing_error' ) ], (int) $result['status'] );
        }

        return new \WP_REST_Response( [ 'ok' => true ], (int) $result['status'] );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function log_error( string $reason, array $context = [] ): void {
        $log = (array) get_option( 'duck_race_checkout_error_log', [] );
        array_unshift( $log, array_merge(
            [ 'time' => current_time( 'mysql' ), 'reason' => '[Stripe Webhook] ' . $reason ],
            $context
        ) );
        update_option( 'duck_race_checkout_error_log', array_slice( $log, 0, 20 ), false );
    }
}
