<?php

namespace DuckRace\Services;

use DuckRace\Database\Schema;
use DuckRace\Mail\Mailer;
use DuckRace\Mail\TemplateRenderer;

defined( 'ABSPATH' ) || exit;

class EmailService {

    public function send_purchase_confirmation( int $purchase_id ): bool {
        $context = $this->purchase_context( $purchase_id );
        if ( null === $context ) {
            return false;
        }

        if ( '' !== (string) $context['email_confirmation_sent_at'] ) {
            return true;
        }

        $renderer = new TemplateRenderer();
        $subject = $renderer->render_subject( 'purchase_confirmation', $context );
        $body = $renderer->render_body( 'purchase_confirmation', $context );

        $ok = ( new Mailer() )->send(
            [
                'to' => (string) $context['email'],
                'subject' => $subject,
                'body' => $body,
                'email_type' => 'purchase_confirmation',
                'race_id' => (int) $context['race_id'],
                'purchase_id' => $purchase_id,
            ]
        );

        if ( $ok ) {
            global $wpdb;
            $table = Schema::table_name( 'purchases' );
            $wpdb->update( $table, [ 'email_confirmation_sent_at' => current_time( 'mysql', true ) ], [ 'id' => $purchase_id ] );
        }

        return $ok;
    }

    public function send_race_reminder( int $race_id ): int {
        global $wpdb;

        $race_table = Schema::table_name( 'races' );
        $contact_table = Schema::table_name( 'contacts' );
        $purchase_table = Schema::table_name( 'purchases' );
        $entries_table = Schema::table_name( 'entries' );

        $race = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$race_table} WHERE id = %d", $race_id ), ARRAY_A );
        if ( ! is_array( $race ) ) {
            return 0;
        }

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.id, c.first_name, c.last_name, c.email
                 FROM {$contact_table} c
                 INNER JOIN {$purchase_table} p ON p.contact_id = c.id
                 WHERE p.race_id = %d AND p.payment_status = 'paid'
                 GROUP BY c.id, c.first_name, c.last_name, c.email",
                $race_id
            ),
            ARRAY_A
        );

        if ( ! is_array( $rows ) ) {
            return 0;
        }

        $renderer = new TemplateRenderer();
        $mailer = new Mailer();
        $sent_count = 0;

        foreach ( $rows as $row ) {
            $numbers = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT e.duck_number
                     FROM {$entries_table} e
                     INNER JOIN {$purchase_table} p ON p.id = e.purchase_id
                     WHERE p.race_id = %d AND p.contact_id = %d AND p.payment_status = 'paid'
                     ORDER BY e.duck_number ASC",
                    $race_id,
                    (int) $row['id']
                )
            );

            $data = [
                'first_name' => (string) $row['first_name'],
                'last_name' => (string) $row['last_name'],
                'race_title' => (string) $race['title'],
                'race_date' => (string) $race['race_date'],
                'race_time' => (string) $race['race_time'],
                'race_location' => (string) $race['location'],
                'duck_numbers' => implode( ', ', array_map( 'strval', is_array( $numbers ) ? $numbers : [] ) ),
            ];

            $subject = $renderer->render_subject( 'race_reminder', $data );
            $body = $renderer->render_body( 'race_reminder', $data );

            $ok = $mailer->send(
                [
                    'to' => (string) $row['email'],
                    'subject' => $subject,
                    'body' => $body,
                    'email_type' => 'race_reminder',
                    'race_id' => $race_id,
                ]
            );

            if ( $ok ) {
                $sent_count++;
            }
        }

        return $sent_count;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function purchase_context( int $purchase_id ): ?array {
        global $wpdb;

        $purchase_table = Schema::table_name( 'purchases' );
        $contact_table = Schema::table_name( 'contacts' );
        $race_table = Schema::table_name( 'races' );
        $entries_table = Schema::table_name( 'entries' );

        $purchase = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$purchase_table} WHERE id = %d", $purchase_id ), ARRAY_A );
        if ( ! is_array( $purchase ) ) {
            return null;
        }

        $contact = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$contact_table} WHERE id = %d", (int) $purchase['contact_id'] ), ARRAY_A );
        $race = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$race_table} WHERE id = %d", (int) $purchase['race_id'] ), ARRAY_A );
        if ( ! is_array( $contact ) || ! is_array( $race ) ) {
            return null;
        }

        $entries = $wpdb->get_results( $wpdb->prepare( "SELECT duck_number, duck_name FROM {$entries_table} WHERE purchase_id = %d ORDER BY duck_number ASC", $purchase_id ), ARRAY_A );

        $numbers = [];
        $names = [];
        if ( is_array( $entries ) ) {
            foreach ( $entries as $entry ) {
                $numbers[] = (string) ( $entry['duck_number'] ?? '' );
                $name = trim( (string) ( $entry['duck_name'] ?? '' ) );
                if ( '' !== $name ) {
                    $names[] = $name;
                }
            }
        }

        return [
            'purchase_id' => $purchase_id,
            'race_id' => (int) $purchase['race_id'],
            'email_confirmation_sent_at' => (string) ( $purchase['email_confirmation_sent_at'] ?? '' ),
            'first_name' => (string) $contact['first_name'],
            'last_name' => (string) $contact['last_name'],
            'email' => (string) $contact['email'],
            'race_title' => (string) $race['title'],
            'race_date' => (string) $race['race_date'],
            'race_time' => (string) $race['race_time'],
            'race_location' => (string) $race['location'],
            'duck_numbers' => implode( ', ', $numbers ),
            'duck_names' => implode( ', ', $names ),
            'purchase_total' => (string) $purchase['grand_total'],
        ];
    }
}
