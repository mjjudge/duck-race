<?php

namespace DuckRace\Core;

defined( 'ABSPATH' ) || exit;

class Plugin {

    public function boot(): void {
        load_plugin_textdomain(
            'duck-race',
            false,
            dirname( plugin_basename( DUCK_RACE_PLUGIN_FILE ) ) . '/languages'
        );

        ( new \DuckRace\Public\BuyFormHandler() )->register();

        if ( is_admin() ) {
            ( new \DuckRace\Admin\Menu() )->register();
            ( new \DuckRace\Admin\SettingsPage() )->register();
            ( new \DuckRace\Admin\RaceEditPage() )->register();
            ( new \DuckRace\Admin\ContactEditPage() )->register();
        }
    }
}
