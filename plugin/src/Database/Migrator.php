<?php

namespace DuckRace\Database;

defined( 'ABSPATH' ) || exit;

class Migrator {

    private const DB_VERSION_OPTION = 'duck_race_db_version';

    /**
     * Ordered list of schema migrations by target plugin version.
     *
     * @var array<string, class-string<MigrationInterface>>
     */
    private const MIGRATIONS = [
        '0.2.0' => Migrations\CreateRacesTable::class,
        '0.3.0' => Migrations\CreateContactsTable::class,
        '0.4.0' => Migrations\CreatePurchasesTable::class,
        '0.5.0' => Migrations\CreateDuckEntriesTable::class,
        '0.6.0' => Migrations\CreateDuckStatusTable::class,
        '0.7.0' => Migrations\CreateAuditLogTable::class,
        '0.8.0' => Migrations\CreateEmailLogTable::class,
        '0.9.0' => Migrations\CreateWinnerPositionsTable::class,
    ];

    public function run(): void {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $installed = self::installed_version();

        foreach ( self::MIGRATIONS as $version => $class ) {
            if ( version_compare( $installed, $version, '<' ) ) {
                ( new $class() )->up();
                update_option( self::DB_VERSION_OPTION, $version, true );
                $installed = $version;
            }
        }

        // Always stamp current plugin version after successful run.
        update_option( self::DB_VERSION_OPTION, DUCK_RACE_VERSION, true );
    }

    public static function installed_version(): string {
        return (string) get_option( self::DB_VERSION_OPTION, '0.0.0' );
    }
}
