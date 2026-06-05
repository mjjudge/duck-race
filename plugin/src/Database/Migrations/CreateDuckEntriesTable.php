<?php

namespace DuckRace\Database\Migrations;

use DuckRace\Database\MigrationInterface;
use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class CreateDuckEntriesTable implements MigrationInterface {

    public function up(): void {
        $table_name = Schema::table_name( 'entries' );
        $collate = Schema::charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            race_id BIGINT UNSIGNED NOT NULL,
            purchase_id BIGINT UNSIGNED NOT NULL,
            contact_id BIGINT UNSIGNED NOT NULL,
            duck_number INT UNSIGNED NOT NULL,
            duck_name VARCHAR(191) NULL,
            dedication_message TEXT NULL,
            entry_status VARCHAR(20) NOT NULL DEFAULT 'reserved',
            winner_position INT UNSIGNED NULL,
            prize_label VARCHAR(191) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY race_duck_unique (race_id, duck_number),
            KEY purchase_id_idx (purchase_id),
            KEY contact_id_idx (contact_id),
            KEY entry_status_idx (entry_status)
        ) {$collate};";

        dbDelta( $sql );
    }
}
