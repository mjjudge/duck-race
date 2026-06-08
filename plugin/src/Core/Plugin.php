<?php

namespace DuckRace\Core;

defined( 'ABSPATH' ) || exit;

class Plugin {

    public function boot(): void {
        // Self-heal: if the administrator role is missing plugin capabilities (e.g. plugin was
        // activated before a new capability was added), re-sync without requiring deactivation.
        $admin = get_role( 'administrator' );
        if ( $admin && ! $admin->has_cap( 'duck_race_manage_sales' ) ) {
            Roles::register();
        }

        // Auto-run pending schema migrations without requiring deactivation/reactivation.
        if ( is_admin() && version_compare( \DuckRace\Database\Migrator::installed_version(), DUCK_RACE_VERSION, '<' ) ) {
            add_action( 'admin_init', static function () {
                ( new \DuckRace\Database\Migrator() )->run();
            } );
        }

        load_plugin_textdomain(
            'duck-race',
            false,
            dirname( plugin_basename( DUCK_RACE_PLUGIN_FILE ) ) . '/languages'
        );

        ( new \DuckRace\Public\BuyFormHandler() )->register();
        ( new \DuckRace\Public\CheckoutController() )->register();
        ( new \DuckRace\Public\TestCheckoutController() )->register();
        ( new \DuckRace\Public\PaymentPagesHandler() )->register();
        ( new \DuckRace\Public\WinnersShortcode() )->register();
        ( new \DuckRace\Rest\ContactRecognitionRoute() )->register();
        ( new \DuckRace\Rest\DuckPreviewRoute() )->register();
        ( new \DuckRace\Rest\StripeWebhookRoute() )->register();
        add_action( \DuckRace\Services\RetentionService::CRON_HOOK, [ new \DuckRace\Services\RetentionService(), 'run_scheduled' ] );
        add_action( \DuckRace\Services\ReservationCleanupService::CRON_HOOK, [ new \DuckRace\Services\ReservationCleanupService(), 'run_scheduled' ] );

        if ( is_admin() ) {
            ( new \DuckRace\Admin\Menu() )->register();
            ( new \DuckRace\Admin\SettingsPage() )->register();
            ( new \DuckRace\Admin\RaceEditPage() )->register();
            ( new \DuckRace\Admin\ContactEditPage() )->register();
            ( new \DuckRace\Admin\ManualSalesPage() )->register();
            ( new \DuckRace\Admin\RaceReminderPage() )->register();
            ( new \DuckRace\Admin\WinnerManagementPage() )->register();
            ( new \DuckRace\Admin\ReportingPage() )->register();
            ( new \DuckRace\Admin\DuckGridPage() )->register();
            ( new \DuckRace\Admin\CampaignMarketingPage() )->register();
        }
    }
}
