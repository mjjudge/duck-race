<?php

namespace DuckRace\Admin;

use DuckRace\Audit\Logger;
use DuckRace\Database\Schema;
use DuckRace\Security\RequestGuard;
use DuckRace\Services\PurchaseService;
use DuckRace\Services\ReportingService;

defined( 'ABSPATH' ) || exit;

class ReportingPage {

    private const EXPORT_NONCE_ACTION  = 'duck_race_export_csv';
    private const RESCUE_NONCE_ACTION  = 'duck_race_rescue_purchase';

    public function register(): void {
        add_action( 'admin_post_duck_race_export_csv',      [ $this, 'handle_export_csv' ] );
        add_action( 'admin_post_duck_race_rescue_purchase', [ $this, 'handle_rescue_purchase' ] );
    }

    public function render(): void {
        RequestGuard::require_capability( 'duck_race_manage_sales' );

        $service = new ReportingService();
        $races = $service->list_races();
        $race_id = (int) ( $_GET['race_id'] ?? ( ! empty( $races ) ? (int) $races[0]->id : 0 ) );
        $dashboard = $race_id > 0 ? $service->race_dashboard( $race_id ) : [];
        $totals = $dashboard['totals'] ?? [];

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Reporting & Export', 'duck-race' ) . '</h1>';

        if ( isset( $_GET['rescued'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Purchase marked as paid. Confirmation email sent.', 'duck-race' ) . '</p></div>';
        }
        if ( isset( $_GET['rescue_error'] ) ) {
            $code = (int) ( $_GET['rescue_error'] ?? 0 );
            $msg = 2 === $code
                ? __( 'Purchase could not be rescued — it was not in pending status.', 'duck-race' )
                : __( 'Invalid rescue request.', 'duck-race' );
            echo '<div class="notice notice-error"><p>' . esc_html( $msg ) . '</p></div>';
        }

        echo '<form method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '">';
        echo '<input type="hidden" name="page" value="duck-race-reporting" />';
        echo '<p><label for="race_id_filter"><strong>' . esc_html__( 'Race', 'duck-race' ) . '</strong></label> ';
        echo '<select id="race_id_filter" name="race_id">';
        foreach ( $races as $race ) {
            $label = (string) $race->title;
            if ( ! empty( $race->race_date ) ) {
                $label .= ' (' . (string) $race->race_date . ')';
            }
            echo '<option value="' . esc_attr( (string) $race->id ) . '" ' . selected( $race_id, (int) $race->id, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select> ';
        submit_button( __( 'Load Dashboard', 'duck-race' ), 'secondary', 'submit', false );
        echo '</p></form>';

        if ( $race_id <= 0 || [] === $dashboard ) {
            echo '<p>' . esc_html__( 'No race data available yet.', 'duck-race' ) . '</p>';
            echo '</div>';
            return;
        }

        echo '<h2>' . esc_html__( 'Sales Summary', 'duck-race' ) . '</h2>';
        echo '<table class="widefat striped" style="max-width: 900px;">';
        echo '<tbody>';
        $this->metric_row( __( 'Online Sales (paid)', 'duck-race' ), $this->money( (float) ( $totals['online_sales_total'] ?? 0 ) ) );
        $this->metric_row( __( 'Manual Sales (paid)', 'duck-race' ), $this->money( (float) ( $totals['manual_sales_total'] ?? 0 ) ) );
        $this->metric_row( __( 'Total Revenue (paid)', 'duck-race' ), $this->money( (float) ( $totals['revenue_total'] ?? 0 ) ) );
        $this->metric_row( __( 'Ducks Sold', 'duck-race' ), (string) (int) ( $totals['ducks_sold'] ?? 0 ) );
        $this->metric_row( __( 'Available Ducks', 'duck-race' ), (string) (int) ( $totals['available_ducks'] ?? 0 ) );
        $this->metric_row( __( 'Reserved Ducks', 'duck-race' ), (string) (int) ( $totals['ducks_reserved'] ?? 0 ) );
        $this->metric_row( __( 'Lost Ducks', 'duck-race' ), (string) (int) ( $totals['ducks_lost'] ?? 0 ) );
        echo '</tbody></table>';

        echo '<h2>' . esc_html__( 'Payment Status', 'duck-race' ) . '</h2>';
        echo '<table class="widefat striped" style="max-width: 900px;"><tbody>';
        $this->metric_row( __( 'Paid Purchases', 'duck-race' ), (string) (int) ( $totals['paid_count'] ?? 0 ) );
        $this->metric_row( __( 'Pending Purchases', 'duck-race' ), (string) (int) ( $totals['pending_count'] ?? 0 ) );
        $this->metric_row( __( 'Failed Purchases', 'duck-race' ), (string) (int) ( $totals['failed_count'] ?? 0 ) );
        $this->metric_row( __( 'Abandoned Purchases', 'duck-race' ), (string) (int) ( $totals['abandoned_count'] ?? 0 ) );
        echo '</tbody></table>';

        echo '<h2>' . esc_html__( 'Consent Totals', 'duck-race' ) . '</h2>';
        echo '<table class="widefat striped" style="max-width: 900px;"><tbody>';
        $this->metric_row( __( 'Contacts in Race', 'duck-race' ), (string) (int) ( $totals['total_contacts'] ?? 0 ) );
        $this->metric_row( __( 'Future Duck Race Consent', 'duck-race' ), (string) (int) ( $totals['consent_duck_race_yes'] ?? 0 ) );
        $this->metric_row( __( 'Organisation Consent', 'duck-race' ), (string) (int) ( $totals['consent_organisation_yes'] ?? 0 ) );
        echo '</tbody></table>';

        $this->render_pending_purchases_section( $race_id );

        echo '<h2>' . esc_html__( 'CSV Exports', 'duck-race' ) . '</h2>';
        echo '<p>' . esc_html__( 'Export selected race data as CSV.', 'duck-race' ) . '</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="duck_race_export_csv" />';
        echo '<input type="hidden" name="race_id" value="' . esc_attr( (string) $race_id ) . '" />';
        wp_nonce_field( self::EXPORT_NONCE_ACTION, '_wpnonce' );
        echo '<p><select name="export_type">';
        echo '<option value="entries">' . esc_html__( 'Race Entries', 'duck-race' ) . '</option>';
        echo '<option value="purchases">' . esc_html__( 'Purchases', 'duck-race' ) . '</option>';
        echo '<option value="contacts">' . esc_html__( 'Contacts', 'duck-race' ) . '</option>';
        echo '<option value="winners">' . esc_html__( 'Winners', 'duck-race' ) . '</option>';
        echo '<option value="accounting">' . esc_html__( 'Accounting Summary', 'duck-race' ) . '</option>';
        echo '</select> ';
        submit_button( __( 'Download CSV', 'duck-race' ), 'primary', 'submit', false );
        echo '</p></form>';

        echo '</div>';
    }

    public function handle_rescue_purchase(): void {
        RequestGuard::require_capability( 'duck_race_manage_sales' );
        RequestGuard::verify_admin_nonce( self::RESCUE_NONCE_ACTION, '_wpnonce' );

        $purchase_id = (int) ( $_POST['purchase_id'] ?? 0 );
        $race_id     = (int) ( $_POST['race_id'] ?? 0 );

        $redirect_base = add_query_arg( [ 'page' => 'duck-race-reporting', 'race_id' => $race_id ], admin_url( 'admin.php' ) );

        if ( $purchase_id <= 0 ) {
            wp_safe_redirect( add_query_arg( 'rescue_error', '1', $redirect_base ) );
            exit;
        }

        $service  = new PurchaseService();
        $purchase = $service->get_by_id( $purchase_id );

        if ( ! is_object( $purchase ) || 'pending' !== (string) $purchase->payment_status ) {
            wp_safe_redirect( add_query_arg( 'rescue_error', '2', $redirect_base ) );
            exit;
        }

        $service->mark_paid( $purchase_id, '', 'manual_rescue' );

        Logger::log(
            'purchase.manually_rescued',
            'purchase',
            $purchase_id,
            [ 'payment_status' => 'pending' ],
            [ 'payment_status' => 'paid', 'method' => 'admin_rescue' ]
        );

        wp_safe_redirect( add_query_arg( 'rescued', '1', $redirect_base ) );
        exit;
    }

    private function render_pending_purchases_section( int $race_id ): void {
        global $wpdb;

        $purchases_table = Schema::table_name( 'purchases' );
        $contacts_table  = Schema::table_name( 'contacts' );
        $entries_table   = Schema::table_name( 'entries' );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.id, p.grand_total, p.created_at, p.stripe_checkout_session_id,
                        c.id AS contact_id, c.first_name, c.last_name, c.email, c.created_at AS contact_created_at,
                        GROUP_CONCAT(e.duck_number ORDER BY e.duck_number SEPARATOR ', ') AS duck_numbers
                 FROM {$purchases_table} p
                 LEFT JOIN {$contacts_table} c ON c.id = p.contact_id
                 LEFT JOIN {$entries_table} e ON e.purchase_id = p.id AND e.entry_status = 'reserved'
                 WHERE p.payment_status = 'pending' AND p.race_id = %d
                 GROUP BY p.id
                 ORDER BY p.created_at DESC",
                $race_id
            )
        );

        if ( empty( $rows ) ) {
            return;
        }

        echo '<h2 style="color:#c00;">' . esc_html__( 'Pending Purchases — Action Required', 'duck-race' ) . '</h2>';
        echo '<p>' . esc_html__( 'These purchases have not been confirmed by Stripe. If payment was collected, use "Mark as Paid" to rescue the purchase and release the duck(s). Verify in your Stripe dashboard before rescuing.', 'duck-race' ) . '</p>';
        echo '<table class="widefat striped" style="max-width:1100px;">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'ID', 'duck-race' ) . '</th>';
        echo '<th>' . esc_html__( 'Buyer', 'duck-race' ) . '</th>';
        echo '<th>' . esc_html__( 'Duck(s)', 'duck-race' ) . '</th>';
        echo '<th>' . esc_html__( 'Amount', 'duck-race' ) . '</th>';
        echo '<th>' . esc_html__( 'Created', 'duck-race' ) . '</th>';
        echo '<th>' . esc_html__( 'Stripe Session', 'duck-race' ) . '</th>';
        echo '<th>' . esc_html__( 'Action', 'duck-race' ) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ( $rows as $row ) {
            $name    = trim( (string) $row->first_name . ' ' . (string) $row->last_name );
            $email   = (string) $row->email;
            $email_display = '' !== $email
                ? $email
                : ( ! empty( $row->contact_id ) ? \DuckRace\Services\ContactService::no_email_display( (int) $row->contact_id, (string) $row->contact_created_at ) : '' );
            $session = (string) $row->stripe_checkout_session_id;
            echo '<tr>';
            echo '<td>' . esc_html( (string) $row->id ) . '</td>';
            echo '<td>' . esc_html( $name ?: '—' ) . ( $email_display ? ' <br><small>' . esc_html( $email_display ) . '</small>' : '' ) . '</td>';
            echo '<td>' . esc_html( (string) ( $row->duck_numbers ?: '—' ) ) . '</td>';
            echo '<td>' . esc_html( $this->money( (float) $row->grand_total ) ) . '</td>';
            echo '<td>' . esc_html( (string) $row->created_at ) . '</td>';
            echo '<td>' . ( $session ? '<code>' . esc_html( $session ) . '</code>' : '—' ) . '</td>';
            echo '<td>';
            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(' . esc_attr( wp_json_encode( __( 'Mark this purchase as paid? Only do this if you have confirmed payment in Stripe.', 'duck-race' ) ) ) . ');">';
            echo '<input type="hidden" name="action" value="duck_race_rescue_purchase" />';
            echo '<input type="hidden" name="purchase_id" value="' . esc_attr( (string) $row->id ) . '" />';
            echo '<input type="hidden" name="race_id" value="' . esc_attr( (string) $race_id ) . '" />';
            wp_nonce_field( self::RESCUE_NONCE_ACTION, '_wpnonce' );
            echo '<button type="submit" class="button button-primary button-small">' . esc_html__( 'Mark as Paid', 'duck-race' ) . '</button>';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    public function handle_export_csv(): void {
        RequestGuard::require_capability( 'duck_race_manage_sales' );
        RequestGuard::verify_admin_nonce( self::EXPORT_NONCE_ACTION, '_wpnonce' );

        $race_id = (int) ( $_POST['race_id'] ?? 0 );
        $type = sanitize_key( (string) ( $_POST['export_type'] ?? '' ) );
        if ( $race_id <= 0 || ! in_array( $type, [ 'entries', 'purchases', 'contacts', 'winners', 'accounting' ], true ) ) {
            wp_die( esc_html__( 'Invalid export request.', 'duck-race' ) );
        }

        $service = new ReportingService();
        $headers = $service->export_headers( $type );
        $rows = $service->export_rows( $race_id, $type );

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="duck-race-' . $type . '-race-' . $race_id . '.csv"' );

        $out = fopen( 'php://output', 'w' );
        if ( false === $out ) {
            wp_die( esc_html__( 'Unable to generate CSV output.', 'duck-race' ) );
        }

        fputcsv( $out, $headers );
        foreach ( $rows as $row ) {
            fputcsv( $out, $row );
        }
        fclose( $out );
        exit;
    }

    private function metric_row( string $label, string $value ): void {
        echo '<tr><th scope="row" style="width:50%;">' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
    }

    private function money( float $amount ): string {
        return 'GBP ' . number_format( $amount, 2, '.', ',' );
    }
}
