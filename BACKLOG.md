# BACKLOG

Backlog for the Duck Race WordPress plugin.

## Priority Levels

- P0: Required for MVP
- P1: Important post-MVP features
- P2: Nice-to-have enhancements

## Build Status

MVP is considered complete after Build Phase 11.

## Phase Summary

### Phase 1 - Foundation

Goal: Plugin installs and activates successfully.

| ID | Item | Priority |
| --- | --- | --- |
| DR-001 | Plugin skeleton | P0 |
| DR-002 | Folder structure | P0 |
| DR-003 | Activation/deactivation | P0 |

### Phase 2 - Database Layer

Goal: Core data model complete.

| ID | Item | Priority |
| --- | --- | --- |
| DR-010 | Schema manager | P0 |
| DR-011 | Races table | P0 |
| DR-012 | Contacts table | P0 |
| DR-013 | Purchases table | P0 |
| DR-014 | Duck entries table | P0 |
| DR-015 | Duck status table | P0 |
| DR-016 | Email log table | P1 |
| DR-017 | Audit log table | P1 |

### Phase 3 - Security and Roles

Goal: Secure administration.

| ID | Item | Priority |
| --- | --- | --- |
| DR-020 | Capabilities | P0 |
| DR-021 | Manager role | P0 |
| DR-022 | Settings admin role | P0 |
| DR-023 | Security checks | P0 |

### Phase 4 - Race Management

Goal: Create and manage races.

| ID | Item | Priority |
| --- | --- | --- |
| DR-030 | Race list | P0 |
| DR-031 | Race editor | P0 |
| DR-032 | Range validation | P0 |
| DR-033 | Race lifecycle | P0 |

### Phase 5 - Contact Engine

Goal: Golden-record contact model.

| ID | Item | Priority |
| --- | --- | --- |
| DR-040 | Contact service | P0 |
| DR-041 | Contact audit trail | P1 |
| DR-042 | Buyer recognition | P1 |
| DR-043 | Contact admin page | P0 |

### Phase 6 - Manual Sales

Goal: Administrators can record all physical sales.

| ID | Item | Priority |
| --- | --- | --- |
| DR-050 | Manual sale form | P0 |
| DR-051 | Manual allocation | P0 |
| DR-052 | Manual payment recording | P0 |

### Phase 7 - Online Purchase Flow

Goal: Public can buy ducks online.

| ID | Item | Priority |
| --- | --- | --- |
| DR-060 | Purchase shortcode | P0 |
| DR-061 | Allocation engine | P0 |
| DR-062 | Chosen number option | P0 |
| DR-063 | Buyer details and consent | P0 |

### Phase 8 - Stripe

Goal: Online payments work safely.

| ID | Item | Priority |
| --- | --- | --- |
| DR-070 | Stripe settings | P0 |
| DR-071 | Checkout creation | P0 |
| DR-072 | Webhook handler | P0 |
| DR-073 | Success/failure pages | P0 |

### Phase 9 - Email System

Goal: Operational email journey complete.

| ID | Item | Priority |
| --- | --- | --- |
| DR-080 | Template renderer | P0 |
| DR-081 | Purchase confirmation | P0 |
| DR-082 | Email settings | P0 |
| DR-083 | Race reminder | P0 |

### Phase 10 - Winners

Goal: Race completion workflow.

| ID | Item | Priority |
| --- | --- | --- |
| DR-090 | Winner positions | P0 |
| DR-091 | Record winners | P0 |
| DR-092 | Winners shortcode | P0 |

### Phase 11 - GDPR

Goal: Compliance and data lifecycle.

| ID | Item | Priority |
| --- | --- | --- |
| DR-100 | Retention settings | P0 |
| DR-101 | Anonymisation service | P0 |
| DR-102 | Retention scheduler | P0 |

## MVP Complete

At this point a Rotary club can:
    • Create races
    • Sell ducks online
    • Record manual sales
    • Collect consent
    • Take Stripe payments
    • Send confirmations
    • Record winners
    • Publish winners
    • Manage GDPR retention

### Phase 12 - Reporting

| ID | Item | Priority |
| --- | --- | --- |
| DR-110 | Dashboard | P1 |
| DR-111 | Exports | P1 |

### Phase 13 - Duck Grid

| ID | Item | Priority |
| --- | --- | --- |
| DR-120 | Duck grid | P1 |
| DR-121 | Filters | P1 |
| DR-122 | Duck modal | P1 |

### Phase 14 - Marketing

| ID | Item | Priority |
| --- | --- | --- |
| DR-130 | Import contacts | P1 |
| DR-131 | Invitation emails | P1 |
| DR-132 | Abandoned checkout | P1 |
| DR-133 | Future-race campaigns | P1 |

### Phase 15 - Enhancements

| ID | Item | Priority |
| --- | --- | --- |
| DR-140 | Ticket PDF generation | P2 |
| DR-141 | Rich email editor | P2 |
| DR-142 | Branding presets | P2 |

### Phase 16 - Hardening

| ID | Item | Priority |
| --- | --- | --- |
| DR-150 | Allocation tests | P1 |
| DR-151 | Stripe tests | P1 |
| DR-152 | Accessibility | P1 |
| DR-153 | Uninstall process | P1 |
| DR-154 | Documentation | P1 |

### Phase 17 - User Testing Fixes

Goal: Address bugs, UX issues, and usability gaps identified during live user testing.

| ID | Item | Priority |
| --- | --- | --- |
| DR-160 | Fix race list duplication bug in Races tab | P0 |
| DR-161 | Move Settings tab to last position in admin navigation | P1 |
| DR-162 | Replace "Slug" with user-friendly label in race editor | P1 |
| DR-163 | Force dd/mm/yyyy date format throughout plugin | P1 |
| DR-164 | Add race image upload to race editor | P2 |
| DR-165 | Add help tooltips to all settings fields | P1 |
| DR-166 | Make duck tile colours and text configurable in settings | P2 |
| DR-167 | Rename "Tiles/Page" to "Ducks/Page" and update pagination options | P1 |
| DR-168 | Rename duck grid buttons to "Lost Duck"/"Duck Found"; show only relevant button | P1 |
| DR-169 | Rename "Reason" to "Comments"; add note icon on tiles; make comments editable/deletable | P1 |
| DR-170 | Show all race winners on duck grid, not just 1st place | P1 |
| DR-171 | Clarify merge tag behaviour for multiple ducks; add HTML email examples | P1 |
| DR-172 | Add address fields and validation to contact edit form | P1 |
| DR-173 | Add postal address to manual sale form | P1 |
| DR-174 | Rename "Duck names" to "Names for ducks" throughout plugin | P1 |
| DR-175 | Add in-plugin help and instructions page | P1 |
| DR-176 | Improve winner positions table clarity in admin | P1 |

### Phase 18 - Refunds

| ID | Item | Priority |
| --- | --- | --- |
| DR-180 | Add duck_race_process_refunds capability | P1 |
| DR-181 | Add refund columns to purchases table (migration) | P1 |
| DR-182 | Build RefundService (Stripe + manual) | P1 |
| DR-183 | Add admin Refunds page and process refund action | P1 |
| DR-184 | Handle charge.refunded Stripe webhook event | P1 |
| DR-185 | Send refund confirmation email to buyer | P2 |

### Phase 20 - Universal Configuration

Goal: Any club anywhere can install and run the plugin without touching code.

| ID | Item | Priority |
| --- | --- | --- |
| DR-200 | Make Stripe response page slugs configurable in Settings | P0 |
| DR-201 | Currency code and symbol configurable in Settings | P1 |
| DR-202 | Gift Aid toggle in Settings (default on; off for non-UK clubs) | P1 |
| DR-203 | Configurable consent opt-in label text + privacy policy URL | P1 |
| DR-204 | Donation quick-select amounts configurable in Settings | P2 |
| DR-205 | Event/item terminology configurable (e.g. "Duck" → "Ticket") | P2 |
| DR-206 | Date format locale setting | P2 |

### Phase 21 - Rich Email Editor

| ID | Item | Priority |
| --- | --- | --- |
| DR-211 | Rich email editor with placeholder picker and WordPress image insertion | P1 |

### Phase 22 - Documentation

| ID | Item | Priority |
| --- | --- | --- |
| DR-220 | Rewrite README.md as an engaging, accessible document for any club | P1 |

### Phase 19 - Duck Reassignment

| ID | Item | Priority |
| --- | --- | --- |
| DR-190 | Reassign duck number from duck grid modal | P0 |

## Delivery Principle

Build the full v1.1 vision progressively. MVP is not a separate reduced product; it is the point in the backlog where the system is usable for a real duck race.

## Status Key

    • [ ] Not started
    • [~] In progress
    • [x] Complete
    • [!] Blocked

EPIC 1 — Plugin Foundation
DR-001 — Create WordPress plugin skeleton
Description
Create the initial plugin structure, bootstrap file and autoloading pattern.
Status
    • [x] Complete
Acceptance Criteria
    • Plugin can be installed and activated in WordPress.
    • Plugin has a clear namespace.
    • Main plugin file contains metadata.
    • No fatal errors on activation.
Dependencies
None.

DR-002 — Create folder structure
Description
Create standard folders for admin, public, domain services, database, assets, templates and tests.
Status
    • [x] Complete
Acceptance Criteria
    • Folder structure matches agreed technical spec.
    • Placeholder files prevent accidental empty directory loss.
    • Structure supports future agent development.
Dependencies
DR-001.

DR-003 — Add activation and deactivation hooks
Description
Add plugin lifecycle hooks for setup and cleanup.
Status
    • [x] Complete
Acceptance Criteria
    • Activation hook runs database migration setup.
    • Deactivation hook does not delete data.
    • Hooks are isolated in dedicated classes.
Dependencies
DR-001.

EPIC 2 — Data Layer
DR-010 — Create database schema manager
Description
Create migration logic for custom plugin tables.
Status
    • [x] Complete
Acceptance Criteria
    • Migrations run safely on activation.
    • Schema version is stored in WordPress options.
    • Re-running migrations is safe.
Dependencies
DR-003.

DR-011 — Create races table
Status
    • [x] Complete
Acceptance Criteria
    • Stores race title, slug, date, time, location, sales window, status, ranges and pricing.
    • Supports multiple races per year.
    • Slug is unique.
Dependencies
DR-010.

DR-012 — Create contacts table
Status
    • [x] Complete
Acceptance Criteria
    • Stores buyer/supporter details.
    • Email address is unique.
    • Enforces UNIQUE(email) at database level.
    • Supports one golden contact record across multiple purchases and races.
    • Stores duck-race consent and wider-organisation consent.
    • Supports anonymisation flag/timestamp.
Dependencies
DR-010.

DR-013 — Create purchases table
Status
    • [x] Complete
Acceptance Criteria
    • Stores race, contact, source, payment status, Stripe IDs, totals and notes.
    • Supports online and manual purchases.
    • Supports abandoned, failed, paid, cancelled and refunded states.
Dependencies
DR-010, DR-011, DR-012.

DR-014 — Create duck entries table
Status
    • [x] Complete
Acceptance Criteria
    • Stores race, purchase, contact, duck number, optional duck name and winner details.
    • Prevents double-selling the same duck number in the same race.
    • Supports sold, reserved, voided and winner states.
Dependencies
DR-013.

DR-015 — Create duck status table
Status
    • [x] Complete
Acceptance Criteria
    • Allows ducks to be marked lost/unavailable per race.
    • Allows lost ducks to be restored.
    • Does not affect other races.
Dependencies
DR-011.

DR-016 — Create email log table
Status
    • [x] Complete
Acceptance Criteria
    • Logs recipient, email type, race, purchase, status and errors.
    • Can be used for resend/debugging.
Dependencies
DR-011, DR-012, DR-013.

DR-017 — Create audit log table
Status
    • [x] Complete
Acceptance Criteria
    • Records important admin and contact update actions.
    • Stores before/after JSON where appropriate.
    • Records user ID and timestamp.
Dependencies
DR-010.

EPIC 3 — Roles and Security
DR-020 — Register plugin capabilities
Status
    • [x] Complete
Acceptance Criteria
    • Adds capabilities for duck race management and settings management.
    • Capabilities are removed only on uninstall, not deactivation.
Dependencies
DR-003.

DR-021 — Create Duck Race Manager role capability set
Status
    • [x] Complete
Acceptance Criteria
    • Can manage races, sales, entries, contacts and winners.
    • Cannot change Stripe or global settings.
Dependencies
DR-020.

DR-022 — Create Duck Race Settings Admin capability set
Status
    • [x] Complete
Acceptance Criteria
    • Can perform all manager actions.
    • Can change plugin settings, Stripe keys, email defaults and branding.
Dependencies
DR-020.

DR-023 — Add nonce and capability checks
Status
    • [x] Complete
Acceptance Criteria
    • All admin actions check capabilities.
    • All state-changing requests use nonces.
    • Public form submissions are protected against CSRF/spam as far as practical.
Dependencies
DR-020.

EPIC 4 — Race Management
DR-030 — Create race admin list page
Status
    • [x] Complete
Acceptance Criteria
    • Admin can view all races.
    • Shows status, date, sales window and ducks sold.
    • Provides create/edit/duplicate links.
Dependencies
DR-011, DR-020.

DR-031 — Create race editor
Status
    • [x] Complete
Acceptance Criteria
    • Admin can create/edit title, date, time, location and description.
    • Admin can configure manual and online ranges.
    • Admin can configure price per duck and chosen-number uplift.
    • Admin can configure sales opening and closing times.
Dependencies
DR-030.

DR-032 — Validate race ranges
Status
    • [x] Complete
Acceptance Criteria
    • Manual and online ranges cannot overlap.
    • Range start must be lower than range end.
    • Online range must contain at least one duck.
    • Clear validation messages are shown.
Dependencies
DR-031.

DR-033 — Add race status lifecycle
Status
    • [x] Complete
Acceptance Criteria
    • Race can move through Draft, Open, Closed, Completed and Archived.
    • Online sales only available when race is Open and within sales dates.
    • Completed races allow winner recording and retention workflows.
Dependencies
DR-031.

EPIC 5 — Contact Management
DR-040 — Create contact service with email as unique key
Status
    • [x] Complete
Acceptance Criteria
    • Contact lookup uses email address.
    • New email creates new contact.
    • Existing email updates existing contact.
    • No duplicate contact is created for the same email.
    • Contact is treated as the parent record for cross-race purchase history.
Dependencies
DR-012.

DR-041 — Add contact update audit trail
Status
    • [x] Complete
Acceptance Criteria
    • Name/address/phone changes are recorded in audit log.
    • Consent changes are recorded with timestamp and source.
    • Previous and new values are available to admin.
Dependencies
DR-017, DR-040.

DR-042 — Add buyer recognition endpoint
Status
    • [x] Complete
Acceptance Criteria
    • Public purchase form can check whether an email already exists.
    • Endpoint is available without login.
    • Existing contact can pre-fill known buyer details.
    • Endpoint returns only minimal non-sensitive fields such as first name and last name.
    • No sensitive data is exposed unnecessarily.
    • Buyer sees message that details can be reviewed and updated.
Dependencies
DR-040, DR-023.

DR-043 — Build admin contact list
Status
    • [x] Complete
Acceptance Criteria
    • Admin can search by name, email or organisation.
    • Admin can view consent status.
    • Admin can view purchase history.
    • Admin can edit contact details.
Dependencies
DR-040.

EPIC 6 — Manual Sales
DR-050 — Create manual sale form
Status
    • [x] Complete
Acceptance Criteria
    • Admin can select race.
    • Admin can enter buyer details.
    • Admin can record consent choices.
    • Admin can add one or more ducks.
    • Admin can optionally name each duck.
Dependencies
DR-031, DR-040.

DR-051 — Manual duck allocation
Status
    • [x] Complete
Acceptance Criteria
    • Admin can allocate next available manual-range ducks.
    • Admin can choose specific available manual-range duck numbers.
    • System prevents duplicate allocation.
    • System warns when trying to use online range.
Dependencies
DR-014, DR-015, DR-050.

DR-052 — Record manual payment details
Status
    • [x] Complete
Acceptance Criteria
    • Admin can record cash, card machine, bank transfer or other.
    • Admin can record amount paid.
    • Purchase status can be marked paid.
    • Manual payment appears in sales reports.
Dependencies
DR-013, DR-050.

EPIC 7 — Public Online Sales
DR-060 — Create [duck_race_buy] shortcode
Status
    • [x] Complete
Acceptance Criteria
    • Shortcode can show current race or specified race slug.
    • Form displays race details.
    • Form allows number of ducks to be selected.
    • Form supports optional duck names.
Dependencies
DR-031.

DR-061 — Add online allocation service
Status
    • [x] Complete
Acceptance Criteria
    • Automatically allocates next available online-range ducks.
    • Excludes sold, reserved and lost ducks.
    • Allocation is race-specific.
    • Allocation is safe against double booking.
Dependencies
DR-014, DR-015.

DR-062 — Add chosen-number option
Status
    • [x] Complete
Acceptance Criteria
    • Buyer can search/check online-range duck availability.
    • Buyer can choose available online duck number.
    • Uplift fee is added.
    • Buyer cannot choose manual-range or lost/sold duck.
Dependencies
DR-061.

DR-063 — Add buyer details and consent section
Status
    • [x] Complete
Acceptance Criteria
    • Buyer enters required contact details.
    • Email field can trigger buyer recognition before checkout.
    • Two opt-ins are shown during purchase only:
        ◦ future duck race communications;
        ◦ wider organisation communications.
    • Buyer can continue without marketing opt-in.
    • Existing contact details update by email address.
Dependencies
DR-040, DR-060.

EPIC 8 — Stripe Integration
DR-070 — Add Stripe settings
Status
    • [x] Complete
Acceptance Criteria
    • Settings Admin can enter publishable key, secret key and webhook secret.
    • Secret values are masked after saving.
    • Settings are capability-protected.
Dependencies
DR-022.

DR-071 — Create Stripe Checkout session
Status
    • [x] Complete
Acceptance Criteria
    • Online purchase creates pending purchase.
    • Duck numbers are reserved before checkout.
    • Stripe Checkout session includes correct total.
    • Buyer is redirected to Stripe.
Dependencies
DR-061, DR-063, DR-070.

DR-072 — Add Stripe webhook handler
Status
    • [x] Complete
Acceptance Criteria
    • Webhook validates Stripe signature.
    • Successful payment marks purchase paid.
    • Duck entries become sold online.
    • Failed or expired checkout releases reservations.
Dependencies
DR-071.

DR-073 — Add success and failure pages
Status
    • [x] Complete
Acceptance Criteria
    • Success page thanks buyer and explains email confirmation.
    • Failure/cancel page explains that ducks are not confirmed.
    • Payment success is not trusted until webhook confirmation.
Dependencies
DR-072.

EPIC 9 — Email Engine
DR-080 — Create email template renderer
Status
    • [x] Complete
Acceptance Criteria
    • Supports merge tags for race, buyer, purchase and ducks.
    • Escapes output safely.
    • Can render plain HTML email.
Dependencies
DR-016.

DR-081 — Create purchase confirmation email
Status
    • [x] Complete
Acceptance Criteria
    • Sent after successful Stripe webhook or confirmed manual sale.
    • Includes duck numbers, duck names, race details and total paid.
    • Uses WordPress mail.
    • Logs result.
Dependencies
DR-080, DR-072.

DR-082 — Add admin email settings
Status
    • [x] Complete
Acceptance Criteria
    • Settings Admin can edit default email text.
    • Organisation logo/branding can be included.
    • Test email can be sent.
Dependencies
DR-080.

DR-083 — Add race reminder email
Status
    • [x] Complete
Acceptance Criteria
    • Admin can send reminder to current race participants.
    • Reminder is operational and does not require marketing opt-in.
    • Email log records results.
Dependencies
DR-080, DR-081.

EPIC 10 — Winner Management
DR-090 — Configure winner positions
Status
    • [x] Complete
Acceptance Criteria
    • Admin can configure 1st, 2nd, 3rd and additional positions.
    • Each position can have optional prize label.
    • Configuration is race-specific.
Dependencies
DR-031.

DR-091 — Record winning ducks
Status
    • [x] Complete
Acceptance Criteria
    • Admin can assign duck numbers to winner positions.
    • System links winners to buyer/contact.
    • Winner status is stored against duck entry.
    • Admin can edit mistakes with audit log.
Dependencies
DR-014, DR-090.

DR-092 — Create [duck_race_winners] shortcode
Status
    • [x] Complete
Acceptance Criteria
    • Displays winner names/business names only.
    • Does not display contact details.
    • Can show position, prize and optional duck number.
    • Works by race slug or current completed race.
Dependencies
DR-091.

EPIC 11 — GDPR and Retention
DR-100 — Add retention settings
Status
    • [x] Complete
Acceptance Criteria
    • Settings Admin can configure non-opt-in retention period.
    • Default is configurable.
    • Setting explains purpose clearly.
Dependencies
DR-022.

DR-101 — Add anonymisation service
Status
    • [x] Complete
Acceptance Criteria
    • Non-opt-in contacts can be anonymised after race retention period.
    • Financial/race records are preserved with minimised personal data.
    • Audit log records anonymisation.
Dependencies
DR-040, DR-100.

DR-102 — Add scheduled retention job
Status
    • [x] Complete
Acceptance Criteria
    • WP-Cron checks for records eligible for anonymisation.
    • Job is safe to rerun.
    • Admin can manually trigger from settings.
Dependencies
DR-101.

MVP CUT LINE
At this point the plugin can run a real duck race:
    • Create race.
    • Sell online through Stripe.
    • Record manual sales.
    • Capture contacts and consent.
    • Send confirmations/reminders.
    • Record winners.
    • Display public winners.
    • Handle basic retention.

EPIC 12 — Reporting and Export
DR-110 — Add race sales dashboard
Status
    • [x] Complete
Acceptance Criteria
    • Shows online sales, manual sales, ducks sold, revenue and available ducks.
    • Shows pending/failed/abandoned payments.
    • Shows consent totals.
Dependencies
DR-013, DR-014.

DR-111 — Add CSV exports
Status
    • [x] Complete
Acceptance Criteria
    • Export race entries.
    • Export purchases.
    • Export contacts.
    • Export winners.
    • Export accounting summary.
Dependencies
DR-110.

EPIC 13 — Visual Duck Grid
DR-120 — Build admin duck grid
Status
    • [x] Complete
Acceptance Criteria
    • Shows numbered duck tiles.
    • Pale yellow = available.
    • Bright yellow = sold.
    • Black = lost.
    • Reserved/pending = muted state.
    • Winner = gold.
Dependencies
DR-014, DR-015.

DR-121 — Add duck grid filters
Status
    • [x] Complete
Acceptance Criteria
    • Filter by available, sold, manual, online, lost, reserved and winners.
    • Search by duck number.
    • Supports at least 1,000 ducks without becoming unusable.
Dependencies
DR-120.

DR-122 — Add duck detail modal
Status
    • [x] Complete
Acceptance Criteria
    • Clicking a duck shows buyer, duck name, purchase, payment and status.
    • Contact details are only shown to authorised admins.
    • Admin can mark lost/restore where permitted.
Dependencies
DR-121.

EPIC 14 — Campaign Marketing
DR-130 — Import previous supporter contacts
Status
    • [x] Complete
Acceptance Criteria
    • Admin can upload CSV.
    • Existing contacts are matched by email.
    • Duplicate contacts are not created.
    • Imported contacts are not automatically opted in unless consent is recorded.
Dependencies
DR-040.

DR-131 — Create previous supporter invitation email
Status
    • [x] Complete
Acceptance Criteria
    • Admin can send invitation to eligible contacts.
    • Email includes race-specific buy link.
    • Only contacts with appropriate consent are included unless there is another valid basis recorded by admin.
Dependencies
DR-080, DR-130.

DR-132 — Add abandoned checkout reminder
Status
    • [x] Complete
Acceptance Criteria
    • Detects pending/abandoned purchases.
    • Sends reminder where operationally appropriate.
    • Does not permanently reserve ducks indefinitely.
    • Logs reminder email.
Dependencies
DR-072, DR-080.

DR-133 — Add winner/future-race marketing emails
Status
    • [x] Complete
Acceptance Criteria
    • Admin can send future-race emails to previous participants with consent.
    • Templates can reference previous race result.
    • Winners can receive personalised copy.
Dependencies
DR-091, DR-080.

EPIC 15 — Optional Extensions
DR-140 — Add raffle-style ticket PDF generation
Description
Generate printable duck-number tickets for manual/in-person sales.
Acceptance Criteria
    • Admin can generate PDF tickets for selected race/range.
    • Tickets show race title, duck number and optional organisation branding.
    • This is not required for MVP.
Dependencies
DR-031.

DR-141 — Add richer email editor
Acceptance Criteria
    • Admin can edit templates with WordPress rich text editor.
    • Images can be inserted.
    • Merge tags can be selected from a helper list.
Dependencies
DR-080.

DR-142 — Add theme/branding presets
Acceptance Criteria
    • Admin can set brand colours and logo.
    • Public form and emails inherit branding.
    • Plugin remains organisation-neutral.
Dependencies
DR-082.

EPIC 16 — Production Hardening
DR-150 — Add automated tests for allocation
Status
    • [x] Complete
Acceptance Criteria
    • Tests cover automatic allocation.
    • Tests cover chosen number allocation.
    • Tests cover lost ducks.
    • Tests cover double-sale prevention.
Dependencies
DR-061, DR-062.

DR-151 — Add Stripe webhook tests
Status
    • [x] Complete
Acceptance Criteria
    • Tests cover successful payment.
    • Tests cover failed payment.
    • Tests cover expired checkout.
    • Tests cover duplicate webhook delivery.
Dependencies
DR-072.

DR-152 — Add accessibility pass
Status
    • [x] Complete
Acceptance Criteria
    • Public form is keyboard usable.
    • Labels are correctly associated.
    • Error messages are readable.
    • Colour is not the only status indicator.
Dependencies
DR-060, DR-120.

DR-153 — Add uninstall behaviour
Status
    • [x] Complete
Acceptance Criteria
    • Plugin does not delete data on deactivation.
    • Uninstall flow is explicit.
    • Admin must confirm destructive data removal.
    • Documentation explains behaviour.
Dependencies
DR-010.

DR-154 — Write admin documentation
Status
    • [x] Complete
Acceptance Criteria
    • Explains race setup.
    • Explains manual sales.
    • Explains online sales.
    • Explains Stripe setup.
    • Explains GDPR/retention.
    • Explains winner recording.
Dependencies
All MVP epics.

EPIC 17 — User Testing Fixes

DR-160 — Fix race list duplication in Races tab
Description
The Races tab renders the entire section twice, from the heading through to the last listed race.
Status
    • [x] Complete
Acceptance Criteria
    • Each race appears exactly once in the Races tab.
    • The section heading is not duplicated.
Dependencies
DR-030.

DR-161 — Move Settings tab to last position in admin navigation
Description
The Settings tab currently appears second in the plugin navigation. It should be the last item. All other tabs retain their current order.
Status
    • [x] Complete
Acceptance Criteria
    • Settings tab appears as the final item in the plugin admin navigation.
    • All other tabs remain in their existing order.
Dependencies
None.

DR-162 — Add explanatory help text to the Slug field in race editor
Description
The word "Slug" is technical jargon. Rather than replacing it, add help text that explains what it is and how it is used.
Status
    • [x] Complete
Acceptance Criteria
    • The "Slug" label is retained.
    • A tooltip or inline help text explains: this is the short identifier used in the race's web page address (e.g. evesham-duck-race-2026).
    • Help text notes the field must contain only lowercase letters, numbers and hyphens.
Dependencies
DR-031.

DR-163 — Force dd/mm/yyyy date format throughout plugin
Description
Dates currently display in mm/dd/yyyy format, following WordPress locale defaults. The plugin will only be used in the UK so all dates must display and be entered as dd/mm/yyyy regardless of locale.
Status
    • [x] Complete
Acceptance Criteria
    • All date display fields use dd/mm/yyyy format.
    • All date input/picker fields accept and display dd/mm/yyyy format.
    • Applies consistently across: race editor, race list, manual sale form, contact records, reports, and any other date-bearing views.
    • Underlying storage format (ISO/database) is unaffected.
Dependencies
DR-031, DR-043.

DR-164 — Add race image upload to race editor
Description
Admins want to attach a venue or event image to a race for use in public-facing pages and admin context.
Status
    • [x] Complete
Acceptance Criteria
    • Admin can upload or select a race image when creating or editing a race.
    • Image is stored against the race record.
    • Image is displayed in the race admin view.
    • Image is accessible via merge tag or shortcode attribute for use in public-facing content.
Dependencies
DR-031.

DR-165 — Add help tooltips to all settings fields
Description
Settings fields such as "Chosen number uplift" are not self-explanatory. Every field should have hover or inline help text explaining its purpose in plain language.
Status
    • [x] Complete
Acceptance Criteria
    • Every field in the Settings page has a tooltip or visible help text.
    • "Chosen number uplift" explains it is an additional charge added when a buyer selects a specific duck number.
    • Help text is accessible without requiring additional clicks.
Dependencies
DR-082.

DR-166 — Make duck tile colours and text configurable in settings
Description
Duck tile background and text colours are currently hardcoded. Admins should be able to adjust them to suit their branding, with the current colour scheme as the default.
Status
    • [x] Complete
Acceptance Criteria
    • Settings Admin can configure background colour for each duck state: available, sold, lost, reserved, winner.
    • Settings Admin can configure text colour (black or white) per duck state independently.
    • Current colour scheme (pale yellow / bright yellow / black / muted / gold) is preserved as the default on fresh install and upgrade.
    • Changes apply immediately to the duck grid without requiring a page reload.
    • No duck state colour is hardcoded in PHP or CSS after this item is complete.
Dependencies
DR-120, DR-082.

DR-167 — Rename "Tiles/Page" to "Ducks/Page" and update pagination options
Description
The duck grid pagination control uses the term "Tiles" which is less intuitive. Rename and offer a wider range of page-size options.
Status
    • [x] Complete
Acceptance Criteria
    • Pagination label reads "Ducks/Page".
    • Available page-size options are: 50, 100, 250, 400, 500.
Dependencies
DR-121.

DR-168 — Rename duck grid buttons and show only the relevant action
Description
"Mark Lost" and "Restore" are less intuitive than "Lost Duck" and "Duck Found". Also, both buttons should not appear at the same time: only the contextually relevant one should be shown.
Note: lost/found status is per physical duck and persists across races (see DR-169 for schema implications).
Status
    • [x] Complete
Acceptance Criteria
    • "Mark Lost" renamed to "Lost Duck".
    • "Restore" renamed to "Duck Found".
    • Only "Lost Duck" is shown when a duck is not currently in a lost state.
    • Only "Duck Found" is shown when a duck is currently in a lost state.
    • Lost/found state is tracked per physical duck number, not per race.
Dependencies
DR-122, DR-169.

DR-169 — Per-duck comments and lost/found status; note icon on tiles; comments editable/deletable
Description
"Reason (optional)" is too narrow. Admins need a general-purpose comments field to record the physical condition or history of a duck (e.g. "damaged, needs replacing"). Comments and lost/found status belong to the physical duck, not to any purchase or race, so they must persist across races. This likely requires a new duck-physical-state table separate from the existing race-scoped duck_status table.
Status
    • [x] Complete
Acceptance Criteria
    • "Reason (optional)" label changed to "Comments" throughout the duck grid.
    • Comments field is present for all duck actions: Lost Duck, Duck Found, and any general update.
    • Comments and lost/found status are stored per physical duck number and persist across all races — they are not race-scoped.
    • It is visually clear in the UI that comments describe the duck's condition, not any buyer.
    • A small note icon appears in the bottom-right corner of any duck tile that has a saved comment.
    • Admin can edit existing comments.
    • Admin can delete existing comments.
    • All action buttons save any content in the Comments field.
    • Schema change: a new table (or redesigned duck_status table) stores physical duck state independently of race. Migration must be versioned and idempotent.
Dependencies
DR-122, DR-015.

DR-170 — Show all race winners on duck grid
Description
Currently only 1st place winners are highlighted in the winner colour on the duck grid. All winner positions should be highlighted, with position detail available on click.
Status
    • [x] Complete
Acceptance Criteria
    • All winner duck tiles display in winner colour (not just 1st place).
    • Clicking a winner duck tile shows the winner position and prize label in the duck detail modal.
    • Existing duck modal is updated to include winner position and prize label where applicable.
Dependencies
DR-122, DR-091.

DR-171 — Clarify merge tag behaviour for multiple ducks; add HTML email examples
Description
It is not clear how {duck_numbers} and {duck_names} render when a buyer purchases multiple ducks. Email settings should explain this and provide example HTML to help admins get started.
Status
    • [x] Complete
Acceptance Criteria
    • Email settings page explains how {duck_numbers} and {duck_names} render for multiple ducks (e.g. comma-separated list).
    • At least one example HTML email template is provided in the email settings area.
    • Merge tag helper list includes example expected output for single and multiple duck purchases.
Dependencies
DR-082, DR-080.

DR-172 — Add address fields and validation to contact edit form
Description
The contact edit form lacks postal address fields. First name, last name and email should be clearly marked as mandatory, and fields such as phone should validate input format.
Status
    • [x] Complete
Acceptance Criteria
    • Contact edit form includes: Address Line 1, Address Line 2, Town/City, County, Postcode.
    • Address fields are optional.
    • First name, last name and email are marked as mandatory with an asterisk.
    • Phone field validates to accept only valid characters (digits, spaces, +, -, parentheses).
    • Client-side and server-side validation is applied.
    • Validation errors are shown inline next to the relevant field.
Dependencies
DR-043.

DR-173 — Add postal address to manual sale form
Description
The manual sale form does not collect a postal address. It should include the same address fields as the contact form so a complete record can be captured at point of sale.
Status
    • [x] Complete
Acceptance Criteria
    • Manual sale form includes the same address fields as DR-172.
    • Address fields are optional.
    • Address is saved against the contact record.
Dependencies
DR-050, DR-172.

DR-174 — Rename "Duck names" to "Names for ducks" throughout plugin
Description
"Duck names" implies the buyer is naming their duck. The intent is that the buyer is purchasing on behalf of others (family members, friends) and recording who each duck belongs to. "Names for ducks" reads correctly either way.
Status
    • [x] Complete
Acceptance Criteria
    • All labels, headings, placeholders and tooltips using "Duck names" are updated to "Names for ducks".
    • Applies across: race editor, manual sale form, purchase shortcode, confirmation emails, and duck detail modal.
Dependencies
DR-060, DR-050.

DR-175 — Add in-plugin help and instructions page
Description
The admin guide is currently only available in docs/ADMIN_GUIDE.md in the repository. Admins setting up the plugin will not necessarily have repository access. A dedicated Help tab within the plugin admin is required.
Status
    • [x] Complete
Acceptance Criteria
    • A dedicated "Help" tab appears in the plugin admin navigation (before Settings).
    • Content covers: race setup, manual sales, online sales, Stripe setup, GDPR/retention, and winner recording.
    • Content is maintained in sync with docs/ADMIN_GUIDE.md.
    • No external repository or GitHub access is required to read the documentation.
Dependencies
DR-154.

DR-176 — Improve winner positions table clarity in admin
Description
The winner positions table in the admin is not easy to read. It should clearly communicate position, duck number, buyer name and prize label.
Status
    • [x] Complete
Acceptance Criteria
    • Winner positions table has clear, descriptive column headers.
    • Each row clearly shows: position, prize label, duck number, and buyer name.
    • Table layout is readable without horizontal scrolling on standard admin screens.
    • Applies to both the winner configuration view and the winner recording view.
Dependencies
DR-090, DR-091.

EPIC 18 — Refunds

DR-180 — Add duck_race_process_refunds capability
Description
Add a dedicated capability for processing refunds. This is a financial action so it is restricted to the Settings Admin role and WP administrators. Duck Race Managers and auto-granted Editors do not receive this capability.
Status
    • [x] Complete
Acceptance Criteria
    • duck_race_process_refunds is added to ALL_CAPS.
    • duck_race_settings_admin role receives the capability (true).
    • duck_race_manager role does not receive it (false).
    • WP administrator role receives it via register().
    • The boot() filter for editors does not grant it.
Dependencies
DR-020.

DR-181 — Add refund columns to purchases table
Description
The purchases table needs four new columns to record refund details: stripe_refund_id, refunded_at, refunded_amount, and refund_reason. A versioned migration adds them using dbDelta.
Status
    • [x] Complete
Acceptance Criteria
    • Migration class AddRefundColumnsToPurchases uses dbDelta on the full CREATE TABLE definition.
    • Adds: stripe_refund_id VARCHAR(191) NULL, refunded_at DATETIME NULL, refunded_amount DECIMAL(10,2) NULL, refund_reason VARCHAR(255) NULL.
    • Migration is registered in Migrator at version 1.4.0.
    • Plugin version is bumped to 0.18.0.
    • Running the migration twice is safe.
Dependencies
DR-013, DR-181 depends on DR-180 being merged first.

DR-182 — Build RefundService
Description
A dedicated service that handles the refund logic for both online (Stripe) and manual purchases.
Status
    • [x] Complete
Acceptance Criteria
    • process(int $purchase_id, string $reason): array{ok:bool, error?:string} method.
    • Validates purchase exists and has payment_status = 'paid'.
    • For online purchases: calls Stripe Refunds API (POST /v1/refunds) using stripe_charge_id or stripe_payment_intent_id.
    • For manual purchases: skips Stripe API and proceeds to mark as refunded.
    • On success: calls PurchaseService::mark_refunded().
    • Returns error string on Stripe API failure without changing DB state.
Dependencies
DR-181, DR-072.

DR-183 — Add admin Refunds page and process refund action
Description
A dedicated Refunds submenu page gated by duck_race_process_refunds. Admin can select a race, see paid purchases, and issue refunds with a reason. Also shows already-refunded purchases for the race.
Status
    • [x] Complete
Acceptance Criteria
    • Page is registered as a hidden submenu (no nav entry unless user has capability).
    • Shows race selector at the top.
    • Lists paid purchases: buyer name, duck numbers, amount, source (online/manual), paid date.
    • Each online or manual paid purchase has a Refund button.
    • Refund form includes optional reason field and JavaScript confirmation.
    • On success: redirects with success notice and purchase is shown as refunded.
    • On failure: redirects with error notice and purchase remains unchanged.
    • Already-refunded purchases are shown in a separate section with refund amount and date.
    • PurchaseService::mark_refunded() voids all duck entries and records audit log entry.
Dependencies
DR-182.

DR-184 — Handle charge.refunded Stripe webhook event
Description
When a refund is issued from the Stripe dashboard rather than via the plugin, Stripe sends a charge.refunded event. The webhook processor must handle this idempotently.
Status
    • [x] Complete
Acceptance Criteria
    • StripeWebhookProcessor handles charge.refunded event type.
    • Finds purchase by stripe_charge_id stored on the purchase record.
    • If purchase is already refunded, returns 200 without changing state.
    • If purchase is paid, calls PurchaseService::mark_refunded() with the Stripe refund ID and amount.
    • Returns 200 for unknown purchases (Stripe may send events for non-plugin charges).
Dependencies
DR-182, DR-072.

EPIC 20 — Universal Configuration

DR-200 — Make Stripe response page slugs configurable in Settings
Description
The success, failure, and buy page slugs are hardcoded as duck-race-success, duck-race-failure, and duck-race-buy throughout StripeService, CheckoutController, and CampaignService. Any site with conflicting slugs, or any admin who wants custom URLs, is currently blocked. Settings Admin must be able to set these without touching code.
Status
    • [x] Complete
Acceptance Criteria
    • Settings page adds three URL/slug fields: Payment Success Page, Payment Failure Page, Buy Page.
    • Fields show the current page URL (derived from slug) as a helper, with a link to edit the page.
    • StripeService reads success_url and cancel_url from settings, falling back to the defaults.
    • CheckoutController and CampaignService read the buy page slug from settings.
    • Existing installs that already have the default pages are unaffected.
    • Installer still creates default pages on fresh install but records their IDs/slugs in settings.
Dependencies
DR-070, DR-073.

DR-201 — Currency code and symbol configurable in Settings
Description
GBP and £ are hardcoded throughout the plugin — in the Stripe API call, the purchases table default, price display, donation buttons, and email formatting. Non-UK clubs cannot use the plugin without code changes.
Status
    • [x] Complete
Acceptance Criteria
    • Settings Admin can set currency code (e.g. GBP, USD, EUR) and currency symbol (e.g. £, $, €).
    • Default: GBP / £ (preserves existing behaviour).
    • Stripe checkout uses the configured currency code.
    • All price display throughout admin and public uses the configured symbol.
    • Currency symbol is available as a {currency_symbol} merge tag in email templates.
    • The database currency column default does not need to change (existing records are valid).
Dependencies
DR-070, DR-082.

DR-202 — Gift Aid toggle in Settings
Description
The Gift Aid checkbox and HMRC declaration text on the buy form are UK-specific and hardcoded. Non-UK clubs see irrelevant and potentially misleading legal text. Gift Aid should be a toggle in Settings.
Status
    • [x] Complete
Acceptance Criteria
    • Settings Admin can enable or disable Gift Aid collection (default: enabled).
    • When disabled: Gift Aid fieldset is completely hidden from the public buy form.
    • When disabled: gift_aid_declared is never set to 1 on new purchases.
    • Existing records with gift_aid_declared = 1 are unaffected — GDPR retention logic continues to apply to them.
    • Anonymisation logic still checks for historical Gift Aid declarations regardless of the toggle.
Dependencies
DR-063, DR-101.

DR-203 — Configurable consent opt-in label text and privacy policy URL
Description
The two marketing consent opt-in labels are hardcoded in BuyFormHandler: "Contact me about future duck races" and "Contact me about other [org] activities". Each club needs its own wording based on their legal advice. A privacy policy URL is also missing from the buy form, which is a GDPR best practice gap.
Status
    • [x] Complete
Acceptance Criteria
    • Settings Admin can set label text for consent opt-in 1 (duck-race communications).
    • Settings Admin can set label text for consent opt-in 2 (wider organisation communications).
    • Settings Admin can set a privacy policy URL.
    • Privacy policy URL, if set, appears as a link near the consent checkboxes on the buy form.
    • Defaults preserve current wording so existing installs are unaffected.
Dependencies
DR-063, DR-082.

DR-204 — Donation quick-select amounts configurable in Settings
Description
The donation quick-select buttons on the buy form are hardcoded at £5, £10, and £15. Clubs with different expectations (higher-value events, different currencies) need to set their own amounts.
Status
    • [x] Complete
Acceptance Criteria
    • Settings Admin can configure up to three quick-donate amounts.
    • Defaults: 5, 10, 15 (in whatever the configured currency is).
    • If all three are left blank/zero, no quick-select buttons are shown.
    • Currency symbol is shown alongside each amount field in settings.
Dependencies
DR-201, DR-063.

DR-205 — Event and item terminology configurable in Settings
Description
The words "Duck" and "Duck Race" are used in all public-facing text. A football club running a "penalty shootout raffle", a school running a "tombola", or a golf club running a "ball drop" cannot brand the plugin appropriately without code changes.
Status
    • [ ] Not started
Acceptance Criteria
    • Settings Admin can set a singular item name (default: "Duck") and an event name (default: "Duck Race").
    • Public buy form, confirmation emails, and winners shortcode output use the configured terms.
    • Admin navigation and internal labels are not changed (they always say "Duck Race").
    • Merge tags {item_name} and {event_name} are available in email templates.
    • Defaults preserve current wording.
Dependencies
DR-060, DR-082.

DR-206 — Date format locale setting
Description
Dates are displayed in dd/mm/yyyy format throughout the plugin, hardcoded for UK use. Clubs in other countries expect their own locale format (mm/dd/yyyy, yyyy-mm-dd, etc.).
Status
    • [ ] Not started
Acceptance Criteria
    • Settings Admin can select from a list of common date format patterns.
    • Default: dd/mm/yyyy.
    • Format applied consistently across admin and public-facing date display.
    • Underlying database storage (ISO format) is unaffected.
Dependencies
DR-163.

EPIC 21 — Rich Email Editor

DR-211 — Rich email editor with placeholder picker and WordPress image insertion
Description
The current email template editing experience is a plain textarea requiring hand-written HTML. Admins need a proper visual editor: a modal that opens from each template's Edit button, containing a TinyMCE rich-text editor (matching the WordPress post editor experience), a placeholder/merge-tag insert dropdown, and WordPress media library integration for inserting images.
Status
    • [x] Complete
Acceptance Criteria
    • Each email template on the Settings page has an "Edit email" button that opens a modal lightbox.
    • The modal contains: a subject line plain-text field, and a body editor using WordPress's wp_editor() (TinyMCE).
    • TinyMCE toolbar includes standard formatting (bold, italic, headings, bullet lists, links) plus a custom "Insert placeholder" dropdown button.
    • "Insert placeholder" lists all available merge tags (e.g. {first_name}, {race_title}, {duck_numbers}, {purchase_total}) and inserts the selected tag at the cursor position in the editor.
    • The TinyMCE "Insert/edit image" button opens the WordPress media library so admins can pick or upload an image.
    • The modal also offers a "Switch to HTML" toggle that shows a raw text area with the current HTML source, for admins who prefer to write HTML directly.
    • Saving the modal writes back to the same settings fields as the current textarea approach — no schema change required.
    • The modal is accessible: focus is trapped within it, Escape closes it, and ARIA roles are applied.
    • Each template (purchase confirmation, race reminder, refund confirmation, supporter invitation, abandoned checkout, winner marketing) gets its own Edit button.
    • Subject line editing remains a plain-text input (no rich text needed for subjects).
Dependencies
DR-082, DR-205.

EPIC 22 — Documentation

DR-220 — Rewrite README.md as an engaging, accessible document
Description
The current README.md is a technical scaffold document aimed at developers. It does not explain what the plugin does, who it is for, or how to get started. Any club wanting to evaluate or adopt the plugin needs an accessible, well-structured front page.
Status
    • [x] Complete
Acceptance Criteria
    • Opens with a clear, plain-language description of what the plugin does and who it is for.
    • Includes a "Key features" section covering: race management, online sales, manual sales, Stripe integration, winner recording, GDPR/retention, reporting.
    • Includes a "Who can use this?" section making clear the plugin is organisation-neutral.
    • Includes a "Quick start" section: install → activate → configure settings → create race → add shortcode.
    • Includes a "Screenshots" placeholder section.
    • Technical contribution notes (architecture, development setup) are retained but moved to a collapsible or clearly marked section.
    • Removes all out-of-date scaffold/planning content.
    • Tone is welcoming to non-technical club treasurers and administrators.
Dependencies
DR-154, DR-175.

EPIC 19 — Duck Reassignment

DR-190 — Reassign duck number from duck grid modal
Description
When a duck number has been pre-sold manually and then inadvertently sold again online (or vice versa), an admin needs to move the online buyer's duck to a different available number to free up the original for its correct owner. The reassignment must be auditable and must not break financial records.
Status
    • [x] Complete
Acceptance Criteria
    • Duck detail modal shows a "Reassign to duck #" input and button for any duck in sold_online, sold_manual or reserved state.
    • Admin enters a target duck number and submits.
    • System validates: target number is within the race range, target number is not already sold/reserved/lost, source duck is not a winner.
    • The duck_number on the entry row is updated atomically.
    • The original duck number is freed (visible as available in the grid).
    • Audit log records: event duck.reassigned, before {duck_number: old}, after {duck_number: new}, context {race_id, purchase_id, contact_id}.
    • Winner ducks cannot be reassigned.
    • Physical duck state (lost/found/comments) stays with the physical duck number, not the entry.
    • After reassignment admin can use Manual Sale to assign the freed number to its correct owner.
Dependencies
DR-122, DR-014.

DR-185 — Send refund confirmation email to buyer
Description
When a refund is processed, send the buyer a confirmation email listing the refunded amount and the duck numbers that have been released.
Status
    • [x] Complete
Acceptance Criteria
    • Email is sent after a successful refund (both admin-initiated and webhook-initiated).
    • Includes: buyer name, refund amount, duck numbers, race name.
    • Uses existing email template renderer and mail system.
    • Email send is logged in the email log.
    • Failure to send email does not roll back the refund.
Dependencies
DR-182, DR-080.
