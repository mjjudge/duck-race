<?php

namespace DuckRace\Database\Migrations;

use DuckRace\Database\MigrationInterface;
use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class CreateContactsTable implements MigrationInterface {

    public function up(): void {
        $table_name = Schema::table_name( 'contacts' );
        $collate = Schema::charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            organisation_name VARCHAR(191) NULL,
            email VARCHAR(191) NOT NULL,
            phone VARCHAR(50) NULL,
            address_line_1 VARCHAR(191) NULL,
            address_line_2 VARCHAR(191) NULL,
            city VARCHAR(100) NULL,
            postcode VARCHAR(30) NULL,
            country VARCHAR(100) NULL,
            consent_duck_race TINYINT(1) NOT NULL DEFAULT 0,
            consent_organisation TINYINT(1) NOT NULL DEFAULT 0,
            consent_timestamp DATETIME NULL,
            consent_source VARCHAR(100) NULL,
            last_purchase_at DATETIME NULL,
            notes TEXT NULL,
            anonymised TINYINT(1) NOT NULL DEFAULT 0,
            anonymised_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY email_unique (email),
            KEY last_purchase_idx (last_purchase_at),
            KEY anonymised_idx (anonymised)
        ) {$collate};";

        dbDelta( $sql );
    }
}
