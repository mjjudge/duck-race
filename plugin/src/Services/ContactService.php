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
     * Sanitize the fields common to every contact create/update path
     * (name, organisation, phone, address, notes — not email, consent or timestamps).
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function clean_fields( array $data ): array {
        return [
            'first_name' => sanitize_text_field( (string) ( $data['first_name'] ?? '' ) ),
            'last_name' => sanitize_text_field( (string) ( $data['last_name'] ?? '' ) ),
            'organisation_name' => sanitize_text_field( (string) ( $data['organisation_name'] ?? '' ) ),
            'phone' => sanitize_text_field( (string) ( $data['phone'] ?? '' ) ),
            'address_line_1' => sanitize_text_field( (string) ( $data['address_line_1'] ?? '' ) ),
            'address_line_2' => sanitize_text_field( (string) ( $data['address_line_2'] ?? '' ) ),
            'city' => sanitize_text_field( (string) ( $data['city'] ?? '' ) ),
            'county' => sanitize_text_field( (string) ( $data['county'] ?? '' ) ),
            'postcode' => sanitize_text_field( (string) ( $data['postcode'] ?? '' ) ),
            'country' => sanitize_text_field( (string) ( $data['country'] ?? '' ) ),
            'notes' => sanitize_textarea_field( (string) ( $data['notes'] ?? '' ) ),
        ];
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

        $clean = array_merge(
            $this->clean_fields( $data ),
            [
                'email' => $email,
                'consent_duck_race' => ! empty( $data['consent_duck_race'] ) ? 1 : 0,
                'consent_organisation' => ! empty( $data['consent_organisation'] ) ? 1 : 0,
                'consent_source' => sanitize_text_field( (string) ( $data['consent_source'] ?? 'admin' ) ),
                'updated_at' => current_time( 'mysql', true ),
            ]
        );

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
     * Create a contact with no email address, for manual sales where the seller
     * could not obtain one. Always inserts a new row — no email means no way to
     * match against an existing contact, so no deduplication is attempted.
     * Consent is always forced off: there is no channel to act on it.
     *
     * @param array<string, mixed> $data
     */
    public function create_without_email( array $data ): int {
        $clean = array_merge(
            $this->clean_fields( $data ),
            [
                'email' => null,
                'consent_duck_race' => 0,
                'consent_organisation' => 0,
                'consent_source' => 'manual_sale_admin_no_email',
                'updated_at' => current_time( 'mysql', true ),
            ]
        );
        $clean['created_at'] = $clean['updated_at'];

        global $wpdb;
        $table = Schema::table_name( 'contacts' );
        $wpdb->insert( $table, $clean );

        $new_id = (int) $wpdb->insert_id;
        Logger::log(
            'contact.created',
            'contact',
            $new_id,
            null,
            $this->snapshot( $clean ),
            [
                'consent_source' => (string) $clean['consent_source'],
            ]
        );

        return $new_id;
    }

    /**
     * Update a known contact directly by its ID — no email matching involved.
     * Used for admin edits of an existing, identified contact, where matching by
     * email would risk silently updating a different row (see upsert_by_email).
     *
     * @param array<string, mixed> $data
     */
    public function update_by_id( int $contact_id, array $data ): int {
        global $wpdb;
        $table = Schema::table_name( 'contacts' );

        $existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $contact_id ) );
        if ( ! $existing ) {
            return 0;
        }

        $email = strtolower( sanitize_email( (string) ( $data['email'] ?? '' ) ) );
        $clean = array_merge(
            $this->clean_fields( $data ),
            [
                'email' => '' !== $email ? $email : null,
                'consent_duck_race' => ! empty( $data['consent_duck_race'] ) ? 1 : 0,
                'consent_organisation' => ! empty( $data['consent_organisation'] ) ? 1 : 0,
                'consent_source' => sanitize_text_field( (string) ( $data['consent_source'] ?? 'admin' ) ),
                'updated_at' => current_time( 'mysql', true ),
            ]
        );

        if ( ! empty( $data['consent_timestamp'] ) ) {
            $clean['consent_timestamp'] = sanitize_text_field( (string) $data['consent_timestamp'] );
        }

        $before = $this->snapshot( (array) $existing );
        $wpdb->update( $table, $clean, [ 'id' => $contact_id ] );
        $after = $this->snapshot( array_merge( (array) $existing, $clean ) );

        Logger::log(
            'contact.updated',
            'contact',
            $contact_id,
            $before,
            $after,
            [
                'consent_timestamp' => (string) ( $clean['consent_timestamp'] ?? '' ),
                'consent_source' => (string) ( $clean['consent_source'] ?? '' ),
            ]
        );

        return $contact_id;
    }

    /**
     * Merge a no-email contact into an existing contact that owns the email the
     * admin has just added. Reassigns all purchases and duck entries onto the
     * target and removes the now-empty source record. Only a source contact with
     * no email can be merged away — this keeps the operation scoped to exactly
     * the "manual sale, email added later" case rather than a general contact merge.
     *
     * @return array{success:bool, target_id?:int, reason?:string}
     */
    public function merge_no_email_contact( int $source_id, int $target_id ): array {
        if ( $source_id <= 0 || $target_id <= 0 || $source_id === $target_id ) {
            return [ 'success' => false, 'reason' => __( 'Invalid merge request.', 'duck-race' ) ];
        }

        global $wpdb;
        $contacts_table = Schema::table_name( 'contacts' );
        $purchases_table = Schema::table_name( 'purchases' );
        $entries_table = Schema::table_name( 'entries' );

        $source = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$contacts_table} WHERE id = %d", $source_id ), ARRAY_A );
        $target = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$contacts_table} WHERE id = %d", $target_id ), ARRAY_A );

        if ( ! $source || ! $target ) {
            return [ 'success' => false, 'reason' => __( 'Contact not found.', 'duck-race' ) ];
        }

        if ( '' !== (string) $source['email'] ) {
            return [ 'success' => false, 'reason' => __( 'Only a contact with no email address can be merged into another contact.', 'duck-race' ) ];
        }

        $wpdb->query( 'START TRANSACTION' );

        $reassigned_purchases = $wpdb->update( $purchases_table, [ 'contact_id' => $target_id ], [ 'contact_id' => $source_id ] );
        $reassigned_entries = $wpdb->update( $entries_table, [ 'contact_id' => $target_id ], [ 'contact_id' => $source_id ] );
        $deleted = $wpdb->delete( $contacts_table, [ 'id' => $source_id ] );

        if ( false === $reassigned_purchases || false === $reassigned_entries || false === $deleted ) {
            $wpdb->query( 'ROLLBACK' );
            return [ 'success' => false, 'reason' => __( 'Merge failed. No records were changed.', 'duck-race' ) ];
        }

        $wpdb->query( 'COMMIT' );

        $this->touch_last_purchase( $target_id );

        $source_name = trim( (string) $source['first_name'] . ' ' . (string) $source['last_name'] );
        $source_reference = self::format_reference( $source_id, (string) $source['created_at'] );

        $target_snapshot = $this->snapshot( $target );
        $before = array_merge( $target_snapshot, [ 'merged_from' => '' ] );
        $after = array_merge( $target_snapshot, [ 'merged_from' => "#{$source_id} {$source_name} ({$source_reference})" ] );

        Logger::log(
            'contact.merged',
            'contact',
            $target_id,
            $before,
            $after,
            [
                'merged_from_contact_id' => $source_id,
                'merged_from_reference' => $source_reference,
                'merged_from_name' => $source_name,
            ]
        );

        return [ 'success' => true, 'target_id' => $target_id ];
    }

    /**
     * A stable, collision-free reference for a contact with no email — derived
     * from the contact's own primary key, so it needs no separate storage or
     * uniqueness check.
     */
    public static function format_reference( int $contact_id, string $created_at ): string {
        $timestamp = strtotime( $created_at ) ?: time();
        $date = gmdate( 'Ymd', $timestamp );
        $suffix = strtoupper( str_pad( base_convert( (string) $contact_id, 10, 36 ), 6, '0', STR_PAD_LEFT ) );

        return "MAN-{$date}-{$suffix}";
    }

    /**
     * Display text for a contact with no email, combining a translatable
     * placeholder with its internal reference. Use everywhere a contact's email
     * would otherwise be shown.
     */
    public static function no_email_display( int $contact_id, string $created_at ): string {
        /* translators: %s: internal purchaser reference, e.g. MAN-20260805-00003K */
        return sprintf( __( 'No email supplied (Ref: %s)', 'duck-race' ), self::format_reference( $contact_id, $created_at ) );
    }

    /**
     * Anonymise a contact for GDPR deletion.
     *
     * If the contact has Gift Aid declarations, name + address are kept for
     * HMRC compliance (6-year retention requirement). All other PII is cleared.
     * If no Gift Aid, all PII fields are removed.
     *
     * Purchase and entry records are preserved for financial audit; the contact
     * row becomes an anonymised placeholder linked to those records.
     *
     * @return array{success:bool, gift_aid:bool, kept:string}|array{success:bool, reason:string}
     */
    public function anonymise( int $contact_id ): array {
        global $wpdb;
        $contacts_table  = Schema::table_name( 'contacts' );
        $purchases_table = Schema::table_name( 'purchases' );

        $contact = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$contacts_table} WHERE id = %d", $contact_id ) );
        if ( ! $contact ) {
            return [ 'success' => false, 'reason' => 'Contact not found.' ];
        }

        $has_gift_aid = (bool) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$purchases_table} WHERE contact_id = %d AND gift_aid_declared = 1",
                $contact_id
            )
        );

        $anon_email = 'anonymised_' . $contact_id . '@gdpr.removed';
        $now        = current_time( 'mysql', true );

        if ( $has_gift_aid ) {
            // HMRC requires donor name + home address for Gift Aid records for 6 years.
            $update = [
                'email'                => $anon_email,
                'phone'                => '',
                'address_line_2'       => '',
                'county'               => '',
                'country'              => '',
                'organisation_name'    => '',
                'consent_duck_race'    => 0,
                'consent_organisation' => 0,
                'notes'                => '',
                'updated_at'           => $now,
            ];
            $kept = 'Name and home address retained for HMRC / Gift Aid compliance.';
        } else {
            $update = [
                'first_name'           => 'Anonymised',
                'last_name'            => '',
                'email'                => $anon_email,
                'phone'                => '',
                'organisation_name'    => '',
                'address_line_1'       => '',
                'address_line_2'       => '',
                'city'                 => '',
                'county'               => '',
                'postcode'             => '',
                'country'              => '',
                'consent_duck_race'    => 0,
                'consent_organisation' => 0,
                'notes'                => '',
                'updated_at'           => $now,
            ];
            $kept = 'No personal data retained.';
        }

        $before = $this->snapshot( (array) $contact );
        $wpdb->update( $contacts_table, $update, [ 'id' => $contact_id ] );
        $after = $this->snapshot( array_merge( (array) $contact, $update ) );

        Logger::log(
            'contact.anonymised',
            'contact',
            $contact_id,
            $before,
            $after,
            [
                'consent_source'    => 'admin_gdpr_delete',
                'gift_aid_retained' => $has_gift_aid ? 'yes' : 'no',
            ]
        );

        return [ 'success' => true, 'gift_aid' => $has_gift_aid, 'kept' => $kept ];
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
            'county' => (string) ( $row['county'] ?? '' ),
            'postcode' => (string) ( $row['postcode'] ?? '' ),
            'country' => (string) ( $row['country'] ?? '' ),
            'consent_duck_race' => (int) ( $row['consent_duck_race'] ?? 0 ),
            'consent_organisation' => (int) ( $row['consent_organisation'] ?? 0 ),
            'consent_timestamp' => (string) ( $row['consent_timestamp'] ?? '' ),
            'consent_source' => (string) ( $row['consent_source'] ?? '' ),
        ];
    }
}
