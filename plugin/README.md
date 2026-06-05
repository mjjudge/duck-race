# Duck Race Plugin Scaffold

This directory is the implementation scaffold for the Duck Race WordPress plugin.

It is intentionally code-light at this stage and provides stable locations for upcoming backlog work.

## Directory Layout

- `src/Core/` bootstrap, lifecycle, installer
- `src/Database/` schema and migration orchestration
- `src/Database/Migrations/` individual migration classes
- `src/Admin/` admin controllers/pages
- `src/Public/` public shortcodes/controllers
- `src/Services/` business services
- `src/Rest/` REST/AJAX-like endpoints
- `src/Mail/` template renderer and mail integration
- `src/Audit/` audit logging
- `src/Security/` capability, nonce and validation helpers
- `templates/admin/` admin templates
- `templates/public/` public templates
- `templates/email/` email templates
- `assets/css/` stylesheet assets
- `assets/js/` script assets
- `languages/` translations

## Notes

- Use organisation-neutral naming and content.
- Keep financial and audit-sensitive logic in services and database layers.
- Treat Stripe webhook confirmation as payment truth.
