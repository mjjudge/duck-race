<?php

namespace DuckRace\Services;

use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class DuckAllocationService {

    public function next_available_numbers( object $race, string $channel, int $count ): array {
        $count = max( 1, $count );

        [ $start, $end ] = $this->channel_range( $race, $channel );

        $available = [];
        for ( $number = $start; $number <= $end; $number++ ) {
            if ( $this->is_lost_for_test( (int) $race->id, $number ) ) {
                continue;
            }
            if ( $this->is_taken_for_test( (int) $race->id, $number ) ) {
                continue;
            }

            $available[] = $number;
            if ( count( $available ) >= $count ) {
                break;
            }
        }

        return $available;
    }

    public function can_choose_online_number( object $race, int $duck_number ): bool {
        $duck_number = (int) $duck_number;
        if ( $duck_number < (int) $race->online_range_start || $duck_number > (int) $race->online_range_end ) {
            return false;
        }

        if ( $this->is_lost_for_test( (int) $race->id, $duck_number ) ) {
            return false;
        }

        return ! $this->is_taken_for_test( (int) $race->id, $duck_number );
    }

    public function validate_manual_number( object $race, int $duck_number ): array {
        $duck_number = (int) $duck_number;
        if ( $duck_number < (int) $race->manual_range_start || $duck_number > (int) $race->manual_range_end ) {
            return [ false, __( 'Selected duck is outside the manual range.', 'duck-race' ) ];
        }

        if ( $this->is_lost_for_test( (int) $race->id, $duck_number ) ) {
            return [ false, __( 'Selected duck is marked unavailable/lost.', 'duck-race' ) ];
        }

        if ( $this->is_taken_for_test( (int) $race->id, $duck_number ) ) {
            return [ false, __( 'Selected duck is already sold or reserved.', 'duck-race' ) ];
        }

        return [ true, '' ];
    }

    protected function is_lost_for_test( int $race_id, int $duck_number ): bool {
        global $wpdb;

        // Check new per-duck physical state table first.
        $physical_table = Schema::table_name( 'duck_physical_state' );
        $physical_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $physical_table ) );
        if ( $physical_exists === $physical_table ) {
            $is_lost = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT is_lost FROM {$physical_table} WHERE duck_number = %d",
                    $duck_number
                )
            );
            if ( null !== $is_lost ) {
                return '1' === (string) $is_lost;
            }
        }

        // Fall back to legacy race-scoped duck_status table.
        $table = Schema::table_name( 'duck_status' );
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( $exists !== $table ) {
            return false;
        }

        $status = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT status FROM {$table} WHERE race_id = %d AND duck_number = %d",
                $race_id,
                $duck_number
            )
        );

        return is_string( $status ) && 'lost' === $status;
    }

    protected function is_taken_for_test( int $race_id, int $duck_number ): bool {
        global $wpdb;
        $table = Schema::table_name( 'entries' );
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( $exists !== $table ) {
            return false;
        }

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                 WHERE race_id = %d AND duck_number = %d
                 AND entry_status IN ('reserved','sold_online','sold_manual','winner')",
                $race_id,
                $duck_number
            )
        );

        return $count > 0;
    }

    /**
     * @return array{0:int,1:int}
     */
    private function channel_range( object $race, string $channel ): array {
        if ( 'manual' === $channel ) {
            return [ (int) $race->manual_range_start, (int) $race->manual_range_end ];
        }

        return [ (int) $race->online_range_start, (int) $race->online_range_end ];
    }
}
