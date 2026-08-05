# Duck Race WordPress Plugin — Technical Specification

## 1. Purpose

Create a reusable WordPress plugin that enables clubs, charities and community organisations to run one or more duck race fundraising events per year.

The plugin must support:

- Online duck sales via Stripe
- Manual and in-person sales recorded by administrators
- Per-race duck number allocation
- Configurable manual and online number ranges
- Optional duck names
- GDPR-aware contact handling
- Race-specific email journeys
- Winner recording
- Admin dashboards and exports
- Reusable branding and email configuration

The plugin should not be Rotary-branded in code. Branding should be configurable through settings and page content.

## 2. Core Concepts

### 2.1 Organisation

The WordPress site owner represents the organisation running the duck race.

Organisation-level settings include:

- Organisation name
- Contact email
- Duck race email/from name
- Logo
- Brand colours
- Footer text
- Privacy policy URL
- Stripe credentials
- Default email templates
- Default duck price
- Default chosen-number uplift
- Default retention period after race closure

### 2.2 Race

A Race is a campaign or event instance.

Each race has:

- Title
- Slug
- Race date
- Race time
- Location
- Public description
- Sales opening date/time
- Sales closing date/time
- Status: Draft, Open, Closed, Completed, Archived
- Manual duck range
- Online duck range
- Total duck range
- Price per duck
- Optional extra donation enabled/disabled
- Chosen-number uplift price
- Maximum ducks per transaction
- Winner/prize configuration
- Race-specific email templates
- Race-specific shortcode

Example:

- Manual range: 1–499
- Online range: 500–1000
- Online buyers can only choose numbers from the online range
- Manual and in-person sales are recorded by admins

### 2.3 Duck Number

A duck number exists per race. The same physical duck number may be reused in future races, but each race has its own allocation state.

Duck statuses:

- Available
- Reserved
- Sold online
- Sold manually
- Lost/unavailable
- Winner
- Voided/refunded

Rules:

- A duck marked lost must not be sold
- A lost duck can be restored to available
- A sold duck cannot be sold again for the same race
- A buyer may pay an uplift to choose a specific available online-range number
- If no number is chosen, the system allocates the next available online duck automatically
- Manual sales must be entered by administrators into the system to maintain a golden record

### 2.4 Purchase

A Purchase represents a transaction or manual sale.

Fields:

- Purchase ID
- Race ID
- Buyer/contact ID
- Purchase source: Online Stripe, Manual/in-person, Admin-entered
- Payment status: Pending, Paid, Failed, Abandoned, Refunded, Cancelled
- Stripe checkout/session/payment intent IDs
- Total duck amount
- Chosen-number uplift total
- Optional donation amount
- Grand total
- Currency
- Created timestamp
- Paid timestamp
- Admin notes
- Email confirmation sent timestamp

A purchase can contain one or more duck entries.

### 2.5 Duck Entry

A Duck Entry links a buyer, race and duck number.

Fields:

- Entry ID
- Race ID
- Purchase ID
- Duck number
- Optional duck name
- Optional dedication/message
- Entry status
- Winner position, if applicable
- Prize label, if applicable

The buyer owns the entry for prize purposes, even if the duck is named for someone else.

### 2.6 Contact

A Contact is the buyer or supporter.

The contact record is the golden record for a person across all races and years.
The primary relationship shape is:

- Contact -> Purchases -> Race -> Duck Entries

Rather than creating race-scoped duplicate contacts.

Fields:

- Contact ID
- First name
- Last name
- Organisation/business name, optional
- Email
- Phone, optional
- Address, optional
- Consent for future duck race communications
- Consent for wider organisation communications
- Consent timestamp
- Consent source
- Last purchase date
- Notes
- Anonymised flag

Rules:

- Email address is the unique contact key
- The contacts table must enforce `UNIQUE(email)`
- A new email address creates a new contact record
- An existing email address updates the existing contact record
- **Manual-sale-only exception**: if a manual (in-person/cash) sale is recorded and the seller could not obtain the buyer's email, `email` may be left NULL. No fake email is ever generated. The contact is identified instead by a reference derived from its ID (`MAN-YYYYMMDD-{id}`). Every such contact is created new — there is no matching/deduplication without an email. This does not apply to online purchases, which still require an email. If an email is added later and it belongs to an existing contact, the two are merged (purchases and duck entries move to the existing contact) rather than silently overwritten or blocked.
- Contact changes such as surname, phone or address must preserve the audit trail rather than creating a duplicate contact

Example:

- Mary Smith <mary@example.com>
- 2026 Evesham Race -> Purchase #101 -> Ducks 512, 513, 514
- 2027 Evesham Race -> Purchase #422 -> Ducks 587, 612

If Mary Smith later becomes Mary Jones with the same email address, the existing contact record is updated and the audit log records the change.

Consent model:

- People can buy ducks without opting into marketing
- Contact details are retained for race administration and prize fulfilment
- If they do not opt in, their personal details should be anonymised after the configured post-race retention period
- If they opt into duck race communications, they can be contacted about future duck races
- If they opt into wider organisation communications, they can be contacted about other club or charity activity

## 3. User Journeys

### 3.1 Online Buyer Journey

1. Visitor lands on the race page via website navigation or QR code.
2. Visitor sees race details, date, location, cause, price and call to action.
3. Visitor chooses number of ducks.
4. For each duck, visitor may optionally add a duck name or choose a specific duck number from the online range, if available.
5. If they choose a specific number, uplift fee is added.
6. Basket total updates dynamically.
7. Visitor enters an email address.
8. The public form may call a lightweight contact-recognition endpoint to check whether that email address already exists.
9. If a contact already exists, the form can display a message such as "Welcome back Mary. We found a previous duck race participant. Your details have been pre-filled. Please review and update if necessary."
10. The form pre-fills non-sensitive buyer details for review and amendment.
11. Visitor completes or updates buyer details.
12. Visitor chooses consent options:
   - Contact me about future duck races
   - Contact me about other organisation activities
13. Visitor proceeds to Stripe Checkout.
14. On successful payment:
    - Duck numbers are confirmed
    - Purchase is marked paid
    - Confirmation email is sent
    - Buyer sees success page
15. If payment is abandoned or failed:
    - Purchase remains pending or abandoned
    - Reminder email may be sent if an email address was captured and consent or legal basis allows operational follow-up

### 3.2 Manual and In-Person Sale Journey

1. Admin opens race admin screen.
2. Admin chooses “Add manual sale”.
3. Admin selects or enters buyer details.
4. Admin records consent choices.
5. Admin enters number of ducks.
6. Admin either selects specific duck numbers from the manual range or allows the system to allocate next available manual numbers.
7. Admin optionally enters duck names.
8. Admin records amount paid and payment method.
9. System marks ducks as sold manually.
10. Optional confirmation email is sent.

Manual sale payment methods may include cash, card machine, bank transfer or other. No non-Stripe online payment flow is required.

### 3.3 Admin Duck Management Journey

Admins can manage ducks in a visual grid, filter by state, inspect details, mark ducks lost or restored, create manual sales and record winners.

### 3.4 Previous Supporter Journey

Admins may import or maintain previous supporter contacts, send race invitations and allow recipients to buy, opt in or unsubscribe.

### 3.5 Winner Management Journey

Admins complete the race, configure winner positions, assign winning duck numbers and optionally publish safe winner information by name or business only.

## 4. Public Shortcodes

### 4.1 Buy Form

`[duck_race_buy race="current"]`

or

`[duck_race_buy race="race-slug"]`

Displays race information, duck purchase form, optional duck naming, optional chosen-number selector, buyer details, consent checkboxes and a Stripe checkout button.

### 4.2 Race Summary

`[duck_race_summary race="current"]`

Displays race title, date, time, location, description and call to action.

### 4.3 Winners

`[duck_race_winners race="race-slug"]`

Displays winner position, optional duck number, winner name or business name, and optional prize label. It must not display email, phone, address or internal notes.

### 4.4 Opt-In and Contact Matching

Contacts are uniquely identified by email address. A purchase submission must create or update a single contact record rather than duplicating contacts.

The public buy form may perform contact recognition before checkout by calling a no-login endpoint such as `check-email`.

The endpoint should:

- Accept an email address
- Return whether a matching contact exists
- Return only the minimum non-sensitive fields needed to improve the form experience, such as first name and last name
- Never expose address, phone, notes, consent history, purchase history or internal identifiers to the public form

Example response:

```json
{
  "exists": true,
  "first_name": "Mary",
  "last_name": "Jones"
}
```

This endpoint is a convenience feature only. It does not create an account, authenticate the user or prove identity, and final contact updates must still be validated and recorded through the normal purchase flow.

There are no public user accounts and no customer login area.

## 5. Admin Screens

The plugin should provide admin screens for:

- Dashboard
- Races
- Duck Grid
- Purchases
- Contacts
- Emails
- Settings

These screens should cover race management, duck allocation visibility, purchase operations, contact administration, email configuration and organisation settings.

## 6. Roles and Capabilities

### 6.1 Duck Race Manager

Can manage races, manual sales, entries, contacts, purchases, operational emails, winners and exports.

Cannot change Stripe settings, global plugin settings or roles/capabilities.

### 6.2 Duck Race Settings Admin

Can do everything a Duck Race Manager can do, plus global settings, Stripe configuration, email defaults, branding, retention settings and role/capability management.

## 7. Data Model

Suggested custom tables:

- `wp_duck_race_races`
- `wp_duck_race_contacts`
- `wp_duck_race_purchases`
- `wp_duck_race_entries`
- `wp_duck_race_duck_status`
- `wp_duck_race_email_log`
- `wp_duck_race_audit_log`

The specification defines race, contact, purchase, entry, duck status, email log and audit log fields in detail. The entries table must enforce duck uniqueness per race to prevent double allocation.

Relationship summary:

- One Contact can have many Purchases
- One Purchase belongs to one Contact and one Race
- One Purchase can have many Duck Entries
- One Race can have many Purchases and many Duck Entries

This preserves a full longitudinal history for each participant while avoiding duplicate contact records across years.

## 8. Stripe Integration

Online payments use Stripe Checkout.

Key rules:

- The local form creates a pending purchase
- Duck numbers are reserved before checkout
- Stripe webhook confirmation is the source of truth for successful payment
- The success page alone must not mark payment as complete
- Reservations should expire if checkout is not completed
- Chosen numbers must be rechecked immediately before checkout creation
- Secret keys must be masked in admin settings

## 9. Reservation and Allocation Logic

Automatic online allocation should allocate the lowest available online-range numbers after excluding sold, reserved, lost and otherwise blocked ducks.

Chosen number allocation must confirm range and availability, apply uplift, reserve the number and confirm the sale only after webhook-backed payment success.

Manual allocation should use the manual range by default and warn about online-range usage unless an authorised settings admin overrides it.

## 10. GDPR and Privacy

Principles:

- Separate race participation from marketing consent
- Allow purchase without marketing opt-in
- Retain non-opt-in buyer data only for legitimate operational purposes
- Anonymise non-opt-in contact details after the configured retention period
- Preserve historical reporting data while minimising personal information
- Provide unsubscribe and consent update links in marketing emails

Recommended default retention: anonymise non-opt-in contacts 60 days after race completion.

Anonymisation should blank or remove personal contact fields while preserving race, duck, payment and winner reporting data where necessary.

## 11. Email Requirements

Use WordPress mail for operational and marketing email types. Marketing emails must only be sent where the relevant consent exists.

Suggested merge tags include:

- `{first_name}`
- `{last_name}`
- `{organisation_name}`
- `{race_title}`
- `{race_date}`
- `{race_time}`
- `{race_location}`
- `{duck_numbers}`
- `{duck_names}`
- `{purchase_total}`
- `{winner_position}`
- `{prize_label}`
- `{buy_link}`
- `{optin_link}`
- `{unsubscribe_link}`
- `{privacy_policy_url}`

## 12. Imports and Exports

Support CSV import of previous contacts and CSV export of race entries, sales, winners, contacts, opt-ins and accounting summaries.

Imported contacts should not be treated as opted in unless explicit consent is recorded.

## 13. Public Winner Display

Public winner display must show only safe winner information such as winner position, optional duck number, name or business name, and optional prize.

It must not expose contact details.

## 14. Security

Requirements include:

- WordPress nonces for admin actions
- Capability checks for admin pages and AJAX endpoints
- Sanitised input
- Escaped output
- Prepared SQL statements
- Secure option storage for Stripe secrets
- Secret masking in UI
- Stripe webhook signature validation
- Idempotent webhook handling for duplicate deliveries
- Database-level protection against double allocation
- Logging of important admin actions
- No personal data leakage through public shortcodes or REST endpoints
- Explicit uninstall behavior where data deletion is opt-in only via admin confirmation

## 15. Accessibility Baseline

Public participant journeys must satisfy a practical accessibility baseline:

- Keyboard-usable purchase form controls
- Correct label association for all required form fields
- Readable, text-based status and validation messaging
- Status not conveyed by colour alone in operational UIs

## 16. Technical Architecture

Suggested structure:

```text
duck-race/
  duck-race.php
  includes/
    Plugin.php
    Activator.php
    Deactivator.php
    Database/
      Schema.php
      Migrations.php
    Domain/
      RaceService.php
      DuckAllocator.php
      PurchaseService.php
      ContactService.php
      EmailService.php
      StripeService.php
      RetentionService.php
    Admin/
      AdminMenu.php
      RaceAdminPage.php
      DuckGridPage.php
      PurchaseAdminPage.php
      ContactAdminPage.php
      EmailTemplatePage.php
      SettingsPage.php
    Public/
      Shortcodes.php
      Assets.php
      Controllers.php
    Rest/
      RaceRoutes.php
      DuckRoutes.php
      CheckoutRoutes.php
    Emails/
      TemplateRenderer.php
      Mailer.php
    Security/
      Capabilities.php
      Sanitizer.php
  assets/
    admin/
    public/
  templates/
    admin/
    public/
    emails/
  tests/
  README.md
  BACKLOG.md
  CLAUDE.md
  AGENTS.md
  MEMORY.md
```

## 17. Front-End Behaviour

The purchase form should support adding and removing ducks, optional naming, optional chosen-number search, price breakdown, uplift display, grand total, buyer validation, clear GDPR consent text and Stripe Checkout redirection.

Use progressive enhancement wherever practical.

## 18. Admin Duck Grid Behaviour

The duck grid is a v1.1-quality feature that should support:

- Pagination or virtual scrolling for 1,000+ ducks
- Search by duck number
- Filter by status
- Click-to-view details
- Bulk lost/restored actions
- Manual sale creation

Recommended tile states:

- Available: pale yellow
- Sold: bright yellow
- Lost: black with white number
- Reserved/pending: muted blue or grey
- Winner: gold

## 19. MVP Boundary

MVP includes:

- Plugin activation and database tables
- Race creation
- Manual and online ranges
- Stripe online checkout
- Automatic online duck allocation
- Manual sale entry
- Contact capture and consent
- Purchase confirmation email
- Basic race reminder email
- Basic admin dashboards
- Basic CSV exports
- Winner recording
- Public winners shortcode
- Basic GDPR anonymisation

Post-MVP or v1.1 can extend this with the full duck grid, rich email editing, campaign flows, imports, abandoned checkout automation, enhanced audit logging and better reporting.

## 20. Acceptance Criteria

Acceptance criteria cover:

- Race setup
- Online purchase flow
- Manual sale flow
- Contact consent capture and updates
- Winner configuration and safe public display
- Operational and marketing email rules
- Security requirements, including webhook validation and prevention of double-selling

## 21. Open Decisions

Open decisions include defaults for price, uplift, max ducks per transaction, retention period, required address or phone fields, manual confirmation email behaviour, public duck number visibility, imported-contact opt-in policy and whether styling is theme-inherited or bundled.

## 21. Recommended Build Sequence

1. Plugin skeleton
2. Database schema and migrations
3. Roles and capabilities
4. Race admin
5. Duck allocation service
6. Manual sale admin
7. Public buy form
8. Stripe Checkout and webhook
9. Confirmation email
10. Contact and consent model
11. Exports
12. Winner recording
13. Public winners shortcode
14. Retention and anonymisation job
15. Dashboard and reporting
16. Previous contact import
17. Race reminder email
18. Abandoned checkout handling
19. Visual duck grid
20. Rich email/template editor
21. Full campaign email tools
22. Audit log and admin polish

MVP is reached around steps 14–15.
