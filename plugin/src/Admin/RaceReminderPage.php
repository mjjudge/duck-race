<?php

namespace DuckRace\Admin;

use DuckRace\Security\RequestGuard;
use DuckRace\Services\EmailService;

defined( 'ABSPATH' ) || exit;

class RaceReminderPage {

    private const NONCE_ACTION = 'duck_race_send_race_reminder';

    public function register(): void {
        add_action( 'admin_post_duck_race_send_race_reminder', [ $this, 'handle_send' ] );
    }

    public function render(): void {
        RequestGuard::require_capability( 'duck_race_manage_sales' );

        $races = $this->get_races();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Race Reminder Emails', 'duck-race' ) . '</h1>';

        if ( isset( $_GET['sent_count'] ) ) {
            echo '<div class="notice notice-success"><p>'
                . sprintf( esc_html__( 'Reminder emails sent: %d', 'duck-race' ), (int) $_GET['sent_count'] )
                . '</p></div>';
        }

        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="duck_race_send_race_reminder" />';
        wp_nonce_field( self::NONCE_ACTION, '_wpnonce' );

        echo '<p><label for="race_id">' . esc_html__( 'Race', 'duck-race' ) . '</label> ';
        echo '<select id="race_id" name="race_id">';
        foreach ( $races as $race ) {
            echo '<option value="' . esc_attr( (string) $race->id ) . '">' . esc_html( (string) $race->title ) . '</option>';
        }
        echo '</select></p>';

        submit_button( __( 'Send Operational Reminder', 'duck-race' ) );
        echo '</form>';
        echo '</div>';
    }

    public function handle_send(): void {
        RequestGuard::require_capability( 'duck_race_manage_sales' );
        RequestGuard::verify_admin_nonce( self::NONCE_ACTION, '_wpnonce' );

        $race_id = (int) ( $_POST['race_id'] ?? 0 );
        $sent = ( new EmailService() )->send_race_reminder( $race_id );

        wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-race-reminders', 'sent_count' => $sent ], admin_url( 'admin.php' ) ) );
        exit;
    }

    /**
     * @return array<int, object>
     */
    private function get_races(): array {
        global $wpdb;
        $table = \DuckRace\Database\Schema::table_name( 'races' );
        $rows = $wpdb->get_results( "SELECT id, title FROM {$table} ORDER BY race_date DESC, id DESC" );

        return is_array( $rows ) ? $rows : [];
    }
}
