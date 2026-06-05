<?php

namespace DuckRace\Services;

use DuckRace\Audit\Logger;
use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class WinnerService {

    /**
     * @return array<int, array{position:int, prize_label:string}>
     */
    public function get_positions( int $race_id ): array {
        if ( $race_id <= 0 ) {
            return [];
        }

        global $wpdb;
        $table = Schema::table_name( 'winner_positions' );
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT position_number, prize_label FROM {$table} WHERE race_id = %d ORDER BY position_number ASC",
                $race_id
            ),
            ARRAY_A
        );

        $positions = [];
        if ( is_array( $rows ) ) {
            foreach ( $rows as $row ) {
                $positions[] = [
                    'position' => (int) ( $row['position_number'] ?? 0 ),
                    'prize_label' => (string) ( $row['prize_label'] ?? '' ),
                ];
            }
        }

        if ( empty( $positions ) ) {
            return [
                [ 'position' => 1, 'prize_label' => '' ],
                [ 'position' => 2, 'prize_label' => '' ],
                [ 'position' => 3, 'prize_label' => '' ],
            ];
        }

        return $positions;
    }

    /**
     * @param array<int, array{position:int, prize_label:string}> $positions
     */
    public function save_positions( int $race_id, array $positions ): void {
        if ( $race_id <= 0 ) {
            return;
        }

        $clean = [];
        foreach ( $positions as $position ) {
            $num = (int) ( $position['position'] ?? 0 );
            if ( $num <= 0 ) {
                continue;
            }

            $clean[ $num ] = [
                'position' => $num,
                'prize_label' => sanitize_text_field( (string) ( $position['prize_label'] ?? '' ) ),
            ];
        }

        foreach ( [ 1, 2, 3 ] as $required ) {
            if ( ! isset( $clean[ $required ] ) ) {
                $clean[ $required ] = [ 'position' => $required, 'prize_label' => '' ];
            }
        }

        ksort( $clean );

        global $wpdb;
        $table = Schema::table_name( 'winner_positions' );
        $before = $this->get_positions( $race_id );

        $wpdb->delete( $table, [ 'race_id' => $race_id ] );
        $now = current_time( 'mysql', true );

        foreach ( $clean as $position ) {
            $wpdb->insert(
                $table,
                [
                    'race_id' => $race_id,
                    'position_number' => $position['position'],
                    'prize_label' => $position['prize_label'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        Logger::log(
            'winner.positions_updated',
            'race',
            $race_id,
            [ 'positions' => $before ],
            [ 'positions' => array_values( $clean ) ]
        );
    }

    /**
     * @param array<int, int> $assignments position => duck_number
     */
    public function assign_winners( int $race_id, array $assignments ): string {
        if ( $race_id <= 0 ) {
            return __( 'Please select a race.', 'duck-race' );
        }

        global $wpdb;
        $entries_table = Schema::table_name( 'entries' );
        $purchases_table = Schema::table_name( 'purchases' );

        $existing = $this->current_winners_snapshot( $race_id );

        $winner_entries = $wpdb->get_results(
            $wpdb->prepare( "SELECT id FROM {$entries_table} WHERE race_id = %d AND winner_position IS NOT NULL", $race_id ),
            ARRAY_A
        );

        if ( is_array( $winner_entries ) ) {
            foreach ( $winner_entries as $entry ) {
                $entry_id = (int) ( $entry['id'] ?? 0 );
                if ( $entry_id <= 0 ) {
                    continue;
                }

                $purchase_source = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT p.purchase_source
                         FROM {$entries_table} e
                         INNER JOIN {$purchases_table} p ON p.id = e.purchase_id
                         WHERE e.id = %d",
                        $entry_id
                    )
                );

                $fallback_status = ( 'manual' === (string) $purchase_source ) ? 'sold_manual' : 'sold_online';
                $wpdb->update(
                    $entries_table,
                    [
                        'entry_status' => $fallback_status,
                        'winner_position' => null,
                        'prize_label' => null,
                        'updated_at' => current_time( 'mysql', true ),
                    ],
                    [ 'id' => $entry_id ]
                );
            }
        }

        $positions = $this->positions_map( $race_id );

        foreach ( $assignments as $position => $duck_number ) {
            $position = (int) $position;
            $duck_number = (int) $duck_number;
            if ( $position <= 0 || $duck_number <= 0 ) {
                continue;
            }

            $entry = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT e.id, e.entry_status
                     FROM {$entries_table} e
                     INNER JOIN {$purchases_table} p ON p.id = e.purchase_id
                     WHERE e.race_id = %d
                     AND e.duck_number = %d
                     AND p.payment_status = 'paid'
                     LIMIT 1",
                    $race_id,
                    $duck_number
                ),
                ARRAY_A
            );

            if ( ! is_array( $entry ) ) {
                return sprintf( __( 'Duck %d cannot be assigned because no paid entry was found for this race.', 'duck-race' ), $duck_number );
            }

            $wpdb->update(
                $entries_table,
                [
                    'entry_status' => 'winner',
                    'winner_position' => $position,
                    'prize_label' => (string) ( $positions[ $position ] ?? '' ),
                    'updated_at' => current_time( 'mysql', true ),
                ],
                [ 'id' => (int) $entry['id'] ]
            );
        }

        $after = $this->current_winners_snapshot( $race_id );
        Logger::log(
            'winner.assignments_updated',
            'race',
            $race_id,
            [ 'winners' => $existing ],
            [ 'winners' => $after ]
        );

        return '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get_public_winners( int $race_id ): array {
        if ( $race_id <= 0 ) {
            return [];
        }

        global $wpdb;
        $entries_table = Schema::table_name( 'entries' );
        $contacts_table = Schema::table_name( 'contacts' );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.winner_position, e.prize_label, e.duck_number,
                        c.first_name, c.last_name, c.organisation_name
                 FROM {$entries_table} e
                 INNER JOIN {$contacts_table} c ON c.id = e.contact_id
                 WHERE e.race_id = %d
                 AND e.winner_position IS NOT NULL
                 ORDER BY e.winner_position ASC, e.duck_number ASC",
                $race_id
            ),
            ARRAY_A
        );

        if ( ! is_array( $rows ) ) {
            return [];
        }

        $results = [];
        foreach ( $rows as $row ) {
            $organisation = trim( (string) ( $row['organisation_name'] ?? '' ) );
            $first = trim( (string) ( $row['first_name'] ?? '' ) );
            $last = trim( (string) ( $row['last_name'] ?? '' ) );
            $display_name = '' !== $organisation ? $organisation : trim( $first . ' ' . $last );

            $results[] = [
                'position' => (int) ( $row['winner_position'] ?? 0 ),
                'prize_label' => (string) ( $row['prize_label'] ?? '' ),
                'duck_number' => (int) ( $row['duck_number'] ?? 0 ),
                'display_name' => $display_name,
            ];
        }

        return $results;
    }

    /**
     * @return array<int, string>
     */
    private function positions_map( int $race_id ): array {
        $map = [];
        foreach ( $this->get_positions( $race_id ) as $position ) {
            $map[ (int) $position['position'] ] = (string) $position['prize_label'];
        }

        return $map;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function current_winners_snapshot( int $race_id ): array {
        global $wpdb;
        $entries_table = Schema::table_name( 'entries' );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT duck_number, winner_position, prize_label
                 FROM {$entries_table}
                 WHERE race_id = %d AND winner_position IS NOT NULL
                 ORDER BY winner_position ASC, duck_number ASC",
                $race_id
            ),
            ARRAY_A
        );

        return is_array( $rows ) ? $rows : [];
    }
}
