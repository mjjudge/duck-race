<?php

declare(strict_types=1);

namespace DuckRace\Tests\Unit;

use DuckRace\Services\PurchaseService;
use DuckRace\Services\StripeWebhookProcessor;

final class StripeWebhookProcessorTest {

    public static function run(): void {
        $processor = new StripeWebhookProcessor();
        $purchaseService = new class extends PurchaseService {
            /** @var array<int, object> */
            public array $store = [];
            /** @var list<string> */
            public array $calls = [];

            public function get_by_id( int $purchase_id ): ?object {
                return $this->store[ $purchase_id ] ?? null;
            }

            public function mark_paid( int $purchase_id, string $payment_intent_id = '', string $charge_id = '' ): void {
                $this->calls[] = 'mark_paid:' . $purchase_id;
                if ( isset( $this->store[ $purchase_id ] ) ) {
                    $this->store[ $purchase_id ]->payment_status = 'paid';
                }
            }

            public function release_reservations( int $purchase_id, string $status = 'failed' ): void {
                $this->calls[] = 'release:' . $purchase_id . ':' . $status;
                if ( isset( $this->store[ $purchase_id ] ) ) {
                    $this->store[ $purchase_id ]->payment_status = $status;
                }
            }
        };

        $purchaseService->store[ 10 ] = (object) [ 'id' => 10, 'payment_status' => 'pending' ];

        // successful payment.
        $okPayload = [
            'type' => 'checkout.session.completed',
            'data' => [ 'object' => [ 'metadata' => [ 'purchase_id' => 10 ], 'payment_intent' => 'pi_1' ] ],
        ];
        $res = $processor->process( $okPayload, $purchaseService );
        self::assertTrue( $res['ok'] && 200 === $res['status'], 'Completed event should process successfully.' );
        self::assertSame( 'paid', (string) $purchaseService->store[10]->payment_status, 'Completed event should mark purchase paid.' );

        // duplicate webhook delivery should not reprocess paid purchase.
        $callsBefore = count( $purchaseService->calls );
        $processor->process( $okPayload, $purchaseService );
        self::assertSame( $callsBefore, count( $purchaseService->calls ), 'Duplicate completed event should be idempotent.' );

        // failed payment.
        $purchaseService->store[ 11 ] = (object) [ 'id' => 11, 'payment_status' => 'pending' ];
        $failedPayload = [
            'type' => 'payment.failed',
            'data' => [ 'object' => [ 'metadata' => [ 'purchase_id' => 11 ] ] ],
        ];
        $processor->process( $failedPayload, $purchaseService );
        self::assertSame( 'failed', (string) $purchaseService->store[11]->payment_status, 'Failed event should set failed status.' );

        // expired checkout.
        $purchaseService->store[ 12 ] = (object) [ 'id' => 12, 'payment_status' => 'pending' ];
        $expiredPayload = [
            'type' => 'checkout.session.expired',
            'data' => [ 'object' => [ 'metadata' => [ 'purchase_id' => 12 ] ] ],
        ];
        $processor->process( $expiredPayload, $purchaseService );
        self::assertSame( 'abandoned', (string) $purchaseService->store[12]->payment_status, 'Expired event should set abandoned status.' );
    }

    private static function assertTrue( bool $value, string $message ): void {
        if ( ! $value ) {
            throw new \RuntimeException( $message );
        }
    }

    private static function assertSame( $expected, $actual, string $message ): void {
        if ( $expected !== $actual ) {
            throw new \RuntimeException( $message . ' Expected: ' . json_encode( $expected ) . ' Actual: ' . json_encode( $actual ) );
        }
    }
}
