<?php

namespace DuckRace\Services;

use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class ReportingService {

    /**
     * @return array<int, object>
     */
    public function list_races(): array {
        global $wpdb;

        $table = Schema::table_name( 'races' );
        $rows = $wpdb->get_results( "SELECT id, title, race_date, status, total_range_start, total_range_end FROM {$table} ORDER BY race_date DESC, id DESC" );

        return is_array( $rows ) ? $rows : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function race_dashboard( int $race_id ): array {
        global $wpdb;

        $purchases_table = Schema::table_name( 'purchases' );
        $entries_table = Schema::table_name( 'entries' );
        $contacts_table = Schema::table_name( 'contacts' );
        $races_table = Schema::table_name( 'races' );

        $race = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$races_table} WHERE id = %d", $race_id ), ARRAY_A );
        if ( ! is_array( $race ) ) {
            return [];
        }

        $sales = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    SUM(CASE WHEN purchase_source = 'online' AND payment_status = 'paid' THEN grand_total ELSE 0 END) AS online_sales_total,
                    SUM(CASE WHEN purchase_source = 'manual' AND payment_status = 'paid' THEN grand_total ELSE 0 END) AS manual_sales_total,
                    SUM(CASE WHEN payment_status = 'paid' THEN grand_total ELSE 0 END) AS revenue_total,
                    SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                    SUM(CASE WHEN payment_status = 'failed' THEN 1 ELSE 0 END) AS failed_count,
                    SUM(CASE WHEN payment_status = 'abandoned' THEN 1 ELSE 0 END) AS abandoned_count,
                    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_count
                 FROM {$purchases_table}
                 WHERE race_id = %d",
                $race_id
            ),
            ARRAY_A
        );

        $entry_counts = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    SUM(CASE WHEN entry_status IN ('sold_online', 'sold_manual', 'winner') THEN 1 ELSE 0 END) AS ducks_sold,
                    SUM(CASE WHEN entry_status = 'sold_online' THEN 1 ELSE 0 END) AS ducks_sold_online,
                    SUM(CASE WHEN entry_status = 'sold_manual' THEN 1 ELSE 0 END) AS ducks_sold_manual,
                    SUM(CASE WHEN entry_status = 'reserved' THEN 1 ELSE 0 END) AS ducks_reserved,
                    SUM(CASE WHEN entry_status = 'lost' THEN 1 ELSE 0 END) AS ducks_lost,
                    SUM(CASE WHEN entry_status = 'winner' THEN 1 ELSE 0 END) AS ducks_winner
                 FROM {$entries_table}
                 WHERE race_id = %d",
                $race_id
            ),
            ARRAY_A
        );

        $consents = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    SUM(CASE WHEN c.consent_duck_race = 1 THEN 1 ELSE 0 END) AS consent_duck_race_yes,
                    SUM(CASE WHEN c.consent_organisation = 1 THEN 1 ELSE 0 END) AS consent_organisation_yes,
                    COUNT(*) AS total_contacts
                 FROM {$contacts_table} c
                 INNER JOIN {$purchases_table} p ON p.contact_id = c.id
                 WHERE p.race_id = %d
                 GROUP BY p.race_id",
                $race_id
            ),
            ARRAY_A
        );

        $total_ducks = max( 0, ( (int) ( $race['total_range_end'] ?? 0 ) - (int) ( $race['total_range_start'] ?? 0 ) ) + 1 );
        $ducks_sold = (int) ( $entry_counts['ducks_sold'] ?? 0 );
        $available_ducks = max( 0, $total_ducks - $ducks_sold - (int) ( $entry_counts['ducks_lost'] ?? 0 ) - (int) ( $entry_counts['ducks_reserved'] ?? 0 ) );

        return [
            'race' => $race,
            'totals' => [
                'online_sales_total' => (float) ( $sales['online_sales_total'] ?? 0 ),
                'manual_sales_total' => (float) ( $sales['manual_sales_total'] ?? 0 ),
                'revenue_total' => (float) ( $sales['revenue_total'] ?? 0 ),
                'paid_count' => (int) ( $sales['paid_count'] ?? 0 ),
                'pending_count' => (int) ( $sales['pending_count'] ?? 0 ),
                'failed_count' => (int) ( $sales['failed_count'] ?? 0 ),
                'abandoned_count' => (int) ( $sales['abandoned_count'] ?? 0 ),
                'ducks_sold' => $ducks_sold,
                'ducks_sold_online' => (int) ( $entry_counts['ducks_sold_online'] ?? 0 ),
                'ducks_sold_manual' => (int) ( $entry_counts['ducks_sold_manual'] ?? 0 ),
                'ducks_reserved' => (int) ( $entry_counts['ducks_reserved'] ?? 0 ),
                'ducks_lost' => (int) ( $entry_counts['ducks_lost'] ?? 0 ),
                'ducks_winner' => (int) ( $entry_counts['ducks_winner'] ?? 0 ),
                'total_ducks' => $total_ducks,
                'available_ducks' => $available_ducks,
                'consent_duck_race_yes' => (int) ( $consents['consent_duck_race_yes'] ?? 0 ),
                'consent_organisation_yes' => (int) ( $consents['consent_organisation_yes'] ?? 0 ),
                'total_contacts' => (int) ( $consents['total_contacts'] ?? 0 ),
            ],
        ];
    }

    /**
     * @return array<int, array<int|string|float|null>>
     */
    public function export_rows( int $race_id, string $type ): array {
        return match ( $type ) {
            'entries' => $this->export_entries( $race_id ),
            'purchases' => $this->export_purchases( $race_id ),
            'contacts' => $this->export_contacts( $race_id ),
            'winners' => $this->export_winners( $race_id ),
            'accounting' => $this->export_accounting( $race_id ),
            default => [],
        };
    }

    /**
     * @return array<int, string>
     */
    public function export_headers( string $type ): array {
        return match ( $type ) {
            'entries' => [ 'entry_id', 'race_id', 'purchase_id', 'contact_id', 'duck_number', 'duck_name', 'entry_status', 'winner_position', 'prize_label', 'created_at' ],
            'purchases' => [ 'purchase_id', 'race_id', 'contact_id', 'purchase_source', 'payment_status', 'total_duck_amount', 'chosen_number_uplift_total', 'donation_amount', 'grand_total', 'currency', 'created_at', 'paid_at' ],
            'contacts' => [ 'contact_id', 'first_name', 'last_name', 'organisation_name', 'email', 'phone', 'consent_duck_race', 'consent_organisation', 'consent_timestamp', 'anonymised', 'created_at' ],
            'winners' => [ 'race_id', 'winner_position', 'prize_label', 'duck_number', 'first_name', 'last_name', 'organisation_name' ],
            'accounting' => [ 'race_id', 'purchase_id', 'payment_status', 'purchase_source', 'total_duck_amount', 'chosen_number_uplift_total', 'donation_amount', 'grand_total', 'currency', 'paid_at' ],
            default => [],
        };
    }

    /**
     * @return array<int, array<int|string|float|null>>
     */
    private function export_entries( int $race_id ): array {
        global $wpdb;
        $table = Schema::table_name( 'entries' );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, race_id, purchase_id, contact_id, duck_number, duck_name, entry_status, winner_position, prize_label, created_at
                 FROM {$table}
                 WHERE race_id = %d
                 ORDER BY duck_number ASC",
                $race_id
            ),
            ARRAY_N
        );

        return is_array( $rows ) ? $rows : [];
    }

    /**
     * @return array<int, array<int|string|float|null>>
     */
    private function export_purchases( int $race_id ): array {
        global $wpdb;
        $table = Schema::table_name( 'purchases' );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, race_id, contact_id, purchase_source, payment_status, total_duck_amount, chosen_number_uplift_total, donation_amount, grand_total, currency, created_at, paid_at
                 FROM {$table}
                 WHERE race_id = %d
                 ORDER BY id ASC",
                $race_id
            ),
            ARRAY_N
        );

        return is_array( $rows ) ? $rows : [];
    }

    /**
     * @return array<int, array<int|string|float|null>>
     */
    private function export_contacts( int $race_id ): array {
        global $wpdb;

        $contacts = Schema::table_name( 'contacts' );
        $purchases = Schema::table_name( 'purchases' );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT c.id, c.first_name, c.last_name, c.organisation_name, c.email, c.phone,
                        c.consent_duck_race, c.consent_organisation, c.consent_timestamp, c.anonymised, c.created_at
                 FROM {$contacts} c
                 INNER JOIN {$purchases} p ON p.contact_id = c.id
                 WHERE p.race_id = %d
                 ORDER BY c.id ASC",
                $race_id
            ),
            ARRAY_N
        );

        return is_array( $rows ) ? $rows : [];
    }

    /**
     * @return array<int, array<int|string|float|null>>
     */
    private function export_winners( int $race_id ): array {
        global $wpdb;

        $entries = Schema::table_name( 'entries' );
        $contacts = Schema::table_name( 'contacts' );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.race_id, e.winner_position, e.prize_label, e.duck_number, c.first_name, c.last_name, c.organisation_name
                 FROM {$entries} e
                 INNER JOIN {$contacts} c ON c.id = e.contact_id
                 WHERE e.race_id = %d
                   AND e.winner_position IS NOT NULL
                 ORDER BY e.winner_position ASC, e.duck_number ASC",
                $race_id
            ),
            ARRAY_N
        );

        return is_array( $rows ) ? $rows : [];
    }

    /**
     * @return array<int, array<int|string|float|null>>
     */
    private function export_accounting( int $race_id ): array {
        global $wpdb;
        $table = Schema::table_name( 'purchases' );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT race_id, id, payment_status, purchase_source, total_duck_amount, chosen_number_uplift_total, donation_amount, grand_total, currency, paid_at
                 FROM {$table}
                 WHERE race_id = %d
                 ORDER BY id ASC",
                $race_id
            ),
            ARRAY_N
        );

        return is_array( $rows ) ? $rows : [];
    }
}
