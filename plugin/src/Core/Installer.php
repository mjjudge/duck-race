<?php

namespace DuckRace\Core;

defined( 'ABSPATH' ) || exit;

class Installer {

    public static function activate(): void {
        Roles::register();
        ( new \DuckRace\Database\Migrator() )->run();
        \DuckRace\Services\RetentionService::ensure_schedule();
        \DuckRace\Services\ReservationCleanupService::ensure_schedule();
        self::create_pages();
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        // Roles and data are intentionally preserved on deactivation.
        \DuckRace\Services\RetentionService::clear_schedule();
        \DuckRace\Services\ReservationCleanupService::clear_schedule();
        flush_rewrite_rules();
    }

    /** Creates any required public pages that are missing. Returns the count created. */
    public static function ensure_pages(): int {
        return self::create_pages();
    }

    private static function create_pages(): int {
        $pages = [
            [
                'title' => 'Duck Race - Buy Ducks',
                'slug' => 'duck-race-buy',
                'content' => '<!-- wp:shortcode -->[duck_race_buy race="current"]<!-- /wp:shortcode -->',
            ],
            [
                'title' => 'Duck Race - Payment Success',
                'slug' => 'duck-race-success',
                'content' => '<!-- wp:shortcode -->[duck_race_payment_success]<!-- /wp:shortcode -->',
            ],
            [
                'title' => 'Duck Race - Payment Failure',
                'slug' => 'duck-race-failure',
                'content' => '<!-- wp:shortcode -->[duck_race_payment_failure]<!-- /wp:shortcode -->',
            ],
        ];

        $created = 0;
        foreach ( $pages as $page ) {
            $existing = get_page_by_path( $page['slug'] );
            if ( $existing ) {
                continue;
            }

            wp_insert_post(
                [
                    'post_title' => $page['title'],
                    'post_name' => $page['slug'],
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                ]
            );
            $created++;
        }

        return $created;
    }
}
