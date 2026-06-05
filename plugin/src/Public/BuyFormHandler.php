<?php

namespace DuckRace\Public;

use DuckRace\Database\Schema;
use DuckRace\Security\RequestGuard;
use DuckRace\Services\RaceLifecycleService;

defined( 'ABSPATH' ) || exit;

class BuyFormHandler {

    private const NONCE_ACTION = 'duck_race_public_buy';

    public function register(): void {
        add_shortcode( 'duck_race_buy', [ $this, 'render_shortcode' ] );
    }

    public function render_shortcode(): string {
        $notice = '';
        $active_race = $this->get_active_online_race();

        if ( null === $active_race ) {
            return '<p>' . esc_html__( 'Online duck sales are currently closed for this race.', 'duck-race' ) . '</p>';
        }

        if ( isset( $_POST['duck_race_form_submitted'] ) ) {
            if ( ! RequestGuard::verify_public_nonce( self::NONCE_ACTION, 'duck_race_nonce' ) ) {
                $notice = '<p>' . esc_html__( 'Security check failed. Please refresh and try again.', 'duck-race' ) . '</p>';
            } elseif ( ! RequestGuard::passes_honeypot( 'website' ) ) {
                $notice = '<p>' . esc_html__( 'Submission rejected.', 'duck-race' ) . '</p>';
            } else {
                $notice = '<p>' . esc_html__( 'Form submission accepted for processing.', 'duck-race' ) . '</p>';
            }
        }

        ob_start();
        ?>
        <form method="post" class="duck-race-buy-form">
            <?php wp_nonce_field( self::NONCE_ACTION, 'duck_race_nonce' ); ?>
            <input type="hidden" name="duck_race_form_submitted" value="1" />
            <input type="hidden" name="race_id" value="<?php echo esc_attr( (string) $active_race->id ); ?>" />

            <p>
                <label for="duck-race-email"><?php esc_html_e( 'Email', 'duck-race' ); ?></label>
                <input id="duck-race-email" type="email" name="email" required />
            </p>

            <p style="display:none;" aria-hidden="true">
                <label for="duck-race-website"><?php esc_html_e( 'Website', 'duck-race' ); ?></label>
                <input id="duck-race-website" type="text" name="website" autocomplete="off" tabindex="-1" />
            </p>

            <p>
                <button type="submit"><?php esc_html_e( 'Continue', 'duck-race' ); ?></button>
            </p>
        </form>
        <?php
        if ( '' !== $notice ) {
            echo wp_kses_post( $notice );
        }

        return (string) ob_get_clean();
    }

    private function get_active_online_race(): ?object {
        global $wpdb;

        $table = Schema::table_name( 'races' );
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( $exists !== $table ) {
            return null;
        }

        $rows = $wpdb->get_results( "SELECT id, status, sales_open_at, sales_close_at FROM {$table} ORDER BY race_date DESC, id DESC" );
        if ( ! is_array( $rows ) ) {
            return null;
        }

        $lifecycle = new RaceLifecycleService();
        foreach ( $rows as $row ) {
            if ( $lifecycle->is_online_sales_open( (string) $row->status, (string) $row->sales_open_at, (string) $row->sales_close_at ) ) {
                return $row;
            }
        }

        return null;
    }
}
