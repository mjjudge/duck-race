<?php

namespace DuckRace\Admin;

use DuckRace\Security\RequestGuard;

defined( 'ABSPATH' ) || exit;

class SettingsPage {

    private const OPTION_KEY = 'duck_race_settings';
    private const NONCE_ACTION = 'duck_race_save_settings';

    public function register(): void {
        add_action( 'admin_post_duck_race_save_settings', [ $this, 'handle_save' ] );
    }

    public function render(): void {
        RequestGuard::require_capability( 'duck_race_manage_settings' );

        $settings = get_option( self::OPTION_KEY, [] );
        $organisation_name = (string) ( $settings['organisation_name'] ?? '' );
        $contact_email = (string) ( $settings['contact_email'] ?? '' );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Duck Race Settings', 'duck-race' ) . '</h1>';

        if ( isset( $_GET['updated'] ) ) {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'duck-race' ) . '</p></div>';
        }

        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="duck_race_save_settings" />';
        wp_nonce_field( self::NONCE_ACTION, '_wpnonce' );

        echo '<table class="form-table" role="presentation">';
        echo '<tr>';
        echo '<th scope="row"><label for="organisation_name">' . esc_html__( 'Organisation name', 'duck-race' ) . '</label></th>';
        echo '<td><input name="organisation_name" id="organisation_name" type="text" class="regular-text" value="' . esc_attr( $organisation_name ) . '" /></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="contact_email">' . esc_html__( 'Contact email', 'duck-race' ) . '</label></th>';
        echo '<td><input name="contact_email" id="contact_email" type="email" class="regular-text" value="' . esc_attr( $contact_email ) . '" /></td>';
        echo '</tr>';
        echo '</table>';

        submit_button( __( 'Save Settings', 'duck-race' ) );
        echo '</form>';
        echo '</div>';
    }

    public function handle_save(): void {
        RequestGuard::require_capability( 'duck_race_manage_settings' );
        RequestGuard::verify_admin_nonce( self::NONCE_ACTION, '_wpnonce' );

        $organisation_name = sanitize_text_field( wp_unslash( $_POST['organisation_name'] ?? '' ) );
        $contact_email = sanitize_email( wp_unslash( $_POST['contact_email'] ?? '' ) );

        update_option(
            self::OPTION_KEY,
            [
                'organisation_name' => $organisation_name,
                'contact_email' => $contact_email,
            ],
            false
        );

        wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-settings', 'updated' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }
}
