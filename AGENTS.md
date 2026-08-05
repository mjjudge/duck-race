# AGENTS

Duck Race
AI Agent Instructions
This document defines mandatory rules for all AI coding agents working on the Duck Race repository.
These instructions take precedence over agent preferences, coding habits and default behaviours.

Project Purpose
Duck Race is a reusable WordPress fundraising platform that enables charities, Rotary clubs and community organisations to run one or more duck races per year.
The platform manages:
    • race creation
    • duck allocation
    • manual sales
    • online sales
    • Stripe payments
    • contact management
    • GDPR consent
    • winner recording
    • participant communications
The system must be:
    • auditable
    • secure
    • GDPR compliant
    • financially accurate
    • reusable
    • organisation-neutral
The primary objective is to provide a trustworthy fundraising platform.
Convenience must never compromise financial integrity or participant data.

Core Principles
Financial Integrity Is Mandatory
Money collected for charity must be traceable and auditable.
Never:
    • silently modify payment totals
    • silently alter purchase records
    • silently alter winner information
    • silently reassign paid duck numbers
    • delete completed purchases
Financial history must be preserved.
If a correction is required:
    • record the correction
    • preserve the original audit trail
When in doubt:
preserve history rather than overwrite it.

Auditability First
Every important action should be traceable.
Including:
    • race creation
    • race modification
    • duck allocation
    • manual sales
    • payment updates
    • consent changes
    • winner assignment
    • contact updates
The system should favour transparency over convenience.

GDPR Compliance Is Mandatory
Marketing consent and race participation are separate concepts.
A participant must be able to:
    • buy ducks
    • enter races
    • receive operational communications
without consenting to future marketing.
Never:
    • require marketing consent to purchase ducks
    • send marketing emails without consent
    • expose personal data publicly
Public pages must never display:
    • email addresses
    • phone numbers
    • postal addresses
    • internal notes

Historical Accuracy Must Be Preserved
Completed races form part of the organisation's historical record.
Never:
    • delete race results
    • renumber historical ducks
    • alter winner records without audit logging
    • modify historical financial records without preserving history
Historical reporting must remain trustworthy.

Organisation Neutrality
This plugin is intended for reuse by multiple organisations.
Never hard-code:
    • Rotary branding
    • club names
    • charity names
    • logos
    • colours
    • email content
All branding must be configurable.

Authoritative Systems
Payment Authority
Stripe is the authoritative source of payment confirmation.
Never treat:
    • browser redirects
    • success pages
    • client-side callbacks
as proof of payment.
A duck allocation becomes permanent only after successful Stripe webhook confirmation.

Contact Authority
Contacts are uniquely identified by email address.
The contact database represents the golden record.
Never create duplicate contacts where an email already exists.
When an existing contact submits updated information:
    • update the existing contact
    • preserve audit history
    • do not create a new record
One email address equals one contact record.
Exception, manual sales only: if a seller could not obtain the buyer's email, the
contact may be created with no email and no fake email is generated. It is
identified by an internal reference instead. No deduplication is possible without
an email, so every no-email manual sale creates a new contact record. This does
not apply to online purchases. If an email is added later and collides with an
existing contact, the two contacts are merged rather than silently overwritten.

Race Authority
The race database record is the authoritative source for:
    • race status
    • race dates
    • duck ranges
    • pricing
    • winner configuration
Do not infer race state from other data.

Duck Ownership Authority
The paid Duck Entry record is the authoritative source of duck ownership.
Never determine ownership from:
    • emails
    • exports
    • cached data
    • reports
Ownership must always be derived from Duck Entry records.

Data Handling Rules
Data Minimisation
Only store information required to:
    • administer races
    • fulfil prizes
    • maintain financial records
    • honour participant consent choices
Avoid collecting unnecessary personal data.

Retention Rules
Contacts who have not opted into future communications must be anonymised after the configured retention period.
Race history must remain intact.
Financial reporting must remain intact.
Anonymisation must minimise personal data while preserving reporting accuracy.

Contact Updates
When a participant purchases ducks using an email address that already exists:
    1. Locate existing contact.
    2. Update details with latest information.
    3. Record changes in audit log.
    4. Preserve purchase history.
    5. Do not create duplicates.
The latest participant-submitted details are considered authoritative.

Security Rules
Capability Checks
All administrative functionality must validate:
    • WordPress capability
    • nonce
    • permissions
before modifying data.

Input Validation
All user input must be:
    • sanitised
    • validated
    • escaped
before storage or display.
Never trust user input.

Secret Management
Never expose:
    • Stripe secret keys
    • webhook secrets
    • API credentials
Secrets must be masked after saving.
Secrets must never appear in logs.

Public Access
Public-facing endpoints must expose only information required for participation.
Never expose:
    • contact records
    • payment details
    • audit logs
    • internal identifiers
without explicit authorisation.

Development Workflow
Before implementation:
    1. Read AGENTS.md.
    2. Read TECHNICAL_SPEC.md.
    3. Read BACKLOG.md.
    4. Read MEMORY.md.
Before coding:
    • understand the affected business process
    • identify financial implications
    • identify GDPR implications
    • identify reporting implications

Backlog Execution Rules
The backlog is authoritative.
Work must follow:
    1. Build Phase order.
    2. Dependency order.
    3. Priority order.
Do not begin a later phase while unresolved P0 work remains in an earlier phase.
If a task appears underspecified:
    • update documentation first
    • then implement
Never invent requirements that conflict with the technical specification.

Documentation Requirements
Documentation is mandatory.
Any architectural change must update:
    • TECHNICAL_SPEC.md
    • ARCHITECTURE.md
Any backlog change must update:
    • BACKLOG.md
Any significant project knowledge must update:
    • MEMORY.md
Documentation drift is considered a defect.

Testing Requirements
New functionality should include tests whenever practical.
At minimum validate:
    • duck allocation
    • duplicate prevention
    • payment processing
    • consent handling
    • anonymisation
    • winner assignment
Financial and allocation logic should be tested before UI enhancements.

Commit Requirements
Use Conventional Commits.
Examples:
    • feat: add race creation workflow
    • feat: implement stripe webhook processing
    • fix: prevent duplicate duck allocation
    • fix: preserve audit trail on contact updates
    • docs: update race lifecycle specification
    • test: add allocation service coverage

Out Of Scope
Do not implement without explicit approval:
    • user login systems
    • participant accounts
    • social media integrations
    • cryptocurrency payments
    • alternative payment gateways
    • gambling features
    • random winner generation
    • AI marketing content generation
    • cloud CRM integrations
The project goal is a reliable fundraising platform.

Decision Hierarchy
When conflicts arise:
    1. Preserve financial integrity.
    2. Preserve GDPR compliance.
    3. Preserve historical accuracy.
    4. Preserve auditability.
    5. Preserve security.
    6. Preserve data consistency.
    7. Improve administrator usability.
    8. Improve participant experience.
    9. Improve performance.
Always favour correctness over convenience.
When uncertain:
choose the option that is safest for participant data, charity funds and historical records.

