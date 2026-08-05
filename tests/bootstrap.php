<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! function_exists( '__' ) ) {
    function __( string $text, string $domain = '' ): string {
        return $text;
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $value ): string {
        return is_string( $value ) ? trim( $value ) : '';
    }
}

if ( ! function_exists( 'sanitize_email' ) ) {
    function sanitize_email( $value ): string {
        return is_string( $value ) ? strtolower( trim( $value ) ) : '';
    }
}

if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $key ): string {
        $key = is_string( $key ) ? strtolower( $key ) : '';
        return preg_replace( '/[^a-z0-9_\-]/', '', $key ) ?? '';
    }
}

if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) {
        return $value;
    }
}

if ( ! function_exists( 'current_time' ) ) {
    function current_time( string $type, bool $gmt = false ) {
        return 'timestamp' === $type ? time() : gmdate( 'Y-m-d H:i:s' );
    }
}

if ( ! function_exists( 'home_url' ) ) {
    function home_url( string $path = '' ): string {
        return 'https://example.test' . $path;
    }
}

if ( ! function_exists( 'add_query_arg' ) ) {
    function add_query_arg( array $args, string $url ): string {
        return $url . ( str_contains( $url, '?' ) ? '&' : '?' ) . http_build_query( $args );
    }
}

if ( ! function_exists( 'wp_generate_password' ) ) {
    function wp_generate_password( int $length = 24, bool $special_chars = true, bool $extra_special_chars = false ): string {
        return substr( str_repeat( 'a', $length ), 0, $length );
    }
}

require_once __DIR__ . '/../plugin/src/Services/DuckAllocationService.php';
require_once __DIR__ . '/../plugin/src/Services/PurchaseService.php';
require_once __DIR__ . '/../plugin/src/Services/StripeWebhookProcessor.php';
require_once __DIR__ . '/../plugin/src/Services/RaceLifecycleService.php';
require_once __DIR__ . '/../plugin/src/Services/ContactService.php';
