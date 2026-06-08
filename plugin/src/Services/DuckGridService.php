<?php

namespace DuckRace\Services;

use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class DuckGridService {

    /**
     * @return array<int, object>
     */
    public function list_races(): array {
        global $wpdb;

        $table = Schema::table_name( 'races' );
        $rows = $wpdb->get_results( "SELECT id, title, race_date FROM {$table} ORDER BY race_date DESC, id DESC" );

        return is_array( $rows ) ? $rows : [];
    }

    public function get_race( int $race_id ): ?object {
        if ( $race_id <= 0 ) {
            return null;
        }

        global $wpdb;

        $table = Schema::table_name( 'races' );
        $race = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $race_id ) );

        return is_object( $race ) ? $race : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function tiles( int $race_id, string $filter, int $search_number, int $page, int $per_page ): array {
        $race = $this->get_race( $race_id );
        if ( null === $race ) {
            return [
                'race' => null,
                'tiles' => [],
                'total_tiles' => 0,
                'total_pages' => 1,
                'page' => 1,
                'per_page' => $per_page,
            ];
        }

        $start = (int) $race->total_range_start;
        $end = (int) $race->total_range_end;
        $all_numbers = range( $start, $end );

        $lost = $this->lost_numbers( $start, $end );
        $entries = $this->entry_map( $race_id, $start, $end );
        $physical = $this->physical_state_map( $start, $end );

        $filtered = [];
        foreach ( $all_numbers as $number ) {
            $entry = $entries[ $number ] ?? null;
            $state = $physical[ $number ] ?? null;
            $status = $this->status_for_number( $entry, isset( $lost[ $number ] ) );

            if ( $search_number > 0 && $number !== $search_number ) {
                continue;
            }

            if ( ! $this->matches_filter( $filter, $number, $status, $race ) ) {
                continue;
            }

            $filtered[] = [
                'duck_number' => $number,
                'status' => $status,
                'purchase_id' => is_array( $entry ) ? (int) ( $entry['purchase_id'] ?? 0 ) : 0,
                'payment_status' => is_array( $entry ) ? (string) ( $entry['payment_status'] ?? '' ) : '',
                'purchase_source' => is_array( $entry ) ? (string) ( $entry['purchase_source'] ?? '' ) : '',
                'duck_name' => is_array( $entry ) ? (string) ( $entry['duck_name'] ?? '' ) : '',
                'entry_status' => is_array( $entry ) ? (string) ( $entry['entry_status'] ?? '' ) : '',
                'winner_position' => is_array( $entry ) ? (int) ( $entry['winner_position'] ?? 0 ) : 0,
                'prize_label' => is_array( $entry ) ? (string) ( $entry['prize_label'] ?? '' ) : '',
                'contact_name' => is_array( $entry ) ? trim( (string) ( $entry['first_name'] ?? '' ) . ' ' . (string) ( $entry['last_name'] ?? '' ) ) : '',
                'organisation_name' => is_array( $entry ) ? (string) ( $entry['organisation_name'] ?? '' ) : '',
                'contact_email' => is_array( $entry ) ? (string) ( $entry['email'] ?? '' ) : '',
                'contact_phone' => is_array( $entry ) ? (string) ( $entry['phone'] ?? '' ) : '',
                'has_comment' => is_array( $state ) && '' !== (string) ( $state['comment'] ?? '' ),
                'duck_comment' => is_array( $state ) ? (string) ( $state['comment'] ?? '' ) : '',
            ];
        }

        $total_tiles = count( $filtered );
        $per_page = max( 50, min( 500, $per_page ) );
        $total_pages = max( 1, (int) ceil( $total_tiles / $per_page ) );
        $page = max( 1, min( $total_pages, $page ) );

        $offset = ( $page - 1 ) * $per_page;
        $tiles = array_slice( $filtered, $offset, $per_page );

        return [
            'race' => $race,
            'tiles' => $tiles,
            'total_tiles' => $total_tiles,
            'total_pages' => $total_pages,
            'page' => $page,
            'per_page' => $per_page,
        ];
    }

    public function can_mark_lost( int $race_id, int $duck_number ): bool {
        if ( $duck_number <= 0 ) {
            return false;
        }

        global $wpdb;
        $entries_table = Schema::table_name( 'entries' );

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$entries_table}
                 WHERE race_id = %d AND duck_number = %d
                 AND entry_status IN ('reserved','sold_online','sold_manual','winner')",
                $race_id,
                $duck_number
            )
        );

        return 0 === $count;
    }

    public function is_lost( int $duck_number ): bool {
        global $wpdb;

        $physical_table = Schema::table_name( 'duck_physical_state' );
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $physical_table ) );
        if ( $exists === $physical_table ) {
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

        return false;
    }

    /**
     * @return array<int, bool>
     */
    private function lost_numbers( int $start, int $end ): array {
        global $wpdb;

        $physical_table = Schema::table_name( 'duck_physical_state' );
        $physical_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $physical_table ) );
        if ( $physical_exists !== $physical_table ) {
            return $this->lost_numbers_legacy( $start, $end );
        }

        // Load ALL physical-state rows in range so we know which ducks have an explicit row.
        $physical_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT duck_number, is_lost FROM {$physical_table}
                 WHERE duck_number BETWEEN %d AND %d",
                $start,
                $end
            ),
            ARRAY_A
        );

        $map          = [];
        $has_row_set  = []; // duck numbers that have any row in duck_physical_state

        if ( is_array( $physical_rows ) ) {
            foreach ( $physical_rows as $row ) {
                $num = (int) $row['duck_number'];
                $has_row_set[ $num ] = true;
                if ( '1' === (string) $row['is_lost'] ) {
                    $map[ $num ] = true;
                }
            }
        }

        // For duck numbers with NO physical_state row, mirror is_lost_for_test() by falling
        // back to the legacy duck_status table. This keeps the grid consistent with allocation checks.
        $legacy = $this->lost_numbers_legacy( $start, $end );
        foreach ( $legacy as $num => $_ ) {
            if ( ! isset( $has_row_set[ $num ] ) ) {
                $map[ $num ] = true;
            }
        }

        return $map;
    }

    /**
     * Fallback to old duck_status table when new table doesn't exist yet.
     *
     * @return array<int, bool>
     */
    private function lost_numbers_legacy( int $start, int $end ): array {
        global $wpdb;

        $table = Schema::table_name( 'duck_status' );
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( $exists !== $table ) {
            return [];
        }

        $rows = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT duck_number FROM {$table}
                 WHERE status = 'lost' AND duck_number BETWEEN %d AND %d",
                $start,
                $end
            )
        );

        if ( ! is_array( $rows ) ) {
            return [];
        }

        $map = [];
        foreach ( $rows as $duck_number ) {
            $map[ (int) $duck_number ] = true;
        }

        return $map;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function physical_state_map( int $start, int $end ): array {
        global $wpdb;

        $table = Schema::table_name( 'duck_physical_state' );
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( $exists !== $table ) {
            return [];
        }

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT duck_number, is_lost, comment FROM {$table}
                 WHERE duck_number BETWEEN %d AND %d",
                $start,
                $end
            ),
            ARRAY_A
        );

        if ( ! is_array( $rows ) ) {
            return [];
        }

        $map = [];
        foreach ( $rows as $row ) {
            $map[ (int) $row['duck_number'] ] = $row;
        }

        return $map;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function entry_map( int $race_id, int $start, int $end ): array {
        global $wpdb;

        $entries = Schema::table_name( 'entries' );
        $purchases = Schema::table_name( 'purchases' );
        $contacts = Schema::table_name( 'contacts' );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.duck_number, e.duck_name, e.entry_status, e.purchase_id, e.winner_position, e.prize_label,
                        p.payment_status, p.purchase_source,
                        c.first_name, c.last_name, c.organisation_name, c.email, c.phone
                 FROM {$entries} e
                 LEFT JOIN {$purchases} p ON p.id = e.purchase_id
                 LEFT JOIN {$contacts} c ON c.id = e.contact_id
                 WHERE e.race_id = %d
                   AND e.duck_number BETWEEN %d AND %d",
                $race_id,
                $start,
                $end
            ),
            ARRAY_A
        );

        if ( ! is_array( $rows ) ) {
            return [];
        }

        $map = [];
        foreach ( $rows as $row ) {
            $duck_number = (int) ( $row['duck_number'] ?? 0 );
            if ( $duck_number <= 0 ) {
                continue;
            }
            $map[ $duck_number ] = $row;
        }

        return $map;
    }

    /**
     * @param array<string, mixed>|null $entry
     */
    private function status_for_number( ?array $entry, bool $lost ): string {
        if ( is_array( $entry ) ) {
            $entry_status = (string) ( $entry['entry_status'] ?? '' );
            return match ( $entry_status ) {
                'winner' => 'winner',
                'reserved' => 'reserved',
                'sold_online' => 'sold_online',
                'sold_manual' => 'sold_manual',
                default => $lost ? 'lost' : 'available',
            };
        }

        return $lost ? 'lost' : 'available';
    }

    private function matches_filter( string $filter, int $duck_number, string $status, object $race ): bool {
        return match ( $filter ) {
            'available' => 'available' === $status,
            'sold' => in_array( $status, [ 'sold_online', 'sold_manual' ], true ),
            'manual' => $duck_number >= (int) $race->manual_range_start && $duck_number <= (int) $race->manual_range_end,
            'online' => $duck_number >= (int) $race->online_range_start && $duck_number <= (int) $race->online_range_end,
            'lost' => 'lost' === $status,
            'reserved' => 'reserved' === $status,
            'winners' => 'winner' === $status,
            default => true,
        };
    }
}
