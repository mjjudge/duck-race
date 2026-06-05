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
        ( new \DuckRace\Public\CheckoutController() )->register();
        ( new \DuckRace\Public\PaymentPagesHandler() )->register();
        ( new \DuckRace\Public\WinnersShortcode() )->register();
        ( new \DuckRace\Rest\ContactRecognitionRoute() )->register();
        ( new \DuckRace\Rest\StripeWebhookRoute() )->register();
        add_action( \DuckRace\Services\RetentionService::CRON_HOOK, [ new \DuckRace\Services\RetentionService(), 'run_scheduled' ] );

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
