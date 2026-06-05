# Duck Race

Reusable WordPress plugin for running charity duck races.

Duck Race supports:

- Multiple races per year
- Manual and online duck sales
- Stripe online payments
- Per-race duck allocation
- Contact and consent management
- Winner recording
- Operational emails
- GDPR-aware retention
- Auditability

## Current Status

Planning/bootstrap stage.

Read these first:

1. `AGENTS.md`
2. `TECHNICAL_SPEC.md`
3. `BACKLOG.md`
4. `MEMORY.md`

## Development Principle

This project handles charity funds and personal data.

Correctness, auditability and GDPR compliance take priority over convenience.

## Product Summary

Duck Race is intended to be a reusable WordPress plugin for clubs, charities and community organisations that want to run one or more duck race fundraising events each year without hard-coding a specific brand into the plugin itself.

The plugin is expected to support:

- Organisation-level branding and email configuration
- Race-specific setup, sales windows, ranges and pricing
- Online sales through Stripe Checkout
- Manual and in-person sales entered by administrators
- Per-race duck allocation with separate manual and online ranges
- Optional duck names and optional chosen-number uplift
- Contact capture with separate operational retention and marketing consent
- Winner recording and public winner display without leaking personal data
- Admin dashboards, CSV exports and audit logging

## MVP Scope

The initial MVP is planned to include:

- Plugin activation and custom database tables
- Race creation and editing
- Manual and online duck ranges
- Automatic online duck allocation
- Stripe checkout and webhook confirmation
- Manual sale entry
- Contact capture and consent recording
- Purchase confirmation email
- Basic race reminder email
- Basic admin dashboards and CSV exports
- Winner recording and a public winners shortcode
- Basic GDPR-aware anonymisation for non-opt-in contacts

## Planned Repository Documents

- `README.md` — project overview and onboarding entry point
- `TECHNICAL_SPEC.md` — full product and architecture specification
- `BACKLOG.md` — phased delivery plan and open work
- `AGENTS.md` — agent/operator guidance for working in this repository
- `CLAUDE.md` — coding-assistant specific working notes
- `MEMORY.md` — durable project context and decisions

## Planned Repository Shape

The technical specification proposes a structure similar to:

```text
duck-race/
  duck-race.php
  includes/
  assets/
  templates/
  tests/
  README.md
  BACKLOG.md
  CLAUDE.md
  AGENTS.md
  MEMORY.md
```

See `TECHNICAL_SPEC.md` for the detailed architecture, data model, journeys, security requirements and acceptance criteria.
