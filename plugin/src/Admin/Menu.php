<?php

namespace DuckRace\Admin;

defined( 'ABSPATH' ) || exit;

class Menu {

    public const TOP_LEVEL_SLUG = 'duck-race';

    public function register(): void {
        add_action( 'admin_menu', [ $this, 'register_pages' ] );
    }

    public function register_pages(): void {
        // Pass '' as callback so add_menu_page does not register a duplicate hook.
        // The Races submenu below (same slug) owns the render callback.
        add_menu_page(
            __( 'Duck Race', 'duck-race' ),
            __( 'Duck Race', 'duck-race' ),
            'duck_race_access',
            self::TOP_LEVEL_SLUG,
            '',
            'dashicons-flag',
            56
        );

        add_submenu_page(
            self::TOP_LEVEL_SLUG,
            __( 'Races', 'duck-race' ),
            __( 'Races', 'duck-race' ),
            'duck_race_manage_races',
            self::TOP_LEVEL_SLUG,
            [ new RaceListPage(), 'render' ]
        );

        add_submenu_page(
            self::TOP_LEVEL_SLUG,
            __( 'Race Editor', 'duck-race' ),
            __( 'Race Editor', 'duck-race' ),
            'duck_race_manage_races',
            'duck-race-race-edit',
            [ new RaceEditPage(), 'render' ]
        );

        add_submenu_page(
            self::TOP_LEVEL_SLUG,
            __( 'Contacts', 'duck-race' ),
            __( 'Contacts', 'duck-race' ),
            'duck_race_manage_contacts',
            'duck-race-contacts',
            [ new ContactListPage(), 'render' ]
        );

        add_submenu_page(
            self::TOP_LEVEL_SLUG,
            __( 'Contact Editor', 'duck-race' ),
            __( 'Contact Editor', 'duck-race' ),
            'duck_race_manage_contacts',
            'duck-race-contact-edit',
            [ new ContactEditPage(), 'render' ]
        );

        add_submenu_page(
            self::TOP_LEVEL_SLUG,
            __( 'Manual Sales', 'duck-race' ),
            __( 'Manual Sales', 'duck-race' ),
            'duck_race_manage_sales',
            'duck-race-manual-sale',
            [ new ManualSalesPage(), 'render' ]
        );

        add_submenu_page(
            self::TOP_LEVEL_SLUG,
            __( 'Duck Grid', 'duck-race' ),
            __( 'Duck Grid', 'duck-race' ),
            'duck_race_manage_entries',
            'duck-race-duck-grid',
            [ new DuckGridPage(), 'render' ]
        );

        add_submenu_page(
            self::TOP_LEVEL_SLUG,
            __( 'Winners', 'duck-race' ),
            __( 'Winners', 'duck-race' ),
            'duck_race_manage_winners',
            'duck-race-winners',
            [ new WinnerManagementPage(), 'render' ]
        );

        add_submenu_page(
            self::TOP_LEVEL_SLUG,
            __( 'Reporting', 'duck-race' ),
            __( 'Reporting', 'duck-race' ),
            'duck_race_manage_sales',
            'duck-race-reporting',
            [ new ReportingPage(), 'render' ]
        );

        add_submenu_page(
            self::TOP_LEVEL_SLUG,
            __( 'Campaigns', 'duck-race' ),
            __( 'Campaigns', 'duck-race' ),
            'duck_race_manage_contacts',
            'duck-race-campaigns',
            [ new CampaignMarketingPage(), 'render' ]
        );

        add_submenu_page(
            self::TOP_LEVEL_SLUG,
            __( 'Race Reminders', 'duck-race' ),
            __( 'Race Reminders', 'duck-race' ),
            'duck_race_manage_sales',
            'duck-race-race-reminders',
            [ new RaceReminderPage(), 'render' ]
        );

        add_submenu_page(
            self::TOP_LEVEL_SLUG,
            __( 'Refunds', 'duck-race' ),
            __( 'Refunds', 'duck-race' ),
            'duck_race_process_refunds',
            'duck-race-refunds',
            [ new RefundPage(), 'render' ]
        );

        add_submenu_page(
            self::TOP_LEVEL_SLUG,
            __( 'Help', 'duck-race' ),
            __( 'Help', 'duck-race' ),
            'duck_race_access',
            'duck-race-help',
            [ new HelpPage(), 'render' ]
        );

        add_submenu_page(
            self::TOP_LEVEL_SLUG,
            __( 'Settings', 'duck-race' ),
            __( 'Settings', 'duck-race' ),
            'duck_race_manage_settings',
            'duck-race-settings',
            [ new SettingsPage(), 'render' ]
        );
    }
}
