<?php

namespace DuckRace\Database\Migrations;

use DuckRace\Database\MigrationInterface;
use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class CreateRacesTable implements MigrationInterface {

    public function up(): void {
        $table_name = Schema::table_name( 'races' );
        $collate = Schema::charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(191) NOT NULL,
            slug VARCHAR(191) NOT NULL,
            race_date DATE NULL,
            race_time TIME NULL,
            location VARCHAR(191) NULL,
            public_description TEXT NULL,
            sales_open_at DATETIME NULL,
            sales_close_at DATETIME NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            manual_range_start INT UNSIGNED NOT NULL,
            manual_range_end INT UNSIGNED NOT NULL,
            online_range_start INT UNSIGNED NOT NULL,
            online_range_end INT UNSIGNED NOT NULL,
            total_range_start INT UNSIGNED NOT NULL,
            total_range_end INT UNSIGNED NOT NULL,
            price_per_duck DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            chosen_number_uplift DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            max_ducks_per_transaction INT UNSIGNED NOT NULL DEFAULT 20,
            extra_donation_enabled TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug_unique (slug),
            KEY status_idx (status),
            KEY race_date_idx (race_date)
        ) {$collate};";

        dbDelta( $sql );
    }
}
