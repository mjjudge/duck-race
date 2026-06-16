<?php

namespace DuckRace\Services;

use DuckRace\Audit\Logger;
use DuckRace\Database\Schema;
use DuckRace\Mail\Mailer;
use DuckRace\Mail\TemplateRenderer;

defined( 'ABSPATH' ) || exit;

class CampaignService {

    /**
     * @return array<string, int>
     */
    public function import_contacts_from_csv( string $tmp_path ): array {
        $result = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        $handle = fopen( $tmp_path, 'r' );
        if ( false === $handle ) {
            return $result;
        }

        $headers = fgetcsv( $handle );
        if ( ! is_array( $headers ) ) {
            fclose( $handle );
            return $result;
        }

        $headers = array_map( static fn( $h ) => sanitize_key( (string) $h ), $headers );
        $contact_service = new ContactService();

        while ( false !== ( $row = fgetcsv( $handle ) ) ) {
            if ( ! is_array( $row ) ) {
                $result['skipped']++;
                continue;
            }

            $data = [];
            foreach ( $headers as $i => $header ) {
                $data[ $header ] = (string) ( $row[ $i ] ?? '' );
            }

            $email = strtolower( sanitize_email( (string) ( $data['email'] ?? '' ) ) );
            if ( '' === $email ) {
                $result['skipped']++;
                continue;
            }

            $existing = $contact_service->find_by_email( $email );

            $explicit_duck = $this->nullable_bool( (string) ( $data['consent_duck_race'] ?? '' ) );
            $explicit_org = $this->nullable_bool( (string) ( $data['consent_organisation'] ?? '' ) );

            $consent_duck = null !== $explicit_duck
                ? $explicit_duck
                : ( $existing ? ( 1 === (int) $existing->consent_duck_race ) : false );
            $consent_org = null !== $explicit_org
                ? $explicit_org
                : ( $existing ? ( 1 === (int) $existing->consent_organisation ) : false );

            $payload = [
                'email' => $email,
                'first_name' => (string) ( $data['first_name'] ?? '' ),
                'last_name' => (string) ( $data['last_name'] ?? '' ),
                'organisation_name' => (string) ( $data['organisation_name'] ?? '' ),
                'phone' => (string) ( $data['phone'] ?? '' ),
                'consent_duck_race' => $consent_duck,
                'consent_organisation' => $consent_org,
                'consent_source' => 'csv_import_admin',
            ];

            if ( null !== $explicit_duck || null !== $explicit_org ) {
                $payload['consent_timestamp'] = current_time( 'mysql', true );
            }

            $saved_id = $contact_service->upsert_by_email( $payload );
            if ( $saved_id <= 0 ) {
                $result['skipped']++;
                continue;
            }

            $result['processed']++;
            if ( $existing ) {
                $result['updated']++;
            } else {
                $result['created']++;
            }
        }

        fclose( $handle );

        return $result;
    }

    public function send_supporter_invitations( int $race_id, bool $allow_without_consent = false, string $legal_basis_note = '' ): int {
        if ( $allow_without_consent && '' === trim( $legal_basis_note ) ) {
            return 0;
        }

        $target_race = ( new RaceService() )->get_by_id( $race_id );
        if ( ! is_object( $target_race ) ) {
            return 0;
        }

        global $wpdb;
        $contacts = Schema::table_name( 'contacts' );
        $purchases = Schema::table_name( 'purchases' );

        $where = "c.anonymised = 0 AND c.email <> ''";
        if ( ! $allow_without_consent ) {
            $where .= ' AND c.consent_duck_race = 1';
        }

        $rows = $wpdb->get_results(
            "SELECT DISTINCT c.id, c.first_name, c.last_name, c.organisation_name, c.email
             FROM {$contacts} c
             INNER JOIN {$purchases} p ON p.contact_id = c.id
             WHERE {$where}",
            ARRAY_A
        );

        if ( ! is_array( $rows ) ) {
            return 0;
        }

        $renderer = new TemplateRenderer();
        $mailer = new Mailer();
        $sent = 0;

        foreach ( $rows as $row ) {
            $context = $this->previous_race_context( (int) $row['id'] );
            $data = [
                'first_name' => (string) $row['first_name'],
                'last_name' => (string) $row['last_name'],
                'organisation_name' => (string) $row['organisation_name'],
                'race_title' => (string) $target_race->title,
                'race_date' => (string) $target_race->race_date,
                'race_time' => (string) $target_race->race_time,
                'race_location' => (string) $target_race->location,
                'buy_link' => $this->buy_link( (string) $target_race->slug ),
                'previous_race_result' => (string) ( $context['previous_race_result'] ?? '' ),
                'winner_position' => (string) ( $context['winner_position'] ?? '' ),
            ];

            $subject = $renderer->render_subject( 'supporter_invitation', $data );
            $body = $renderer->render_body( 'supporter_invitation', $data );

            $ok = $mailer->send(
                [
                    'to' => (string) $row['email'],
                    'subject' => $subject,
                    'body' => $body,
                    'email_type' => 'supporter_invitation',
                    'race_id' => $race_id,
                ]
            );

            if ( $ok ) {
                $sent++;
            }
        }

        if ( $allow_without_consent && '' !== trim( $legal_basis_note ) ) {
            Logger::log(
                'campaign.supporter_invitation_override',
                'race',
                $race_id,
                null,
                [ 'sent' => $sent ],
                [ 'legal_basis_note' => sanitize_text_field( $legal_basis_note ) ]
            );
        }

        return $sent;
    }

    public function send_abandoned_checkout_reminders( int $older_than_hours = 24 ): int {
        $older_than_hours = max( 1, min( 168, $older_than_hours ) );

        global $wpdb;

        $purchases = Schema::table_name( 'purchases' );
        $contacts = Schema::table_name( 'contacts' );
        $races = Schema::table_name( 'races' );
        $entries = Schema::table_name( 'entries' );
        $email_log = Schema::table_name( 'email_log' );

        $cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $older_than_hours * HOUR_IN_SECONDS ) );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.id AS purchase_id, p.race_id, p.contact_id, p.created_at,
                        c.first_name, c.last_name, c.email,
                        r.title, r.slug, r.race_date, r.race_time, r.location
                 FROM {$purchases} p
                 INNER JOIN {$contacts} c ON c.id = p.contact_id
                 INNER JOIN {$races} r ON r.id = p.race_id
                 WHERE p.purchase_source = 'online'
                   AND p.payment_status = 'pending'
                   AND p.created_at <= %s
                   AND c.email <> ''",
                $cutoff
            ),
            ARRAY_A
        );

        if ( ! is_array( $rows ) ) {
            return 0;
        }

        $renderer = new TemplateRenderer();
        $mailer = new Mailer();
        $purchase_service = new PurchaseService();
        $sent = 0;

        foreach ( $rows as $row ) {
            $already_sent = 0;
            $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $email_log ) );
            if ( $exists === $email_log ) {
                $already_sent = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$email_log}
                         WHERE purchase_id = %d
                           AND email_type = %s
                           AND status = %s",
                        (int) $row['purchase_id'],
                        'abandoned_checkout_reminder',
                        'sent'
                    )
                );
            }

            if ( $already_sent > 0 ) {
                $purchase_service->release_reservations( (int) $row['purchase_id'], 'abandoned' );
                continue;
            }

            $numbers = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT duck_number FROM {$entries}
                     WHERE purchase_id = %d AND entry_status = 'reserved'
                     ORDER BY duck_number ASC",
                    (int) $row['purchase_id']
                )
            );

            $data = [
                'first_name' => (string) $row['first_name'],
                'last_name' => (string) $row['last_name'],
                'race_title' => (string) $row['title'],
                'race_date' => (string) $row['race_date'],
                'race_time' => (string) $row['race_time'],
                'race_location' => (string) $row['location'],
                'buy_link' => $this->buy_link( (string) $row['slug'] ),
                'duck_numbers' => implode( ', ', array_map( 'strval', is_array( $numbers ) ? $numbers : [] ) ),
            ];

            $subject = $renderer->render_subject( 'abandoned_checkout', $data );
            $body = $renderer->render_body( 'abandoned_checkout', $data );

            $ok = $mailer->send(
                [
                    'to' => (string) $row['email'],
                    'subject' => $subject,
                    'body' => $body,
                    'email_type' => 'abandoned_checkout_reminder',
                    'race_id' => (int) $row['race_id'],
                    'purchase_id' => (int) $row['purchase_id'],
                ]
            );

            if ( $ok ) {
                $sent++;
            }

            // Always release stale reservations to avoid indefinite locking.
            $purchase_service->release_reservations( (int) $row['purchase_id'], 'abandoned' );
        }

        return $sent;
    }

    public function send_winner_future_race_emails( int $race_id ): int {
        $target_race = ( new RaceService() )->get_by_id( $race_id );
        if ( ! is_object( $target_race ) ) {
            return 0;
        }

        global $wpdb;
        $contacts = Schema::table_name( 'contacts' );
        $purchases = Schema::table_name( 'purchases' );

        $rows = $wpdb->get_results(
            "SELECT DISTINCT c.id, c.first_name, c.last_name, c.organisation_name, c.email
             FROM {$contacts} c
             INNER JOIN {$purchases} p ON p.contact_id = c.id
             WHERE c.anonymised = 0
               AND c.consent_duck_race = 1
               AND c.email <> ''",
            ARRAY_A
        );

        if ( ! is_array( $rows ) ) {
            return 0;
        }

        $renderer = new TemplateRenderer();
        $mailer = new Mailer();
        $sent = 0;

        foreach ( $rows as $row ) {
            $context = $this->previous_race_context( (int) $row['id'] );
            $data = [
                'first_name' => (string) $row['first_name'],
                'last_name' => (string) $row['last_name'],
                'organisation_name' => (string) $row['organisation_name'],
                'race_title' => (string) $target_race->title,
                'race_date' => (string) $target_race->race_date,
                'race_time' => (string) $target_race->race_time,
                'race_location' => (string) $target_race->location,
                'buy_link' => $this->buy_link( (string) $target_race->slug ),
                'previous_race_result' => (string) ( $context['previous_race_result'] ?? '' ),
                'winner_position' => (string) ( $context['winner_position'] ?? '' ),
            ];

            $subject = $renderer->render_subject( 'winner_future_race_marketing', $data );
            $body = $renderer->render_body( 'winner_future_race_marketing', $data );

            $ok = $mailer->send(
                [
                    'to' => (string) $row['email'],
                    'subject' => $subject,
                    'body' => $body,
                    'email_type' => 'winner_future_race_marketing',
                    'race_id' => $race_id,
                ]
            );

            if ( $ok ) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * @return array<string, string>
     */
    private function previous_race_context( int $contact_id ): array {
        global $wpdb;

        $purchases = Schema::table_name( 'purchases' );
        $races = Schema::table_name( 'races' );
        $entries = Schema::table_name( 'entries' );

        $race = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT r.id, r.title, r.race_date
                 FROM {$races} r
                 INNER JOIN {$purchases} p ON p.race_id = r.id
                 WHERE p.contact_id = %d
                   AND p.payment_status = 'paid'
                 ORDER BY r.race_date DESC, r.id DESC
                 LIMIT 1",
                $contact_id
            ),
            ARRAY_A
        );

        if ( ! is_array( $race ) ) {
            return [ 'previous_race_result' => '', 'winner_position' => '' ];
        }

        $numbers = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT e.duck_number
                 FROM {$entries} e
                 INNER JOIN {$purchases} p ON p.id = e.purchase_id
                 WHERE p.contact_id = %d
                   AND p.race_id = %d
                 ORDER BY e.duck_number ASC",
                $contact_id,
                (int) $race['id']
            )
        );

        $winner_position = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT e.winner_position
                 FROM {$entries} e
                 INNER JOIN {$purchases} p ON p.id = e.purchase_id
                 WHERE p.contact_id = %d
                   AND p.race_id = %d
                   AND e.winner_position IS NOT NULL
                 ORDER BY e.winner_position ASC
                 LIMIT 1",
                $contact_id,
                (int) $race['id']
            )
        );

        $result = (string) $race['title'];
        if ( ! empty( $race['race_date'] ) ) {
            $result .= ' (' . (string) $race['race_date'] . ')';
        }
        if ( is_array( $numbers ) && ! empty( $numbers ) ) {
            $result .= ' - ducks: ' . implode( ', ', array_map( 'strval', $numbers ) );
        }

        $winner_text = '';
        if ( null !== $winner_position && '' !== (string) $winner_position ) {
            $winner_text = (string) $winner_position;
            $result .= ' - winner position: ' . $winner_text;
        }

        return [
            'previous_race_result' => $result,
            'winner_position' => $winner_text,
        ];
    }

    private function buy_link( string $race_slug ): string {
        $settings = get_option( 'duck_race_settings', [] );
        $buy_slug = (string) ( $settings['buy_page_slug'] ?? 'duck-race-buy' );
        return add_query_arg( [ 'race' => $race_slug ], home_url( '/' . $buy_slug . '/' ) );
    }

    private function nullable_bool( string $value ): ?bool {
        $value = strtolower( trim( $value ) );
        if ( '' === $value ) {
            return null;
        }

        if ( in_array( $value, [ '1', 'yes', 'y', 'true' ], true ) ) {
            return true;
        }

        if ( in_array( $value, [ '0', 'no', 'n', 'false' ], true ) ) {
            return false;
        }

        return null;
    }
}
