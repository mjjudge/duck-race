<?php

namespace DuckRace\Database\Migrations;

use DuckRace\Database\MigrationInterface;
use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class CreateDuckPhysicalStateTable implements MigrationInterface {

    public function up(): void {
        $table_name = Schema::table_name( 'duck_physical_state' );
        $collate = Schema::charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            duck_number INT UNSIGNED NOT NULL,
            is_lost TINYINT(1) NOT NULL DEFAULT 0,
            comment TEXT NULL,
            changed_at DATETIME NOT NULL,
            changed_by BIGINT UNSIGNED NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY duck_number_unique (duck_number),
            KEY is_lost_idx (is_lost)
        ) {$collate};";

        dbDelta( $sql );
    }
}
