<?php

namespace DuckRace\Database\Migrations;

use DuckRace\Database\MigrationInterface;
use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class CreateAuditLogTable implements MigrationInterface {

    public function up(): void {
        $table_name = Schema::table_name( 'audit_log' );
        $collate = Schema::charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type VARCHAR(100) NOT NULL,
            entity_type VARCHAR(50) NOT NULL,
            entity_id BIGINT UNSIGNED NULL,
            actor_user_id BIGINT UNSIGNED NULL,
            before_json LONGTEXT NULL,
            after_json LONGTEXT NULL,
            context_json LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY event_type_idx (event_type),
            KEY entity_lookup_idx (entity_type, entity_id),
            KEY actor_user_id_idx (actor_user_id),
            KEY created_at_idx (created_at)
        ) {$collate};";

        dbDelta( $sql );
    }
}
