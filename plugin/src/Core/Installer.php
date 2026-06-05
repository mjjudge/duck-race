<?php

namespace DuckRace\Core;

defined( 'ABSPATH' ) || exit;

class Installer {

    public static function activate(): void {
        Roles::register();
        ( new \DuckRace\Database\Migrator() )->run();
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        // Roles and data are intentionally preserved on deactivation.
        flush_rewrite_rules();
    }
}
