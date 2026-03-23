# ERFA ACA-7 Email Tool (WordPress Plugin)

A self-contained WordPress plugin that embeds the ACA-7 opposition email tool via shortcode. Users enter their info, auto-find their California State Senator by ZIP (with manual select fallback), and launch a prefilled email via mailto or Gmail. Sends are tracked via a same-origin REST endpoint.

## Features
- **Shortcode embed**: `[aca7_email_tool]` renders an iframe pointing to the bundled `assets/index.html` (width 100%, max 900px, height ~1600px).
- **Data loading**: Bundled senator and ZIP maps (`assets/data/40senators.json`, `20senators.json`, `SenateZipMap.json`, `SenateDistricts.json`).
- **Address → senator**: ZIP-based lookup first; fallback to CA API via CORS proxy; manual dropdown as last resort.
- **Templates**: Standard, Urgent (default), Personal. Placeholders `[SENATOR_NAME]`, `[YOUR_NAME]`, `[YOUR_ADDRESS]` are auto-filled; address uses street, city, and CA ZIP. Subject is read-only.
- **Recipients**: Primary (senator + assistant) in To; BCC-only to first 20 senators/assistants excluding the matched senator. Summary shows counts.
- **Send flows**: "Send via Email App" (mailto) and "Send via Gmail" (web compose). Success/status message updates after launch.
- **Branding**: Blue header on white, logo linked to erfapac.com, title “Vote No on ACA-7,” subtitle “Email Your Senator to Oppose ACA-7 Now!”
- **Usage tracking**: Same-origin REST at `/wp-json/erfa/v1/usage` (GET/POST) storing option `erfa_usage_data` with `count` and `recentSends` (last 10). Falls back to `localStorage` if API fails. Auto-refresh every 60s.
- **Layout**: Full-width container, compact padding/margins per latest tweaks; success status visible by default with updated text.

## Shortcode
```
[aca7_email_tool]
```

## REST Usage Endpoint
- **GET** `/wp-json/erfa/v1/usage` → `{ count, recentSends }`
- **POST** `/wp-json/erfa/v1/usage` → body `{ senator, city, zip }`, appends to recent and increments count.
- Data persists in the WP option `erfa_usage_data` (not overwritten by plugin updates). Backup/restore via:
  - `wp option get erfa_usage_data > erfa_usage_backup.json`
  - `wp option update erfa_usage_data "$(cat erfa_usage_backup.json)"`

## Files
- `erfa-aca7-email.php` — main plugin bootstrap + REST routes + shortcode.
- `assets/index.html` — full client app (UI, logic, usage calls).
- `assets/data/*.json` — senators and ZIP maps.
- `assets/img/erfapac-logo.png` — logo.
- `version.txt` — current version number.

## Version History
- **1.2 (current)**: UI compaction; success message shown by default with guidance; status updates after send; button labels simplified (no icons); `[YOUR_ADDRESS]` placeholder added and auto-filled; merged total count into history heading; tightened spacing.
- **1.1**: Repositioned success message below buttons; widened layout to 100%; PST timestamps; senator dropdown names without “Senator”; usage fetch hardened; added `version.txt` and bumped version constant.
- **1.0**: Initial plugin packaging with shortcode/iframe, REST usage tracking, branding, BCC-only recipients (first 20), ZIP lookup, and templates (default Urgent).

## Notes for Future Maintainers
- Default data loads from bundled JSON; runtime fetches include `?v=1` cache-bust query.
- BCC construction excludes the matched senator/assistant; displays count when large.
- If Chrome CDP/Gmail scraping is needed for bounces, that’s out-of-scope here; this plugin only launches the client composer.
- Usage endpoint must be reachable on the same origin; during local file testing (no WP), API calls will 404 and the tool will use localStorage fallback.
