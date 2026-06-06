<?php

namespace DuckRace\Admin;

use DuckRace\Security\RequestGuard;
use DuckRace\Services\ContactService;
use DuckRace\Services\DuckAllocationService;
use DuckRace\Services\PurchaseService;
use DuckRace\Services\RaceService;

defined( 'ABSPATH' ) || exit;

class ManualSalesPage {

    private const NONCE_ACTION = 'duck_race_save_manual_sale';

    public function register(): void {
        add_action( 'admin_post_duck_race_save_manual_sale', [ $this, 'handle_save' ] );
    }

    public function render(): void {
        RequestGuard::require_capability( 'duck_race_manage_sales' );

        $races = $this->get_races();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Add Manual Sale', 'duck-race' ) . '</h1>';

        if ( isset( $_GET['saved'] ) ) {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Manual sale saved.', 'duck-race' ) . '</p></div>';
        }

        if ( isset( $_GET['error'] ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['error'] ) ) ) . '</p></div>';
        }

        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="duck_race_save_manual_sale" />';
        wp_nonce_field( self::NONCE_ACTION, '_wpnonce' );

        echo '<table class="form-table" role="presentation">';

        echo '<tr><th scope="row"><label for="race_id">' . esc_html__( 'Race', 'duck-race' ) . '</label></th><td>';
        echo '<select name="race_id" id="race_id" required>';
        foreach ( $races as $race ) {
            echo '<option value="' . esc_attr( (string) $race->id ) . '">' . esc_html( (string) $race->title ) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th scope="row"><label for="email">' . esc_html__( 'Buyer email', 'duck-race' ) . '</label></th><td><input class="regular-text" type="email" name="email" id="email" required /></td></tr>';
        echo '<tr><th scope="row"><label for="first_name">' . esc_html__( 'First name', 'duck-race' ) . '</label></th><td><input class="regular-text" type="text" name="first_name" id="first_name" required /></td></tr>';
        echo '<tr><th scope="row"><label for="last_name">' . esc_html__( 'Last name', 'duck-race' ) . '</label></th><td><input class="regular-text" type="text" name="last_name" id="last_name" required /></td></tr>';
        echo '<tr><th scope="row"><label for="phone">' . esc_html__( 'Phone', 'duck-race' ) . '</label></th>';
        echo '<td><input class="regular-text" type="tel" name="phone" id="phone" pattern="[0-9 +().\\-]+" />';
        echo '<p class="description">' . esc_html__( 'Optional. Digits, spaces, +, -, and brackets only.', 'duck-race' ) . '</p></td></tr>';

        echo '<tr><th scope="row">' . esc_html__( 'Address', 'duck-race' ) . '</th><td>';
        echo '<input class="regular-text" type="text" name="address_line_1" placeholder="' . esc_attr__( 'Address line 1 (optional)', 'duck-race' ) . '" /><br style="margin-bottom:4px;" />';
        echo '<input class="regular-text" type="text" name="address_line_2" placeholder="' . esc_attr__( 'Address line 2 (optional)', 'duck-race' ) . '" /><br style="margin-bottom:4px;" />';
        echo '<input class="regular-text" type="text" name="city" placeholder="' . esc_attr__( 'Town / City (optional)', 'duck-race' ) . '" /><br style="margin-bottom:4px;" />';
        echo '<input class="regular-text" type="text" name="county" placeholder="' . esc_attr__( 'County (optional)', 'duck-race' ) . '" /><br style="margin-bottom:4px;" />';
        echo '<input style="width:160px;" type="text" name="postcode" placeholder="' . esc_attr__( 'Postcode (optional)', 'duck-race' ) . '" />';
        echo '</td></tr>';

        echo '<tr><th scope="row">' . esc_html__( 'Consent', 'duck-race' ) . '</th><td>';
        echo '<label><input type="checkbox" name="consent_duck_race" value="1" /> ' . esc_html__( 'Future duck race communications', 'duck-race' ) . '</label><br />';
        echo '<label><input type="checkbox" name="consent_organisation" value="1" /> ' . esc_html__( 'Wider organisation communications', 'duck-race' ) . '</label>';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="duck_count">' . esc_html__( 'Number of ducks', 'duck-race' ) . '</label></th><td><input type="number" min="1" max="50" name="duck_count" id="duck_count" value="1" required /></td></tr>';
        echo '<tr><th scope="row"><label for="selected_numbers">' . esc_html__( 'Specific duck numbers (optional)', 'duck-race' ) . '</label></th><td><input class="regular-text" type="text" name="selected_numbers" id="selected_numbers" placeholder="e.g. 12, 13, 14" /><p class="description">' . esc_html__( 'Leave empty to auto-allocate next available manual-range ducks.', 'duck-race' ) . '</p></td></tr>';
        echo '<tr><th scope="row"><label for="duck_names">' . esc_html__( 'Names for ducks (optional)', 'duck-race' ) . '</label></th><td><input class="large-text" type="text" name="duck_names" id="duck_names" placeholder="e.g. Daisy, Sunny" /><p class="description">' . esc_html__( 'Comma-separated. These identify who each duck is bought for — a family member, friend, etc.', 'duck-race' ) . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="payment_method">' . esc_html__( 'Payment method', 'duck-race' ) . '</label></th><td>';
        echo '<select name="payment_method" id="payment_method">';
        foreach ( [ 'cash', 'card_machine', 'bank_transfer', 'other' ] as $method ) {
            echo '<option value="' . esc_attr( $method ) . '">' . esc_html( ucwords( str_replace( '_', ' ', $method ) ) ) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th scope="row"><label for="amount_paid">' . esc_html__( 'Amount paid', 'duck-race' ) . '</label></th><td><input type="number" step="0.01" min="0" name="amount_paid" id="amount_paid" required /></td></tr>';
        echo '<tr><th scope="row"><label for="admin_notes">' . esc_html__( 'Admin notes', 'duck-race' ) . '</label></th><td><textarea class="large-text" rows="3" name="admin_notes" id="admin_notes"></textarea></td></tr>';

        echo '</table>';

        submit_button( __( 'Save Manual Sale', 'duck-race' ) );
        echo '</form>';
        echo '</div>';
    }

    public function handle_save(): void {
        RequestGuard::require_capability( 'duck_race_manage_sales' );
        RequestGuard::verify_admin_nonce( self::NONCE_ACTION, '_wpnonce' );

        $race_id = (int) ( $_POST['race_id'] ?? 0 );
        $race = ( new RaceService() )->get_by_id( $race_id );
        if ( ! $race ) {
            $this->redirect_error( __( 'Invalid race.', 'duck-race' ) );
        }

        $contact_id = ( new ContactService() )->upsert_by_email(
            [
                'email' => wp_unslash( $_POST['email'] ?? '' ),
                'first_name' => wp_unslash( $_POST['first_name'] ?? '' ),
                'last_name' => wp_unslash( $_POST['last_name'] ?? '' ),
                'phone' => wp_unslash( $_POST['phone'] ?? '' ),
                'address_line_1' => wp_unslash( $_POST['address_line_1'] ?? '' ),
                'address_line_2' => wp_unslash( $_POST['address_line_2'] ?? '' ),
                'city' => wp_unslash( $_POST['city'] ?? '' ),
                'county' => wp_unslash( $_POST['county'] ?? '' ),
                'postcode' => wp_unslash( $_POST['postcode'] ?? '' ),
                'consent_duck_race' => ! empty( $_POST['consent_duck_race'] ),
                'consent_organisation' => ! empty( $_POST['consent_organisation'] ),
                'consent_source' => 'manual_sale_admin',
                'consent_timestamp' => current_time( 'mysql', true ),
            ]
        );
        if ( $contact_id <= 0 ) {
            $this->redirect_error( __( 'Could not save contact.', 'duck-race' ) );
        }

        $duck_count = max( 1, (int) ( $_POST['duck_count'] ?? 1 ) );
        $selected_numbers = $this->parse_numbers( (string) ( $_POST['selected_numbers'] ?? '' ) );

        $allocator = new DuckAllocationService();
        $duck_numbers = [];
        if ( ! empty( $selected_numbers ) ) {
            foreach ( $selected_numbers as $number ) {
                [ $ok, $msg ] = $allocator->validate_manual_number( $race, $number );
                if ( ! $ok ) {
                    $this->redirect_error( $msg );
                }
                $duck_numbers[] = $number;
            }
        } else {
            $duck_numbers = $allocator->next_available_numbers( $race, 'manual', $duck_count );
            if ( count( $duck_numbers ) < $duck_count ) {
                $this->redirect_error( __( 'Not enough manual-range ducks available.', 'duck-race' ) );
            }
        }

        $names = $this->parse_names( (string) ( $_POST['duck_names'] ?? '' ) );

        $purchase_id = ( new PurchaseService() )->create_manual_sale(
            (int) $race->id,
            $contact_id,
            $duck_numbers,
            $names,
            sanitize_text_field( wp_unslash( $_POST['payment_method'] ?? 'other' ) ),
            (float) ( $_POST['amount_paid'] ?? 0 ),
            sanitize_textarea_field( wp_unslash( $_POST['admin_notes'] ?? '' ) )
        );

        if ( $purchase_id <= 0 ) {
            $this->redirect_error( __( 'Could not save manual sale.', 'duck-race' ) );
        }

        wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-manual-sale', 'saved' => '1', 'purchase_id' => $purchase_id ], admin_url( 'admin.php' ) ) );
        exit;
    }

    /**
     * @return array<int, object>
     */
    private function get_races(): array {
        global $wpdb;
        $table = \DuckRace\Database\Schema::table_name( 'races' );
        $rows = $wpdb->get_results( "SELECT id, title FROM {$table} ORDER BY race_date DESC, id DESC" );

        return is_array( $rows ) ? $rows : [];
    }

    /**
     * @return array<int, int>
     */
    private function parse_numbers( string $raw ): array {
        $parts = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
        $numbers = [];
        foreach ( $parts as $part ) {
            $n = (int) $part;
            if ( $n > 0 ) {
                $numbers[] = $n;
            }
        }

        return array_values( array_unique( $numbers ) );
    }

    /**
     * @return array<int, string>
     */
    private function parse_names( string $raw ): array {
        $parts = array_filter( array_map( 'trim', explode( ',', $raw ) ) );

        return array_values( array_map( 'sanitize_text_field', $parts ) );
    }

    private function redirect_error( string $message ): void {
        wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-manual-sale', 'error' => rawurlencode( $message ) ], admin_url( 'admin.php' ) ) );
        exit;
    }
}
