<?php

namespace DuckRace\Services;

use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class ContactService {

    public function find_by_email( string $email ): ?object {
        $email = strtolower( sanitize_email( $email ) );
        if ( '' === $email ) {
            return null;
        }

        global $wpdb;
        $table = Schema::table_name( 'contacts' );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE email = %s", $email ) );

        return is_object( $row ) ? $row : null;
    }

    /**
     * Create or update a contact keyed by email.
     *
     * @param array<string, mixed> $data
     */
    public function upsert_by_email( array $data ): int {
        $email = strtolower( sanitize_email( (string) ( $data['email'] ?? '' ) ) );
        if ( '' === $email ) {
            return 0;
        }

        $clean = [
            'first_name' => sanitize_text_field( (string) ( $data['first_name'] ?? '' ) ),
            'last_name' => sanitize_text_field( (string) ( $data['last_name'] ?? '' ) ),
            'organisation_name' => sanitize_text_field( (string) ( $data['organisation_name'] ?? '' ) ),
            'email' => $email,
            'phone' => sanitize_text_field( (string) ( $data['phone'] ?? '' ) ),
            'address_line_1' => sanitize_text_field( (string) ( $data['address_line_1'] ?? '' ) ),
            'address_line_2' => sanitize_text_field( (string) ( $data['address_line_2'] ?? '' ) ),
            'city' => sanitize_text_field( (string) ( $data['city'] ?? '' ) ),
            'postcode' => sanitize_text_field( (string) ( $data['postcode'] ?? '' ) ),
            'country' => sanitize_text_field( (string) ( $data['country'] ?? '' ) ),
            'consent_duck_race' => ! empty( $data['consent_duck_race'] ) ? 1 : 0,
            'consent_organisation' => ! empty( $data['consent_organisation'] ) ? 1 : 0,
            'consent_source' => sanitize_text_field( (string) ( $data['consent_source'] ?? 'admin' ) ),
            'notes' => sanitize_textarea_field( (string) ( $data['notes'] ?? '' ) ),
            'updated_at' => current_time( 'mysql', true ),
        ];

        if ( ! empty( $data['consent_timestamp'] ) ) {
            $clean['consent_timestamp'] = sanitize_text_field( (string) $data['consent_timestamp'] );
        }

        global $wpdb;
        $table = Schema::table_name( 'contacts' );
        $existing = $this->find_by_email( $email );

        if ( $existing ) {
            $wpdb->update( $table, $clean, [ 'id' => (int) $existing->id ] );
            return (int) $existing->id;
        }

        $clean['created_at'] = $clean['updated_at'];
        $wpdb->insert( $table, $clean );

        return (int) $wpdb->insert_id;
    }
}
