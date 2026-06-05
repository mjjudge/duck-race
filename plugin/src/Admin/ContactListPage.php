<?php

namespace DuckRace\Admin;

use DuckRace\Database\Schema;
use DuckRace\Security\RequestGuard;

defined( 'ABSPATH' ) || exit;

class ContactListPage {

    public function render(): void {
        RequestGuard::require_capability( 'duck_race_manage_contacts' );

        $search = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
        $rows = $this->get_rows( $search );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Contacts', 'duck-race' ) . '</h1>';

        echo '<form method="get" style="margin: 1rem 0;">';
        echo '<input type="hidden" name="page" value="duck-race-contacts" />';
        echo '<input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Search name, email, organisation', 'duck-race' ) . '" /> ';
        echo '<button class="button" type="submit">' . esc_html__( 'Search', 'duck-race' ) . '</button> ';
        echo '<a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=duck-race-contact-edit' ) ) . '">' . esc_html__( 'Add Contact', 'duck-race' ) . '</a>';
        echo '</form>';

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Name', 'duck-race' ) . '</th>';
        echo '<th>' . esc_html__( 'Email', 'duck-race' ) . '</th>';
        echo '<th>' . esc_html__( 'Organisation', 'duck-race' ) . '</th>';
        echo '<th>' . esc_html__( 'Consent', 'duck-race' ) . '</th>';
        echo '<th>' . esc_html__( 'Purchases', 'duck-race' ) . '</th>';
        echo '<th>' . esc_html__( 'Actions', 'duck-race' ) . '</th>';
        echo '</tr></thead><tbody>';

        if ( empty( $rows ) ) {
            echo '<tr><td colspan="6">' . esc_html__( 'No contacts found.', 'duck-race' ) . '</td></tr>';
        } else {
            foreach ( $rows as $row ) {
                $name = trim( (string) $row->first_name . ' ' . (string) $row->last_name );
                $consent = sprintf(
                    /* translators: 1: race consent, 2: organisation consent */
                    __( 'Race: %1$s | Org: %2$s', 'duck-race' ),
                    ( (int) $row->consent_duck_race ) ? __( 'Yes', 'duck-race' ) : __( 'No', 'duck-race' ),
                    ( (int) $row->consent_organisation ) ? __( 'Yes', 'duck-race' ) : __( 'No', 'duck-race' )
                );

                echo '<tr>';
                echo '<td>' . esc_html( $name ) . '</td>';
                echo '<td>' . esc_html( (string) $row->email ) . '</td>';
                echo '<td>' . esc_html( (string) $row->organisation_name ) . '</td>';
                echo '<td>' . esc_html( $consent ) . '</td>';
                echo '<td>' . esc_html( (string) $row->purchase_count ) . '</td>';
                echo '<td><a href="' . esc_url( admin_url( 'admin.php?page=duck-race-contact-edit&id=' . (int) $row->id ) ) . '">' . esc_html__( 'Edit', 'duck-race' ) . '</a></td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    /**
     * @return array<int, object>
     */
    private function get_rows( string $search ): array {
        global $wpdb;

        $contacts_table = Schema::table_name( 'contacts' );
        $purchases_table = Schema::table_name( 'purchases' );

        $contacts_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $contacts_table ) );
        if ( $contacts_exists !== $contacts_table ) {
            return [];
        }

        $purchases_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $purchases_table ) );
        $purchase_join = '';
        $purchase_select = '0 AS purchase_count';

        if ( $purchases_exists === $purchases_table ) {
            $purchase_join = "LEFT JOIN (SELECT contact_id, COUNT(*) AS purchase_count FROM {$purchases_table} GROUP BY contact_id) p ON p.contact_id = c.id";
            $purchase_select = 'COALESCE(p.purchase_count, 0) AS purchase_count';
        }

        $where = '';
        $args = [];
        if ( '' !== $search ) {
            $term = '%' . $wpdb->esc_like( $search ) . '%';
            $where = 'WHERE c.first_name LIKE %s OR c.last_name LIKE %s OR c.email LIKE %s OR c.organisation_name LIKE %s';
            $args = [ $term, $term, $term, $term ];
        }

        $sql = "
            SELECT c.id, c.first_name, c.last_name, c.email, c.organisation_name,
                c.consent_duck_race, c.consent_organisation,
                {$purchase_select}
            FROM {$contacts_table} c
            {$purchase_join}
            {$where}
            ORDER BY c.updated_at DESC, c.id DESC
            LIMIT 200
        ";

        $results = empty( $args )
            ? $wpdb->get_results( $sql )
            : $wpdb->get_results( $wpdb->prepare( $sql, $args ) );

        return is_array( $results ) ? $results : [];
    }
}
