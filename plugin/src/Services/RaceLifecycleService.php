<?php

namespace DuckRace\Services;

defined( 'ABSPATH' ) || exit;

class RaceLifecycleService {

    /**
     * @var array<string, array<int, string>>
     */
    private const ALLOWED_TRANSITIONS = [
        'draft' => [ 'open', 'archived', 'draft' ],
        'open' => [ 'open', 'closed', 'completed', 'archived' ],
        'closed' => [ 'closed', 'completed', 'archived' ],
        'completed' => [ 'completed', 'archived' ],
        'archived' => [ 'archived' ],
    ];

    public function can_transition( string $from_status, string $to_status ): bool {
        $allowed = self::ALLOWED_TRANSITIONS[ $from_status ] ?? [];

        return in_array( $to_status, $allowed, true );
    }

    public function is_online_sales_open( string $status, string $sales_open_at, string $sales_close_at ): bool {
        if ( 'open' !== $status ) {
            return false;
        }

        $now = current_time( 'timestamp', true );
        $open_ts = strtotime( $sales_open_at ?: '' );
        $close_ts = strtotime( $sales_close_at ?: '' );

        if ( false === $open_ts || false === $close_ts ) {
            return false;
        }

        return $now >= $open_ts && $now <= $close_ts;
    }

    public function allows_winner_and_retention_workflows( string $status ): bool {
        return 'completed' === $status;
    }
}
