<?php

namespace DuckRace\Rest;

use DuckRace\Services\ContactService;

defined( 'ABSPATH' ) || exit;

class ContactRecognitionRoute {

    public function register(): void {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        register_rest_route(
            'duck-race/v1',
            '/check-email',
            [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [ $this, 'handle_check_email' ],
                    'permission_callback' => '__return_true',
                ],
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'handle_check_email' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );
    }

    public function handle_check_email( \WP_REST_Request $request ): \WP_REST_Response {
        $email = sanitize_email( (string) ( $request->get_param( 'email' ) ?? '' ) );

        if ( '' === $email || ! is_email( $email ) ) {
            return new \WP_REST_Response( [ 'exists' => false ], 200 );
        }

        $contact = ( new ContactService() )->find_by_email( $email );
        if ( ! $contact ) {
            return new \WP_REST_Response( [ 'exists' => false ], 200 );
        }

        return new \WP_REST_Response(
            [
                'exists' => true,
                'first_name' => (string) $contact->first_name,
                'last_name' => (string) $contact->last_name,
            ],
            200
        );
    }
}
