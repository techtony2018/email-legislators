<?php
/**
 * Plugin Name: ERFA ACA7 Email Tool - Inline
 * Description: ACA-7 email tool + usage API (v1.9.13 layout/trend plus senator-grouped maps and ZIP drill-down).
 * Version: 1.9.31
 * Author: ERFA PAC
 */
if (!defined('ABSPATH')) exit;

define('ERFA_ACA7_EMAIL_VERSION', '1.9.31');

function erfa_aca7_normalize_usage_data($raw) {
    $data = is_array($raw) ? $raw : [];
    $recent = isset($data['recentSends']) && is_array($data['recentSends']) ? $data['recentSends'] : [];

    // Treat recentSends as the single source of truth.
    // Rebuild derived aggregates from scratch on every normalization pass so
    // counts cannot drift upward when this function is called repeatedly.
    $locations = [];
    $senators = [];

    foreach ($recent as $item) {
        if (!is_array($item)) continue;
        $senator = isset($item['senator']) ? sanitize_text_field($item['senator']) : 'Unknown Senator';
        $city = isset($item['city']) ? sanitize_text_field($item['city']) : '';
        $zip = isset($item['zip']) ? sanitize_text_field((string)$item['zip']) : '';
        if ($city === '' && $zip === '') continue;
        $time = isset($item['time']) && is_string($item['time']) ? $item['time'] : current_time('c');
        $key = strtolower($city) . '|' . $zip;

        if (!isset($locations[$key]) || !is_array($locations[$key])) {
            $locations[$key] = [
                'city' => $city,
                'zip' => $zip,
                'count' => 0,
                'firstSent' => $time,
                'lastSent' => $time,
            ];
        }
        $locations[$key]['city'] = $locations[$key]['city'] ?: $city;
        $locations[$key]['zip'] = $locations[$key]['zip'] ?: $zip;
        $locations[$key]['count'] = (int)($locations[$key]['count'] ?? 0) + 1;
        if (empty($locations[$key]['firstSent']) || strcmp($time, $locations[$key]['firstSent']) < 0) $locations[$key]['firstSent'] = $time;
        if (empty($locations[$key]['lastSent']) || strcmp($time, $locations[$key]['lastSent']) > 0) $locations[$key]['lastSent'] = $time;

        if (!isset($senators[$senator]) || !is_array($senators[$senator])) {
            $senators[$senator] = [
                'name' => $senator,
                'count' => 0,
                'firstSent' => $time,
                'lastSent' => $time,
                'locations' => [],
            ];
        }
        $senators[$senator]['count'] = (int)($senators[$senator]['count'] ?? 0) + 1;
        if (empty($senators[$senator]['firstSent']) || strcmp($time, $senators[$senator]['firstSent']) < 0) $senators[$senator]['firstSent'] = $time;
        if (empty($senators[$senator]['lastSent']) || strcmp($time, $senators[$senator]['lastSent']) > 0) $senators[$senator]['lastSent'] = $time;
        if (!isset($senators[$senator]['locations']) || !is_array($senators[$senator]['locations'])) {
            $senators[$senator]['locations'] = [];
        }
        if (!isset($senators[$senator]['locations'][$key]) || !is_array($senators[$senator]['locations'][$key])) {
            $senators[$senator]['locations'][$key] = [
                'city' => $city,
                'zip' => $zip,
                'count' => 0,
                'firstSent' => $time,
                'lastSent' => $time,
            ];
        }
        $senators[$senator]['locations'][$key]['city'] = $senators[$senator]['locations'][$key]['city'] ?: $city;
        $senators[$senator]['locations'][$key]['zip'] = $senators[$senator]['locations'][$key]['zip'] ?: $zip;
        $senators[$senator]['locations'][$key]['count'] = (int)($senators[$senator]['locations'][$key]['count'] ?? 0) + 1;
        if (empty($senators[$senator]['locations'][$key]['firstSent']) || strcmp($time, $senators[$senator]['locations'][$key]['firstSent']) < 0) $senators[$senator]['locations'][$key]['firstSent'] = $time;
        if (empty($senators[$senator]['locations'][$key]['lastSent']) || strcmp($time, $senators[$senator]['locations'][$key]['lastSent']) > 0) $senators[$senator]['locations'][$key]['lastSent'] = $time;
    }

    return [
        'count' => isset($data['count']) ? (int)$data['count'] : count($recent),
        'recentSends' => $recent,
        'locations' => $locations,
        'senators' => $senators,
    ];
}

add_action('rest_api_init', function () {
    register_rest_route('erfa/v1', '/usage', [
        [
            'methods'  => 'GET',
            'callback' => function () {
                $raw = get_option('erfa_usage_data', ['count' => 0, 'recentSends' => [], 'locations' => [], 'senators' => []]);
                $normalized = erfa_aca7_normalize_usage_data($raw);
                // Self-heal any previously drifted aggregate counts by persisting the
                // rebuilt derived data back into the option store.
                if ($raw !== $normalized) {
                    update_option('erfa_usage_data', $normalized, false);
                }
                return rest_ensure_response($normalized);
            },
            'permission_callback' => '__return_true',
        ],
        [
            'methods'  => 'POST',
            'callback' => function ($request) {
                $params = $request->get_json_params();
                $senator = sanitize_text_field($params['senator'] ?? '');
                $city = sanitize_text_field($params['city'] ?? '');
                $zip = sanitize_text_field((string)($params['zip'] ?? ''));
                $time = current_time('c');

                $data = erfa_aca7_normalize_usage_data(get_option('erfa_usage_data', ['count' => 0, 'recentSends' => [], 'locations' => [], 'senators' => []]));
                $data['count'] = (int)($data['count'] ?? 0) + 1;
                array_unshift($data['recentSends'], [
                    'senator' => $senator,
                    'city' => $city,
                    'zip' => $zip,
                    'time' => $time,
                ]);
                // Keep full history for maps; UI limits the recent table to 10 rows.
                $data = erfa_aca7_normalize_usage_data($data);
                update_option('erfa_usage_data', $data, false);

                return rest_ensure_response([
                    'success' => true,
                    'count' => $data['count'],
                    'locations' => $data['locations'],
                    'senators' => $data['senators'],
                ]);
            },
            'permission_callback' => '__return_true',
        ],
    ]);
});

function erfa_aca7_render_inline_tool() {
    $html_path = __DIR__ . '/assets/index.html';
    if (!file_exists($html_path)) return '<p>ACA7 tool missing.</p>';

    $plugin_url = plugin_dir_url(__FILE__) . 'assets/';
    $html = file_get_contents($html_path);
    $search = [
        'src="img/', "src='img/", 'href="img/', '"img/', "'img/",
        '"data/', "'data/", 'fetch("data/', "fetch('data/"
    ];
    $replace = [
        'src="' . $plugin_url . 'img/', "src='" . $plugin_url . "img/", 'href="' . $plugin_url . 'img/', '"' . $plugin_url . 'img/', "'" . $plugin_url . "img/",
        '"' . $plugin_url . 'data/', "'" . $plugin_url . "data/", 'fetch("' . $plugin_url . 'data/', "fetch('" . $plugin_url . "data/"
    ];
    $html = str_replace($search, $replace, $html);
    $html = str_replace('{{APP_VERSION_LABEL}}', ' (v' . esc_html(ERFA_ACA7_EMAIL_VERSION) . ')', $html);
    $html = preg_replace('/<!DOCTYPE[\s\S]*?<body[^>]*>/i', '', $html, 1);
    $html = preg_replace('/<\/body>\s*<\/html>/i', '', $html, 1);

    $critical_css = '<style>
        .aca7-inline-wrapper { max-width: 900px; margin: 0 auto; }
        .aca7-inline-wrapper .container { width: 100%; max-width: 900px; margin: 0 auto; }
        .aca7-inline-wrapper .header { text-align: center; }
        .aca7-inline-wrapper .address-row { display: flex !important; gap: 12px; flex-wrap: nowrap; align-items: flex-start; margin-top: 10px; margin-bottom: 10px; }
        .aca7-inline-wrapper .address-row .form-group { flex: 1; min-width: 0; }
        .aca7-inline-wrapper .address-row .form-group:last-child { flex: 0 0 140px; }
        .aca7-inline-wrapper .address-row .form-group input { width: 100% !important; }
        .aca7-inline-wrapper .subject-row { display: flex; gap: 10px; align-items: center; }
        .aca7-inline-wrapper .subject-row label { margin: 0; min-width: 110px; }
        .aca7-inline-wrapper .subject-row input { flex: 1; width: 100% !important; display: none; }
        .aca7-inline-wrapper .subject-row .subject-text { flex: 1; }
        .aca7-inline-wrapper .form-row .form-group { width: 50%; }
        .aca7-inline-wrapper textarea#emailBody { width: 100%; max-width: 900px; }
        .aca7-inline-wrapper .lookup-btn, .aca7-inline-wrapper .submit-btn { background: #4285F4; color: #fff; border: 1px solid #4285F4; border-radius: 8px; padding: 10px 14px; font-weight: 600; cursor: pointer; width: 100%; display: inline-block; text-align: center; }
        .aca7-inline-wrapper .lookup-btn:hover, .aca7-inline-wrapper .submit-btn:hover { background: #2f6cd1; }
        .aca7-inline-wrapper button { background: #4285F4 !important; color: #fff !important; border: 1px solid #4285F4 !important; border-radius: 8px !important; padding: 10px 14px !important; }
        .aca7-inline-wrapper button:hover { background: #2f6cd1 !important; }
        .aca7-inline-wrapper button:disabled { background: #ccc !important; border-color: #aaa !important; color: #666 !important; cursor: not-allowed !important; }
        .aca7-inline-wrapper input, .aca7-inline-wrapper textarea, .aca7-inline-wrapper select { width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 15px; box-sizing: border-box; }
        .aca7-inline-wrapper .embed-status { font-size: 12px; color: #555; margin-bottom: 8px; }
    </style>';

    return '<div class="aca7-inline-wrapper">' . $critical_css . $html . '</div>';
}

add_shortcode('aca7_email_tool_inline', 'erfa_aca7_render_inline_tool');
add_shortcode('aca7_email_tool', 'erfa_aca7_render_inline_tool');
