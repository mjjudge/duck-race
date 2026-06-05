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
