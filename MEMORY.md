# MEMORY

MEMORY.md
Project Overview
Duck Race is a reusable WordPress plugin designed to support charity duck races run by Rotary clubs, charities and community organisations.
The plugin is intended to be organisation-neutral and configurable.
The initial use case is Rotary in the Vale's annual duck race, but all Rotary-specific branding must remain outside the core product.
The plugin should support multiple races per year and multiple years of historical data.

Design Philosophy
The project prioritises:
    1. Financial integrity.
    2. GDPR compliance.
    3. Historical accuracy.
    4. Auditability.
    5. Reusability.
    6. Administrator simplicity.
The plugin is intended to become a trusted operational system rather than a simple event website.

Key Architectural Decisions
No User Accounts
Decision Date:
Project inception.
Decision:
Participants do not create accounts.
There are:
    • no usernames
    • no passwords
    • no customer portals
    • no participant login area
Reason:
Duck race participants typically enter once or twice per year.
Accounts would introduce:
    • friction
    • support burden
    • password resets
    • GDPR complexity
The purchase journey should remain as simple as possible.
Future agents should not introduce login systems without explicit approval.

Email Address Is The Contact Key
Decision:
Email address is the unique identifier for contacts.
Implementation:
UNIQUE(email)
Reason:
Participants may:
    • move house
    • change surname
    • change phone number
Email addresses are the most stable identifier available.
When an existing email address is used:
    • update the existing contact
    • do not create a duplicate contact
    • preserve audit history
Example:
Mary Smith
mary@example.com
becomes
Mary Jones
mary@example.com
The existing contact record is updated.

Single Golden Contact Record
Decision:
The system maintains a single contact record per email address.
The canonical relationship shape is:
    • Contact
    • Purchases
    • Race
    • Duck Entries
Reason:
Avoids duplicate contacts.
Improves GDPR management.
Improves future race marketing.
Improves reporting.
A contact may have:
    • multiple races
    • multiple purchases
    • multiple duck entries
but only one contact record.
Example history:
Mary Smith / mary@example.com may have:
    • 2026 Evesham Race → Purchase #101 → Ducks 512, 513, 514
    • 2027 Evesham Race → Purchase #422 → Ducks 587, 612

Marketing Consent Is Separate From Participation
Decision:
Participants can purchase ducks without consenting to future communications.
Reason:
GDPR compliance.
Participation and marketing are separate purposes.
Required operational communications may still be sent regarding:
    • purchases
    • race administration
    • prize fulfilment
    • race reminders for the race already entered
Marketing communications require consent.

Contact Consent Collection
Decision:
Consent options appear only during duck purchase.
There is no separate public opt-in page.
Reason:
Keeps the user journey simple.
Avoids unnecessary screens.
Consent is collected when a participant is already providing their details.
Current consent options:
    1. Future duck race communications.
    2. Wider organisation communications.

Online Payments Use Stripe Only
Decision:
Online payments are handled exclusively through Stripe.
Reason:
Reduces complexity.
Reduces support burden.
Provides a mature payment platform.
Alternative online payment providers are out of scope.
Manual sales remain supported.

Stripe Webhook Is Truth
Decision:
Stripe webhooks are the authoritative source of payment confirmation.
Never trust:
    • success pages
    • browser redirects
    • client-side callbacks
Reason:
Payments can fail after redirects.
Webhook confirmation is the only reliable source.
Duck ownership becomes permanent only after webhook confirmation.

Manual Sales Must Be Recorded
Decision:
All manual sales must be entered into the system.
Reason:
The system should become the golden operational record.
Avoid parallel spreadsheets.
Avoid reconciliation problems.
Avoid duplicated administration.
Manual sales may originate from:
    • cafés
    • Rotary members
    • race-day sales
    • local businesses
but must ultimately be recorded in Duck Race.

Automatic Duck Allocation
Decision:
Online purchases receive the next available online-range duck number by default.
Reason:
Simple user experience.
Fast checkout.
Reduced administration.
Participants may optionally select a specific duck number.

Chosen Duck Numbers Are Premium
Decision:
Participants may pay an additional fee to choose a specific duck number.
Reason:
Some participants enjoy selecting favourite numbers.
The feature creates additional fundraising opportunities.
Only available online-range numbers may be selected.

Separate Manual And Online Ranges
Decision:
Each race contains:
    • manual range
    • online range
Example:
Manual:
1-499

Online:
500-1000
Reason:
Manual sales may be recorded later.
Online sales require immediate availability checking.
Separate ranges reduce collision risk.

Race History Must Be Preserved
Decision:
Completed races are historical records.
Reason:
Future reporting.
Fundraising transparency.
Winner history.
Future marketing.
Completed race data should not be deleted except where legally required.

Winners Belong To Buyers
Decision:
Prizes belong to the purchaser/contact record.
Not necessarily the duck name.
Example:
Grandmother purchases:
Duck 501 — Sophie
Duck 502 — Thomas
Duck 503 — Emily
The grandmother remains the owner for prize purposes.
Reason:
The purchaser entered the contract and provided contact details.

Public Winners Display
Decision:
Public winners pages display only:
    • winner name
    • organisation name
    • position
    • optional duck number
Never display:
    • email
    • phone
    • address
    • notes
Reason:
Privacy and GDPR compliance.

UI Decisions
Visual Duck Grid
Status:
Post-MVP feature.
Decision:
Administrators should eventually see a visual duck grid.
Colours:
Available:
Pale yellow
Sold:
Bright yellow
Lost:
Black with white number
Reserved:
Muted/grey-blue
Winner:
Gold
Reason:
Fast operational visibility.
Easy race-day administration.

Buyer Recognition
Status:
Planned feature.
Decision:
The purchase form should recognise existing email addresses.
Implementation shape:
Public no-login endpoint such as `check-email` returns only minimal non-sensitive fields needed to pre-fill the form.
Example:
Welcome back Mary.

We found your details from a previous duck race entry.

Please review and update them if necessary.
Reason:
Improves user experience.
Keeps contact records current.
Reduces data entry.
No login required.

MVP Boundary
A race can be run successfully when the following are available:
    • race creation
    • manual sales
    • online sales
    • Stripe integration
    • contact management
    • consent capture
    • confirmation emails
    • winner recording
    • public winner display
    • GDPR retention
Everything else is enhancement work.

Explicitly Deferred
The following ideas have been discussed but deliberately deferred:
Printable Ticket Generation
Status:
Deferred.
Reason:
Current organisers already use physical raffle tickets.
May become useful for other organisations later.
Potential future feature.

User Accounts
Status:
Rejected.
Reason:
Adds complexity with little benefit.
Future agents should not revisit without explicit approval.

Alternative Payment Providers
Status:
Rejected.
Reason:
Stripe is sufficient for current requirements.
Additional gateways increase maintenance burden.

Public Duck Ownership Lookup
Status:
Rejected.
Reason:
Privacy concerns.
Not required operationally.

Rotary In The Vale Context
The project originated from Rotary in the Vale's duck race process.
Current operational observations:
    • Manual ticket sales occur through cafés and Rotary members.
    • Organisers currently maintain separate records.
    • Goal is to make Duck Race the authoritative record.
    • Future race marketing is important.
    • Previous participant contact lists exist and may be imported.
    • Winners are normally 1st, 2nd and 3rd place but must remain configurable.
The plugin should remain useful to organisations that have never heard of Rotary.

Future Considerations
Potential future enhancements:
    • ticket PDF generation
    • richer email editor
    • abandoned checkout recovery
    • advanced reporting
    • advanced campaign management
    • multi-organisation deployments
    • additional branding options
These are not required for MVP.
Future agents should not prioritise them over core race functionality.

Guiding Principle
When uncertain:
Ask:
"Does this improve trust, auditability, GDPR compliance or fundraising operations?"
If not, it is probably not a priority.
