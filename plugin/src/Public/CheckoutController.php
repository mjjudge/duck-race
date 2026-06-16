<?php

namespace DuckRace\Public;

use DuckRace\Security\RequestGuard;
use DuckRace\Services\ContactService;
use DuckRace\Services\DuckAllocationService;
use DuckRace\Services\PurchaseService;
use DuckRace\Services\RaceService;
use DuckRace\Services\StripeService;

defined( 'ABSPATH' ) || exit;

class CheckoutController {

    private const NONCE_ACTION = 'duck_race_public_buy';

    public function register(): void {
        add_action( 'admin_post_nopriv_duck_race_start_checkout', [ $this, 'start_checkout' ] );
        add_action( 'admin_post_duck_race_start_checkout', [ $this, 'start_checkout' ] );
    }

    public function start_checkout(): void {
        if ( ! RequestGuard::verify_public_nonce( self::NONCE_ACTION, 'duck_race_nonce' ) ) {
            $this->redirect_failure( __( 'Security check failed.', 'duck-race' ) );
        }

        if ( ! RequestGuard::passes_honeypot( 'website' ) ) {
            $this->redirect_failure( __( 'Submission rejected.', 'duck-race' ) );
        }

        $race_id = (int) ( $_POST['race_id'] ?? 0 );
        $race = ( new RaceService() )->get_by_id( $race_id );
        if ( ! $race ) {
            $this->redirect_failure( __( 'Race not found.', 'duck-race' ) );
        }

        $lifecycle = new \DuckRace\Services\RaceLifecycleService();
        if ( ! $lifecycle->is_online_sales_open( (string) $race->status, (string) $race->sales_open_at, (string) $race->sales_close_at ) ) {
            $this->redirect_failure( __( 'Online sales are closed for this race.', 'duck-race' ) );
        }

        $duck_count = max( 1, (int) ( $_POST['duck_count'] ?? 1 ) );

        // Honour preview numbers if provided; fall back to auto-allocation for unavailable ones.
        $preview_numbers = array_values(
            array_filter( array_map( 'absint', (array) ( $_POST['duck_number_preview'] ?? [] ) ) )
        );

        $allocator = new DuckAllocationService();
        $allocated = [];

        foreach ( $preview_numbers as $number ) {
            if ( count( $allocated ) >= $duck_count ) {
                break;
            }
            if ( $allocator->can_choose_online_number( $race, $number ) ) {
                $allocated[] = $number;
            }
        }

        $remaining = max( 0, $duck_count - count( $allocated ) );
        if ( $remaining > 0 ) {
            $auto = $allocator->next_available_numbers( $race, 'online', $remaining );
            if ( count( $auto ) < $remaining ) {
                $this->redirect_failure( __( 'Not enough ducks available.', 'duck-race' ) );
            }
            $allocated = array_merge( $allocated, $auto );
        }

        $contact_data = [
            'email'                => wp_unslash( $_POST['email'] ?? '' ),
            'first_name'           => wp_unslash( $_POST['first_name'] ?? '' ),
            'last_name'            => wp_unslash( $_POST['last_name'] ?? '' ),
            'consent_duck_race'    => ! empty( $_POST['consent_duck_race'] ),
            'consent_organisation' => ! empty( $_POST['consent_organisation'] ),
            'consent_source'       => 'online_purchase_form',
            'consent_timestamp'    => current_time( 'mysql', true ),
        ];

        // Include optional address/phone only when non-empty so existing values are not overwritten with blanks.
        foreach ( [ 'phone', 'address_line_1', 'address_line_2', 'city', 'county', 'postcode' ] as $field ) {
            $val = sanitize_text_field( wp_unslash( (string) ( $_POST[ $field ] ?? '' ) ) );
            if ( '' !== $val ) {
                $contact_data[ $field ] = $val;
            }
        }

        $contact_id = ( new ContactService() )->upsert_by_email( $contact_data );
        if ( $contact_id <= 0 ) {
            $this->redirect_failure( __( 'Could not save buyer details.', 'duck-race' ) );
        }

        // Accept per-duck name inputs (duck_name[]) from the new form.
        $duck_names = array_values(
            array_filter(
                array_map(
                    'sanitize_text_field',
                    array_map( 'wp_unslash', (array) ( $_POST['duck_name'] ?? [] ) )
                )
            )
        );

        $uplift_total      = 0.0;
        $total_duck_amount = (float) $race->price_per_duck * count( $allocated );

        // Voluntary donation: clamp to a reasonable max and round to 2dp.
        $donation_raw    = (float) ( $_POST['donation_amount'] ?? 0 );
        $donation_amount = max( 0.0, min( 1000.0, round( $donation_raw, 2 ) ) );
        $gift_aid        = ! empty( $_POST['gift_aid'] );

        $purchase_service = new PurchaseService();
        $purchase_id = $purchase_service->create_online_pending(
            (int) $race->id,
            $contact_id,
            $allocated,
            $duck_names,
            $total_duck_amount,
            $uplift_total,
            $donation_amount,
            $gift_aid
        );

        if ( $purchase_id <= 0 ) {
            $this->redirect_failure( __( 'Could not create purchase.', 'duck-race' ) );
        }

        $purchase        = $purchase_service->get_by_id( $purchase_id );
        $duck_word       = count( $allocated ) === 1 ? 'duck' : 'ducks';
        $race_title      = (string) ( $race->title ?: 'Duck Race' );
        $product_name    = sprintf( '%s — %d %s', $race_title, count( $allocated ), $duck_word );
        $payment_ref     = sprintf( 'Duck Race: %s | %d %s | Ref #%d', $race_title, count( $allocated ), $duck_word, $purchase_id );
        $amount          = $purchase ? (float) $purchase->grand_total : (float) $total_duck_amount + (float) $uplift_total;

        $session = ( new StripeService() )->create_checkout_session( $purchase_id, $amount, $product_name, $payment_ref );
        $purchase_service->attach_checkout_session( $purchase_id, $session['session_id'] );

        // wp_safe_redirect blocks external domains (e.g. checkout.stripe.com) — use wp_redirect.
        wp_redirect( esc_url_raw( $session['checkout_url'] ) );
        exit;
    }

    /**
     * @return array<int, int>
     */
    private function parse_numbers( string $raw ): array {
        $parts = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
        $numbers = [];
        foreach ( $parts as $part ) {
            $n = (int) $part;
            if ( $n > 0 ) {
                $numbers[] = $n;
            }
        }

        return array_values( array_unique( $numbers ) );
    }

    /**
     * @return array<int, string>
     */
    private function parse_names( string $raw ): array {
        $parts = array_filter( array_map( 'trim', explode( ',', $raw ) ) );

        return array_values( array_map( 'sanitize_text_field', $parts ) );
    }

    private function redirect_failure( string $message ): void {
        $entry = [
            'time'       => current_time( 'mysql' ),
            'reason'     => $message,
            'race_id'    => (int) ( $_POST['race_id'] ?? 0 ),
            'email'      => sanitize_email( (string) ( $_POST['email'] ?? '' ) ),
            'duck_count' => (int) ( $_POST['duck_count'] ?? 0 ),
        ];

        // PHP error log (visible in WP_DEBUG_LOG or server logs).
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions
        error_log( sprintf(
            '[Duck Race Checkout] %s | race_id=%d | email=%s | duck_count=%d',
            $message,
            $entry['race_id'],
            $entry['email'],
            $entry['duck_count']
        ) );

        // Admin-visible ring buffer — keeps last 20 failures.
        $log = (array) get_option( 'duck_race_checkout_error_log', [] );
        array_unshift( $log, $entry );
        update_option( 'duck_race_checkout_error_log', array_slice( $log, 0, 20 ), false );

        $settings     = get_option( 'duck_race_settings', [] );
        $failure_slug = (string) ( $settings['failure_page_slug'] ?? 'duck-race-failure' );
        $url = add_query_arg( [ 'error' => rawurlencode( $message ) ], home_url( '/' . $failure_slug ) );
        wp_safe_redirect( $url );
        exit;
    }
}
