<?php
/**
 * Plugin Name: Duck Race
 * Plugin URI:  https://github.com/mjjudge/duck-race
 * Description: Reusable WordPress fundraising platform for running duck races.
 * Version:     0.6.0
 * Requires at least: 6.0
 * Requires PHP: 8.2
 * Author:      Duck Race Contributors
 * License:     GPL-2.0-or-later
 * Text Domain: duck-race
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'DUCK_RACE_VERSION', '0.6.0' );
define( 'DUCK_RACE_PLUGIN_FILE', __FILE__ );
define( 'DUCK_RACE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DUCK_RACE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Maps DuckRace\Foo\Bar to plugin/src/Foo/Bar.php.
spl_autoload_register( static function ( string $class ): void {
    if ( ! str_starts_with( $class, 'DuckRace\\' ) ) {
        return;
    }

    $file = DUCK_RACE_PLUGIN_DIR . 'src/' . str_replace( '\\', '/', substr( $class, 9 ) ) . '.php';
    if ( is_readable( $file ) ) {
        require $file;
    }
} );

register_activation_hook( __FILE__, [ DuckRace\Core\Installer::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ DuckRace\Core\Installer::class, 'deactivate' ] );

add_action( 'plugins_loaded', static function (): void {
    ( new DuckRace\Core\Plugin() )->boot();
} );
