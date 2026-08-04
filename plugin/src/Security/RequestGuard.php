<?php

namespace DuckRace\Security;

defined( 'ABSPATH' ) || exit;

class RequestGuard {

    public static function require_capability( string $capability ): void {
        if ( ! current_user_can( $capability ) ) {
            wp_die( esc_html__( 'Access denied.', 'duck-race' ) );
        }
    }

    public static function verify_admin_nonce( string $nonce_action, string $nonce_name = '_wpnonce' ): void {
        $nonce = sanitize_text_field( wp_unslash( $_POST[ $nonce_name ] ?? '' ) );
        if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
            wp_die( esc_html__( 'Security check failed.', 'duck-race' ) );
        }
    }

    public static function verify_public_nonce( string $nonce_action, string $nonce_name ): bool {
        $nonce = sanitize_text_field( wp_unslash( $_POST[ $nonce_name ] ?? '' ) );

        return (bool) wp_verify_nonce( $nonce, $nonce_action );
    }

    public static function passes_honeypot( string $field_name ): bool {
        $value = sanitize_text_field( wp_unslash( $_POST[ $field_name ] ?? '' ) );

        return '' === $value;
    }

    /**
     * Marks the current response as non-cacheable. Purchase-flow pages and endpoints render
     * per-visitor state (nonces, live duck availability, payment status) that a page cache or
     * CDN must never serve to a second visitor. Must be called before any output starts.
     */
    public static function send_nocache_headers(): void {
        if ( ! defined( 'DONOTCACHEPAGE' ) ) {
            define( 'DONOTCACHEPAGE', true );
        }
        nocache_headers();
    }
}
