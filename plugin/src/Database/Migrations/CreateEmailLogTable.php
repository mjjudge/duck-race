<?php

namespace DuckRace\Database\Migrations;

use DuckRace\Database\MigrationInterface;
use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class CreateEmailLogTable implements MigrationInterface {

    public function up(): void {
        $table_name = Schema::table_name( 'email_log' );
        $collate = Schema::charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            recipient VARCHAR(191) NOT NULL,
            email_type VARCHAR(100) NOT NULL,
            race_id BIGINT UNSIGNED NULL,
            purchase_id BIGINT UNSIGNED NULL,
            status VARCHAR(20) NOT NULL,
            error_message TEXT NULL,
            subject VARCHAR(191) NULL,
            body_preview TEXT NULL,
            sent_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY email_type_idx (email_type),
            KEY recipient_idx (recipient),
            KEY race_id_idx (race_id),
            KEY purchase_id_idx (purchase_id),
            KEY status_idx (status),
            KEY created_at_idx (created_at)
        ) {$collate};";

        dbDelta( $sql );
    }
}
