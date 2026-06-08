<?php

namespace DuckRace\Admin;

use DuckRace\Security\RequestGuard;
use DuckRace\Services\ContactService;
use DuckRace\Services\DuckAllocationService;
use DuckRace\Services\PurchaseService;
use DuckRace\Services\RaceService;

defined( 'ABSPATH' ) || exit;

class ManualSalesPage {

    private const NONCE_ACTION = 'duck_race_save_manual_sale';

    public function register(): void {
        add_action( 'admin_post_duck_race_save_manual_sale', [ $this, 'handle_save' ] );
        add_action( 'wp_ajax_duck_race_check_duck_numbers', [ $this, 'ajax_check_duck_numbers' ] );
    }

    public function render(): void {
        RequestGuard::require_capability( 'duck_race_manage_sales' );

        $races = $this->get_races();

        // Pre-fill from duck grid "Mark as Sold" link.
        $prefill_duck_number = absint( $_GET['duck_number'] ?? 0 );
        $prefill_race_id     = absint( $_GET['race_id'] ?? ( ! empty( $races ) ? (int) $races[0]->id : 0 ) );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Add Manual Sale', 'duck-race' ) . '</h1>';

        if ( isset( $_GET['saved'] ) ) {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Manual sale saved.', 'duck-race' ) . '</p></div>';
        }

        if ( isset( $_GET['error'] ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['error'] ) ) ) . '</p></div>';
        }

        // Pass data for JS duck-check.
        echo '<script>';
        echo 'var drCheckNonce=' . wp_json_encode( wp_create_nonce( 'duck_race_check_ducks' ) ) . ';';
        echo 'var drAjaxUrl=' . wp_json_encode( admin_url( 'admin-ajax.php' ) ) . ';';
        echo 'var drCheckEmailUrl=' . wp_json_encode( rest_url( 'duck-race/v1/check-email' ) ) . ';';
        echo '</script>';

        echo '<form id="manual-sale-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="duck_race_save_manual_sale" />';
        echo '<input type="hidden" name="online_range_confirmed" id="online_range_confirmed" value="" />';
        wp_nonce_field( self::NONCE_ACTION, '_wpnonce' );

        echo '<table class="form-table" role="presentation">';

        echo '<tr><th scope="row"><label for="race_id">' . esc_html__( 'Race', 'duck-race' ) . '</label></th><td>';
        echo '<select name="race_id" id="race_id" required>';
        foreach ( $races as $race ) {
            $selected = selected( $prefill_race_id, (int) $race->id, false );
            echo '<option value="' . esc_attr( (string) $race->id ) . '" ' . $selected . '>' . esc_html( (string) $race->title ) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th scope="row"><label for="email">' . esc_html__( 'Buyer email', 'duck-race' ) . '</label></th>';
        echo '<td><input class="regular-text" type="email" name="email" id="email" required />';
        echo '<p id="ms-contact-found" style="display:none;margin:4px 0 0;padding:6px 10px;background:#edfaed;border-left:3px solid #0a0;font-size:13px;">';
        echo esc_html__( 'Existing contact found — details pre-filled. Update any fields that have changed.', 'duck-race' );
        echo '</p></td></tr>';
        echo '<tr><th scope="row"><label for="first_name">' . esc_html__( 'First name', 'duck-race' ) . '</label></th><td><input class="regular-text" type="text" name="first_name" id="first_name" required /></td></tr>';
        echo '<tr><th scope="row"><label for="last_name">' . esc_html__( 'Last name', 'duck-race' ) . '</label></th><td><input class="regular-text" type="text" name="last_name" id="last_name" required /></td></tr>';
        echo '<tr><th scope="row"><label for="phone">' . esc_html__( 'Phone', 'duck-race' ) . '</label></th>';
        echo '<td><input class="regular-text" type="tel" name="phone" id="phone" pattern="[0-9 +().-]+" />';
        echo '<p class="description">' . esc_html__( 'Optional. Digits, spaces, +, -, and brackets only.', 'duck-race' ) . '</p></td></tr>';

        echo '<tr><th scope="row">' . esc_html__( 'Address', 'duck-race' ) . '</th><td>';
        echo '<input class="regular-text" type="text" name="address_line_1" id="ms-address1" placeholder="' . esc_attr__( 'Address line 1 (optional)', 'duck-race' ) . '" /><br style="margin-bottom:4px;" />';
        echo '<input class="regular-text" type="text" name="address_line_2" id="ms-address2" placeholder="' . esc_attr__( 'Address line 2 (optional)', 'duck-race' ) . '" /><br style="margin-bottom:4px;" />';
        echo '<input class="regular-text" type="text" name="city" id="ms-city" placeholder="' . esc_attr__( 'Town / City (optional)', 'duck-race' ) . '" /><br style="margin-bottom:4px;" />';
        echo '<input class="regular-text" type="text" name="county" id="ms-county" placeholder="' . esc_attr__( 'County (optional)', 'duck-race' ) . '" /><br style="margin-bottom:4px;" />';
        echo '<input style="width:160px;" type="text" name="postcode" id="ms-postcode" placeholder="' . esc_attr__( 'Postcode (optional)', 'duck-race' ) . '" />';
        echo '</td></tr>';

        echo '<tr><th scope="row">' . esc_html__( 'Consent', 'duck-race' ) . '</th><td>';
        echo '<label><input type="checkbox" name="consent_duck_race" value="1" id="ms-consent-duck-race" /> ' . esc_html__( 'Future duck race communications', 'duck-race' ) . '</label><br />';
        echo '<label><input type="checkbox" name="consent_organisation" value="1" id="ms-consent-organisation" /> ' . esc_html__( 'Wider organisation communications', 'duck-race' ) . '</label>';
        echo '<p id="ms-consent-imported" style="display:none;margin:4px 0 0;font-size:12px;color:#555;">' . esc_html__( 'Consent settings imported from existing contact record — untick to change.', 'duck-race' ) . '</p>';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="duck_count">' . esc_html__( 'Number of ducks', 'duck-race' ) . '</label></th><td><input type="number" min="1" max="50" name="duck_count" id="duck_count" value="1" required /></td></tr>';
        $prefill_numbers_str = $prefill_duck_number > 0 ? (string) $prefill_duck_number : '';
        echo '<tr><th scope="row"><label for="selected_numbers">' . esc_html__( 'Specific duck numbers (optional)', 'duck-race' ) . '</label></th><td><input class="regular-text" type="text" name="selected_numbers" id="selected_numbers" placeholder="e.g. 12, 13, 14" value="' . esc_attr( $prefill_numbers_str ) . '" /><p class="description">' . esc_html__( 'Leave empty to auto-allocate next available manual-range ducks. Comma-separate multiple numbers.', 'duck-race' ) . '</p></td></tr>';
        echo '<tr><th scope="row"><label for="duck_names">' . esc_html__( 'Names for ducks (optional)', 'duck-race' ) . '</label></th><td><input class="large-text" type="text" name="duck_names" id="duck_names" placeholder="e.g. Daisy, Sunny" /><p class="description">' . esc_html__( 'Comma-separated. These identify who each duck is bought for — a family member, friend, etc.', 'duck-race' ) . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="payment_method">' . esc_html__( 'Payment method', 'duck-race' ) . '</label></th><td>';
        echo '<select name="payment_method" id="payment_method">';
        foreach ( [ 'cash', 'card_machine', 'bank_transfer', 'other' ] as $method ) {
            echo '<option value="' . esc_attr( $method ) . '">' . esc_html( ucwords( str_replace( '_', ' ', $method ) ) ) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th scope="row"><label for="amount_paid">' . esc_html__( 'Amount paid', 'duck-race' ) . '</label></th><td><input type="number" step="0.01" min="0" name="amount_paid" id="amount_paid" autocomplete="off" required /></td></tr>';
        echo '<tr><th scope="row"><label for="admin_notes">' . esc_html__( 'Admin notes', 'duck-race' ) . '</label></th><td><textarea class="large-text" rows="3" name="admin_notes" id="admin_notes"></textarea></td></tr>';

        echo '</table>';

        submit_button( __( 'Save Manual Sale', 'duck-race' ) );
        echo '</form>';

        // Duck number pre-check JS — intercepts submit, validates numbers via AJAX, shows specific errors.
        ?>
<script>
(function() {
    var form = document.getElementById("manual-sale-form");
    var numbersField = document.getElementById("selected_numbers");
    var raceSelect = document.getElementById("race_id");
    if (!form || !numbersField || !raceSelect) return;

    // Track online-range confirmation in memory to avoid any DOM null issues.
    var onlineRangeConfirmed = false;

    function setConfirmedField(val) {
        var el = document.getElementById("online_range_confirmed");
        if (el) el.value = val;
    }

    var statusLabels = {
        reserved: "currently reserved (in-progress checkout)",
        sold_online: "already sold online",
        sold_manual: "already sold manually",
        winner: "a race winner",
        lost: "marked as lost",
        out_of_range: "outside all configured ranges"
    };

    form.addEventListener("submit", function(e) {
        var raw = numbersField.value.trim();
        if (!raw) return; // empty = auto-allocate, no check needed

        var numbers = raw.split(",").map(function(s) { return parseInt(s.trim(), 10); }).filter(function(n) { return n > 0; });
        if (numbers.length === 0) return;

        e.preventDefault();

        var body = new FormData();
        body.append("action", "duck_race_check_duck_numbers");
        body.append("nonce", drCheckNonce);
        body.append("race_id", raceSelect.value);
        body.append("numbers", numbers.join(","));

        fetch(drAjaxUrl, { method: "POST", body: body })
            .then(function(r) {
                var status = r.status;
                return r.text().then(function(text) {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        // Server returned non-JSON — log it so it can be diagnosed in the console.
                        console.error("[Duck Race] AJAX check returned non-JSON (HTTP " + status + "):", text.substring(0, 500));
                        throw new Error("HTTP " + status + " — server did not return JSON. See browser console for details.");
                    }
                });
            })
            .then(function(resp) {
                if (!resp || !resp.success) {
                    var msg = (resp && resp.data && resp.data.message) ? resp.data.message : "Could not validate duck numbers. Please try again.";
                    alert(msg);
                    return;
                }
                var results = resp.data || {};
                var blocked = [];
                var onlineWarnings = [];

                numbers.forEach(function(num) {
                    var info = (results[String(num)]) || (results[num]) || {};
                    var status = info.status || "available";
                    if (status !== "available") {
                        blocked.push("Duck #" + num + " — " + (statusLabels[status] || status) + ".");
                    } else if (info.in_online_range) {
                        onlineWarnings.push(num);
                    }
                });

                if (blocked.length > 0) {
                    var msg = "The following duck(s) cannot be sold:\n\n" + blocked.join("\n") + "\n\nPlease correct the duck numbers. Use the Duck Grid to check availability.";
                    alert(msg);
                    return;
                }

                if (onlineWarnings.length > 0 && !onlineRangeConfirmed) {
                    var warn = "Duck" + (onlineWarnings.length > 1 ? "s" : "") + " #" + onlineWarnings.join(", #") + " " + (onlineWarnings.length > 1 ? "are" : "is") + " in the online sales range (normally sold online).\n\nAre you sure you want to proceed with a manual sale for " + (onlineWarnings.length > 1 ? "these ducks" : "this duck") + "?";
                    if (!confirm(warn)) return;
                    onlineRangeConfirmed = true;
                }

                setConfirmedField(onlineRangeConfirmed ? "1" : "");
                HTMLFormElement.prototype.submit.call(form);
            })
            .catch(function(err) {
                console.error("[Duck Race] Duck number check error:", err);
                var detail = err && err.message ? "\n\n(" + err.message + ")" : "";
                if (confirm("Could not verify duck availability." + detail + "\n\nThe server will still validate when you save. Proceed anyway?")) {
                    HTMLFormElement.prototype.submit.call(form);
                }
            });
    });
})();

// ── Email look-up: pre-fill contact details when email is entered ──────────
(function () {
    var emailEl   = document.getElementById("email");
    var noticeEl  = document.getElementById("ms-contact-found");
    var consentNoteEl = document.getElementById("ms-consent-imported");
    if (!emailEl || !drCheckEmailUrl) return;

    emailEl.addEventListener("blur", function () {
        var email = emailEl.value.trim();
        if (!email) return;

        fetch(drCheckEmailUrl + "?email=" + encodeURIComponent(email))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.exists) return;

                // Pre-fill text fields only if currently empty.
                var textMap = {
                    "first_name":  data.first_name,
                    "last_name":   data.last_name,
                    "phone":       data.phone,
                    "ms-address1": data.address_line_1,
                    "ms-address2": data.address_line_2,
                    "ms-city":     data.city,
                    "ms-county":   data.county,
                    "ms-postcode": data.postcode
                };
                var ids = Object.keys(textMap);
                for (var i = 0; i < ids.length; i++) {
                    var el = document.getElementById(ids[i]);
                    if (el && !el.value && textMap[ids[i]]) el.value = textMap[ids[i]];
                }

                // Always apply previous consent settings.
                var cdr = document.getElementById("ms-consent-duck-race");
                var co  = document.getElementById("ms-consent-organisation");
                if (cdr) cdr.checked = !!data.consent_duck_race;
                if (co)  co.checked  = !!data.consent_organisation;
                if (consentNoteEl) consentNoteEl.style.display = "block";

                if (noticeEl) noticeEl.style.display = "block";
            })
            .catch(function () {}); // silent — admin can fill manually
    });
})();
</script>
        <?php
        echo '</div>';
    }

    public function ajax_check_duck_numbers(): void {
        // Use wp_send_json_error throughout so the response is always valid JSON.
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'duck_race_check_ducks' ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed. Please reload the page and try again.' ], 403 );
        }

        if ( ! current_user_can( 'duck_race_manage_sales' ) ) {
            wp_send_json_error( [ 'message' => 'Access denied.' ], 403 );
        }

        $race_id = absint( $_POST['race_id'] ?? 0 );
        $race    = ( new RaceService() )->get_by_id( $race_id );
        if ( ! $race ) {
            wp_send_json_error( [ 'message' => 'Invalid race.' ] );
        }

        $raw     = sanitize_text_field( wp_unslash( $_POST['numbers'] ?? '' ) );
        $numbers = array_values( array_filter( array_map( 'absint', explode( ',', $raw ) ) ) );

        $allocator = new DuckAllocationService();
        $results   = [];
        foreach ( $numbers as $num ) {
            $status     = $allocator->get_duck_entry_status( $race_id, $num, $race );
            $in_online  = $num >= (int) $race->online_range_start && $num <= (int) $race->online_range_end;
            $results[ $num ] = [
                'status'         => $status,
                'in_online_range' => $in_online,
            ];
        }

        wp_send_json_success( $results );
    }

    public function handle_save(): void {
        RequestGuard::require_capability( 'duck_race_manage_sales' );
        RequestGuard::verify_admin_nonce( self::NONCE_ACTION, '_wpnonce' );

        $race_id = (int) ( $_POST['race_id'] ?? 0 );
        $race = ( new RaceService() )->get_by_id( $race_id );
        if ( ! $race ) {
            $this->redirect_error( __( 'Invalid race.', 'duck-race' ) );
        }

        $contact_id = ( new ContactService() )->upsert_by_email(
            [
                'email' => wp_unslash( $_POST['email'] ?? '' ),
                'first_name' => wp_unslash( $_POST['first_name'] ?? '' ),
                'last_name' => wp_unslash( $_POST['last_name'] ?? '' ),
                'phone' => wp_unslash( $_POST['phone'] ?? '' ),
                'address_line_1' => wp_unslash( $_POST['address_line_1'] ?? '' ),
                'address_line_2' => wp_unslash( $_POST['address_line_2'] ?? '' ),
                'city' => wp_unslash( $_POST['city'] ?? '' ),
                'county' => wp_unslash( $_POST['county'] ?? '' ),
                'postcode' => wp_unslash( $_POST['postcode'] ?? '' ),
                'consent_duck_race' => ! empty( $_POST['consent_duck_race'] ),
                'consent_organisation' => ! empty( $_POST['consent_organisation'] ),
                'consent_source' => 'manual_sale_admin',
                'consent_timestamp' => current_time( 'mysql', true ),
            ]
        );
        if ( $contact_id <= 0 ) {
            $this->redirect_error( __( 'Could not save contact.', 'duck-race' ) );
        }

        $duck_count = max( 1, (int) ( $_POST['duck_count'] ?? 1 ) );
        $selected_numbers = $this->parse_numbers( (string) ( $_POST['selected_numbers'] ?? '' ) );

        $allow_online = ! empty( $_POST['online_range_confirmed'] );
        $allocator = new DuckAllocationService();
        $duck_numbers = [];
        if ( ! empty( $selected_numbers ) ) {
            $errors = [];
            foreach ( $selected_numbers as $number ) {
                [ $ok, $msg ] = $allocator->validate_manual_number( $race, $number, $allow_online );
                if ( ! $ok ) {
                    $errors[] = $msg;
                } else {
                    $duck_numbers[] = $number;
                }
            }
            if ( ! empty( $errors ) ) {
                $this->redirect_error( implode( ' ', $errors ) );
            }
        } else {
            $duck_numbers = $allocator->next_available_numbers( $race, 'manual', $duck_count );
            if ( count( $duck_numbers ) < $duck_count ) {
                $this->redirect_error( __( 'Not enough manual-range ducks available.', 'duck-race' ) );
            }
        }

        $names = $this->parse_names( (string) ( $_POST['duck_names'] ?? '' ) );

        $purchase_id = ( new PurchaseService() )->create_manual_sale(
            (int) $race->id,
            $contact_id,
            $duck_numbers,
            $names,
            sanitize_text_field( wp_unslash( $_POST['payment_method'] ?? 'other' ) ),
            (float) ( $_POST['amount_paid'] ?? 0 ),
            sanitize_textarea_field( wp_unslash( $_POST['admin_notes'] ?? '' ) )
        );

        if ( $purchase_id <= 0 ) {
            $this->redirect_error( __( 'Could not save manual sale.', 'duck-race' ) );
        }

        wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-manual-sale', 'saved' => '1', 'purchase_id' => $purchase_id ], admin_url( 'admin.php' ) ) );
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

    /**
     * @return array<int, int>
     */
    private function parse_numbers( string $raw ): array {
        $parts = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
        $numbers = [];
        foreach ( $parts as $part ) {
            $n = (int) $part;
            if ( $n > 0 ) {
                $numbers[] = $n;
            }
        }

        return array_values( array_unique( $numbers ) );
    }

    /**
     * @return array<int, string>
     */
    private function parse_names( string $raw ): array {
        $parts = array_filter( array_map( 'trim', explode( ',', $raw ) ) );

        return array_values( array_map( 'sanitize_text_field', $parts ) );
    }

    private function redirect_error( string $message ): void {
        wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-manual-sale', 'error' => rawurlencode( $message ) ], admin_url( 'admin.php' ) ) );
        exit;
    }
}
