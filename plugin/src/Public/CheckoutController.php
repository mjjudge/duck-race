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
        $chosen_numbers = $this->parse_numbers( (string) ( $_POST['chosen_numbers'] ?? '' ) );

        $allocator = new DuckAllocationService();
        $allocated = [];
        $uplift_total = 0.0;

        foreach ( $chosen_numbers as $number ) {
            if ( ! $allocator->can_choose_online_number( $race, $number ) ) {
                $this->redirect_failure( __( 'A chosen duck number is unavailable.', 'duck-race' ) );
            }
            $allocated[] = $number;
            $uplift_total += (float) $race->chosen_number_uplift;
        }

        $remaining = max( 0, $duck_count - count( $allocated ) );
        if ( $remaining > 0 ) {
            $auto = $allocator->next_available_numbers( $race, 'online', $remaining );
            if ( count( $auto ) < $remaining ) {
                $this->redirect_failure( __( 'Not enough ducks available.', 'duck-race' ) );
            }
            $allocated = array_merge( $allocated, $auto );
        }

        $contact_id = ( new ContactService() )->upsert_by_email(
            [
                'email' => wp_unslash( $_POST['email'] ?? '' ),
                'first_name' => wp_unslash( $_POST['first_name'] ?? '' ),
                'last_name' => wp_unslash( $_POST['last_name'] ?? '' ),
                'consent_duck_race' => ! empty( $_POST['consent_duck_race'] ),
                'consent_organisation' => ! empty( $_POST['consent_organisation'] ),
                'consent_source' => 'online_purchase_form',
                'consent_timestamp' => current_time( 'mysql', true ),
            ]
        );
        if ( $contact_id <= 0 ) {
            $this->redirect_failure( __( 'Could not save buyer details.', 'duck-race' ) );
        }

        $duck_names = $this->parse_names( (string) ( $_POST['duck_names'] ?? '' ) );
        $total_duck_amount = (float) $race->price_per_duck * count( $allocated );

        $purchase_service = new PurchaseService();
        $purchase_id = $purchase_service->create_online_pending(
            (int) $race->id,
            $contact_id,
            $allocated,
            $duck_names,
            $total_duck_amount,
            $uplift_total,
            0.0
        );

        if ( $purchase_id <= 0 ) {
            $this->redirect_failure( __( 'Could not create purchase.', 'duck-race' ) );
        }

        $purchase = $purchase_service->get_by_id( $purchase_id );
        $description = sprintf( 'Duck Race Purchase #%d', $purchase_id );
        $amount = $purchase ? (float) $purchase->grand_total : (float) $total_duck_amount + (float) $uplift_total;

        $session = ( new StripeService() )->create_checkout_session( $purchase_id, $amount, $description );
        $purchase_service->attach_checkout_session( $purchase_id, $session['session_id'] );

        wp_safe_redirect( esc_url_raw( $session['checkout_url'] ) );
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
        $url = add_query_arg( [ 'error' => rawurlencode( $message ) ], home_url( '/duck-race-failure' ) );
        wp_safe_redirect( $url );
        exit;
    }
}
