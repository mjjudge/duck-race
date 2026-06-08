<?php

namespace DuckRace\Services;

defined( 'ABSPATH' ) || exit;

class RaceLifecycleService {

    /**
     * @var array<string, array<int, string>>
     */
    private const ALLOWED_TRANSITIONS = [
        'draft'     => [ 'draft', 'open', 'test', 'archived' ],
        'test'      => [ 'test', 'draft', 'open', 'archived' ],
        'open'      => [ 'open', 'closed', 'completed', 'archived' ],
        'closed'    => [ 'closed', 'completed', 'archived' ],
        'completed' => [ 'completed', 'archived' ],
        'archived'  => [ 'archived' ],
    ];

    public function can_transition( string $from_status, string $to_status ): bool {
        $allowed = self::ALLOWED_TRANSITIONS[ $from_status ] ?? [];

        return in_array( $to_status, $allowed, true );
    }

    public function is_online_sales_open( string $status, string $sales_open_at, string $sales_close_at ): bool {
        if ( 'test' === $status ) {
            return true;
        }

        if ( 'open' !== $status ) {
            return false;
        }

        // If no date window is configured, trust the 'open' status.
        if ( '' === trim( $sales_open_at ) || '' === trim( $sales_close_at ) ) {
            return true;
        }

        $now      = current_time( 'timestamp', true );
        $open_ts  = strtotime( $sales_open_at );
        $close_ts = strtotime( $sales_close_at );

        // Unparseable dates: fall back to trusting the status.
        if ( false === $open_ts || false === $close_ts ) {
            return true;
        }

        return $now >= $open_ts && $now <= $close_ts;
    }

    public function allows_winner_and_retention_workflows( string $status ): bool {
        return 'completed' === $status;
    }
}
