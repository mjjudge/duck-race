<?php

namespace DuckRace\Services;

defined( 'ABSPATH' ) || exit;

class StripeWebhookProcessor {

    /**
     * @param array<string, mixed> $payload
     * @return array{ok:bool,status:int,error?:string}
     */
    public function process( array $payload, PurchaseService $purchase_service ): array {
        $event  = (string) ( $payload['type'] ?? '' );
        $object = is_array( $payload['data']['object'] ?? null ) ? $payload['data']['object'] : [];

        if ( '' === $event ) {
            return [ 'ok' => false, 'status' => 400, 'error' => 'missing_fields' ];
        }

        // charge.refunded carries a charge object, not a session — look up by charge ID.
        if ( 'charge.refunded' === $event ) {
            $charge_id = (string) ( $object['id'] ?? '' );
            $refund    = is_array( $object['refunds']['data'][0] ?? null ) ? $object['refunds']['data'][0] : [];
            $refund_id = (string) ( $refund['id'] ?? '' );
            $amount    = isset( $refund['amount'] ) ? (float) $refund['amount'] / 100 : (float) ( $object['amount_refunded'] ?? 0 ) / 100;

            if ( '' !== $charge_id ) {
                $purchase = $purchase_service->get_by_charge_id( $charge_id );
                if ( $purchase && 'refunded' !== (string) $purchase->payment_status ) {
                    $purchase_service->mark_refunded( (int) $purchase->id, $refund_id, $amount, '', 'stripe_webhook' );
                }
            }

            return [ 'ok' => true, 'status' => 200 ];
        }

        // All remaining events carry purchase_id in session metadata or client_reference_id.
        $purchase_id = (int) ( $object['metadata']['purchase_id'] ?? $object['client_reference_id'] ?? 0 );

        if ( $purchase_id <= 0 ) {
            return [ 'ok' => false, 'status' => 400, 'error' => 'missing_fields' ];
        }

        $purchase = $purchase_service->get_by_id( $purchase_id );
        if ( ! is_object( $purchase ) ) {
            return [ 'ok' => false, 'status' => 404, 'error' => 'purchase_not_found' ];
        }

        if ( 'checkout.session.completed' === $event ) {
            if ( 'paid' !== (string) $purchase->payment_status ) {
                $purchase_service->mark_paid(
                    $purchase_id,
                    (string) ( $object['payment_intent'] ?? '' ),
                    (string) ( $object['payment_intent'] ?? '' )
                );
            }

            return [ 'ok' => true, 'status' => 200 ];
        }

        if ( in_array( $event, [ 'checkout.session.expired', 'payment.failed', 'checkout.session.async_payment_failed' ], true ) ) {
            $already_terminal = in_array( (string) $purchase->payment_status, [ 'failed', 'abandoned', 'cancelled', 'paid' ], true );
            if ( ! $already_terminal ) {
                $status = ( 'checkout.session.expired' === $event ) ? 'abandoned' : 'failed';
                $purchase_service->release_reservations( $purchase_id, $status );
            }

            return [ 'ok' => true, 'status' => 200 ];
        }

        return [ 'ok' => true, 'status' => 200 ];
    }
}
