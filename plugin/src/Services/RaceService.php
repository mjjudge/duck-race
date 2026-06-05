<?php

namespace DuckRace\Services;

use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class RaceService {

    public function get_by_id( int $race_id ): ?object {
        if ( $race_id <= 0 ) {
            return null;
        }

        global $wpdb;
        $table = Schema::table_name( 'races' );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $race_id ) );

        return is_object( $row ) ? $row : null;
    }

    public function get_by_slug( string $slug ): ?object {
        $slug = sanitize_title( $slug );
        if ( '' === $slug ) {
            return null;
        }

        global $wpdb;
        $table = Schema::table_name( 'races' );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s", $slug ) );

        return is_object( $row ) ? $row : null;
    }

    public function get_current_open_race(): ?object {
        global $wpdb;
        $table = Schema::table_name( 'races' );
        $rows = $wpdb->get_results( "SELECT * FROM {$table} WHERE status = 'open' ORDER BY race_date ASC, id ASC" );

        if ( ! is_array( $rows ) ) {
            return null;
        }

        $lifecycle = new RaceLifecycleService();
        foreach ( $rows as $row ) {
            if ( $lifecycle->is_online_sales_open( (string) $row->status, (string) $row->sales_open_at, (string) $row->sales_close_at ) ) {
                return $row;
            }
        }

        return null;
    }

    public function get_current_completed_race(): ?object {
        global $wpdb;

        $table = Schema::table_name( 'races' );
        $row = $wpdb->get_row( "SELECT * FROM {$table} WHERE status = 'completed' ORDER BY race_date DESC, id DESC LIMIT 1" );

        return is_object( $row ) ? $row : null;
    }
}
