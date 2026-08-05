<?php

namespace DuckRace\Database\Migrations;

use DuckRace\Database\MigrationInterface;
use DuckRace\Database\Schema;

defined( 'ABSPATH' ) || exit;

class AddNullableEmailToContacts implements MigrationInterface {

    public function up(): void {
        global $wpdb;
        $table = Schema::table_name( 'contacts' );

        // dbDelta doesn't reliably alter an existing column's nullability, so ALTER
        // directly. Idempotent: re-running MODIFY to the same definition is a no-op.
        // UNIQUE KEY email_unique is untouched — MySQL treats multiple NULLs as
        // distinct, so contacts with no email still can't collide with each other
        // or with a contact that has a real email.
        $wpdb->query( "ALTER TABLE {$table} MODIFY email VARCHAR(191) NULL" );
    }
}
