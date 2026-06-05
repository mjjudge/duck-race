<?php

namespace DuckRace\Database\Migrations;

use DuckRace\Database\MigrationInterface;
use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class CreateWinnerPositionsTable implements MigrationInterface {

    public function up(): void {
        $table_name = Schema::table_name( 'winner_positions' );
        $collate = Schema::charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            race_id BIGINT UNSIGNED NOT NULL,
            position_number INT UNSIGNED NOT NULL,
            prize_label VARCHAR(191) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY race_position_unique (race_id, position_number),
            KEY race_id_idx (race_id)
        ) {$collate};";

        dbDelta( $sql );
    }
}
