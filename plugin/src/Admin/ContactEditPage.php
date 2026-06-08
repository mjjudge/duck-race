<?php

namespace DuckRace\Admin;

use DuckRace\Database\Schema;
use DuckRace\Security\RequestGuard;
use DuckRace\Services\ContactService;

defined( 'ABSPATH' ) || exit;

class ContactEditPage {

    private const NONCE_ACTION          = 'duck_race_save_contact';
    private const ANONYMISE_NONCE_ACTION = 'duck_race_anonymise_contact';

    public function register(): void {
        add_action( 'admin_post_duck_race_save_contact', [ $this, 'handle_save' ] );
        add_action( 'admin_post_duck_race_anonymise_contact', [ $this, 'handle_anonymise' ] );
    }

    public function render(): void {
        RequestGuard::require_capability( 'duck_race_manage_contacts' );

        $contact = $this->load_contact();
        $audit_events = ( ! empty( $contact['id'] ) ) ? $this->load_recent_audit_events( (int) $contact['id'] ) : [];

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Edit Contact', 'duck-race' ) . '</h1>';
        echo '<p><span style="color:#d63638;">*</span> ' . esc_html__( 'Required fields', 'duck-race' ) . '</p>';

        if ( isset( $_GET['updated'] ) ) {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Contact saved.', 'duck-race' ) . '</p></div>';
        }

        if ( isset( $_GET['anonymised'] ) ) {
            echo '<div class="notice notice-warning"><p>' . esc_html( sanitize_text_field( wp_unslash( (string) $_GET['anonymised'] ) ) ) . '</p></div>';
        }

        if ( isset( $_GET['error'] ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html( sanitize_text_field( wp_unslash( (string) ( $_GET['error'] ?? '' ) ) ) ) . '</p></div>';
        }

        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" id="contact-edit-form" novalidate>';
        echo '<input type="hidden" name="action" value="duck_race_save_contact" />';
        echo '<input type="hidden" name="contact_id" value="' . esc_attr( (string) $contact['id'] ) . '" />';
        wp_nonce_field( self::NONCE_ACTION, '_wpnonce' );

        echo '<table class="form-table" role="presentation">';
        $this->required_text_row( 'first_name', __( 'First name', 'duck-race' ), (string) $contact['first_name'] );
        $this->required_text_row( 'last_name', __( 'Last name', 'duck-race' ), (string) $contact['last_name'] );
        $this->text_row( 'organisation_name', __( 'Organisation', 'duck-race' ), (string) $contact['organisation_name'] );
        $this->required_text_row( 'email', __( 'Email', 'duck-race' ), (string) $contact['email'], 'email' );
        $this->phone_row( (string) $contact['phone'] );

        echo '<tr><th scope="row">' . esc_html__( 'Address', 'duck-race' ) . '</th><td>';
        echo '<input class="regular-text" type="text" name="address_line_1" id="address_line_1" value="' . esc_attr( (string) $contact['address_line_1'] ) . '" placeholder="' . esc_attr__( 'Address line 1', 'duck-race' ) . '" /><br style="margin-bottom:4px;" />';
        echo '<input class="regular-text" type="text" name="address_line_2" id="address_line_2" value="' . esc_attr( (string) $contact['address_line_2'] ) . '" placeholder="' . esc_attr__( 'Address line 2 (optional)', 'duck-race' ) . '" /><br style="margin-bottom:4px;" />';
        echo '<input class="regular-text" type="text" name="city" id="city" value="' . esc_attr( (string) $contact['city'] ) . '" placeholder="' . esc_attr__( 'Town / City', 'duck-race' ) . '" /><br style="margin-bottom:4px;" />';
        echo '<input class="regular-text" type="text" name="county" id="county" value="' . esc_attr( (string) $contact['county'] ) . '" placeholder="' . esc_attr__( 'County (optional)', 'duck-race' ) . '" /><br style="margin-bottom:4px;" />';
        echo '<input style="width:160px;" type="text" name="postcode" id="postcode" value="' . esc_attr( (string) $contact['postcode'] ) . '" placeholder="' . esc_attr__( 'Postcode', 'duck-race' ) . '" />';
        echo '<p class="description">' . esc_html__( 'Address is optional but recommended for postal communications.', 'duck-race' ) . '</p>';
        echo '</td></tr>';

        echo '<tr><th scope="row">' . esc_html__( 'Consent', 'duck-race' ) . '</th><td>';
        echo '<label><input type="checkbox" name="consent_duck_race" value="1" ' . checked( (int) $contact['consent_duck_race'], 1, false ) . ' /> ' . esc_html__( 'Future duck race communications', 'duck-race' ) . '</label><br />';
        echo '<label><input type="checkbox" name="consent_organisation" value="1" ' . checked( (int) $contact['consent_organisation'], 1, false ) . ' /> ' . esc_html__( 'Wider organisation communications', 'duck-race' ) . '</label>';
        echo '</td></tr>';

        echo '</table>';

        submit_button( __( 'Save Contact', 'duck-race' ) );
        echo '</form>';

        echo '<script>';
        echo '(function(){';
        echo 'document.getElementById("contact-edit-form").addEventListener("submit",function(e){';
        echo 'let ok=true;';
        echo 'const req=["first_name","last_name","email"];';
        echo 'req.forEach(function(id){';
        echo 'const el=document.getElementById(id);';
        echo 'if(el&&""===el.value.trim()){';
        echo 'el.style.borderColor="#d63638";ok=false;';
        echo '}else if(el){el.style.borderColor="";}';
        echo '});';
        echo 'const phone=document.getElementById("phone");';
        echo 'if(phone&&phone.value.trim()!==""&&!/^[0-9 +().\\-]+$/.test(phone.value)){';
        echo 'phone.style.borderColor="#d63638";';
        echo 'phone.setCustomValidity("' . esc_js( __( 'Please enter a valid phone number (digits, spaces, +, -, brackets only).', 'duck-race' ) ) . '");';
        echo 'ok=false;';
        echo '}else if(phone){phone.style.borderColor="";phone.setCustomValidity("");}';
        echo 'if(!ok){e.preventDefault();alert("' . esc_js( __( 'Please correct the highlighted fields before saving.', 'duck-race' ) ) . '");}';
        echo '});';
        echo '})();';
        echo '</script>';

        if ( ! empty( $audit_events ) ) {
            echo '<h2>' . esc_html__( 'Recent Audit History', 'duck-race' ) . '</h2>';
            echo '<div style="max-height:400px;overflow-y:auto;border:1px solid #c3c4c7;border-radius:4px;margin-bottom:20px;">';
            echo '<table class="widefat" style="border:0;">';
            echo '<thead style="position:sticky;top:0;background:#f6f7f7;z-index:1;"><tr>';
            echo '<th style="white-space:nowrap;">' . esc_html__( 'When', 'duck-race' ) . '</th>';
            echo '<th>' . esc_html__( 'What changed', 'duck-race' ) . '</th>';
            echo '<th style="white-space:nowrap;">' . esc_html__( 'Via', 'duck-race' ) . '</th>';
            echo '</tr></thead><tbody>';

            foreach ( $audit_events as $event ) {
                $before = is_string( $event->before_json ) && '' !== $event->before_json
                    ? (array) json_decode( $event->before_json, true )
                    : [];
                $after = is_string( $event->after_json ) && '' !== $event->after_json
                    ? (array) json_decode( $event->after_json, true )
                    : [];
                $source = (string) ( $after['consent_source'] ?? $before['consent_source'] ?? '' );

                echo '<tr>';
                echo '<td style="white-space:nowrap;vertical-align:top;">' . esc_html( (string) $event->created_at ) . '</td>';
                echo '<td style="vertical-align:top;">' . $this->render_audit_diff( $before, $after ) . '</td>';
                echo '<td style="vertical-align:top;white-space:nowrap;"><small>' . esc_html( $this->friendly_source( $source ) ) . '</small></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
            echo '</div>';
        }

        if ( ! empty( $contact['id'] ) ) {
            echo '<hr style="margin:24px 0;" />';
            echo '<h2>' . esc_html__( 'GDPR — Delete / Anonymise Contact', 'duck-race' ) . '</h2>';
            echo '<p>';
            esc_html_e( 'Anonymising a contact removes personal data from the contact record while preserving all purchase and financial records.', 'duck-race' );
            echo '<br />';
            esc_html_e( 'If the contact has Gift Aid declarations, name and home address are retained for HMRC compliance (minimum 6 years). All other personal data (email, phone, consent flags, notes) will be cleared.', 'duck-race' );
            echo '</p>';
            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'' . esc_js( __( 'Permanently anonymise this contact? This cannot be undone.', 'duck-race' ) ) . '\');">';
            echo '<input type="hidden" name="action" value="duck_race_anonymise_contact" />';
            echo '<input type="hidden" name="contact_id" value="' . esc_attr( (string) $contact['id'] ) . '" />';
            wp_nonce_field( self::ANONYMISE_NONCE_ACTION, '_wpnonce' );
            echo '<p><button type="submit" class="button button-link-delete">' . esc_html__( 'Anonymise Contact (GDPR)', 'duck-race' ) . '</button></p>';
            echo '</form>';
        }

        echo '</div>';
    }

    public function handle_save(): void {
        RequestGuard::require_capability( 'duck_race_manage_contacts' );
        RequestGuard::verify_admin_nonce( self::NONCE_ACTION, '_wpnonce' );

        $first_name = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
        $last_name = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
        $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

        if ( '' === $first_name || '' === $last_name || '' === $email ) {
            $error = __( 'First name, last name and email are required.', 'duck-race' );
            $contact_id = (int) ( $_POST['contact_id'] ?? 0 );
            wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-contact-edit', 'id' => $contact_id, 'error' => rawurlencode( $error ) ], admin_url( 'admin.php' ) ) );
            exit;
        }

        $phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
        if ( '' !== $phone && ! preg_match( '/^[0-9 +().\-]+$/', $phone ) ) {
            $error = __( 'Please enter a valid phone number.', 'duck-race' );
            $contact_id = (int) ( $_POST['contact_id'] ?? 0 );
            wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-contact-edit', 'id' => $contact_id, 'error' => rawurlencode( $error ) ], admin_url( 'admin.php' ) ) );
            exit;
        }

        $service = new ContactService();
        $contact_id = (int) ( $_POST['contact_id'] ?? 0 );

        if ( $contact_id > 0 ) {
            $existing = $this->load_contact_by_id( $contact_id );
            if ( ! empty( $existing['email'] ) && $existing['email'] !== $email ) {
                wp_die( esc_html__( 'Email cannot be changed on existing contacts. Use merge/update flow.', 'duck-race' ) );
            }
        }

        $saved_id = $service->upsert_by_email(
            [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'organisation_name' => wp_unslash( $_POST['organisation_name'] ?? '' ),
                'email' => $email,
                'phone' => $phone,
                'address_line_1' => wp_unslash( $_POST['address_line_1'] ?? '' ),
                'address_line_2' => wp_unslash( $_POST['address_line_2'] ?? '' ),
                'city' => wp_unslash( $_POST['city'] ?? '' ),
                'county' => wp_unslash( $_POST['county'] ?? '' ),
                'postcode' => wp_unslash( $_POST['postcode'] ?? '' ),
                'consent_duck_race' => ! empty( $_POST['consent_duck_race'] ),
                'consent_organisation' => ! empty( $_POST['consent_organisation'] ),
                'consent_source' => 'admin',
                'consent_timestamp' => current_time( 'mysql', true ),
            ]
        );

        wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-contact-edit', 'id' => $saved_id, 'updated' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function handle_anonymise(): void {
        RequestGuard::require_capability( 'duck_race_manage_contacts' );
        RequestGuard::verify_admin_nonce( self::ANONYMISE_NONCE_ACTION, '_wpnonce' );

        $contact_id = (int) ( $_POST['contact_id'] ?? 0 );
        if ( $contact_id <= 0 ) {
            wp_die( esc_html__( 'Invalid contact ID.', 'duck-race' ) );
        }

        $result = ( new ContactService() )->anonymise( $contact_id );

        if ( ! $result['success'] ) {
            $error = $result['reason'] ?? __( 'Could not anonymise contact.', 'duck-race' );
            wp_safe_redirect( add_query_arg(
                [ 'page' => 'duck-race-contact-edit', 'id' => $contact_id, 'error' => rawurlencode( $error ) ],
                admin_url( 'admin.php' )
            ) );
            exit;
        }

        $notice = __( 'Contact anonymised.', 'duck-race' ) . ' ' . ( $result['kept'] ?? '' );
        wp_safe_redirect( add_query_arg(
            [ 'page' => 'duck-race-contact-edit', 'id' => $contact_id, 'anonymised' => rawurlencode( $notice ) ],
            admin_url( 'admin.php' )
        ) );
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
            'address_line_1' => '',
            'address_line_2' => '',
            'city' => '',
            'county' => '',
            'postcode' => '',
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

    private function required_text_row( string $name, string $label, string $value, string $type = 'text' ): void {
        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . ' <span style="color:#d63638;" aria-hidden="true">*</span></label></th>';
        echo '<td><input class="regular-text" name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '" type="' . esc_attr( $type ) . '" value="' . esc_attr( $value ) . '" required /></td>';
        echo '</tr>';
    }

    private function phone_row( string $value ): void {
        echo '<tr>';
        echo '<th scope="row"><label for="phone">' . esc_html__( 'Phone', 'duck-race' ) . '</label></th>';
        echo '<td><input class="regular-text" name="phone" id="phone" type="tel" value="' . esc_attr( $value ) . '" pattern="[0-9 +().-]+" />';
        echo '<p class="description">' . esc_html__( 'Digits, spaces, +, -, and brackets only.', 'duck-race' ) . '</p>';
        echo '</td></tr>';
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    private function render_audit_diff( array $before, array $after ): string {
        $skip = [ 'consent_timestamp', 'consent_source' ];
        $labels = [
            'first_name'           => 'First name',
            'last_name'            => 'Last name',
            'organisation_name'    => 'Organisation',
            'email'                => 'Email',
            'phone'                => 'Phone',
            'address_line_1'       => 'Address line 1',
            'address_line_2'       => 'Address line 2',
            'city'                 => 'City',
            'county'               => 'County',
            'postcode'             => 'Postcode',
            'country'              => 'Country',
            'consent_duck_race'    => 'Duck race consent',
            'consent_organisation' => 'Organisation consent',
        ];

        $all_keys = array_unique( array_merge( array_keys( $before ), array_keys( $after ) ) );
        $rows = '';
        foreach ( $all_keys as $key ) {
            if ( in_array( $key, $skip, true ) ) {
                continue;
            }
            $b = $before[ $key ] ?? null;
            $a = $after[ $key ] ?? null;
            // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- intentional loose compare for 0/"0" consent values
            if ( $b == $a ) {
                continue;
            }
            $label = $labels[ $key ] ?? $key;
            $rows .= '<tr>';
            $rows .= '<td style="padding:2px 10px 2px 0;font-weight:600;white-space:nowrap;">' . esc_html( $label ) . '</td>';
            $rows .= '<td style="padding:2px 10px 2px 0;color:#c00;">' . esc_html( $this->format_audit_value( $key, $b ) ) . '</td>';
            $rows .= '<td style="padding:2px 0;color:#008a00;">' . esc_html( $this->format_audit_value( $key, $a ) ) . '</td>';
            $rows .= '</tr>';
        }

        if ( '' === $rows ) {
            $ts_changed = ( $before['consent_timestamp'] ?? '' ) !== ( $after['consent_timestamp'] ?? '' );
            return $ts_changed
                ? '<em style="color:#666;">' . esc_html__( 'Consent re-confirmed (no profile changes)', 'duck-race' ) . '</em>'
                : '<em style="color:#666;">' . esc_html__( 'No changes recorded', 'duck-race' ) . '</em>';
        }

        return '<table style="border-collapse:collapse;font-size:13px;">'
            . '<thead><tr>'
            . '<th style="padding:0 10px 4px 0;color:#666;font-weight:normal;">' . esc_html__( 'Field', 'duck-race' ) . '</th>'
            . '<th style="padding:0 10px 4px 0;color:#666;font-weight:normal;">' . esc_html__( 'Before', 'duck-race' ) . '</th>'
            . '<th style="padding:0 0 4px;color:#666;font-weight:normal;">' . esc_html__( 'After', 'duck-race' ) . '</th>'
            . '</tr></thead>'
            . '<tbody>' . $rows . '</tbody></table>';
    }

    /**
     * @param mixed $value
     */
    private function format_audit_value( string $field, $value ): string {
        if ( null === $value || '' === $value ) {
            return '(empty)';
        }
        if ( in_array( $field, [ 'consent_duck_race', 'consent_organisation' ], true ) ) {
            return $value ? 'Yes' : 'No';
        }
        return (string) $value;
    }

    private function friendly_source( string $source ): string {
        return match ( $source ) {
            'online_purchase_form' => 'Online purchase',
            'manual_sale_admin'    => 'Manual sale (admin)',
            'admin_edit'           => 'Admin edit',
            default                => $source ?: '—',
        };
    }
}
