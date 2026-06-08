<?php

namespace DuckRace\Rest;

use DuckRace\Services\DuckAllocationService;
use DuckRace\Services\RaceService;

defined( 'ABSPATH' ) || exit;

class DuckPreviewRoute {

    public function register(): void {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        register_rest_route(
            'duck-race/v1',
            '/preview-ducks',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'handle' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'race_id' => [
                        'validate_callback' => fn( $v ) => is_numeric( $v ) && (int) $v > 0,
                        'sanitize_callback' => 'absint',
                        'required'          => true,
                    ],
                    'count' => [
                        'validate_callback' => fn( $v ) => is_numeric( $v ) && (int) $v > 0,
                        'sanitize_callback' => 'absint',
                        'required'          => true,
                    ],
                ],
            ]
        );
    }

    public function handle( \WP_REST_Request $request ): \WP_REST_Response {
        $race_id = (int) $request->get_param( 'race_id' );
        $count   = min( 100, (int) $request->get_param( 'count' ) );

        $race = ( new RaceService() )->get_by_id( $race_id );
        if ( ! $race ) {
            return new \WP_REST_Response( [ 'error' => 'Race not found.' ], 404 );
        }

        $numbers = ( new DuckAllocationService() )->next_available_numbers( $race, 'online', $count );

        return new \WP_REST_Response(
            [
                'numbers'   => $numbers,
                'available' => count( $numbers ),
            ],
            200
        );
    }
}
