<?php

namespace DuckRace\Admin;

use DuckRace\Security\RequestGuard;

defined( 'ABSPATH' ) || exit;

class HelpPage {

    public function render(): void {
        RequestGuard::require_capability( 'duck_race_access' );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Duck Race — Help &amp; Instructions', 'duck-race' ) . '</h1>';
        echo '<p>' . esc_html__( 'This guide covers day-to-day administration of the Duck Race plugin.', 'duck-race' ) . '</p>';

        echo '<nav style="margin-bottom:20px;padding:12px 16px;background:#fff;border:1px solid #ccd0d4;">';
        echo '<strong>' . esc_html__( 'Contents', 'duck-race' ) . '</strong><br />';
        echo '<a href="#help-race-setup">' . esc_html__( '1. Race Setup', 'duck-race' ) . '</a> &middot; ';
        echo '<a href="#help-manual-sales">' . esc_html__( '2. Manual Sales', 'duck-race' ) . '</a> &middot; ';
        echo '<a href="#help-online-sales">' . esc_html__( '3. Online Sales', 'duck-race' ) . '</a> &middot; ';
        echo '<a href="#help-stripe">' . esc_html__( '4. Stripe Setup', 'duck-race' ) . '</a> &middot; ';
        echo '<a href="#help-gdpr">' . esc_html__( '5. GDPR &amp; Retention', 'duck-race' ) . '</a> &middot; ';
        echo '<a href="#help-winners">' . esc_html__( '6. Winners', 'duck-race' ) . '</a> &middot; ';
        echo '<a href="#help-reporting">' . esc_html__( '7. Reporting &amp; Export', 'duck-race' ) . '</a> &middot; ';
        echo '<a href="#help-duck-grid">' . esc_html__( '8. Duck Grid', 'duck-race' ) . '</a> &middot; ';
        echo '<a href="#help-uninstall">' . esc_html__( '9. Uninstall', 'duck-race' ) . '</a>';
        echo '</nav>';

        echo '<h2 id="help-race-setup">' . esc_html__( '1. Race Setup', 'duck-race' ) . '</h2>';
        echo '<ol>';
        echo '<li>' . esc_html__( 'Open Duck Race &gt; Races.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Select Create Race.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Enter a title, date, time, location and description.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Set the Slug — this is the short identifier used in the race\'s web page address (e.g. evesham-duck-race-2026). Use lowercase letters, numbers and hyphens only.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Configure manual and online duck number ranges with no overlap. Manual range is for physical sales; online range is for web purchases.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Set price per duck, chosen-number uplift (the extra charge when a buyer picks a specific duck number) and the maximum ducks per transaction.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Set sales open and close times, then change status to Open when ready.', 'duck-race' ) . '</li>';
        echo '</ol>';
        echo '<p><strong>' . esc_html__( 'Notes:', 'duck-race' ) . '</strong></p>';
        echo '<ul>';
        echo '<li>' . esc_html__( 'Online sales only run when race status is Open and within the sales window.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Winner and retention workflows are available for completed races.', 'duck-race' ) . '</li>';
        echo '</ul>';

        echo '<h2 id="help-manual-sales">' . esc_html__( '2. Manual Sales', 'duck-race' ) . '</h2>';
        echo '<ol>';
        echo '<li>' . esc_html__( 'Open Duck Race &gt; Manual Sales.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Select the race.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Enter buyer details, address (optional) and consent selections.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Enter the number of ducks and optional specific manual-range numbers. Leave blank to auto-allocate.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Optionally enter names for ducks — these identify who each duck belongs to, for example family members.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Enter the payment method and amount paid, then save.', 'duck-race' ) . '</li>';
        echo '</ol>';
        echo '<p><strong>' . esc_html__( 'Safety:', 'duck-race' ) . '</strong> ' . esc_html__( 'Manual range validation prevents collisions and out-of-range use. Already sold, reserved or lost ducks cannot be reallocated.', 'duck-race' ) . '</p>';

        echo '<h2 id="help-online-sales">' . esc_html__( '3. Online Sales', 'duck-race' ) . '</h2>';
        echo '<p>' . esc_html__( 'Add the shortcode', 'duck-race' ) . ' <code>[duck_race_buy]</code> ' . esc_html__( 'to any WordPress page to display the online purchase form.', 'duck-race' ) . '</p>';
        echo '<ol>';
        echo '<li>' . esc_html__( 'Buyer selects quantity and optional names for ducks or chosen numbers.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Buyer enters their contact details and consent selections.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'The system creates a pending purchase and reserves the ducks.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Buyer is redirected to Stripe Checkout to pay.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Stripe webhook confirms payment and finalises duck ownership.', 'duck-race' ) . '</li>';
        echo '</ol>';
        echo '<p><strong>' . esc_html__( 'Important:', 'duck-race' ) . '</strong> ' . esc_html__( 'The success page alone does not confirm payment. Stripe webhook confirmation is required before a purchase is treated as paid.', 'duck-race' ) . '</p>';

        echo '<h2 id="help-stripe">' . esc_html__( '4. Stripe Setup', 'duck-race' ) . '</h2>';
        echo '<ol>';
        echo '<li>' . esc_html__( 'Open Duck Race &gt; Settings.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Enter your Stripe publishable key (starts with pk_).', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Enter your Stripe secret key (starts with sk_). Keep this private.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Enter your Stripe webhook secret (starts with whsec_).', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Save settings.', 'duck-race' ) . '</li>';
        echo '</ol>';
        echo '<p><strong>' . esc_html__( 'Webhook endpoint:', 'duck-race' ) . '</strong> <code>/wp-json/duck-race/v1/stripe-webhook</code></p>';
        echo '<p>' . esc_html__( 'Register this URL in your Stripe Dashboard under Developers &gt; Webhooks. Use the Stripe CLI to forward webhooks locally during testing.', 'duck-race' ) . '</p>';

        echo '<h2 id="help-gdpr">' . esc_html__( '5. GDPR &amp; Retention', 'duck-race' ) . '</h2>';
        echo '<p><strong>' . esc_html__( 'Consent model:', 'duck-race' ) . '</strong></p>';
        echo '<ul>';
        echo '<li>' . esc_html__( 'Buyers can purchase ducks without consenting to future communications.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Future duck race communications and wider organisation communications are separate consent options.', 'duck-race' ) . '</li>';
        echo '</ul>';
        echo '<p><strong>' . esc_html__( 'Retention controls:', 'duck-race' ) . '</strong></p>';
        echo '<ol>';
        echo '<li>' . esc_html__( 'Open Duck Race &gt; Settings.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Configure the non-opt-in retention period (in days). Contacts without consent are anonymised after this period following their last race activity.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Use Run Retention Now for immediate manual processing.', 'duck-race' ) . '</li>';
        echo '</ol>';
        echo '<p>' . esc_html__( 'Anonymisation removes personal details while preserving financial and race records. All anonymisation actions are logged.', 'duck-race' ) . '</p>';

        echo '<h2 id="help-winners">' . esc_html__( '6. Winner Recording', 'duck-race' ) . '</h2>';
        echo '<ol>';
        echo '<li>' . esc_html__( 'Open Duck Race &gt; Winners.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Select the race from the dropdown.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Configure winner positions (1st, 2nd, 3rd are required) and optional prize labels.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Assign duck numbers to each winner position and save.', 'duck-race' ) . '</li>';
        echo '</ol>';
        echo '<p>' . esc_html__( 'To display winners publicly, add the shortcode', 'duck-race' ) . ' <code>[duck_race_winners]</code> ' . esc_html__( 'to any WordPress page. This shows winner names and positions only — no contact details are displayed.', 'duck-race' ) . '</p>';

        echo '<h2 id="help-reporting">' . esc_html__( '7. Reporting &amp; Export', 'duck-race' ) . '</h2>';
        echo '<ol>';
        echo '<li>' . esc_html__( 'Open Duck Race &gt; Reporting.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Select a race to view sales, payment status and consent totals.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Use the export buttons to download CSV files for entries, purchases, contacts, winners or accounting.', 'duck-race' ) . '</li>';
        echo '</ol>';

        echo '<h2 id="help-duck-grid">' . esc_html__( '8. Duck Grid', 'duck-race' ) . '</h2>';
        echo '<p>' . esc_html__( 'The Duck Grid provides a visual overview of all duck numbers for a race.', 'duck-race' ) . '</p>';
        echo '<ul>';
        echo '<li>' . esc_html__( 'Select a race and use filters to view ducks by status (available, sold, lost, reserved, winners).', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Click any duck tile to view details, mark it as Lost Duck or Duck Found, or add a comment about its condition.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'A small note icon on a tile means a comment has been saved for that duck.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Lost and found status and comments follow the physical duck across all races.', 'duck-race' ) . '</li>';
        echo '</ul>';

        echo '<h2 id="help-uninstall">' . esc_html__( '9. Uninstall Behaviour', 'duck-race' ) . '</h2>';
        echo '<ul>';
        echo '<li>' . esc_html__( 'Deactivating the plugin does not delete any data.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Data is only deleted on uninstall if the Destructive uninstall confirmation setting is enabled in Settings.', 'duck-race' ) . '</li>';
        echo '<li>' . esc_html__( 'Without that confirmation, uninstalling the plugin leaves all race, contact and purchase data intact.', 'duck-race' ) . '</li>';
        echo '</ul>';

        echo '</div>';
    }
}
