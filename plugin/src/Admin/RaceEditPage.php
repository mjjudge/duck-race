<?php

namespace DuckRace\Admin;

use DuckRace\Database\Schema;
use DuckRace\Security\RequestGuard;
use DuckRace\Services\RaceLifecycleService;

defined( 'ABSPATH' ) || exit;

class RaceEditPage {

    private const NONCE_ACTION = 'duck_race_save_race';

    public function register(): void {
        add_action( 'admin_post_duck_race_save_race', [ $this, 'handle_save' ] );
    }

    public function render(): void {
        RequestGuard::require_capability( 'duck_race_manage_races' );

        $race = $this->load_race();
        $is_edit = ! empty( $race['id'] );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html( $is_edit ? __( 'Edit Race', 'duck-race' ) : __( 'Create Race', 'duck-race' ) ) . '</h1>';

        if ( isset( $_GET['updated'] ) ) {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Race saved.', 'duck-race' ) . '</p></div>';
        }

        if ( isset( $_GET['error'] ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['error'] ) ) ) . '</p></div>';
        }

        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="duck_race_save_race" />';
        echo '<input type="hidden" name="race_id" value="' . esc_attr( (string) $race['id'] ) . '" />';
        wp_nonce_field( self::NONCE_ACTION, '_wpnonce' );

        echo '<table class="form-table" role="presentation">';
        $this->render_text_row( 'title', __( 'Title', 'duck-race' ), (string) $race['title'] );
        $this->render_text_row( 'slug', __( 'Slug', 'duck-race' ), (string) $race['slug'] );
        $this->render_text_row( 'race_date', __( 'Race date', 'duck-race' ), (string) $race['race_date'], 'date' );
        $this->render_text_row( 'race_time', __( 'Race time', 'duck-race' ), (string) $race['race_time'], 'time' );
        $this->render_text_row( 'location', __( 'Location', 'duck-race' ), (string) $race['location'] );
        $this->render_textarea_row( 'public_description', __( 'Public description', 'duck-race' ), (string) $race['public_description'] );
        $this->render_select_row( 'status', __( 'Status', 'duck-race' ), (string) $race['status'], [ 'draft', 'open', 'closed', 'completed', 'archived' ] );
        $this->render_text_row( 'sales_open_at', __( 'Sales open at', 'duck-race' ), (string) $race['sales_open_at'], 'datetime-local' );
        $this->render_text_row( 'sales_close_at', __( 'Sales close at', 'duck-race' ), (string) $race['sales_close_at'], 'datetime-local' );

        $this->render_text_row( 'manual_range_start', __( 'Manual range start', 'duck-race' ), (string) $race['manual_range_start'], 'number' );
        $this->render_text_row( 'manual_range_end', __( 'Manual range end', 'duck-race' ), (string) $race['manual_range_end'], 'number' );
        $this->render_text_row( 'online_range_start', __( 'Online range start', 'duck-race' ), (string) $race['online_range_start'], 'number' );
        $this->render_text_row( 'online_range_end', __( 'Online range end', 'duck-race' ), (string) $race['online_range_end'], 'number' );

        $this->render_text_row( 'price_per_duck', __( 'Price per duck', 'duck-race' ), (string) $race['price_per_duck'], 'number', '0.01' );
        $this->render_text_row( 'chosen_number_uplift', __( 'Chosen-number uplift', 'duck-race' ), (string) $race['chosen_number_uplift'], 'number', '0.01' );
        $this->render_text_row( 'max_ducks_per_transaction', __( 'Max ducks per transaction', 'duck-race' ), (string) $race['max_ducks_per_transaction'], 'number' );

        echo '</table>';

        submit_button( __( 'Save Race', 'duck-race' ) );
        echo '</form>';
        echo '</div>';
    }

    public function handle_save(): void {
        RequestGuard::require_capability( 'duck_race_manage_races' );
        RequestGuard::verify_admin_nonce( self::NONCE_ACTION, '_wpnonce' );

        $data = [
            'title' => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
            'slug' => sanitize_title( wp_unslash( $_POST['slug'] ?? '' ) ),
            'race_date' => sanitize_text_field( wp_unslash( $_POST['race_date'] ?? '' ) ),
            'race_time' => sanitize_text_field( wp_unslash( $_POST['race_time'] ?? '' ) ),
            'location' => sanitize_text_field( wp_unslash( $_POST['location'] ?? '' ) ),
            'public_description' => sanitize_textarea_field( wp_unslash( $_POST['public_description'] ?? '' ) ),
            'status' => sanitize_text_field( wp_unslash( $_POST['status'] ?? 'draft' ) ),
            'sales_open_at' => sanitize_text_field( wp_unslash( $_POST['sales_open_at'] ?? '' ) ),
            'sales_close_at' => sanitize_text_field( wp_unslash( $_POST['sales_close_at'] ?? '' ) ),
            'manual_range_start' => max( 0, (int) ( $_POST['manual_range_start'] ?? 0 ) ),
            'manual_range_end' => max( 0, (int) ( $_POST['manual_range_end'] ?? 0 ) ),
            'online_range_start' => max( 0, (int) ( $_POST['online_range_start'] ?? 0 ) ),
            'online_range_end' => max( 0, (int) ( $_POST['online_range_end'] ?? 0 ) ),
            'price_per_duck' => number_format( (float) ( $_POST['price_per_duck'] ?? 0 ), 2, '.', '' ),
            'chosen_number_uplift' => number_format( (float) ( $_POST['chosen_number_uplift'] ?? 0 ), 2, '.', '' ),
            'max_ducks_per_transaction' => max( 1, (int) ( $_POST['max_ducks_per_transaction'] ?? 20 ) ),
            'extra_donation_enabled' => 0,
        ];

        $validation_error = $this->validate_ranges( $data );
        if ( '' !== $validation_error ) {
            $this->redirect_with_error( $validation_error, (int) ( $_POST['race_id'] ?? 0 ) );
        }

        $data['total_range_start'] = min( $data['manual_range_start'], $data['online_range_start'] );
        $data['total_range_end'] = max( $data['manual_range_end'], $data['online_range_end'] );

        $now = current_time( 'mysql', true );
        $race_id = (int) ( $_POST['race_id'] ?? 0 );

        global $wpdb;
        $table = Schema::table_name( 'races' );

                $race_id = (int) ( $_POST['race_id'] ?? 0 );
                $lifecycle = new RaceLifecycleService();
                $current_status = $this->current_status( $race_id );
                if ( ! $lifecycle->can_transition( $current_status, $data['status'] ) ) {
                    $this->redirect_with_error( __( 'Invalid race status transition.', 'duck-race' ), $race_id );
                }

        if ( $race_id > 0 ) {
            $data['updated_at'] = $now;
            $wpdb->update( $table, $data, [ 'id' => $race_id ] );
        } else {
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
            $wpdb->insert( $table, $data );
            $race_id = (int) $wpdb->insert_id;
        }

        wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-race-edit', 'id' => $race_id, 'updated' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    private function validate_ranges( array $data ): string {
        if ( $data['manual_range_start'] >= $data['manual_range_end'] ) {
            return __( 'Manual range start must be lower than manual range end.', 'duck-race' );
        }

        if ( $data['online_range_start'] >= $data['online_range_end'] ) {
            return __( 'Online range start must be lower than online range end.', 'duck-race' );
        }

        if ( $data['online_range_end'] - $data['online_range_start'] < 1 ) {
            return __( 'Online range must contain at least one duck number.', 'duck-race' );
        }

        $manual_overlaps_online = $data['manual_range_start'] <= $data['online_range_end']
            && $data['online_range_start'] <= $data['manual_range_end'];

        if ( $manual_overlaps_online ) {
            return __( 'Manual and online ranges cannot overlap.', 'duck-race' );
        }

        return '';
    }

    private function redirect_with_error( string $error, int $race_id ): void {
        wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-race-edit', 'id' => $race_id, 'error' => rawurlencode( $error ) ], admin_url( 'admin.php' ) ) );
        exit;
    }

    private function current_status( int $race_id ): string {
        if ( $race_id <= 0 ) {
            return 'draft';
        }

        global $wpdb;
        $table = Schema::table_name( 'races' );
        $status = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$table} WHERE id = %d", $race_id ) );

        return is_string( $status ) && '' !== $status ? $status : 'draft';
    }

    /**
     * @return array<string, int|string>
     */
    private function load_race(): array {
        $defaults = [
            'id' => 0,
            'title' => '',
            'slug' => '',
            'race_date' => '',
            'race_time' => '',
            'location' => '',
            'public_description' => '',
            'status' => 'draft',
            'sales_open_at' => '',
            'sales_close_at' => '',
            'manual_range_start' => 1,
            'manual_range_end' => 499,
            'online_range_start' => 500,
            'online_range_end' => 1000,
            'price_per_duck' => '0.00',
            'chosen_number_uplift' => '0.00',
            'max_ducks_per_transaction' => 20,
        ];

        $race_id = (int) ( $_GET['id'] ?? 0 );
        if ( $race_id <= 0 ) {
            return $defaults;
        }

        global $wpdb;
        $table = Schema::table_name( 'races' );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $race_id ), ARRAY_A );

        if ( ! is_array( $row ) ) {
            return $defaults;
        }

        return array_merge( $defaults, $row );
    }

    private function render_text_row( string $name, string $label, string $value, string $type = 'text', string $step = '' ): void {
        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th>';
        echo '<td><input class="regular-text" name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '" type="' . esc_attr( $type ) . '" value="' . esc_attr( $value ) . '"';
        if ( '' !== $step ) {
            echo ' step="' . esc_attr( $step ) . '"';
        }
        echo ' /></td>';
        echo '</tr>';
    }

    private function render_textarea_row( string $name, string $label, string $value ): void {
        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th>';
        echo '<td><textarea class="large-text" rows="4" name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea></td>';
        echo '</tr>';
    }

    /**
     * @param array<int, string> $options
     */
    private function render_select_row( string $name, string $label, string $value, array $options ): void {
        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th>';
        echo '<td><select name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '">';
        foreach ( $options as $option ) {
            echo '<option value="' . esc_attr( $option ) . '" ' . selected( $value, $option, false ) . '>' . esc_html( ucfirst( $option ) ) . '</option>';
        }
        echo '</select></td>';
        echo '</tr>';
    }
}
