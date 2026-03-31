# ERFA ACA-7 Email Tool (WordPress plugin package)

## Install
1. Zip the plugin folder:
   ```bash
   cd ~/email-legislators/wp-plugin
   zip -r erfa-aca7-email.zip erfa-aca7-email
   ```
2. In WordPress admin: **Plugins → Add New → Upload Plugin**
3. Upload `erfa-aca7-email.zip` and activate.
4. Add shortcode to any page/post:
   ```
   [aca7_email_tool]
   ```

## Included files
- `erfa-aca7-email.php` (plugin bootstrap + REST routes + shortcode)
- `assets/index.html` (tool UI)
- `assets/data/*.json` (senators + zip maps)
- `assets/img/erfapac-logo.png`

## REST endpoint
- `GET /wp-json/erfa/v1/usage`
- `POST /wp-json/erfa/v1/usage`

Stored in WP option: `erfa_usage_data`.
