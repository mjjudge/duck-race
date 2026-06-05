<?php

namespace DuckRace\Services;

use DuckRace\Audit\Logger;
use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class RetentionService {

    public const CRON_HOOK = 'duck_race_retention_cron';

    public static function ensure_schedule(): void {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
        }
    }

    public static function clear_schedule(): void {
        wp_clear_scheduled_hook( self::CRON_HOOK );
    }

    public function run_scheduled(): int {
        return $this->run_anonymisation( 'cron' );
    }

    public function run_manual(): int {
        return $this->run_anonymisation( 'manual' );
    }

    public function retention_days(): int {
        $settings = get_option( 'duck_race_settings', [] );
        $days = (int) ( $settings['retention_non_opt_in_days'] ?? 365 );

        return max( 30, min( 3650, $days ) );
    }

    private function run_anonymisation( string $source ): int {
        global $wpdb;

        $contacts_table = Schema::table_name( 'contacts' );
        $purchases_table = Schema::table_name( 'purchases' );
        $races_table = Schema::table_name( 'races' );

        $days = $this->retention_days();
        $cutoff = gmdate( 'Y-m-d', strtotime( '-' . $days . ' days' ) );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.id
                 FROM {$contacts_table} c
                 INNER JOIN {$purchases_table} p ON p.contact_id = c.id
                 INNER JOIN {$races_table} r ON r.id = p.race_id
                 WHERE c.anonymised = 0
                   AND c.consent_duck_race = 0
                   AND c.consent_organisation = 0
                   AND p.payment_status = 'paid'
                 GROUP BY c.id
                 HAVING MAX(CASE WHEN r.status IN ('draft', 'open', 'closed') THEN 1 ELSE 0 END) = 0
                    AND MAX(COALESCE(r.race_date, DATE(p.created_at))) <= %s",
                $cutoff
            ),
            ARRAY_A
        );

        if ( ! is_array( $rows ) || [] === $rows ) {
            $this->store_last_run_meta( 0 );
            return 0;
        }

        $anonymised = 0;
        foreach ( $rows as $row ) {
            $contact_id = (int) ( $row['id'] ?? 0 );
            if ( $contact_id <= 0 ) {
                continue;
            }

            $contact = $wpdb->get_row(
                $wpdb->prepare( "SELECT * FROM {$contacts_table} WHERE id = %d", $contact_id ),
                ARRAY_A
            );
            if ( ! is_array( $contact ) ) {
                continue;
            }

            $before = [
                'first_name' => (string) ( $contact['first_name'] ?? '' ),
                'last_name' => (string) ( $contact['last_name'] ?? '' ),
                'organisation_name' => (string) ( $contact['organisation_name'] ?? '' ),
                'email' => (string) ( $contact['email'] ?? '' ),
                'phone' => (string) ( $contact['phone'] ?? '' ),
                'address_line_1' => (string) ( $contact['address_line_1'] ?? '' ),
                'address_line_2' => (string) ( $contact['address_line_2'] ?? '' ),
                'city' => (string) ( $contact['city'] ?? '' ),
                'postcode' => (string) ( $contact['postcode'] ?? '' ),
                'country' => (string) ( $contact['country'] ?? '' ),
                'notes' => (string) ( $contact['notes'] ?? '' ),
                'anonymised' => (int) ( $contact['anonymised'] ?? 0 ),
            ];

            $now = current_time( 'mysql', true );
            $wpdb->update(
                $contacts_table,
                [
                    'first_name' => 'Anonymised',
                    'last_name' => 'Contact #' . $contact_id,
                    'organisation_name' => '',
                    'phone' => '',
                    'address_line_1' => '',
                    'address_line_2' => '',
                    'city' => '',
                    'postcode' => '',
                    'country' => '',
                    'notes' => '',
                    'anonymised' => 1,
                    'anonymised_at' => $now,
                    'updated_at' => $now,
                ],
                [ 'id' => $contact_id ]
            );

            $after = [
                'first_name' => 'Anonymised',
                'last_name' => 'Contact #' . $contact_id,
                'organisation_name' => '',
                'email' => (string) ( $contact['email'] ?? '' ),
                'phone' => '',
                'address_line_1' => '',
                'address_line_2' => '',
                'city' => '',
                'postcode' => '',
                'country' => '',
                'notes' => '',
                'anonymised' => 1,
                'anonymised_at' => $now,
            ];

            Logger::log(
                'contact.anonymised',
                'contact',
                $contact_id,
                $before,
                $after,
                [
                    'source' => $source,
                    'retention_days' => $days,
                    'cutoff_date' => $cutoff,
                ]
            );

            $anonymised++;
        }

        $this->store_last_run_meta( $anonymised );

        return $anonymised;
    }

    private function store_last_run_meta( int $count ): void {
        update_option( 'duck_race_retention_last_run_at', current_time( 'mysql', true ), false );
        update_option( 'duck_race_retention_last_run_count', $count, false );
    }
}
