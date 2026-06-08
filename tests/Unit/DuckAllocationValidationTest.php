<?php

declare(strict_types=1);

namespace DuckRace\Tests\Unit;

use DuckRace\Services\DuckAllocationService;

final class DuckAllocationValidationTest {

    public static function run(): void {
        $service = new class extends DuckAllocationService {
            /** @var array<int, bool> */
            public array $lost = [];
            /** @var array<int, bool> */
            public array $taken = [];

            protected function is_lost_for_test( int $race_id, int $duck_number ): bool {
                return ! empty( $this->lost[ $duck_number ] );
            }

            protected function is_taken_for_test( int $race_id, int $duck_number ): bool {
                return ! empty( $this->taken[ $duck_number ] );
            }
        };

        $race = (object) [
            'id'                  => 1,
            'manual_range_start'  => 1,
            'manual_range_end'    => 499,
            'online_range_start'  => 500,
            'online_range_end'    => 1000,
        ];

        // ── validate_manual_number ─────────────────────────────────────────────

        // Available duck in manual range is accepted.
        [ $ok, $msg ] = $service->validate_manual_number( $race, 5 );
        self::assertTrue( $ok, 'Available manual-range duck should be accepted.' );
        self::assertSameStr( '', $msg, 'Accepted duck should have empty error message.' );

        // Taken duck in manual range is rejected with its number in the message.
        $service->taken = [ 10 => true ];
        [ $ok, $msg ] = $service->validate_manual_number( $race, 10 );
        self::assertFalse( $ok, 'Taken manual-range duck should be rejected.' );
        self::assertContains( '10', $msg, 'Error message for taken duck should include the duck number.' );
        $service->taken = [];

        // Lost duck in manual range is rejected.
        $service->lost = [ 7 => true ];
        [ $ok, ] = $service->validate_manual_number( $race, 7 );
        self::assertFalse( $ok, 'Lost manual-range duck should be rejected.' );
        $service->lost = [];

        // Online-range duck rejected without allow flag.
        [ $ok, $msg ] = $service->validate_manual_number( $race, 600 );
        self::assertFalse( $ok, 'Online-range duck without flag should be rejected.' );
        self::assertContains( '600', $msg, 'Range error should include the duck number.' );

        // Online-range duck accepted with allow flag.
        [ $ok, ] = $service->validate_manual_number( $race, 600, true );
        self::assertTrue( $ok, 'Available online-range duck with allow flag should be accepted.' );

        // Online-range duck taken — rejected even with allow flag.
        $service->taken = [ 600 => true ];
        [ $ok, $msg ] = $service->validate_manual_number( $race, 600, true );
        self::assertFalse( $ok, 'Taken online-range duck should be rejected even with allow flag.' );
        self::assertContains( '600', $msg, 'Taken message should include the duck number.' );
        $service->taken = [];

        // Duck outside all ranges is rejected.
        [ $ok, ] = $service->validate_manual_number( $race, 9999 );
        self::assertFalse( $ok, 'Duck outside all ranges should be rejected.' );
        [ $ok, ] = $service->validate_manual_number( $race, 9999, true );
        self::assertFalse( $ok, 'Duck outside all ranges should be rejected even with allow flag.' );

        // ── double-sell prevention via validate_manual_number ──────────────────

        // Two ducks — first valid, second taken — only second should fail.
        $service->taken = [ 50 => true ];
        [ $ok50, ] = $service->validate_manual_number( $race, 50 );
        [ $ok49, ] = $service->validate_manual_number( $race, 49 );
        self::assertFalse( $ok50, 'Double-sell of duck 50 should be prevented.' );
        self::assertTrue( $ok49, 'Adjacent duck 49 should still be available.' );
        $service->taken = [];
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

    private static function assertSameStr( string $expected, string $actual, string $message ): void {
        if ( $expected !== $actual ) {
            throw new \RuntimeException( $message . ' Expected: ' . json_encode( $expected ) . ' Actual: ' . json_encode( $actual ) );
        }
    }

    private static function assertContains( string $needle, string $haystack, string $message ): void {
        if ( ! str_contains( $haystack, $needle ) ) {
            throw new \RuntimeException( $message . ' String ' . json_encode( $needle ) . ' not found in ' . json_encode( $haystack ) );
        }
    }
}
