<?php

namespace DuckRace\Services;

use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class PurchaseService {

    /**
     * @param array<int, int> $duck_numbers
     * @param array<int, string> $duck_names
     */
    public function create_manual_sale(
        int $race_id,
        int $contact_id,
        array $duck_numbers,
        array $duck_names,
        string $payment_method,
        float $amount_paid,
        string $admin_notes = ''
    ): int {
        global $wpdb;
        $purchases_table = Schema::table_name( 'purchases' );
        $entries_table = Schema::table_name( 'entries' );

        $amount = number_format( $amount_paid, 2, '.', '' );
        $now = current_time( 'mysql', true );

        $wpdb->insert(
            $purchases_table,
            [
                'race_id' => $race_id,
                'contact_id' => $contact_id,
                'purchase_source' => 'manual',
                'payment_status' => 'paid',
                'total_duck_amount' => $amount,
                'chosen_number_uplift_total' => '0.00',
                'donation_amount' => '0.00',
                'grand_total' => $amount,
                'currency' => 'GBP',
                'admin_notes' => trim( $payment_method . ' | ' . $admin_notes ),
                'created_at' => $now,
                'paid_at' => $now,
            ]
        );

        $purchase_id = (int) $wpdb->insert_id;
        foreach ( $duck_numbers as $idx => $duck_number ) {
            $duck_name = isset( $duck_names[ $idx ] ) ? sanitize_text_field( $duck_names[ $idx ] ) : '';
            $wpdb->insert(
                $entries_table,
                [
                    'race_id' => $race_id,
                    'purchase_id' => $purchase_id,
                    'contact_id' => $contact_id,
                    'duck_number' => (int) $duck_number,
                    'duck_name' => $duck_name,
                    'entry_status' => 'sold_manual',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        ( new ContactService() )->touch_last_purchase( $contact_id );

        ( new EmailService() )->send_purchase_confirmation( $purchase_id );

        return $purchase_id;
    }

    /**
     * @param array<int, int> $duck_numbers
     * @param array<int, string> $duck_names
     */
    public function create_online_pending(
        int $race_id,
        int $contact_id,
        array $duck_numbers,
        array $duck_names,
        float $total_duck_amount,
        float $chosen_uplift_total,
        float $donation_amount,
        bool $gift_aid_declared = false
    ): int {
        global $wpdb;
        $purchases_table = Schema::table_name( 'purchases' );
        $entries_table = Schema::table_name( 'entries' );

        $now = current_time( 'mysql', true );
        $grand_total = number_format( $total_duck_amount + $chosen_uplift_total + $donation_amount, 2, '.', '' );

        $wpdb->insert(
            $purchases_table,
            [
                'race_id' => $race_id,
                'contact_id' => $contact_id,
                'purchase_source' => 'online',
                'payment_status' => 'pending',
                'total_duck_amount' => number_format( $total_duck_amount, 2, '.', '' ),
                'chosen_number_uplift_total' => number_format( $chosen_uplift_total, 2, '.', '' ),
                'donation_amount' => number_format( $donation_amount, 2, '.', '' ),
                'grand_total' => $grand_total,
                'currency' => 'GBP',
                'gift_aid_declared' => $gift_aid_declared ? 1 : 0,
                'created_at' => $now,
            ]
        );

        $purchase_id = (int) $wpdb->insert_id;

        foreach ( $duck_numbers as $idx => $duck_number ) {
            $wpdb->insert(
                $entries_table,
                [
                    'race_id' => $race_id,
                    'purchase_id' => $purchase_id,
                    'contact_id' => $contact_id,
                    'duck_number' => (int) $duck_number,
                    'duck_name' => sanitize_text_field( $duck_names[ $idx ] ?? '' ),
                    'entry_status' => 'reserved',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        ( new ContactService() )->touch_last_purchase( $contact_id );

        return $purchase_id;
    }

    public function attach_checkout_session( int $purchase_id, string $session_id ): void {
        global $wpdb;
        $table = Schema::table_name( 'purchases' );
        $wpdb->update( $table, [ 'stripe_checkout_session_id' => sanitize_text_field( $session_id ) ], [ 'id' => $purchase_id ] );
    }

    public function mark_paid( int $purchase_id, string $payment_intent_id = '', string $charge_id = '' ): void {
        global $wpdb;
        $purchase_table = Schema::table_name( 'purchases' );
        $entries_table = Schema::table_name( 'entries' );
        $now = current_time( 'mysql', true );

        $wpdb->update(
            $purchase_table,
            [
                'payment_status' => 'paid',
                'stripe_payment_intent_id' => sanitize_text_field( $payment_intent_id ),
                'stripe_charge_id' => sanitize_text_field( $charge_id ),
                'paid_at' => $now,
            ],
            [ 'id' => $purchase_id ]
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$entries_table} SET entry_status = 'sold_online', updated_at = %s
                 WHERE purchase_id = %d AND entry_status = 'reserved'",
                $now,
                $purchase_id
            )
        );

        ( new EmailService() )->send_purchase_confirmation( $purchase_id );
    }

    public function mark_refunded( int $purchase_id, string $refund_id, float $amount, string $reason, string $source = 'admin' ): void {
        global $wpdb;
        $purchase_table = Schema::table_name( 'purchases' );
        $entries_table  = Schema::table_name( 'entries' );
        $now            = current_time( 'mysql', true );

        $wpdb->update(
            $purchase_table,
            [
                'payment_status'  => 'refunded',
                'stripe_refund_id' => sanitize_text_field( $refund_id ),
                'refunded_amount' => number_format( $amount, 2, '.', '' ),
                'refund_reason'   => sanitize_text_field( $reason ),
                'refunded_at'     => $now,
            ],
            [ 'id' => $purchase_id ]
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$entries_table} SET entry_status = 'voided', updated_at = %s
                 WHERE purchase_id = %d AND entry_status IN ('sold_online','sold_manual')",
                $now,
                $purchase_id
            )
        );

        \DuckRace\Audit\Logger::log(
            'purchase.refunded',
            'purchase',
            $purchase_id,
            null,
            [ 'refund_id' => $refund_id, 'amount' => $amount, 'reason' => $reason, 'source' => $source ]
        );

        ( new EmailService() )->send_refund_confirmation( $purchase_id, $amount );
    }

    public function get_paid_by_race( int $race_id ): array {
        global $wpdb;
        $purchases_table = Schema::table_name( 'purchases' );
        $contacts_table  = Schema::table_name( 'contacts' );
        $entries_table   = Schema::table_name( 'entries' );

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.*, c.first_name, c.last_name, c.email, c.created_at AS contact_created_at,
                        GROUP_CONCAT(e.duck_number ORDER BY e.duck_number ASC SEPARATOR ', ') AS duck_numbers
                 FROM {$purchases_table} p
                 LEFT JOIN {$contacts_table} c ON c.id = p.contact_id
                 LEFT JOIN {$entries_table} e ON e.purchase_id = p.id
                 WHERE p.race_id = %d AND p.payment_status IN ('paid','refunded')
                 GROUP BY p.id
                 ORDER BY p.paid_at DESC",
                $race_id
            )
        ) ?: [];
    }

    public function get_by_charge_id( string $charge_id ): ?object {
        global $wpdb;
        $table = Schema::table_name( 'purchases' );
        $row   = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE stripe_charge_id = %s LIMIT 1", $charge_id )
        );

        return is_object( $row ) ? $row : null;
    }

    public function release_reservations( int $purchase_id, string $status = 'failed' ): void {
        global $wpdb;
        $purchase_table = Schema::table_name( 'purchases' );
        $entries_table = Schema::table_name( 'entries' );

        $wpdb->update( $purchase_table, [ 'payment_status' => sanitize_text_field( $status ) ], [ 'id' => $purchase_id ] );
        $wpdb->delete( $entries_table, [ 'purchase_id' => $purchase_id, 'entry_status' => 'reserved' ] );
    }

    public function get_by_id( int $purchase_id ): ?object {
        global $wpdb;
        $table = Schema::table_name( 'purchases' );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $purchase_id ) );

        return is_object( $row ) ? $row : null;
    }

    /**
     * @param array<int, int>    $duck_numbers
     * @param array<int, string> $duck_names
     */
    public function create_test_simulation(
        int $race_id,
        int $contact_id,
        array $duck_numbers,
        array $duck_names,
        float $total_duck_amount
    ): int {
        global $wpdb;
        $purchases_table = Schema::table_name( 'purchases' );
        $entries_table   = Schema::table_name( 'entries' );

        $now = current_time( 'mysql', true );

        $wpdb->insert(
            $purchases_table,
            [
                'race_id'                    => $race_id,
                'contact_id'                 => $contact_id,
                'purchase_source'            => 'test_simulation',
                'payment_status'             => 'pending',
                'total_duck_amount'          => number_format( $total_duck_amount, 2, '.', '' ),
                'chosen_number_uplift_total' => '0.00',
                'donation_amount'            => '0.00',
                'grand_total'                => number_format( $total_duck_amount, 2, '.', '' ),
                'currency'                   => 'GBP',
                'gift_aid_declared'          => 0,
                'created_at'                 => $now,
            ]
        );

        $purchase_id = (int) $wpdb->insert_id;

        foreach ( $duck_numbers as $idx => $duck_number ) {
            $wpdb->insert(
                $entries_table,
                [
                    'race_id'      => $race_id,
                    'purchase_id'  => $purchase_id,
                    'contact_id'   => $contact_id,
                    'duck_number'  => (int) $duck_number,
                    'duck_name'    => sanitize_text_field( $duck_names[ $idx ] ?? '' ),
                    'entry_status' => 'reserved',
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]
            );
        }

        ( new ContactService() )->touch_last_purchase( $contact_id );

        return $purchase_id;
    }

    public function delete_test_purchases( int $race_id ): int {
        global $wpdb;
        $purchases_table = Schema::table_name( 'purchases' );
        $entries_table   = Schema::table_name( 'entries' );

        $purchase_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM {$purchases_table} WHERE race_id = %d AND purchase_source = 'test_simulation'",
                $race_id
            )
        );

        if ( ! empty( $purchase_ids ) ) {
            $placeholders = implode( ',', array_fill( 0, count( $purchase_ids ), '%d' ) );
            // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
            $wpdb->query( $wpdb->prepare( "DELETE FROM {$entries_table} WHERE purchase_id IN ({$placeholders})", ...$purchase_ids ) );
        }

        return (int) $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$purchases_table} WHERE race_id = %d AND purchase_source = 'test_simulation'",
                $race_id
            )
        );
    }

    public function has_real_purchases( int $race_id ): bool {
        global $wpdb;
        $table = Schema::table_name( 'purchases' );

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE race_id = %d AND purchase_source != 'test_simulation'",
                $race_id
            )
        ) > 0;
    }
}
