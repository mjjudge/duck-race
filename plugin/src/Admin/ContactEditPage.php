<?php

namespace DuckRace\Admin;

use DuckRace\Database\Schema;
use DuckRace\Security\RequestGuard;
use DuckRace\Services\ContactService;

defined( 'ABSPATH' ) || exit;

class ContactEditPage {

    private const NONCE_ACTION = 'duck_race_save_contact';

    public function register(): void {
        add_action( 'admin_post_duck_race_save_contact', [ $this, 'handle_save' ] );
    }

    public function render(): void {
        RequestGuard::require_capability( 'duck_race_manage_contacts' );

        $contact = $this->load_contact();
        $audit_events = ( ! empty( $contact['id'] ) ) ? $this->load_recent_audit_events( (int) $contact['id'] ) : [];

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Edit Contact', 'duck-race' ) . '</h1>';

        if ( isset( $_GET['updated'] ) ) {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Contact saved.', 'duck-race' ) . '</p></div>';
        }

        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="duck_race_save_contact" />';
        echo '<input type="hidden" name="contact_id" value="' . esc_attr( (string) $contact['id'] ) . '" />';
        wp_nonce_field( self::NONCE_ACTION, '_wpnonce' );

        echo '<table class="form-table" role="presentation">';
        $this->text_row( 'first_name', __( 'First name', 'duck-race' ), (string) $contact['first_name'] );
        $this->text_row( 'last_name', __( 'Last name', 'duck-race' ), (string) $contact['last_name'] );
        $this->text_row( 'organisation_name', __( 'Organisation', 'duck-race' ), (string) $contact['organisation_name'] );
        $this->text_row( 'email', __( 'Email', 'duck-race' ), (string) $contact['email'], 'email' );
        $this->text_row( 'phone', __( 'Phone', 'duck-race' ), (string) $contact['phone'] );

        echo '<tr><th scope="row">' . esc_html__( 'Consent', 'duck-race' ) . '</th><td>';
        echo '<label><input type="checkbox" name="consent_duck_race" value="1" ' . checked( (int) $contact['consent_duck_race'], 1, false ) . ' /> ' . esc_html__( 'Future duck race communications', 'duck-race' ) . '</label><br />';
        echo '<label><input type="checkbox" name="consent_organisation" value="1" ' . checked( (int) $contact['consent_organisation'], 1, false ) . ' /> ' . esc_html__( 'Wider organisation communications', 'duck-race' ) . '</label>';
        echo '</td></tr>';

        echo '</table>';

        submit_button( __( 'Save Contact', 'duck-race' ) );
        echo '</form>';

        if ( ! empty( $audit_events ) ) {
            echo '<h2>' . esc_html__( 'Recent Audit History', 'duck-race' ) . '</h2>';
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th>' . esc_html__( 'When', 'duck-race' ) . '</th>';
            echo '<th>' . esc_html__( 'Event', 'duck-race' ) . '</th>';
            echo '<th>' . esc_html__( 'Before', 'duck-race' ) . '</th>';
            echo '<th>' . esc_html__( 'After', 'duck-race' ) . '</th>';
            echo '</tr></thead><tbody>';

            foreach ( $audit_events as $event ) {
                echo '<tr>';
                echo '<td>' . esc_html( (string) $event->created_at ) . '</td>';
                echo '<td>' . esc_html( (string) $event->event_type ) . '</td>';
                echo '<td><pre style="white-space:pre-wrap;">' . esc_html( (string) $event->before_json ) . '</pre></td>';
                echo '<td><pre style="white-space:pre-wrap;">' . esc_html( (string) $event->after_json ) . '</pre></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        echo '</div>';
    }

    public function handle_save(): void {
        RequestGuard::require_capability( 'duck_race_manage_contacts' );
        RequestGuard::verify_admin_nonce( self::NONCE_ACTION, '_wpnonce' );

        $service = new ContactService();
        $contact_id = (int) ( $_POST['contact_id'] ?? 0 );

        if ( $contact_id > 0 ) {
            $existing = $this->load_contact_by_id( $contact_id );
            if ( ! empty( $existing['email'] ) && $existing['email'] !== sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ) ) {
                wp_die( esc_html__( 'Email cannot be changed on existing contacts. Use merge/update flow.', 'duck-race' ) );
            }
        }

        $saved_id = $service->upsert_by_email(
            [
                'first_name' => wp_unslash( $_POST['first_name'] ?? '' ),
                'last_name' => wp_unslash( $_POST['last_name'] ?? '' ),
                'organisation_name' => wp_unslash( $_POST['organisation_name'] ?? '' ),
                'email' => wp_unslash( $_POST['email'] ?? '' ),
                'phone' => wp_unslash( $_POST['phone'] ?? '' ),
                'consent_duck_race' => ! empty( $_POST['consent_duck_race'] ),
                'consent_organisation' => ! empty( $_POST['consent_organisation'] ),
                'consent_source' => 'admin',
                'consent_timestamp' => current_time( 'mysql', true ),
            ]
        );

        wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-contact-edit', 'id' => $saved_id, 'updated' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    /**
     * @return array<string, int|string>
     */
    private function load_contact(): array {
        $defaults = [
            'id' => 0,
            'first_name' => '',
            'last_name' => '',
            'organisation_name' => '',
            'email' => '',
            'phone' => '',
            'consent_duck_race' => 0,
            'consent_organisation' => 0,
        ];

        $contact_id = (int) ( $_GET['id'] ?? 0 );
        if ( $contact_id <= 0 ) {
            return $defaults;
        }

        return array_merge( $defaults, $this->load_contact_by_id( $contact_id ) );
    }

    /**
     * @return array<string, int|string>
     */
    private function load_contact_by_id( int $contact_id ): array {
        global $wpdb;
        $table = Schema::table_name( 'contacts' );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $contact_id ), ARRAY_A );

        return is_array( $row ) ? $row : [];
    }

    /**
     * @return array<int, object>
     */
    private function load_recent_audit_events( int $contact_id ): array {
        global $wpdb;

        $table = Schema::table_name( 'audit_log' );
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( $exists !== $table ) {
            return [];
        }

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT event_type, before_json, after_json, created_at
                 FROM {$table}
                 WHERE entity_type = %s AND entity_id = %d
                 ORDER BY id DESC
                 LIMIT 10",
                'contact',
                $contact_id
            )
        );

        return is_array( $rows ) ? $rows : [];
    }

    private function text_row( string $name, string $label, string $value, string $type = 'text' ): void {
        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th>';
        echo '<td><input class="regular-text" name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '" type="' . esc_attr( $type ) . '" value="' . esc_attr( $value ) . '" /></td>';
        echo '</tr>';
    }
}
