# ERFA Email Tool: Standalone + WordPress Plugin

This repo contains **two deliverables**:

1) **Standalone web app** (for local dev/testing)
- Location: `standalone/`

2) **WordPress plugin** (what’s deployed on erfapac.com)
- Location: `wp-plugin/erfa-aca7-email/`

## Which one is “the real thing”?

**Production is the WordPress plugin.**

We keep the standalone UI in sync with the plugin’s embedded UI so you can iterate locally, then ship via the plugin.

## Repo layout

- `standalone/`
  - `index.html` — main UI
  - `server.js` — local dev server
  - `data/` — JSON (districts / senators / ZIP mapping)
  - `img/` — assets

- `wp-plugin/erfa-aca7-email/`
  - `erfa-aca7-email.php` — WP plugin bootstrap (version lives here)
  - `assets/index.html` — embedded UI used by the shortcode
  - `assets/data/*` — data used by the UI

- `scripts/`
  - `sync-standalone-to-wp-plugin.sh` — copies `standalone/{index.html,data,img}` into the plugin assets

## Run standalone locally

```bash
cd standalone
npm install
node server.js
```

## Sync standalone → WordPress plugin assets

After editing the standalone UI/data:

```bash
./scripts/sync-standalone-to-wp-plugin.sh
```

## Versioning (source of truth)
WordPress reads the plugin version from the **plugin header** in:
- `wp-plugin/erfa-aca7-email/erfa-aca7-email.php` → `Version: x.y.z`

We also keep it in sync in:
- `define('ERFA_ACA7_EMAIL_VERSION', 'x.y.z')` (same file)
- `wp-plugin/erfa-aca7-email/version.txt`

## Deploy to WordPress

Zip **the folder** `wp-plugin/erfa-aca7-email/` and upload in WP Admin:

**Plugins → Add New → Upload Plugin**

(When prompted, choose **“Replace current with uploaded”.**)
