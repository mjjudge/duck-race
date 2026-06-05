<?php

namespace DuckRace\Services;

use DuckRace\Audit\Logger;
use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class ContactService {

    public function touch_last_purchase( int $contact_id ): void {
        if ( $contact_id <= 0 ) {
            return;
        }

        global $wpdb;
        $table = Schema::table_name( 'contacts' );
        $wpdb->update(
            $table,
            [
                'last_purchase_at' => current_time( 'mysql', true ),
                'updated_at' => current_time( 'mysql', true ),
            ],
            [ 'id' => $contact_id ]
        );
    }

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
            $before = $this->snapshot( (array) $existing );
            $wpdb->update( $table, $clean, [ 'id' => (int) $existing->id ] );

            $after = $this->snapshot( array_merge( (array) $existing, $clean ) );
            Logger::log(
                'contact.updated',
                'contact',
                (int) $existing->id,
                $before,
                $after,
                [
                    'consent_timestamp' => (string) ( $clean['consent_timestamp'] ?? '' ),
                    'consent_source' => (string) ( $clean['consent_source'] ?? '' ),
                ]
            );

            return (int) $existing->id;
        }

        $clean['created_at'] = $clean['updated_at'];
        $wpdb->insert( $table, $clean );

        $new_id = (int) $wpdb->insert_id;
        Logger::log(
            'contact.created',
            'contact',
            $new_id,
            null,
            $this->snapshot( $clean ),
            [
                'consent_timestamp' => (string) ( $clean['consent_timestamp'] ?? '' ),
                'consent_source' => (string) ( $clean['consent_source'] ?? '' ),
            ]
        );

        return $new_id;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function snapshot( array $row ): array {
        return [
            'first_name' => (string) ( $row['first_name'] ?? '' ),
            'last_name' => (string) ( $row['last_name'] ?? '' ),
            'organisation_name' => (string) ( $row['organisation_name'] ?? '' ),
            'email' => (string) ( $row['email'] ?? '' ),
            'phone' => (string) ( $row['phone'] ?? '' ),
            'address_line_1' => (string) ( $row['address_line_1'] ?? '' ),
            'address_line_2' => (string) ( $row['address_line_2'] ?? '' ),
            'city' => (string) ( $row['city'] ?? '' ),
            'postcode' => (string) ( $row['postcode'] ?? '' ),
            'country' => (string) ( $row['country'] ?? '' ),
            'consent_duck_race' => (int) ( $row['consent_duck_race'] ?? 0 ),
            'consent_organisation' => (int) ( $row['consent_organisation'] ?? 0 ),
            'consent_timestamp' => (string) ( $row['consent_timestamp'] ?? '' ),
            'consent_source' => (string) ( $row['consent_source'] ?? '' ),
        ];
    }
}
