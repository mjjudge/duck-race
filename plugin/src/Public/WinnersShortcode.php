<?php

namespace DuckRace\Public;

use DuckRace\Services\RaceService;
use DuckRace\Services\WinnerService;

defined( 'ABSPATH' ) || exit;

class WinnersShortcode {

    public function register(): void {
        add_shortcode( 'duck_race_winners', [ $this, 'render_shortcode' ] );
    }

    /**
     * @param array<string, string> $atts
     */
    public function render_shortcode( array $atts = [] ): string {
        $atts = shortcode_atts(
            [ 'race' => 'current' ],
            $atts,
            'duck_race_winners'
        );

        $race_slug = sanitize_text_field( (string) ( $atts['race'] ?? 'current' ) );

        $race_service = new RaceService();
        $race = ( 'current' !== $race_slug && '' !== $race_slug )
            ? $race_service->get_by_slug( $race_slug )
            : $race_service->get_current_completed_race();

        if ( null === $race ) {
            return '<p>' . esc_html__( 'Winner information is not available yet.', 'duck-race' ) . '</p>';
        }

        $winners = ( new WinnerService() )->get_winners( (int) $race->id );
        if ( [] === $winners ) {
            return '<p>' . esc_html__( 'No winners have been published for this race yet.', 'duck-race' ) . '</p>';
        }

        ob_start();
        echo '<div class="duck-race-winners">';
        echo '<h3>' . esc_html( (string) $race->title ) . '</h3>';
        echo '<ul>';

        foreach ( $winners as $winner ) {
            echo '<li>';
            echo '<strong>' . esc_html( (string) $winner['position_label'] ) . '</strong>: ';
            echo esc_html( (string) $winner['display_name'] );

            if ( '' !== (string) $winner['prize_label'] ) {
                echo ' (' . esc_html( (string) $winner['prize_label'] ) . ')';
            }

            if ( (int) $winner['duck_number'] > 0 ) {
                echo ' - ' . esc_html__( 'Duck', 'duck-race' ) . ' #' . esc_html( (string) (int) $winner['duck_number'] );
            }

            echo '</li>';
        }

        echo '</ul>';
        echo '</div>';

        return (string) ob_get_clean();
    }
}
