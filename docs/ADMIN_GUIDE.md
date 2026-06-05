# Duck Race Admin Guide

This guide explains day-to-day administration of Duck Race.

## 1. Race Setup

1. Open Duck Race > Races.
2. Select Create Race.
3. Enter title, date, time, location and description.
4. Configure manual and online duck ranges with no overlap.
5. Set price per duck, chosen-number uplift and max ducks per transaction.
6. Set sales open/close times and move status to Open when ready.

Notes:

- Online sales only run during open status and valid sales window.
- Winner and retention workflows are intended for completed races.

## 2. Manual Sales

1. Open Duck Race > Manual Sales.
2. Select the race.
3. Enter buyer details and consent selections.
4. Enter duck count and optional specific manual-range numbers.
5. Enter payment method and amount paid.
6. Save manual sale.

Safety behavior:

- Manual range validation prevents collisions and out-of-range use.
- Already sold/reserved/lost ducks cannot be reallocated.

## 3. Online Sales

Public online sales use [duck_race_buy].

Flow summary:

1. Buyer enters duck quantity and optional names/chosen numbers.
2. Buyer enters details and consent selections.
3. System creates pending purchase and reserves ducks.
4. Buyer is redirected to Stripe Checkout.
5. Stripe webhook confirms payment and finalizes duck ownership.

Important:

- Success page is not payment authority.
- Stripe webhook confirmation is required before purchase is treated as paid.

## 4. Stripe Setup

1. Open Duck Race > Settings.
2. Set Stripe publishable key.
3. Set Stripe secret key.
4. Set Stripe webhook secret.
5. Save settings.

Webhook endpoint:

- /wp-json/duck-race/v1/stripe-webhook

Recommended test workflow:

- Use Stripe test mode.
- Forward webhooks to the endpoint above from Stripe CLI.

## 5. GDPR and Retention

Consent model:

- Participation does not require marketing consent.
- Future duck race and wider organisation consent are separate.

Retention controls:

1. Open Duck Race > Settings.
2. Configure non-opt-in retention period (days).
3. Use Run Retention Now for manual processing if needed.

Anonymisation behavior:

- Applies to eligible non-opt-in contacts after retention threshold.
- Preserves financial and race records.
- Records anonymisation in audit log.

## 6. Winner Recording

1. Open Duck Race > Winners.
2. Select race.
3. Configure winner positions and optional prize labels.
4. Assign duck numbers to winner positions.
5. Save winners.

Public display:

- Use [duck_race_winners] shortcode.
- Shows safe winner information only.

## 7. Reporting and Export

1. Open Duck Race > Reporting.
2. Select race.
3. Review sales, payment status and consent summary.
4. Export CSV for entries, purchases, contacts, winners, or accounting.

## 8. Uninstall Behavior

- Deactivation does not delete data.
- Uninstall deletes data only if explicit confirmation is enabled in settings.
- Without confirmation, uninstall leaves plugin data intact.
