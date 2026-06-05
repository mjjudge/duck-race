# CLAUDE

Assistant working notes for this repository.

This file is intentionally practical and implementation-focused. It complements, but does not override, `AGENTS.md`, `TECHNICAL_SPEC.md`, `BACKLOG.md` and `MEMORY.md`.

## 1. Reading Order

Before making changes:

1. `AGENTS.md`
2. `README.md`
3. `TECHNICAL_SPEC.md`
4. `BACKLOG.md`
5. `MEMORY.md`
6. `WORDPRESS_SETUP.md`

## 2. Project State

- Current state is bootstrap/scaffold plus specification.
- Default approach is documentation-first and scaffold-first.
- Do not skip backlog phase order unless explicitly approved.

## 3. Core Non-Negotiables

- Financial integrity over convenience.
- GDPR compliance over growth tactics.
- Historical race and payment accuracy must be preserved.
- Auditability for sensitive state changes.
- Security checks on all admin/state-changing flows.

If there is tension between UX and safety, choose safety.

## 4. Data and Domain Rules

- Contact is the golden record.
- Contact identity key is email (`UNIQUE(email)`).
- Relationship shape is contact -> purchases -> race -> duck entries.
- Existing email updates existing contact (with audit), never duplicate.
- Stripe webhook confirmation is the payment source of truth.
- Success page/redirect is not proof of payment.
- Duck ownership authority is paid duck entry records.

## 5. WordPress Architecture Direction

Use the scaffold under `plugin/` as the default structure:

- `plugin/duck-race.php` (entry point when created)
- `plugin/src/Core/` for bootstrap, installer, lifecycle
- `plugin/src/Database/` and `plugin/src/Database/Migrations/`
- `plugin/src/Admin/` for admin page/controllers
- `plugin/src/Public/` for public shortcodes/controllers
- `plugin/src/Services/` for business services
- `plugin/src/Rest/` for endpoints/AJAX-like public checks
- `plugin/src/Mail/`, `plugin/src/Audit/`, `plugin/src/Security/`
- `plugin/templates/` and `plugin/assets/`
- `tests/Unit` and `tests/Integration`

Design preference: thin controllers/pages, thick service layer.

## 6. Capability and Security Expectations

For every admin or mutating action:

- Capability check
- Nonce check
- Input sanitize/validate
- Escaped output
- Prepared SQL for database writes/queries

For public endpoints:

- Return minimal data only
- No contact/payment/audit leakage
- Avoid exposing internal identifiers without strong reason

## 7. Contact Recognition Feature Shape

Contact recognition should be convenience only.

- Triggered from purchase email entry
- No login required
- Returns minimal non-sensitive fields for prefill
- Example fields: `exists`, `first_name`, `last_name`
- Never treat it as identity proof

## 8. Migration and Release Discipline

- Migrations must be versioned and idempotent.
- Prefer additive schema changes.
- Preserve historical financial records.
- If migration is added, bump plugin version and document migration intent.
- Never rely on manual DB edits in production.

## 9. Testing Priorities

Prioritise tests for:

- Allocation safety and double-sell prevention
- Stripe webhook transitions and retries
- Contact deduplication/update behavior
- Consent handling and retention/anonymisation logic
- Winner assignment integrity

## 10. Naming and Reuse

- Keep plugin organisation-neutral (no Rotary-specific hard-coding).
- Keep branding configurable.
- Avoid campaign-specific constants in domain logic.

## 11. Documentation Maintenance Rules

When architecture changes:

- Update `TECHNICAL_SPEC.md`
- Update `WORDPRESS_SETUP.md` if setup flow changes

When delivery scope/order changes:

- Update `BACKLOG.md`

When durable decisions are made:

- Update `MEMORY.md`

## 12. Practical Workflow for Agents

When implementing backlog work:

1. Reference the specific backlog item ID(s).
2. Confirm dependencies are already complete.
3. Implement smallest safe change.
4. Add/adjust tests where practical.
5. Validate lint/errors.
6. Update docs/memory if decision-level changes occurred.

## 13. Out-of-Scope Reminder

Do not introduce without explicit approval:

- user accounts/logins
- non-Stripe online payments
- gambling/random winner generation
- CRM/social integrations
- AI-generated marketing automations

## 14. Build Sequence Reminder

When implementation starts, follow the sequence in `TECHNICAL_SPEC.md` and the phase ordering in `BACKLOG.md`.
