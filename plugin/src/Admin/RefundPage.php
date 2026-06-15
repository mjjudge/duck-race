<?php

namespace DuckRace\Admin;

use DuckRace\Database\Schema;
use DuckRace\Security\RequestGuard;
use DuckRace\Services\PurchaseService;
use DuckRace\Services\RefundService;

defined( 'ABSPATH' ) || exit;

class RefundPage {

    private const NONCE_ACTION = 'duck_race_process_refund';

    public function register(): void {
        add_action( 'admin_post_duck_race_process_refund', [ $this, 'handle_refund' ] );
    }

    public function render(): void {
        RequestGuard::require_capability( 'duck_race_process_refunds' );

        $race_id = absint( $_GET['race_id'] ?? 0 );
        $races   = $this->get_races();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Refunds', 'duck-race' ) . '</h1>';

        if ( isset( $_GET['refunded'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Refund processed successfully.', 'duck-race' ) . '</p></div>';
        }

        if ( isset( $_GET['error'] ) ) {
            $msg = sanitize_text_field( wp_unslash( (string) ( $_GET['error'] ?? '' ) ) );
            echo '<div class="notice notice-error is-dismissible"><p>';
            echo esc_html( $this->friendly_error( $msg ) );
            echo '</p></div>';
        }

        // Race selector.
        echo '<form method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '">';
        echo '<input type="hidden" name="page" value="duck-race-refunds" />';
        echo '<select name="race_id" onchange="this.form.submit()">';
        echo '<option value="0">' . esc_html__( '— Select a race —', 'duck-race' ) . '</option>';
        foreach ( $races as $race ) {
            $selected = selected( $race_id, (int) $race->id, false );
            echo '<option value="' . esc_attr( (string) $race->id ) . '" ' . $selected . '>' . esc_html( $race->title ) . '</option>';
        }
        echo '</select>';
        echo '</form>';

        if ( $race_id <= 0 ) {
            echo '</div>';
            return;
        }

        $service   = new PurchaseService();
        $purchases = $service->get_paid_by_race( $race_id );

        $paid      = array_filter( $purchases, static fn( $p ) => 'paid' === (string) $p->payment_status );
        $refunded  = array_filter( $purchases, static fn( $p ) => 'refunded' === (string) $p->payment_status );

        echo '<h2>' . esc_html__( 'Paid Purchases', 'duck-race' ) . '</h2>';

        if ( empty( $paid ) ) {
            echo '<p>' . esc_html__( 'No paid purchases for this race.', 'duck-race' ) . '</p>';
        } else {
            echo '<table class="widefat striped">';
            echo '<thead><tr>';
            echo '<th>' . esc_html__( 'ID', 'duck-race' ) . '</th>';
            echo '<th>' . esc_html__( 'Buyer', 'duck-race' ) . '</th>';
            echo '<th>' . esc_html__( 'Duck numbers', 'duck-race' ) . '</th>';
            echo '<th>' . esc_html__( 'Amount', 'duck-race' ) . '</th>';
            echo '<th>' . esc_html__( 'Source', 'duck-race' ) . '</th>';
            echo '<th>' . esc_html__( 'Paid', 'duck-race' ) . '</th>';
            echo '<th>' . esc_html__( 'Action', 'duck-race' ) . '</th>';
            echo '</tr></thead><tbody>';

            foreach ( $paid as $row ) {
                $buyer     = esc_html( trim( $row->first_name . ' ' . $row->last_name ) );
                $amount    = '£' . number_format( (float) $row->grand_total, 2 );
                $source    = 'online' === $row->purchase_source ? __( 'Online (Stripe)', 'duck-race' ) : __( 'Manual', 'duck-race' );
                $paid_date = $row->paid_at ? wp_date( 'd/m/Y', strtotime( $row->paid_at ) ) : '—';

                echo '<tr>';
                echo '<td>' . esc_html( (string) $row->id ) . '</td>';
                echo '<td>' . $buyer . '<br><small>' . esc_html( $row->email ) . '</small></td>';
                echo '<td>' . esc_html( (string) ( $row->duck_numbers ?: '—' ) ) . '</td>';
                echo '<td>' . esc_html( $amount ) . '</td>';
                echo '<td>' . esc_html( $source ) . '</td>';
                echo '<td>' . esc_html( $paid_date ) . '</td>';
                echo '<td>';
                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(' . esc_attr( wp_json_encode( __( 'Issue a full refund for this purchase? This cannot be undone.', 'duck-race' ) ) ) . ');">';
                echo '<input type="hidden" name="action" value="duck_race_process_refund" />';
                echo '<input type="hidden" name="purchase_id" value="' . esc_attr( (string) $row->id ) . '" />';
                echo '<input type="hidden" name="race_id" value="' . esc_attr( (string) $race_id ) . '" />';
                wp_nonce_field( self::NONCE_ACTION, 'duck_race_refund_nonce' );
                echo '<input type="text" name="reason" placeholder="' . esc_attr__( 'Reason (optional)', 'duck-race' ) . '" style="width:160px;margin-right:4px;" />';
                echo '<button type="submit" class="button button-secondary">' . esc_html__( 'Refund', 'duck-race' ) . '</button>';
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        if ( ! empty( $refunded ) ) {
            echo '<h2 style="margin-top:2em;">' . esc_html__( 'Refunded Purchases', 'duck-race' ) . '</h2>';
            echo '<table class="widefat striped">';
            echo '<thead><tr>';
            echo '<th>' . esc_html__( 'ID', 'duck-race' ) . '</th>';
            echo '<th>' . esc_html__( 'Buyer', 'duck-race' ) . '</th>';
            echo '<th>' . esc_html__( 'Duck numbers', 'duck-race' ) . '</th>';
            echo '<th>' . esc_html__( 'Refunded amount', 'duck-race' ) . '</th>';
            echo '<th>' . esc_html__( 'Reason', 'duck-race' ) . '</th>';
            echo '<th>' . esc_html__( 'Refunded on', 'duck-race' ) . '</th>';
            echo '</tr></thead><tbody>';

            foreach ( $refunded as $row ) {
                $buyer         = esc_html( trim( $row->first_name . ' ' . $row->last_name ) );
                $refund_amount = '£' . number_format( (float) ( $row->refunded_amount ?? $row->grand_total ), 2 );
                $refund_date   = $row->refunded_at ? wp_date( 'd/m/Y', strtotime( $row->refunded_at ) ) : '—';

                echo '<tr>';
                echo '<td>' . esc_html( (string) $row->id ) . '</td>';
                echo '<td>' . $buyer . '</td>';
                echo '<td>' . esc_html( (string) ( $row->duck_numbers ?: '—' ) ) . '</td>';
                echo '<td>' . esc_html( $refund_amount ) . '</td>';
                echo '<td>' . esc_html( (string) ( $row->refund_reason ?: '—' ) ) . '</td>';
                echo '<td>' . esc_html( $refund_date ) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        echo '</div>';
    }

    public function handle_refund(): void {
        RequestGuard::require_capability( 'duck_race_process_refunds' );
        check_admin_referer( self::NONCE_ACTION, 'duck_race_refund_nonce' );

        $purchase_id = absint( $_POST['purchase_id'] ?? 0 );
        $race_id     = absint( $_POST['race_id'] ?? 0 );
        $reason      = sanitize_text_field( wp_unslash( (string) ( $_POST['reason'] ?? '' ) ) );

        $redirect = add_query_arg(
            [ 'page' => 'duck-race-refunds', 'race_id' => $race_id ],
            admin_url( 'admin.php' )
        );

        if ( $purchase_id <= 0 ) {
            wp_safe_redirect( add_query_arg( 'error', 'invalid_purchase', $redirect ) );
            exit;
        }

        $result = ( new RefundService() )->process( $purchase_id, $reason );

        if ( $result['ok'] ) {
            wp_safe_redirect( add_query_arg( 'refunded', '1', $redirect ) );
        } else {
            wp_safe_redirect( add_query_arg( 'error', rawurlencode( $result['error'] ?? 'unknown' ), $redirect ) );
        }
        exit;
    }

    /**
     * @return object[]
     */
    private function get_races(): array {
        global $wpdb;
        $table = Schema::table_name( 'races' );

        return $wpdb->get_results(
            "SELECT id, title FROM {$table} ORDER BY race_date DESC"
        ) ?: [];
    }

    private function friendly_error( string $code ): string {
        return match ( $code ) {
            'purchase_not_found'    => __( 'Purchase not found.', 'duck-race' ),
            'not_refundable'        => __( 'This purchase is not in a refundable state.', 'duck-race' ),
            'stripe_not_configured' => __( 'Stripe is not configured. Please check Settings.', 'duck-race' ),
            'no_stripe_identifier'  => __( 'No Stripe charge or payment intent found for this purchase.', 'duck-race' ),
            'stripe_request_failed' => __( 'Could not reach Stripe. Please try again.', 'duck-race' ),
            'invalid_purchase'      => __( 'Invalid purchase.', 'duck-race' ),
            default                 => sprintf( __( 'Refund failed: %s', 'duck-race' ), $code ),
        };
    }
}
