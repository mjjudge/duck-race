<?php

namespace DuckRace\Admin;

use DuckRace\Database\Schema;
use DuckRace\Security\RequestGuard;
use DuckRace\Services\WinnerService;

defined( 'ABSPATH' ) || exit;

class WinnerManagementPage {

    private const NONCE_SAVE_POSITIONS = 'duck_race_save_winner_positions';
    private const NONCE_SAVE_WINNERS = 'duck_race_save_winners';

    public function register(): void {
        add_action( 'admin_post_duck_race_save_winner_positions', [ $this, 'handle_save_positions' ] );
        add_action( 'admin_post_duck_race_save_winners', [ $this, 'handle_save_winners' ] );
    }

    public function render(): void {
        RequestGuard::require_capability( 'duck_race_manage_winners' );

        $races = $this->get_races();
        $race_id = (int) ( $_GET['race_id'] ?? ( ! empty( $races ) ? (int) $races[0]->id : 0 ) );
        $service = new WinnerService();
        $positions = $service->get_positions( $race_id );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Winner Management', 'duck-race' ) . '</h1>';

        if ( isset( $_GET['updated'] ) ) {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Winner settings updated.', 'duck-race' ) . '</p></div>';
        }
        if ( isset( $_GET['error'] ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html( sanitize_text_field( wp_unslash( (string) $_GET['error'] ) ) ) . '</p></div>';
        }

        echo '<form method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '">';
        echo '<input type="hidden" name="page" value="duck-race-winners" />';
        echo '<p><label for="race_id_filter">' . esc_html__( 'Race', 'duck-race' ) . '</label> ';
        echo '<select id="race_id_filter" name="race_id">';
        foreach ( $races as $race ) {
            echo '<option value="' . esc_attr( (string) $race->id ) . '" ' . selected( $race_id, (int) $race->id, false ) . '>' . esc_html( (string) $race->title ) . '</option>';
        }
        echo '</select> ';
        submit_button( __( 'Load Race', 'duck-race' ), 'secondary', 'submit', false );
        echo '</p></form>';

        if ( $race_id <= 0 ) {
            echo '<p>' . esc_html__( 'Create a race first before managing winners.', 'duck-race' ) . '</p>';
            echo '</div>';
            return;
        }

        echo '<h2>' . esc_html__( 'Winner Positions', 'duck-race' ) . '</h2>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="duck_race_save_winner_positions" />';
        echo '<input type="hidden" name="race_id" value="' . esc_attr( (string) $race_id ) . '" />';
        wp_nonce_field( self::NONCE_SAVE_POSITIONS, '_wpnonce' );

        echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Position', 'duck-race' ) . '</th><th>' . esc_html__( 'Prize label (optional)', 'duck-race' ) . '</th></tr></thead><tbody>';
        foreach ( $positions as $i => $position ) {
            echo '<tr>';
            echo '<td><input type="number" min="1" name="positions[' . esc_attr( (string) $i ) . '][position]" value="' . esc_attr( (string) $position['position'] ) . '" /></td>';
            echo '<td><input class="regular-text" type="text" name="positions[' . esc_attr( (string) $i ) . '][prize_label]" value="' . esc_attr( (string) $position['prize_label'] ) . '" /></td>';
            echo '</tr>';
        }
        for ( $extra = 0; $extra < 3; $extra++ ) {
            $idx = count( $positions ) + $extra;
            echo '<tr>';
            echo '<td><input type="number" min="1" name="positions[' . esc_attr( (string) $idx ) . '][position]" value="" placeholder="' . esc_attr( (string) ( $idx + 1 ) ) . '" /></td>';
            echo '<td><input class="regular-text" type="text" name="positions[' . esc_attr( (string) $idx ) . '][prize_label]" value="" /></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        submit_button( __( 'Save Winner Positions', 'duck-race' ) );
        echo '</form>';

        echo '<h2>' . esc_html__( 'Assign Winning Ducks', 'duck-race' ) . '</h2>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="duck_race_save_winners" />';
        echo '<input type="hidden" name="race_id" value="' . esc_attr( (string) $race_id ) . '" />';
        wp_nonce_field( self::NONCE_SAVE_WINNERS, '_wpnonce' );
        echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Position', 'duck-race' ) . '</th><th>' . esc_html__( 'Prize', 'duck-race' ) . '</th><th>' . esc_html__( 'Duck Number', 'duck-race' ) . '</th></tr></thead><tbody>';
        foreach ( $positions as $position ) {
            echo '<tr>';
            echo '<td>' . esc_html( (string) $position['position'] ) . '</td>';
            echo '<td>' . esc_html( (string) $position['prize_label'] ) . '</td>';
            echo '<td><input type="number" min="1" name="assignments[' . esc_attr( (string) $position['position'] ) . ']" value="' . esc_attr( (string) $this->winner_duck_for_position( $race_id, (int) $position['position'] ) ) . '" /></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        submit_button( __( 'Save Winners', 'duck-race' ) );
        echo '</form>';

        echo '</div>';
    }

    public function handle_save_positions(): void {
        RequestGuard::require_capability( 'duck_race_manage_winners' );
        RequestGuard::verify_admin_nonce( self::NONCE_SAVE_POSITIONS, '_wpnonce' );

        $race_id = (int) ( $_POST['race_id'] ?? 0 );
        $positions_raw = $_POST['positions'] ?? [];
        $positions = [];

        if ( is_array( $positions_raw ) ) {
            foreach ( $positions_raw as $row ) {
                if ( ! is_array( $row ) ) {
                    continue;
                }
                $positions[] = [
                    'position' => (int) ( $row['position'] ?? 0 ),
                    'prize_label' => sanitize_text_field( wp_unslash( (string) ( $row['prize_label'] ?? '' ) ) ),
                ];
            }
        }

        ( new WinnerService() )->save_positions( $race_id, $positions );

        wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-winners', 'race_id' => $race_id, 'updated' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function handle_save_winners(): void {
        RequestGuard::require_capability( 'duck_race_manage_winners' );
        RequestGuard::verify_admin_nonce( self::NONCE_SAVE_WINNERS, '_wpnonce' );

        $race_id = (int) ( $_POST['race_id'] ?? 0 );
        $assignments_raw = $_POST['assignments'] ?? [];
        $assignments = [];

        if ( is_array( $assignments_raw ) ) {
            foreach ( $assignments_raw as $position => $duck_number ) {
                $assignments[ (int) $position ] = (int) $duck_number;
            }
        }

        $error = ( new WinnerService() )->assign_winners( $race_id, $assignments );
        if ( '' !== $error ) {
            wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-winners', 'race_id' => $race_id, 'error' => rawurlencode( $error ) ], admin_url( 'admin.php' ) ) );
            exit;
        }

        wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-winners', 'race_id' => $race_id, 'updated' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    /**
     * @return array<int, object>
     */
    private function get_races(): array {
        global $wpdb;
        $table = Schema::table_name( 'races' );
        $rows = $wpdb->get_results( "SELECT id, title FROM {$table} ORDER BY race_date DESC, id DESC" );

        return is_array( $rows ) ? $rows : [];
    }

    private function winner_duck_for_position( int $race_id, int $position ): int {
        global $wpdb;
        $table = Schema::table_name( 'entries' );

        $duck_number = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT duck_number FROM {$table} WHERE race_id = %d AND winner_position = %d LIMIT 1",
                $race_id,
                $position
            )
        );

        return (int) $duck_number;
    }
}
