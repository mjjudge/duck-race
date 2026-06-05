<?php

namespace DuckRace\Database\Migrations;

use DuckRace\Database\MigrationInterface;
use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class CreateDuckStatusTable implements MigrationInterface {

    public function up(): void {
        $table_name = Schema::table_name( 'duck_status' );
        $collate = Schema::charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            race_id BIGINT UNSIGNED NOT NULL,
            duck_number INT UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'lost',
            reason VARCHAR(191) NULL,
            changed_at DATETIME NOT NULL,
            changed_by BIGINT UNSIGNED NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY race_duck_status_unique (race_id, duck_number),
            KEY status_idx (status),
            KEY changed_at_idx (changed_at)
        ) {$collate};";

        dbDelta( $sql );
    }
}
