# Lite Build Notes

This folder is a GitHub/local-install working copy extracted from the full `new/` app.

## Copied From Full App

Top-level app files for login, dashboard shell, admissions, patients, reports, profile, centre management, and shared templates.

Copied folders:

- `ajax/`
- `controllers/`
- `core/`
- `img/`
- `languages/`
- `models/`
- `modules/`
- `operations/`
- `views/`

## Intentionally Not Copied Yet

- `admin/` private hosted admin area
- `api/` hosted/API surface
- `vendor/`
- `lib/tcpdf/` and heavier dependencies
- `ngo/`
- `vet/`
- `m/` mobile app area
- `user_images/`
- `user_documents/`
- `public/` WordPress/public hosted widgets from full app
- `.vscode/`
- local test files

## Lite Direction

Lite is multi-user but single-centre.

Keep `centre_id` in the schema for compatibility, but do not expose centre switching in the UI.

## Next Strip/Adapt Pass

1. Replace committed `config.php` with installer-generated config handling.
2. Remove hosted-only left menu links and module store links.
3. Keep core admission/patient workflows.
4. Build database schema from the copied app tables.
5. Add simple local login/install flow.

## Full-only Features Removed From Lite Copy

- FireText SMS send endpoints
- SMS log patient tab and send-SMS form
- Centre API key management view
- Module Store entry point
