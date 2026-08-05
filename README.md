# Duck Race — WordPress Fundraising Plugin

Run a duck race fundraiser on your WordPress site without writing a line of code. Duck Race handles online ticket sales, payment processing, the race-day duck grid, winner recording, and post-race email — all from a clean admin interface any volunteer can use.

---

## What is a duck race?

A duck race is a classic community fundraiser. Numbered plastic ducks are "sold" to supporters, then released on a river, stream, or course together. The owner of the duck that crosses the finish line first wins a prize. It's family-friendly, scalable from 50 to 5,000 ducks, and a proven crowd-pleaser for sports clubs, Rotary clubs, school PTAs, hospices, and any community group.

---

## Who is this plugin for?

Duck Race is built to be adopted by **any club or organisation**, not just the one that originally commissioned it. Every fundraising-specific detail is configurable:

- Your organisation name and branding
- Your currency (GBP, USD, EUR, or any ISO 4217 code)
- Whether to show Gift Aid (UK-specific, can be hidden)
- Donation quick-pick amounts on the checkout form
- Consent checkbox wording and your privacy policy link
- Stripe redirect page URLs
- All email templates — subject lines and bodies — via a built-in visual editor

---

## Feature overview

| Area | What you get |
| --- | --- |
| **Races** | Create and manage multiple races per year, each with its own duck range, price, sales window, and status |
| **Online sales** | Stripe Checkout integration — buyers choose a duck number, pay, and receive a confirmation email automatically |
| **Manual sales** | Admin form for in-person or phone sales; same allocation rules as online. Buyer's email can be marked as unavailable (e.g. cash sale at an event) — the sale is still recorded and traceable, just without email confirmation |
| **Duck grid** | Visual grid showing every duck tile — available, sold, reserved, winner. Click a tile to manage its status or reassign it to a different number |
| **Contacts** | GDPR-aware contact database with separate duck-race consent and general marketing consent |
| **Email** | Visual email editor (TinyMCE) with a placeholder picker and WordPress media library for all email templates |
| **Winners** | Record winners by position; public-facing display shows results without leaking personal data |
| **Refunds** | Process full refunds through Stripe from the admin; entries are voided and a refund email is sent automatically |
| **Campaigns** | Send targeted emails to previous supporters for re-engagement or race reminders |
| **Reporting** | Per-race sales summary and CSV export |
| **Audit log** | Every sensitive state change (payment, refund, winner, reassignment) is recorded |
| **Retention** | Scheduled anonymisation of non-opted-in contacts after a configurable period |

---

## Requirements

- WordPress 6.0 or higher
- PHP 8.2 or higher
- A [Stripe](https://stripe.com) account (free to create; fees apply per transaction)

---

## Getting started

### 1. Install the plugin

Upload the `duck-race` folder to `wp-content/plugins/` and activate it from the WordPress Plugins screen.

On activation the plugin creates three WordPress pages automatically:

| Page | Slug | Purpose |
| --- | --- | --- |
| Buy Ducks | `duck-race-buy` | The public purchase form |
| Payment Success | `duck-race-success` | Shown after successful payment |
| Payment Failure | `duck-race-failure` | Shown after cancelled or failed payment |

### 2. Connect Stripe

1. Log in to your [Stripe Dashboard](https://dashboard.stripe.com).
2. Go to **Developers → API Keys** and copy your **Publishable key** and **Secret key**.
3. In WordPress, open **Duck Race → Settings** and paste both keys.
4. Still in Stripe, go to **Developers → Webhooks → Add endpoint**.
   - Copy the **Webhook endpoint URL** shown in Duck Race Settings and paste it into Stripe.
   - Listen for the `checkout.session.completed` event.
5. After creating the endpoint, copy the **Signing secret** from Stripe and paste it into Duck Race Settings.
6. Save settings.

### 3. Create your first race

Go to **Duck Race → Races → New Race** and fill in:

- **Title** — shown to buyers on the purchase form
- **Duck range** — e.g. 1–500 for online, 501–600 for manual sales
- **Price per duck**
- **Race date, time, and location** — shown on the buy form and in emails
- **Max ducks per transaction** — prevents one person buying everything
- **Status** — set to *Open* when you are ready to accept sales

### 4. Point buyers to the buy page

The purchase form lives at `yoursite.com/duck-race-buy/?race=your-race-slug`. Share this link in emails, social media, or embed it on any page. Buyers choose a duck number, optionally name their duck, add a voluntary donation, and pay via Stripe Checkout.

---

## Settings reference

All settings are in **Duck Race → Settings**.

### General

| Setting | Default | Notes |
| --- | --- | --- |
| Organisation name | — | Used in email templates and on the buy form |
| Contact email | — | Reply-to address for outgoing emails |
| Default duck price | 2.50 | Pre-filled when creating a new race; each race can override this |
| Currency code | GBP | ISO 4217 code sent to Stripe (e.g. USD, EUR) |
| Currency symbol | £ | Displayed on the buy form and in emails (e.g. $, €) |
| Gift Aid | On | Untick for non-UK organisations |
| Donation quick-pick amounts | 5, 10, 15 | Three preset donation buttons; set to 0 to hide a button |
| Consent label — duck races | default text | Wording of the first consent checkbox |
| Consent label — organisation | default text | Wording of the second consent checkbox |
| Privacy policy URL | — | When set, a link appears under the consent checkboxes |

### Stripe

| Setting | Notes |
| --- | --- |
| Publishable key | `pk_live_…` or `pk_test_…` — safe to show publicly |
| Secret key | `sk_live_…` or `sk_test_…` — never share this |
| Webhook endpoint URL | Register this exact URL in Stripe Webhooks |
| Webhook secret | `whsec_…` — from the Stripe Webhook page after creating the endpoint |
| Payment success page slug | WordPress page slug for post-payment redirect (default: `duck-race-success`) |
| Payment failure/cancel page slug | WordPress page slug when payment fails (default: `duck-race-failure`) |
| Buy page slug | WordPress page slug for the purchase form (default: `duck-race-buy`) |

### Email templates

Duck Race ships with default email templates for:

- **Purchase confirmation** — sent to the buyer after successful payment
- **Race reminder** — sent manually via Duck Race → Race Reminders
- **Refund confirmation** — sent when a refund is processed

Each template has a subject line and a full HTML body. The body field uses the WordPress visual editor (TinyMCE) with:

- **Visual / Text tabs** — write in WYSIWYG mode or raw HTML
- **Insert Image** — attach images from your WordPress Media Library
- **Placeholder picker** — insert dynamic tags from the dropdown (e.g. `{first_name}`, `{duck_numbers}`)

#### Available placeholders

| Tag | Output |
| --- | --- |
| `{first_name}` | Buyer's first name |
| `{last_name}` | Buyer's last name |
| `{organisation_name}` | Your organisation name |
| `{race_title}` | The race name |
| `{race_date}` | Race date |
| `{race_time}` | Race time |
| `{race_location}` | Race venue |
| `{duck_numbers}` | Comma-separated list of the buyer's duck numbers |
| `{duck_names}` | Comma-separated list of named ducks |
| `{purchase_total}` | Amount paid |
| `{buy_link}` | Link to the buy page for this race |
| `{refund_amount}` | Amount refunded (refund confirmation only) |
| `{previous_race_result}` | Summary of a previous race (campaigns) |
| `{winner_position}` | Finish position (winner campaigns) |

---

## Admin pages

| Page | Capability | Purpose |
| --- | --- | --- |
| Races | Manager | Create and edit races |
| Contacts | Manager | View and edit buyer contact records |
| Manual Sales | Manager | Record in-person or phone sales |
| Duck Grid | Manager | Visual duck status board with per-tile management |
| Winners | Manager | Record race results |
| Reporting | Manager | Sales summary and CSV export |
| Campaigns | Manager | Send targeted emails to past supporters |
| Race Reminders | Manager | Send operational reminders to duck owners |
| Refunds | Settings Admin | Process full refunds via Stripe |
| Settings | Settings Admin | All configuration (see above) |
| Help | All | Quick-reference guide |

---

## Access control

The plugin adds two roles:

- **Duck Race Manager** (`duck_race_manager`) — full access to race operations, sales, contacts, winners, campaigns, and reporting, but not financial settings or refunds.
- **Duck Race Settings Admin** (`duck_race_settings_admin`) — all of the above plus settings and refund processing.

WordPress users with the **Editor** role are automatically granted Duck Race Manager access without any permanent database change.

---

## Running a race — day-of guide

1. Open **Duck Race → Duck Grid** and select your race.
2. The grid shows every duck tile in colour:
   - **Yellow** — available
   - **Gold** — sold
   - **Grey** — reserved (checkout in progress)
   - **Dark** — lost (removed from allocation)
   - **Orange** — winner
3. Click any tile to open the management panel:
   - Change status (mark as lost, release a reservation, etc.)
   - Reassign a duck to a different number (useful if a number was pre-sold manually and also sold online)
4. After the race, go to **Duck Race → Winners**, select the race, and record the finishing positions.

---

## Refunds

To issue a refund:

1. Go to **Duck Race → Refunds**.
2. Select the race and find the purchase.
3. Enter a reason and click **Refund**.

For online purchases the plugin calls the Stripe API to issue the refund. For manual/cash purchases the admin records the refund without a Stripe call. In both cases:

- The purchase is marked as refunded
- All duck entries are voided
- A refund confirmation email is sent to the buyer

---

## GDPR and data retention

- Buyers are always asked for operational consent (duck race) and optional marketing consent (wider organisation).
- Contacts who have not opted in to marketing are anonymised after a configurable retention period (default 365 days from their last race activity).
- Financial records (amounts, dates, race IDs) are preserved after anonymisation; only personal details (name, email, address) are removed.
- The plugin does not use cookies or tracking beyond what WordPress itself sets.
- Deactivating the plugin never deletes data. Uninstall only deletes data if you tick **Destructive uninstall** in Settings before uninstalling.

---

## Developer notes

- **Namespace:** `DuckRace\`
- **Entry point:** `plugin/duck-race.php`
- **PHP autoloader:** maps `DuckRace\Foo\Bar` to `plugin/src/Foo/Bar.php`
- **Database:** custom tables (contacts, races, entries, purchases, audit_log) created on activation via `dbDelta()`
- **Migrations:** versioned, idempotent; see `plugin/src/Database/Migrations/`
- **Tests:** PHPUnit unit tests in `tests/Unit/`; integration tests in `tests/Integration/`
- **Settings:** stored as a single serialised WordPress option `duck_race_settings`
- **Stripe:** uses the Stripe Checkout hosted page (no PCI scope on your server); webhook signature verified on every event

See [TECHNICAL_SPEC.md](TECHNICAL_SPEC.md) for the full architecture reference and [AGENTS.md](AGENTS.md) for AI-assistant working notes.

---

## Support the project

Duck Race is free and open source. If it has helped your club raise money and enjoy a great event, please consider:

- [☕ Buy the Developer a Coffee](https://buymeacoffee.com/marcusjudge) — a small token of appreciation for the hours spent building and maintaining this plugin
- [❤️ Donate to Rotary in the Vale](https://rotaryinthevale.org/) — support the Rotary club that originally commissioned this software and continues to use it to raise funds for the local community

---

## Contributing and issues

- Issues: [GitHub Issues](https://github.com/mjjudge/duck-race/issues)
- Contributions welcome via pull request — please read [AGENTS.md](AGENTS.md) first.

---

*Duck Race is free software released under the GPL-2.0-or-later licence.*
