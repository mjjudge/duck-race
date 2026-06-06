<?php

namespace DuckRace\Admin;

use DuckRace\Database\Schema;
use DuckRace\Security\RequestGuard;
use DuckRace\Services\RaceLifecycleService;

defined( 'ABSPATH' ) || exit;

class RaceListPage {

    public function render(): void {
        RequestGuard::require_capability( 'duck_race_manage_races' );

        $rows = $this->get_race_rows();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Races', 'duck-race' ) . '</h1>';
        echo '<p>' . esc_html__( 'Create, edit and manage race lifecycle.', 'duck-race' ) . '</p>';
        echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=duck-race-race-edit' ) ) . '">' . esc_html__( 'Create Race', 'duck-race' ) . '</a></p>';

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Title', 'duck-race' ) . '</th>';
        echo '<th>' . esc_html__( 'Status', 'duck-race' ) . '</th>';
        echo '<th>' . esc_html__( 'Race Date', 'duck-race' ) . '</th>';
        echo '<th>' . esc_html__( 'Sales Window', 'duck-race' ) . '</th>';
        echo '<th>' . esc_html__( 'Ducks Sold', 'duck-race' ) . '</th>';
        echo '<th>' . esc_html__( 'Actions', 'duck-race' ) . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        if ( empty( $rows ) ) {
            echo '<tr><td colspan="6">' . esc_html__( 'No races found yet.', 'duck-race' ) . '</td></tr>';
        } else {
            foreach ( $rows as $row ) {
                $race_date_display = $this->format_date( (string) $row->race_date );
                $sales_open_display = $this->format_datetime( (string) $row->sales_open_at );
                $sales_close_display = $this->format_datetime( (string) $row->sales_close_at );
                $sales_window = $sales_open_display . ' – ' . $sales_close_display;
                echo '<tr>';
                echo '<td>' . esc_html( (string) $row->title ) . '</td>';
                echo '<td>' . esc_html( ucfirst( (string) $row->status ) ) . '</td>';
                echo '<td>' . esc_html( $race_date_display ) . '</td>';
                echo '<td>' . esc_html( $sales_window ) . '</td>';
                echo '<td>' . esc_html( (string) $row->ducks_sold ) . '</td>';
                echo '<td>';
                echo '<a href="' . esc_url( admin_url( 'admin.php?page=duck-race-race-edit' ) ) . '">' . esc_html__( 'Create', 'duck-race' ) . '</a> | ';
                echo '<a href="' . esc_url( admin_url( 'admin.php?page=duck-race-race-edit&id=' . (int) $row->id ) ) . '">' . esc_html__( 'Edit', 'duck-race' ) . '</a> | ';
                echo '<a href="#">' . esc_html__( 'Duplicate', 'duck-race' ) . '</a>';
                $lifecycle = new RaceLifecycleService();
                if ( $lifecycle->allows_winner_and_retention_workflows( (string) $row->status ) ) {
                    echo '<br /><small>' . esc_html__( 'Winner and retention workflows enabled', 'duck-race' ) . '</small>';
                }
                echo '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }

    private function format_date( string $value ): string {
        if ( '' === $value ) {
            return '—';
        }
        $ts = strtotime( $value );
        return false !== $ts ? date( 'd/m/Y', $ts ) : $value;
    }

    private function format_datetime( string $value ): string {
        if ( '' === $value ) {
            return '—';
        }
        $ts = strtotime( $value );
        return false !== $ts ? date( 'd/m/Y H:i', $ts ) : $value;
    }

    /**
     * @return array<int, object>
     */
    private function get_race_rows(): array {
        global $wpdb;

        $races_table = Schema::table_name( 'races' );
        $entries_table = Schema::table_name( 'entries' );

        $races_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $races_table ) );
        if ( $races_exists !== $races_table ) {
            return [];
        }

        $entries_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $entries_table ) );
        $sold_statuses = "'sold_online','sold_manual','winner'";

        if ( $entries_exists === $entries_table ) {
            $sql = "
                SELECT r.id, r.title, r.status, r.race_date, r.sales_open_at, r.sales_close_at,
                    COALESCE(e.ducks_sold, 0) AS ducks_sold
                FROM {$races_table} r
                LEFT JOIN (
                    SELECT race_id, COUNT(*) AS ducks_sold
                    FROM {$entries_table}
                    WHERE entry_status IN ({$sold_statuses})
                    GROUP BY race_id
                ) e ON e.race_id = r.id
                ORDER BY r.race_date DESC, r.id DESC
            ";
            $rows = $wpdb->get_results( $sql );
            return is_array( $rows ) ? $rows : [];
        }

        $sql = "
            SELECT r.id, r.title, r.status, r.race_date, r.sales_open_at, r.sales_close_at,
                0 AS ducks_sold
            FROM {$races_table} r
            ORDER BY r.race_date DESC, r.id DESC
        ";
        $rows = $wpdb->get_results( $sql );

        return is_array( $rows ) ? $rows : [];
    }
}
