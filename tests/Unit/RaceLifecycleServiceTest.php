<?php

declare(strict_types=1);

namespace DuckRace\Tests\Unit;

use DuckRace\Services\RaceLifecycleService;

final class RaceLifecycleServiceTest {

    public static function run(): void {
        $svc = new RaceLifecycleService();

        // ── is_online_sales_open ────────────────────────────────────────────────

        // Non-open status is always closed.
        self::assertFalse( $svc->is_online_sales_open( 'draft', '', '' ), 'Draft race should not be open for sales.' );
        self::assertFalse( $svc->is_online_sales_open( 'closed', '', '' ), 'Closed race should not be open for sales.' );
        self::assertFalse( $svc->is_online_sales_open( 'completed', '', '' ), 'Completed race should not be open for sales.' );

        // Open status with no date window trusts the status.
        self::assertTrue( $svc->is_online_sales_open( 'open', '', '' ), 'Open race with no date window should be open.' );
        self::assertTrue( $svc->is_online_sales_open( 'open', '', '2030-01-01' ), 'Open race with only close date should be open (incomplete window).' );
        self::assertTrue( $svc->is_online_sales_open( 'open', '2020-01-01', '' ), 'Open race with only open date should be open (incomplete window).' );

        // Open status, currently inside window.
        $past   = gmdate( 'Y-m-d H:i:s', time() - 3600 );
        $future = gmdate( 'Y-m-d H:i:s', time() + 3600 );
        self::assertTrue( $svc->is_online_sales_open( 'open', $past, $future ), 'Open race inside date window should be open.' );

        // Open status, window not yet started.
        $later = gmdate( 'Y-m-d H:i:s', time() + 3600 );
        $even_later = gmdate( 'Y-m-d H:i:s', time() + 7200 );
        self::assertFalse( $svc->is_online_sales_open( 'open', $later, $even_later ), 'Open race before window opens should be closed.' );

        // Open status, window already closed.
        $earlier = gmdate( 'Y-m-d H:i:s', time() - 7200 );
        $also_past = gmdate( 'Y-m-d H:i:s', time() - 3600 );
        self::assertFalse( $svc->is_online_sales_open( 'open', $earlier, $also_past ), 'Open race after window closes should be closed.' );

        // Unparseable date strings fall back to trusting status.
        self::assertTrue( $svc->is_online_sales_open( 'open', 'not-a-date', 'also-not-a-date' ), 'Unparseable dates should fall back to trusting open status.' );

        // ── can_transition ─────────────────────────────────────────────────────

        self::assertTrue( $svc->can_transition( 'draft', 'open' ), 'Draft can transition to open.' );
        self::assertTrue( $svc->can_transition( 'open', 'completed' ), 'Open can transition to completed.' );
        self::assertTrue( $svc->can_transition( 'open', 'closed' ), 'Open can transition to closed.' );
        self::assertFalse( $svc->can_transition( 'completed', 'open' ), 'Completed cannot revert to open.' );
        self::assertFalse( $svc->can_transition( 'archived', 'open' ), 'Archived cannot revert to open.' );
        self::assertFalse( $svc->can_transition( 'draft', 'completed' ), 'Draft cannot jump to completed.' );
        self::assertTrue( $svc->can_transition( 'open', 'archived' ), 'Open can be directly archived.' );
    }

    private static function assertTrue( bool $value, string $message ): void {
        if ( ! $value ) {
            throw new \RuntimeException( $message );
        }
    }

    private static function assertFalse( bool $value, string $message ): void {
        if ( $value ) {
            throw new \RuntimeException( $message );
        }
    }
}
