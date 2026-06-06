<?php

namespace DuckRace\Admin;

use DuckRace\Mail\Mailer;
use DuckRace\Mail\TemplateRenderer;
use DuckRace\Security\RequestGuard;
use DuckRace\Services\RetentionService;

defined( 'ABSPATH' ) || exit;

class SettingsPage {

    private const OPTION_KEY = 'duck_race_settings';
    private const NONCE_ACTION = 'duck_race_save_settings';
    private const TEST_NONCE_ACTION = 'duck_race_send_test_email';
    private const RETENTION_NONCE_ACTION = 'duck_race_run_retention_now';

    public function register(): void {
        add_action( 'admin_post_duck_race_save_settings', [ $this, 'handle_save' ] );
        add_action( 'admin_post_duck_race_send_test_email', [ $this, 'handle_send_test_email' ] );
        add_action( 'admin_post_duck_race_run_retention_now', [ $this, 'handle_run_retention_now' ] );
    }

    public function render(): void {
        RequestGuard::require_capability( 'duck_race_manage_settings' );

        $settings = get_option( self::OPTION_KEY, [] );
        $organisation_name = (string) ( $settings['organisation_name'] ?? '' );
        $contact_email = (string) ( $settings['contact_email'] ?? '' );
        $publishable_key = (string) ( $settings['stripe_publishable_key'] ?? '' );
        $secret_key = (string) ( $settings['stripe_secret_key'] ?? '' );
        $webhook_secret = (string) ( $settings['stripe_webhook_secret'] ?? '' );
        $email_logo_url = (string) ( $settings['email_logo_url'] ?? '' );
        $purchase_subject = (string) ( $settings['email_purchase_confirmation_subject'] ?? '' );
        $purchase_body = (string) ( $settings['email_purchase_confirmation_body'] ?? '' );
        $reminder_subject = (string) ( $settings['email_race_reminder_subject'] ?? '' );
        $reminder_body = (string) ( $settings['email_race_reminder_body'] ?? '' );
        $test_email_to = (string) ( $settings['email_test_recipient'] ?? $contact_email );
        $retention_days = (int) ( $settings['retention_non_opt_in_days'] ?? 365 );
        $confirm_uninstall_data_removal = ! empty( $settings['confirm_uninstall_data_removal'] );
        $retention_last_run_at = (string) get_option( 'duck_race_retention_last_run_at', '' );
        $retention_last_run_count = (int) get_option( 'duck_race_retention_last_run_count', 0 );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Duck Race Settings', 'duck-race' ) . '</h1>';

        if ( isset( $_GET['updated'] ) ) {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'duck-race' ) . '</p></div>';
        }

        if ( isset( $_GET['test_email'] ) ) {
            $test_result = sanitize_text_field( wp_unslash( (string) $_GET['test_email'] ) );
            if ( 'sent' === $test_result ) {
                echo '<div class="notice notice-success"><p>' . esc_html__( 'Test email sent.', 'duck-race' ) . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'Test email failed. Check email settings and recipient.', 'duck-race' ) . '</p></div>';
            }
        }

        if ( isset( $_GET['retention_run'] ) ) {
            echo '<div class="notice notice-success"><p>'
                . sprintf( esc_html__( 'Retention run completed. Contacts anonymised: %d', 'duck-race' ), (int) $_GET['retention_run'] )
                . '</p></div>';
        }

        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="duck_race_save_settings" />';
        wp_nonce_field( self::NONCE_ACTION, '_wpnonce' );

        echo '<table class="form-table" role="presentation">';

        echo '<tr>';
        echo '<th scope="row"><label for="organisation_name">' . esc_html__( 'Organisation name', 'duck-race' ) . '</label></th>';
        echo '<td><input name="organisation_name" id="organisation_name" type="text" class="regular-text" value="' . esc_attr( $organisation_name ) . '" />';
        echo '<p class="description">' . esc_html__( 'Your organisation\'s name, used in email templates and on the public purchase form.', 'duck-race' ) . '</p></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="contact_email">' . esc_html__( 'Contact email', 'duck-race' ) . '</label></th>';
        echo '<td><input name="contact_email" id="contact_email" type="email" class="regular-text" value="' . esc_attr( $contact_email ) . '" />';
        echo '<p class="description">' . esc_html__( 'Used as the reply-to address for emails sent to buyers, and as the default recipient for test emails.', 'duck-race' ) . '</p></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="stripe_publishable_key">' . esc_html__( 'Stripe publishable key', 'duck-race' ) . '</label></th>';
        echo '<td><input name="stripe_publishable_key" id="stripe_publishable_key" type="text" class="regular-text" value="' . esc_attr( $publishable_key ) . '" />';
        echo '<p class="description">' . esc_html__( 'Found in your Stripe Dashboard under Developers &gt; API Keys. Starts with pk_live_ or pk_test_. This key is safe to display publicly.', 'duck-race' ) . '</p></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="stripe_secret_key">' . esc_html__( 'Stripe secret key', 'duck-race' ) . '</label></th>';
        echo '<td><input name="stripe_secret_key" id="stripe_secret_key" type="password" class="regular-text" value="" placeholder="' . esc_attr( $this->masked_placeholder( $secret_key ) ) . '" />';
        echo '<p class="description">' . esc_html__( 'Found in your Stripe Dashboard under Developers &gt; API Keys. Starts with sk_live_ or sk_test_. Keep this private — never share it. Leave blank to keep the existing key.', 'duck-race' ) . '</p></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="stripe_webhook_secret">' . esc_html__( 'Stripe webhook secret', 'duck-race' ) . '</label></th>';
        echo '<td><input name="stripe_webhook_secret" id="stripe_webhook_secret" type="password" class="regular-text" value="" placeholder="' . esc_attr( $this->masked_placeholder( $webhook_secret ) ) . '" />';
        echo '<p class="description">' . sprintf( esc_html__( 'Found in your Stripe Dashboard under Developers &gt; Webhooks after registering the endpoint %s. Starts with whsec_. Leave blank to keep the existing secret.', 'duck-race' ), '<code>/wp-json/duck-race/v1/stripe-webhook</code>' ) . '</p></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="email_logo_url">' . esc_html__( 'Email logo URL', 'duck-race' ) . '</label></th>';
        echo '<td><input name="email_logo_url" id="email_logo_url" type="url" class="regular-text" value="' . esc_attr( $email_logo_url ) . '" />';
        echo '<p class="description">' . esc_html__( 'Full URL of an image to display at the top of outgoing emails. Leave blank to send text-only emails without a logo.', 'duck-race' ) . '</p></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="email_purchase_confirmation_subject">' . esc_html__( 'Purchase confirmation subject', 'duck-race' ) . '</label></th>';
        echo '<td><input name="email_purchase_confirmation_subject" id="email_purchase_confirmation_subject" type="text" class="regular-text" value="' . esc_attr( $purchase_subject ) . '" />';
        echo '<p class="description">' . esc_html__( 'Subject line of the email sent to the buyer after a successful payment. Merge tags are supported.', 'duck-race' ) . '</p></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="email_purchase_confirmation_body">' . esc_html__( 'Purchase confirmation body (HTML)', 'duck-race' ) . '</label></th>';
        echo '<td><textarea name="email_purchase_confirmation_body" id="email_purchase_confirmation_body" rows="8" class="large-text code">' . esc_textarea( $purchase_body ) . '</textarea>';
        echo '<p class="description">' . esc_html__( 'HTML body of the purchase confirmation email. Use the merge tags listed below.', 'duck-race' ) . '</p></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="email_race_reminder_subject">' . esc_html__( 'Race reminder subject', 'duck-race' ) . '</label></th>';
        echo '<td><input name="email_race_reminder_subject" id="email_race_reminder_subject" type="text" class="regular-text" value="' . esc_attr( $reminder_subject ) . '" />';
        echo '<p class="description">' . esc_html__( 'Subject line of the operational reminder email sent to race participants. Merge tags are supported.', 'duck-race' ) . '</p></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="email_race_reminder_body">' . esc_html__( 'Race reminder body (HTML)', 'duck-race' ) . '</label></th>';
        echo '<td><textarea name="email_race_reminder_body" id="email_race_reminder_body" rows="8" class="large-text code">' . esc_textarea( $reminder_body ) . '</textarea>';
        echo '<p class="description">' . esc_html__( 'HTML body of the race reminder email. Use the merge tags listed below.', 'duck-race' ) . '</p></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="retention_non_opt_in_days">' . esc_html__( 'Non-opt-in retention period (days)', 'duck-race' ) . '</label></th>';
        echo '<td>';
        echo '<input name="retention_non_opt_in_days" id="retention_non_opt_in_days" type="number" min="30" max="3650" class="small-text" value="' . esc_attr( (string) $retention_days ) . '" />';
        echo '<p class="description">' . esc_html__( 'How long buyer details are kept for contacts who have not consented to future communications. After this period (measured from their last completed race activity), their personal details are removed while financial and race records are preserved. Default is 365 days.', 'duck-race' ) . '</p>';
        if ( '' !== $retention_last_run_at ) {
            echo '<p class="description">' . sprintf( esc_html__( 'Last retention run: %1$s (%2$d anonymised)', 'duck-race' ), esc_html( $retention_last_run_at ), (int) $retention_last_run_count ) . '</p>';
        }
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="confirm_uninstall_data_removal">' . esc_html__( 'Destructive uninstall', 'duck-race' ) . '</label></th>';
        echo '<td>';
        echo '<label><input name="confirm_uninstall_data_removal" id="confirm_uninstall_data_removal" type="checkbox" value="1" ' . checked( $confirm_uninstall_data_removal, true, false ) . ' /> ' . esc_html__( 'I confirm plugin data may be deleted on uninstall.', 'duck-race' ) . '</label>';
        echo '<p class="description">' . esc_html__( 'Deactivating the plugin never deletes any data. Uninstall will only delete race, contact and payment data if this box is ticked. Leave unticked if you are temporarily deactivating the plugin.', 'duck-race' ) . '</p>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row">' . esc_html__( 'Merge tags', 'duck-race' ) . '</th>';
        echo '<td>';
        echo '<p><code>{first_name}</code> <code>{last_name}</code> <code>{organisation_name}</code> <code>{race_title}</code> <code>{race_date}</code> <code>{race_time}</code> <code>{race_location}</code> <code>{duck_numbers}</code> <code>{duck_names}</code> <code>{purchase_total}</code> <code>{buy_link}</code> <code>{previous_race_result}</code> <code>{winner_position}</code></p>';
        echo '<p class="description">' . esc_html__( 'When a buyer purchases multiple ducks, {duck_numbers} outputs a comma-separated list of all their duck numbers (e.g. 12, 34, 56) and {duck_names} outputs a comma-separated list of the names given for each duck (e.g. Sophie, Thomas, Emily). If no names were provided, {duck_names} is blank.', 'duck-race' ) . '</p>';
        echo '<details style="margin-top:10px;">';
        echo '<summary style="cursor:pointer;font-weight:600;">' . esc_html__( 'Example HTML email template', 'duck-race' ) . '</summary>';
        echo '<pre style="background:#f6f7f7;padding:12px;overflow:auto;font-size:12px;">' . esc_html( $this->example_email_template() ) . '</pre>';
        echo '</details>';
        echo '</td>';
        echo '</tr>';

        echo '</table>';

        submit_button( __( 'Save Settings', 'duck-race' ) );
        echo '</form>';

        echo '<hr />';
        echo '<h2>' . esc_html__( 'Send Test Email', 'duck-race' ) . '</h2>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="duck_race_send_test_email" />';
        wp_nonce_field( self::TEST_NONCE_ACTION, '_wpnonce' );
        echo '<p><label for="email_test_recipient">' . esc_html__( 'Recipient email', 'duck-race' ) . '</label><br />';
        echo '<input name="email_test_recipient" id="email_test_recipient" type="email" class="regular-text" value="' . esc_attr( $test_email_to ) . '" /></p>';
        submit_button( __( 'Send Test Email', 'duck-race' ), 'secondary', 'submit', false );
        echo '</form>';

        echo '<hr />';
        echo '<h2>' . esc_html__( 'Retention', 'duck-race' ) . '</h2>';
        echo '<p>' . esc_html__( 'Run anonymisation now for eligible non-opt-in contacts. This job is also scheduled daily via WP-Cron.', 'duck-race' ) . '</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="duck_race_run_retention_now" />';
        wp_nonce_field( self::RETENTION_NONCE_ACTION, '_wpnonce' );
        submit_button( __( 'Run Retention Now', 'duck-race' ), 'secondary', 'submit', false );
        echo '</form>';
        echo '<hr />';
        echo '<h2>' . esc_html__( 'Documentation', 'duck-race' ) . '</h2>';
        echo '<p>' . sprintf(
            esc_html__( 'For setup and operating guidance, visit the %s page.', 'duck-race' ),
            '<a href="' . esc_url( admin_url( 'admin.php?page=duck-race-help' ) ) . '">' . esc_html__( 'Help', 'duck-race' ) . '</a>'
        ) . '</p>';
        echo '</div>';
    }

    public function handle_save(): void {
        RequestGuard::require_capability( 'duck_race_manage_settings' );
        RequestGuard::verify_admin_nonce( self::NONCE_ACTION, '_wpnonce' );

        $organisation_name = sanitize_text_field( wp_unslash( $_POST['organisation_name'] ?? '' ) );
        $contact_email = sanitize_email( wp_unslash( $_POST['contact_email'] ?? '' ) );
        $existing = get_option( self::OPTION_KEY, [] );

        $publishable_key = sanitize_text_field( wp_unslash( $_POST['stripe_publishable_key'] ?? '' ) );
        $secret_key_input = sanitize_text_field( wp_unslash( $_POST['stripe_secret_key'] ?? '' ) );
        $webhook_secret_input = sanitize_text_field( wp_unslash( $_POST['stripe_webhook_secret'] ?? '' ) );
        $email_logo_url = esc_url_raw( wp_unslash( $_POST['email_logo_url'] ?? '' ) );
        $purchase_subject = sanitize_text_field( wp_unslash( $_POST['email_purchase_confirmation_subject'] ?? '' ) );
        $purchase_body = wp_kses_post( wp_unslash( $_POST['email_purchase_confirmation_body'] ?? '' ) );
        $reminder_subject = sanitize_text_field( wp_unslash( $_POST['email_race_reminder_subject'] ?? '' ) );
        $reminder_body = wp_kses_post( wp_unslash( $_POST['email_race_reminder_body'] ?? '' ) );
        $retention_days = max( 30, min( 3650, (int) ( $_POST['retention_non_opt_in_days'] ?? (int) ( $existing['retention_non_opt_in_days'] ?? 365 ) ) ) );
        $confirm_uninstall_data_removal = isset( $_POST['confirm_uninstall_data_removal'] ) ? 1 : 0;

        $secret_key = '' !== $secret_key_input
            ? $secret_key_input
            : (string) ( $existing['stripe_secret_key'] ?? '' );
        $webhook_secret = '' !== $webhook_secret_input
            ? $webhook_secret_input
            : (string) ( $existing['stripe_webhook_secret'] ?? '' );

        update_option(
            self::OPTION_KEY,
            [
                'organisation_name' => $organisation_name,
                'contact_email' => $contact_email,
                'stripe_publishable_key' => $publishable_key,
                'stripe_secret_key' => $secret_key,
                'stripe_webhook_secret' => $webhook_secret,
                'email_logo_url' => $email_logo_url,
                'email_purchase_confirmation_subject' => $purchase_subject,
                'email_purchase_confirmation_body' => $purchase_body,
                'email_race_reminder_subject' => $reminder_subject,
                'email_race_reminder_body' => $reminder_body,
                'email_test_recipient' => sanitize_email( wp_unslash( $_POST['email_test_recipient'] ?? (string) ( $existing['email_test_recipient'] ?? '' ) ) ),
                'retention_non_opt_in_days' => $retention_days,
                'confirm_uninstall_data_removal' => $confirm_uninstall_data_removal,
            ],
            false
        );

        wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-settings', 'updated' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function handle_send_test_email(): void {
        RequestGuard::require_capability( 'duck_race_manage_settings' );
        RequestGuard::verify_admin_nonce( self::TEST_NONCE_ACTION, '_wpnonce' );

        $settings = get_option( self::OPTION_KEY, [] );
        $recipient = sanitize_email( wp_unslash( $_POST['email_test_recipient'] ?? '' ) );
        if ( '' === $recipient ) {
            $recipient = sanitize_email( (string) ( $settings['contact_email'] ?? '' ) );
        }

        $settings['email_test_recipient'] = $recipient;
        update_option( self::OPTION_KEY, $settings, false );

        $renderer = new TemplateRenderer();
        $sample = [
            'first_name' => 'Test',
            'last_name' => 'Recipient',
            'organisation_name' => (string) ( $settings['organisation_name'] ?? '' ),
            'race_title' => 'Sample Duck Race',
            'race_date' => gmdate( 'Y-m-d' ),
            'race_time' => '12:00',
            'race_location' => 'Sample Venue',
            'duck_numbers' => '12, 34, 56',
            'duck_names' => 'Sophie, Thomas, Emily',
            'purchase_total' => '15.00',
        ];

        $subject = $renderer->render_subject( 'purchase_confirmation', $sample );
        $body = $renderer->render_body( 'purchase_confirmation', $sample );

        $ok = ( new Mailer() )->send(
            [
                'to' => $recipient,
                'subject' => $subject,
                'body' => $body,
                'email_type' => 'test_email',
            ]
        );

        wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-settings', 'test_email' => $ok ? 'sent' : 'failed' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function handle_run_retention_now(): void {
        RequestGuard::require_capability( 'duck_race_manage_settings' );
        RequestGuard::verify_admin_nonce( self::RETENTION_NONCE_ACTION, '_wpnonce' );

        $count = ( new RetentionService() )->run_manual();

        wp_safe_redirect( add_query_arg( [ 'page' => 'duck-race-settings', 'retention_run' => $count ], admin_url( 'admin.php' ) ) );
        exit;
    }

    private function example_email_template(): string {
        return '<p>Hello {first_name},</p>
<p>Thank you for your duck race purchase for <strong>{race_title}</strong>!</p>
<p>Your duck number(s): <strong>{duck_numbers}</strong></p>
<p>Names for your ducks: {duck_names}</p>
<p>Amount paid: &pound;{purchase_total}</p>
<p>The race takes place on {race_date} at {race_time}, {race_location}.</p>
<p>We look forward to seeing you there. Good luck!</p>
<p>Best wishes,<br />{organisation_name}</p>';
    }

    private function masked_placeholder( string $secret ): string {
        if ( '' === $secret ) {
            return '';
        }

        $len = strlen( $secret );
        if ( $len <= 8 ) {
            return str_repeat( '*', $len );
        }

        return substr( $secret, 0, 4 ) . str_repeat( '*', max( 1, $len - 8 ) ) . substr( $secret, -4 );
    }
}
