<?php

namespace DuckRace\Admin;

use DuckRace\Security\RequestGuard;
use DuckRace\Services\CampaignService;
use DuckRace\Services\RaceService;

defined( 'ABSPATH' ) || exit;

class CampaignMarketingPage {

    private const NONCE_IMPORT = 'duck_race_import_supporters_csv';
    private const NONCE_INVITE = 'duck_race_send_supporter_invites';
    private const NONCE_ABANDONED = 'duck_race_send_abandoned_reminders';
    private const NONCE_WINNER = 'duck_race_send_winner_future_emails';

    public function register(): void {
        add_action( 'admin_post_duck_race_import_supporters_csv', [ $this, 'handle_import_csv' ] );
        add_action( 'admin_post_duck_race_send_supporter_invites', [ $this, 'handle_send_invites' ] );
        add_action( 'admin_post_duck_race_send_abandoned_reminders', [ $this, 'handle_send_abandoned' ] );
        add_action( 'admin_post_duck_race_send_winner_future_emails', [ $this, 'handle_send_winner_future' ] );
    }

    public function render(): void {
        RequestGuard::require_capability( 'duck_race_manage_contacts' );

        $races = $this->races();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Campaign Marketing', 'duck-race' ) . '</h1>';

        if ( isset( $_GET['imported'] ) ) {
            echo '<div class="notice notice-success"><p>' . esc_html( sanitize_text_field( wp_unslash( (string) $_GET['imported'] ) ) ) . '</p></div>';
        }
        if ( isset( $_GET['sent'] ) ) {
            echo '<div class="notice notice-success"><p>' . esc_html( sanitize_text_field( wp_unslash( (string) $_GET['sent'] ) ) ) . '</p></div>';
        }
        if ( isset( $_GET['error'] ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html( sanitize_text_field( wp_unslash( (string) $_GET['error'] ) ) ) . '</p></div>';
        }

        echo '<h2>' . esc_html__( 'Import Previous Supporters (CSV)', 'duck-race' ) . '</h2>';
        echo '<p>' . esc_html__( 'CSV headers: email, first_name, last_name, organisation_name, phone, consent_duck_race, consent_organisation. Consent is only set when explicitly supplied.', 'duck-race' ) . '</p>';
        echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="duck_race_import_supporters_csv" />';
        wp_nonce_field( self::NONCE_IMPORT, '_wpnonce' );
        echo '<input type="file" name="supporters_csv" accept=".csv,text/csv" required /> ';
        submit_button( __( 'Import CSV', 'duck-race' ), 'secondary', 'submit', false );
        echo '</form>';

        echo '<hr />';
        echo '<h2>' . esc_html__( 'Send Previous Supporter Invitation', 'duck-race' ) . '</h2>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="duck_race_send_supporter_invites" />';
        wp_nonce_field( self::NONCE_INVITE, '_wpnonce' );
        echo '<p><label for="invite_race_id">' . esc_html__( 'Target race', 'duck-race' ) . '</label> ';
        echo '<select id="invite_race_id" name="race_id">';
        foreach ( $races as $race ) {
            echo '<option value="' . esc_attr( (string) $race->id ) . '">' . esc_html( (string) $race->title ) . '</option>';
        }
        echo '</select></p>';
        echo '<p><label><input type="checkbox" name="allow_without_consent" value="1" /> ' . esc_html__( 'Allow contacts without duck-race consent (requires legal basis note)', 'duck-race' ) . '</label></p>';
        echo '<p><label for="legal_basis_note">' . esc_html__( 'Legal basis note', 'duck-race' ) . '</label><br /><input id="legal_basis_note" class="large-text" type="text" name="legal_basis_note" /></p>';
        submit_button( __( 'Send Invitation Emails', 'duck-race' ), 'primary', 'submit', false );
        echo '</form>';

        echo '<hr />';
        echo '<h2>' . esc_html__( 'Send Abandoned Checkout Reminders', 'duck-race' ) . '</h2>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="duck_race_send_abandoned_reminders" />';
        wp_nonce_field( self::NONCE_ABANDONED, '_wpnonce' );
        echo '<p><label for="abandoned_hours">' . esc_html__( 'Pending older than hours', 'duck-race' ) . '</label> <input id="abandoned_hours" type="number" min="1" max="168" name="older_than_hours" value="24" /></p>';
        submit_button( __( 'Send Abandoned Reminders', 'duck-race' ), 'secondary', 'submit', false );
        echo '</form>';

        echo '<hr />';
        echo '<h2>' . esc_html__( 'Send Winner/Future-Race Emails', 'duck-race' ) . '</h2>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="duck_race_send_winner_future_emails" />';
        wp_nonce_field( self::NONCE_WINNER, '_wpnonce' );
        echo '<p><label for="winner_race_id">' . esc_html__( 'Target race', 'duck-race' ) . '</label> ';
        echo '<select id="winner_race_id" name="race_id">';
        foreach ( $races as $race ) {
            echo '<option value="' . esc_attr( (string) $race->id ) . '">' . esc_html( (string) $race->title ) . '</option>';
        }
        echo '</select></p>';
        submit_button( __( 'Send Winner/Future-Race Emails', 'duck-race' ), 'primary', 'submit', false );
        echo '</form>';

        echo '</div>';
    }

    public function handle_import_csv(): void {
        RequestGuard::require_capability( 'duck_race_manage_contacts' );
        RequestGuard::verify_admin_nonce( self::NONCE_IMPORT, '_wpnonce' );

        if ( ! isset( $_FILES['supporters_csv'] ) || ! is_array( $_FILES['supporters_csv'] ) ) {
            $this->redirect_error( __( 'CSV file is required.', 'duck-race' ) );
        }

        $tmp = (string) ( $_FILES['supporters_csv']['tmp_name'] ?? '' );
        if ( '' === $tmp || ! is_uploaded_file( $tmp ) ) {
            $this->redirect_error( __( 'CSV upload failed.', 'duck-race' ) );
        }

        $result = ( new CampaignService() )->import_contacts_from_csv( $tmp );
        $message = sprintf(
            /* translators: 1: processed, 2: created, 3: updated, 4: skipped */
            __( 'Import complete. Processed: %1$d, Created: %2$d, Updated: %3$d, Skipped: %4$d', 'duck-race' ),
            (int) $result['processed'],
            (int) $result['created'],
            (int) $result['updated'],
            (int) $result['skipped']
        );

        wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-campaigns', 'imported' => rawurlencode( $message ) ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function handle_send_invites(): void {
        RequestGuard::require_capability( 'duck_race_manage_contacts' );
        RequestGuard::verify_admin_nonce( self::NONCE_INVITE, '_wpnonce' );

        $race_id = (int) ( $_POST['race_id'] ?? 0 );
        $allow_without_consent = ! empty( $_POST['allow_without_consent'] );
        $legal_basis_note = sanitize_text_field( wp_unslash( (string) ( $_POST['legal_basis_note'] ?? '' ) ) );

        if ( $allow_without_consent && '' === $legal_basis_note ) {
            $this->redirect_error( __( 'Legal basis note is required when sending without consent filter.', 'duck-race' ) );
        }

        $sent = ( new CampaignService() )->send_supporter_invitations( $race_id, $allow_without_consent, $legal_basis_note );

        wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-campaigns', 'sent' => rawurlencode( sprintf( __( 'Invitation emails sent: %d', 'duck-race' ), $sent ) ) ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function handle_send_abandoned(): void {
        RequestGuard::require_capability( 'duck_race_manage_contacts' );
        RequestGuard::verify_admin_nonce( self::NONCE_ABANDONED, '_wpnonce' );

        $hours = max( 1, min( 168, (int) ( $_POST['older_than_hours'] ?? 24 ) ) );
        $sent = ( new CampaignService() )->send_abandoned_checkout_reminders( $hours );

        wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-campaigns', 'sent' => rawurlencode( sprintf( __( 'Abandoned checkout reminders sent: %d', 'duck-race' ), $sent ) ) ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function handle_send_winner_future(): void {
        RequestGuard::require_capability( 'duck_race_manage_contacts' );
        RequestGuard::verify_admin_nonce( self::NONCE_WINNER, '_wpnonce' );

        $race_id = (int) ( $_POST['race_id'] ?? 0 );
        $sent = ( new CampaignService() )->send_winner_future_race_emails( $race_id );

        wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-campaigns', 'sent' => rawurlencode( sprintf( __( 'Winner/future-race emails sent: %d', 'duck-race' ), $sent ) ) ], admin_url( 'admin.php' ) ) );
        exit;
    }

    /**
     * @return array<int, object>
     */
    private function races(): array {
        global $wpdb;
        $table = \DuckRace\Database\Schema::table_name( 'races' );
        $rows = $wpdb->get_results( "SELECT id, title FROM {$table} ORDER BY race_date DESC, id DESC" );

        return is_array( $rows ) ? $rows : [];
    }

    private function redirect_error( string $message ): void {
        wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-campaigns', 'error' => rawurlencode( $message ) ], admin_url( 'admin.php' ) ) );
        exit;
    }
}
