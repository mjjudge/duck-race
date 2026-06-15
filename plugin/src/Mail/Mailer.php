<?php

namespace DuckRace\Mail;

use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class Mailer {

    /**
     * @param array<string, mixed> $args
     */
    public function send( array $args ): bool {
        $to = sanitize_email( (string) ( $args['to'] ?? '' ) );
        $subject = sanitize_text_field( (string) ( $args['subject'] ?? '' ) );
        $body = (string) ( $args['body'] ?? '' );
        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

        $settings   = (array) get_option( 'duck_race_settings', [] );
        $from_email = sanitize_email( (string) ( $settings['contact_email'] ?? '' ) );
        $from_name  = sanitize_text_field( (string) ( $settings['organisation_name'] ?? '' ) );

        $from_email_filter = '' !== $from_email ? static fn() => $from_email : null;
        $from_name_filter  = '' !== $from_email && '' !== $from_name ? static fn() => $from_name : null;

        if ( $from_email_filter ) {
            add_filter( 'wp_mail_from', $from_email_filter );
        }
        if ( $from_name_filter ) {
            add_filter( 'wp_mail_from_name', $from_name_filter );
        }

        $ok = false;
        $error = '';
        if ( '' !== $to && '' !== $subject && '' !== trim( wp_strip_all_tags( $body ) ) ) {
            $ok = wp_mail( $to, $subject, $body, $headers );
            if ( ! $ok ) {
                $error = 'wp_mail_failed';
            }
        } else {
            $error = 'invalid_email_payload';
        }

        if ( $from_email_filter ) {
            remove_filter( 'wp_mail_from', $from_email_filter );
        }
        if ( $from_name_filter ) {
            remove_filter( 'wp_mail_from_name', $from_name_filter );
        }

        $this->log_event(
            $to,
            sanitize_text_field( (string) ( $args['email_type'] ?? 'generic' ) ),
            isset( $args['race_id'] ) ? (int) $args['race_id'] : null,
            isset( $args['purchase_id'] ) ? (int) $args['purchase_id'] : null,
            $ok ? 'sent' : 'failed',
            $error,
            $subject,
            $body
        );

        return $ok;
    }

    private function log_event(
        string $recipient,
        string $email_type,
        ?int $race_id,
        ?int $purchase_id,
        string $status,
        string $error,
        string $subject,
        string $body
    ): void {
        global $wpdb;

        $table = Schema::table_name( 'email_log' );
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( $exists !== $table ) {
            return;
        }

        $wpdb->insert(
            $table,
            [
                'recipient' => $recipient,
                'email_type' => $email_type,
                'race_id' => $race_id,
                'purchase_id' => $purchase_id,
                'status' => $status,
                'error_message' => ( '' !== $error ) ? $error : null,
                'subject' => $subject,
                'body_preview' => mb_substr( wp_strip_all_tags( $body ), 0, 500 ),
                'sent_at' => ( 'sent' === $status ) ? current_time( 'mysql', true ) : null,
                'created_at' => current_time( 'mysql', true ),
            ]
        );
    }
}
