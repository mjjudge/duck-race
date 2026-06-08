<?php

namespace DuckRace\Services;

use DuckRace\Audit\Logger;
use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class ReservationCleanupService {

    public const CRON_HOOK = 'duck_race_cleanup_stale_reservations';

    /**
     * Called by the hourly cron — releases all pending purchases older than 2 hours.
     */
    public function run_scheduled(): void {
        $this->release_stale( 7200 );
    }

    /**
     * Finds pending purchases older than $older_than_seconds, marks them abandoned,
     * and deletes their reserved duck entries.
     *
     * @param int $older_than_seconds Minimum age before a reservation is considered stale.
     * @return int Number of purchases abandoned.
     */
    public function release_stale( int $older_than_seconds = 7200 ): int {
        global $wpdb;

        $purchase_table = Schema::table_name( 'purchases' );
        $cutoff         = gmdate( 'Y-m-d H:i:s', time() - $older_than_seconds );

        $stale_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM {$purchase_table} WHERE payment_status = 'pending' AND created_at <= %s",
                $cutoff
            )
        );

        if ( empty( $stale_ids ) ) {
            return 0;
        }

        $purchase_service = new PurchaseService();
        foreach ( $stale_ids as $id ) {
            $purchase_service->release_reservations( (int) $id, 'abandoned' );
        }

        Logger::log(
            'system',
            0,
            'stale_reservations_released',
            [
                'count'               => count( $stale_ids ),
                'purchase_ids'        => array_map( 'intval', $stale_ids ),
                'older_than_seconds'  => $older_than_seconds,
                'triggered_by'        => did_action( 'wp_ajax_' ) ? 'admin' : 'cron',
            ]
        );

        return count( $stale_ids );
    }

    public static function ensure_schedule(): void {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
        }
    }

    public static function clear_schedule(): void {
        $timestamp = wp_next_scheduled( self::CRON_HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::CRON_HOOK );
        }
    }
}
