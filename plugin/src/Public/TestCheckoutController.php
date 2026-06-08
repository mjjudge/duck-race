<?php

namespace DuckRace\Public;

use DuckRace\Security\RequestGuard;
use DuckRace\Services\ContactService;
use DuckRace\Services\DuckAllocationService;
use DuckRace\Services\PurchaseService;
use DuckRace\Services\RaceService;

defined( 'ABSPATH' ) || exit;

class TestCheckoutController {

    private const NONCE_ACTION = 'duck_race_public_buy';

    public function register(): void {
        add_action( 'admin_post_nopriv_duck_race_simulate_checkout', [ $this, 'simulate_checkout' ] );
        add_action( 'admin_post_duck_race_simulate_checkout', [ $this, 'simulate_checkout' ] );
    }

    public function simulate_checkout(): void {
        if ( ! RequestGuard::verify_public_nonce( self::NONCE_ACTION, 'duck_race_nonce' ) ) {
            $this->redirect_failure( __( 'Security check failed.', 'duck-race' ) );
        }

        if ( ! RequestGuard::passes_honeypot( 'website' ) ) {
            $this->redirect_failure( __( 'Submission rejected.', 'duck-race' ) );
        }

        $race_id = (int) ( $_POST['race_id'] ?? 0 );
        $race    = ( new RaceService() )->get_by_id( $race_id );
        if ( ! $race ) {
            $this->redirect_failure( __( 'Race not found.', 'duck-race' ) );
        }

        if ( 'test' !== (string) $race->status ) {
            $this->redirect_failure( __( 'Simulate checkout is only available for races in test mode.', 'duck-race' ) );
        }

        $duck_count     = max( 1, (int) ( $_POST['duck_count'] ?? 1 ) );
        $price_per_duck = (float) $race->price_per_duck;

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

        $duck_names = array_values(
            array_map(
                'sanitize_text_field',
                array_map( 'wp_unslash', (array) ( $_POST['duck_name'] ?? [] ) )
            )
        );

        $service     = new PurchaseService();
        $purchase_id = $service->create_test_simulation(
            $race_id,
            $contact_id,
            $allocated,
            $duck_names,
            $price_per_duck * count( $allocated )
        );

        if ( $purchase_id <= 0 ) {
            $this->redirect_failure( __( 'Could not create test purchase record.', 'duck-race' ) );
        }

        $service->mark_paid( $purchase_id );

        wp_safe_redirect( add_query_arg( [ 'purchase_id' => $purchase_id ], home_url( '/duck-race-success' ) ) );
        exit;
    }

    private function redirect_failure( string $message ): void {
        wp_safe_redirect( add_query_arg( [ 'error' => rawurlencode( $message ) ], home_url( '/duck-race-failure' ) ) );
        exit;
    }
}
