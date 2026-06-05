<?php

declare(strict_types=1);

namespace DuckRace\Tests\Unit;

use DuckRace\Services\DuckAllocationService;

final class AllocationServiceTest {

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
            'id' => 1,
            'manual_range_start' => 1,
            'manual_range_end' => 10,
            'online_range_start' => 11,
            'online_range_end' => 20,
        ];

        // automatic allocation from online range.
        $service->lost = [];
        $service->taken = [];
        $auto = $service->next_available_numbers( $race, 'online', 3 );
        self::assertSame( [ 11, 12, 13 ], $auto, 'Auto allocation should take lowest online ducks.' );

        // chosen number allocation allowed for available online duck.
        self::assertTrue( $service->can_choose_online_number( $race, 15 ), 'Chosen online duck 15 should be allowed when free.' );

        // lost ducks are not allocatable.
        $service->lost = [ 11 => true ];
        $autoLost = $service->next_available_numbers( $race, 'online', 1 );
        self::assertSame( [ 12 ], $autoLost, 'Lost ducks should be skipped in auto allocation.' );
        self::assertFalse( $service->can_choose_online_number( $race, 11 ), 'Lost duck should not be choosable.' );

        // double-sale prevention via taken state.
        $service->lost = [];
        $service->taken = [ 11 => true, 12 => true ];
        $autoTaken = $service->next_available_numbers( $race, 'online', 2 );
        self::assertSame( [ 13, 14 ], $autoTaken, 'Taken ducks should be excluded to prevent double-sale.' );
        self::assertFalse( $service->can_choose_online_number( $race, 11 ), 'Taken duck should not be choosable.' );
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

    /**
     * @param array<int, int> $expected
     * @param array<int, int> $actual
     */
    private static function assertSame( array $expected, array $actual, string $message ): void {
        if ( $expected !== $actual ) {
            throw new \RuntimeException( $message . ' Expected: ' . json_encode( $expected ) . ' Actual: ' . json_encode( $actual ) );
        }
    }
}
