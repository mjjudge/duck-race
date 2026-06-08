<?php

namespace DuckRace\Database\Migrations;

use DuckRace\Database\MigrationInterface;
use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class AddGiftAidToPurchases implements MigrationInterface {

    public function up(): void {
        $table_name = Schema::table_name( 'purchases' );
        $collate    = Schema::charset_collate();

        // dbDelta adds missing columns when given the full CREATE TABLE definition.
        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            race_id BIGINT UNSIGNED NOT NULL,
            contact_id BIGINT UNSIGNED NOT NULL,
            purchase_source VARCHAR(30) NOT NULL,
            payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
            stripe_checkout_session_id VARCHAR(191) NULL,
            stripe_payment_intent_id VARCHAR(191) NULL,
            stripe_charge_id VARCHAR(191) NULL,
            total_duck_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            chosen_number_uplift_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            donation_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            grand_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            currency CHAR(3) NOT NULL DEFAULT 'GBP',
            gift_aid_declared TINYINT(1) NOT NULL DEFAULT 0,
            admin_notes TEXT NULL,
            email_confirmation_sent_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            paid_at DATETIME NULL,
            PRIMARY KEY  (id),
            KEY race_id_idx (race_id),
            KEY contact_id_idx (contact_id),
            KEY payment_status_idx (payment_status),
            KEY created_at_idx (created_at),
            KEY paid_at_idx (paid_at)
        ) {$collate};";

        dbDelta( $sql );
    }
}
