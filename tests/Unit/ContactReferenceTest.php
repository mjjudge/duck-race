<?php

declare(strict_types=1);

namespace DuckRace\Tests\Unit;

use DuckRace\Services\ContactService;

final class ContactReferenceTest {

    public static function run(): void {
        $created_at = '2026-08-05 10:30:00';

        // Reference is stable for the same id/timestamp.
        $ref1 = ContactService::format_reference( 3, $created_at );
        $ref2 = ContactService::format_reference( 3, $created_at );
        self::assertSameStr( $ref1, $ref2, 'Reference should be deterministic for the same contact id and timestamp.' );

        // Reference includes the date.
        self::assertContains( '20260805', $ref1, 'Reference should include the created date.' );
        self::assertContains( 'MAN-', $ref1, 'Reference should be prefixed with MAN-.' );

        // Different contact ids produce different references.
        $other = ContactService::format_reference( 4, $created_at );
        if ( $ref1 === $other ) {
            throw new \RuntimeException( 'References for different contact ids should differ.' );
        }

        // A malformed/blank timestamp still produces a usable reference rather than erroring.
        $fallback = ContactService::format_reference( 5, '' );
        self::assertContains( 'MAN-', $fallback, 'Reference should still be well-formed with a blank timestamp.' );

        // no_email_display() wraps the reference in a translatable label.
        $display = ContactService::no_email_display( 3, $created_at );
        self::assertContains( $ref1, $display, 'Display string should contain the formatted reference.' );
        self::assertContains( 'No email supplied', $display, 'Display string should explain there is no email.' );
    }

    private static function assertSameStr( string $expected, string $actual, string $message ): void {
        if ( $expected !== $actual ) {
            throw new \RuntimeException( $message . ' Expected: ' . json_encode( $expected ) . ' Actual: ' . json_encode( $actual ) );
        }
    }

    private static function assertContains( string $needle, string $haystack, string $message ): void {
        if ( ! str_contains( $haystack, $needle ) ) {
            throw new \RuntimeException( $message . ' String ' . json_encode( $needle ) . ' not found in ' . json_encode( $haystack ) );
        }
    }
}
