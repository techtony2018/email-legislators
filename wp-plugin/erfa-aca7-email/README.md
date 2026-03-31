# ERFA ACA-7 Email Tool (WordPress Plugin)

WordPress plugin version of the ACA7 email tool. This working tree keeps the v1.9.13 plugin layout/content/trend chart, while adding the approved senator-grouped map drill-down behavior.

## Features
- Inline shortcode rendering via:
  - `[aca7_email_tool_inline]`
  - `[aca7_email_tool]`
- Same-origin REST API at `/wp-json/erfa/v1/usage`
- v1.9.13-style form layout, email content, recent-sends section, and trend chart
- Maps always visible whenever usage history exists
- Top-level maps grouped by senator, split into North / Central / South California
- Senator marker labels shown directly on the map
- Click-through drill-down to a single ZIP-level map with Back navigation
- Recent list stays capped to the latest 10 sends in the UI
- City and ZIP shown in separate recent-history columns
- Full history retained in WordPress/local fallback for maps and trend aggregation
- Auto-refresh every 60 seconds for maps, recent list, and trend

## Files
- `erfa-aca7-email.php` — plugin bootstrap, REST route normalization, shortcodes
- `assets/index.html` — inline UI (baseline v1.9.13 content plus map additions)
- `assets/data/*.json` — bundled senator and ZIP map data
- `assets/img/*` — plugin images
- `version.txt` — plugin version

## Version
- `1.9.14` — restores the v1.9.13 plugin UI/trend presentation and adds always-visible senator-grouped maps with regional split and ZIP drill-down while keeping WordPress API wiring correct.
- `1.10.1` — prior sync that fixed WordPress usage API wiring but also drifted the UI away from the v1.9.13 plugin baseline.
