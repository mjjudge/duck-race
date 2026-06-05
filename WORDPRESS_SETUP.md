# WordPress Setup Reference (Based on Local TOL Repo)

This document captures the WordPress plugin setup pattern proven in the local `TOL` repository and adapts it for Duck Race.

It is intended as a technical reference for how to structure and run the plugin in local and production WordPress environments.

## 1. Baseline Environment

Use environment parity where possible:

- WordPress: 6.0+
- PHP: 8.2+
- MySQL: 5.7+ (InnoDB, utf8mb4)
- Custom WP table prefix supported (do not assume `wp_`)

Key rule:

- Always build custom plugin tables from `$wpdb->prefix`, not hard-coded names.

## 2. Plugin Bootstrap Pattern

In TOL, bootstrap is handled in `plugin/tree-of-light.php` with:

- Plugin header metadata
- constants (version, plugin dir, plugin url)
- PSR-style namespace autoloader mapped to `plugin/src/`
- activation and deactivation hooks
- `plugins_loaded` boot entrypoint

Duck Race should use the same shape:

- single plugin entry file
- one `Core\Plugin` boot class
- all handlers/pages registered from boot

## 3. Activation and Deactivation Lifecycle

TOL activation (`Installer::activate`) performs:

1. Register roles/capabilities
2. Run migrations
3. Create required public WordPress pages (if missing)
4. Flush rewrite rules
5. Log activation in audit log

TOL deactivation (`Installer::deactivate`) performs:

- flush rewrite rules
- log deactivation
- intentionally does not remove roles/data

Recommended Duck Race policy:

- Keep data and role assignments on deactivate
- Remove roles only on uninstall
- Never delete financial history on deactivate

## 4. Migration Strategy

TOL uses a versioned migrator (`Database\Migrator`) with:

- ordered migration class map by version
- `version_compare` against installed db version option
- idempotent migration execution
- plugin option storing db version
- audit logging of applied migrations

Duck Race should keep this exact approach.

Suggested option key pattern:

- `duck_race_db_version`

Migration rules:

- Migrations must be additive and reversible where practical
- Never silently rewrite historical financial records
- Prefer corrective migration entries over destructive changes

## 5. Roles and Capabilities

TOL defines dedicated plugin roles and explicit capabilities in one place (`Core\Roles`).

Good pattern to reuse:

- central capability map
- role registration idempotent via `add_role`
- grant core capabilities to trusted WP roles where needed
- remove plugin roles/caps on uninstall only

For Duck Race, keep capability boundaries explicit:

- race operations
- contact management
- purchase/financial operations
- settings/secrets
- export/report access
- audit access

## 6. Public Pages and Shortcodes

TOL creates required public pages during activation using stable slugs and shortcode content.

Reusable pattern:

- define required pages in one installer/developer tool list
- create if missing (safe re-run)
- keep public forms shortcode-driven

For Duck Race this aligns with pages such as:

- race summary/buy page
- payment success/failure pages
- winners page

## 7. Admin and Public Registration

TOL `Core\Plugin::boot()` separates concerns cleanly:

- always register public handlers
- register admin pages only when `is_admin()`
- enqueue assets scoped to relevant contexts

Duck Race should follow the same registration discipline to reduce plugin overhead and avoid global script/style loading.

## 8. Secrets and Settings

TOL local setup notes confirm:

- no `.env` requirement for local WordPress plugin development
- secrets entered via plugin settings UI
- stored in WP options (not in code)
- never commit secrets

Duck Race should follow this, with additional controls:

- mask sensitive values in admin UI after save
- never log secret material
- treat Stripe webhook confirmation as payment truth

## 9. Local Development Workflow

TOL pattern uses LocalWP (recommended) and supports DDEV.

Practical local workflow:

1. Create local WP site with PHP 8.2
2. Set WP table prefix to match production style if required
3. Symlink plugin folder into `wp-content/plugins/`
4. Activate plugin in WP admin
5. Configure settings (Stripe test keys etc.)
6. Use Stripe CLI for webhook forwarding in test mode

Example webhook forwarding pattern:

- `stripe listen --forward-to <local-site>/wp-json/<namespace>/<webhook-endpoint>`

## 10. Deployment Pattern

TOL docs use file-based deployment with mandatory backup before release and migration confirmation after deployment.

Duck Race should adopt the same release safety controls:

- backup before upload
- increment plugin version when migration is included
- verify migration run
- run smoke checks on public pages and admin screens

## 11. Recommended Duck Race Implementation Checklist

- Create plugin entry file and constants
- Add autoloader for `DuckRace\...` namespace
- Implement `Core\Plugin` boot orchestration
- Implement `Core\Installer` activate/deactivate
- Implement `Database\Migrator` with ordered map
- Implement `Core\Roles` capability matrix
- Implement required page creation with idempotency
- Wire settings page for Stripe and email sender config
- Add audit logging for activation, deactivation, migrations, and contact updates

## 12. Source Reference

This setup reference was derived from the local repository at:

- `/home/marcus-judge/myapps/TOL`

Primary files reviewed:

- `plugin/tree-of-light.php`
- `plugin/src/Core/Plugin.php`
- `plugin/src/Core/Installer.php`
- `plugin/src/Database/Migrator.php`
- `plugin/src/Core/Roles.php`
- `deployment-notes/LOCAL_DEV_SETUP.md`
- `docs/ARCHITECTURE.md`
