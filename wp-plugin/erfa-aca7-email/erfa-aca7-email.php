<?php
/**
 * Plugin Name: ERFA ACA-7 Email Tool
 * Description: Embed the ACA-7 email tool via shortcode and track usage through a same-origin REST endpoint.
 * Version: 1.9.20
 * Author: ERFA
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ERFA_ACA7_EMAIL_VERSION', '1.9.20');

class ERFA_ACA7_Email_Tool {
    const OPTION_KEY = 'erfa_usage_data';

    public function __construct() {
        add_shortcode('aca7_email_tool', array($this, 'render_shortcode'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    public function render_shortcode($atts = array()) {
        $plugin_url = plugin_dir_url(__FILE__);
        $iframe_src = esc_url($plugin_url . 'assets/index.html?v=' . ERFA_ACA7_EMAIL_VERSION);

        ob_start();
        ?>
        <div class="erfa-aca7-email-tool-wrap" style="width:100%;max-width:900px;margin:0 auto;">
            <iframe
                src="<?php echo $iframe_src; ?>"
                title="ERFA ACA-7 Email Tool"
                style="width:100%;height:1700px;border:0;display:block;"
                loading="lazy"
                referrerpolicy="same-origin"
            ></iframe>
        </div>
        <?php
        return ob_get_clean();
    }

    public function register_rest_routes() {
        register_rest_route('erfa/v1', '/usage', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_usage'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'post_usage'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'senator' => array('required' => false, 'type' => 'string'),
                    'city' => array('required' => false, 'type' => 'string'),
                    'zip' => array('required' => false, 'type' => 'string'),
                ),
            ),
        ));
    }

    private function normalize_usage_data($raw) {
        $data = is_array($raw) ? $raw : array();

        $count = isset($data['count']) && is_numeric($data['count']) ? intval($data['count']) : 0;
        $recent_sends = isset($data['recentSends']) && is_array($data['recentSends']) ? $data['recentSends'] : array();
        $locations = isset($data['locations']) && is_array($data['locations']) ? $data['locations'] : array();
        $senators = isset($data['senators']) && is_array($data['senators']) ? $data['senators'] : array();

        return array(
            'count' => $count,
            'recentSends' => $recent_sends,
            'locations' => $locations,
            'senators' => $senators,
        );
    }

    public function get_usage() {
        $data = get_option(self::OPTION_KEY, array());
        return rest_ensure_response($this->normalize_usage_data($data));
    }

    public function post_usage(WP_REST_Request $request) {
        $senator = sanitize_text_field((string) $request->get_param('senator'));
        $city = sanitize_text_field((string) $request->get_param('city'));
        $zip = sanitize_text_field((string) $request->get_param('zip'));

        $data = $this->normalize_usage_data(get_option(self::OPTION_KEY, array()));

        $time = current_time('c');
        $senator_key = $senator !== '' ? $senator : 'Unknown Senator';
        $location_key = strtolower($city) . '|' . $zip;

        $data['count'] = intval($data['count']) + 1;

        array_unshift($data['recentSends'], array(
            'senator' => $senator_key,
            'city' => $city,
            'zip' => $zip,
            'time' => $time,
        ));
        $data['recentSends'] = array_slice($data['recentSends'], 0, 10);

        if ($city !== '' || $zip !== '') {
            if (!isset($data['locations'][$location_key]) || !is_array($data['locations'][$location_key])) {
                $data['locations'][$location_key] = array(
                    'city' => $city,
                    'zip' => $zip,
                    'count' => 0,
                    'firstSent' => $time,
                    'lastSent' => $time,
                );
            }

            $location = $data['locations'][$location_key];
            $location['city'] = isset($location['city']) && $location['city'] !== '' ? $location['city'] : $city;
            $location['zip'] = isset($location['zip']) && $location['zip'] !== '' ? $location['zip'] : $zip;
            $location['count'] = isset($location['count']) ? intval($location['count']) + 1 : 1;
            $location['firstSent'] = isset($location['firstSent']) && $location['firstSent'] !== '' ? $location['firstSent'] : $time;
            $location['lastSent'] = $time;
            $data['locations'][$location_key] = $location;

            if (!isset($data['senators'][$senator_key]) || !is_array($data['senators'][$senator_key])) {
                $data['senators'][$senator_key] = array(
                    'name' => $senator_key,
                    'count' => 0,
                    'firstSent' => $time,
                    'lastSent' => $time,
                    'locations' => array(),
                );
            }

            $senator_entry = $data['senators'][$senator_key];
            $senator_entry['count'] = isset($senator_entry['count']) ? intval($senator_entry['count']) + 1 : 1;
            $senator_entry['firstSent'] = isset($senator_entry['firstSent']) && $senator_entry['firstSent'] !== '' ? $senator_entry['firstSent'] : $time;
            $senator_entry['lastSent'] = $time;

            if (!isset($senator_entry['locations']) || !is_array($senator_entry['locations'])) {
                $senator_entry['locations'] = array();
            }

            if (!isset($senator_entry['locations'][$location_key]) || !is_array($senator_entry['locations'][$location_key])) {
                $senator_entry['locations'][$location_key] = array(
                    'city' => $city,
                    'zip' => $zip,
                    'count' => 0,
                    'firstSent' => $time,
                    'lastSent' => $time,
                );
            }

            $senator_location = $senator_entry['locations'][$location_key];
            $senator_location['city'] = isset($senator_location['city']) && $senator_location['city'] !== '' ? $senator_location['city'] : $city;
            $senator_location['zip'] = isset($senator_location['zip']) && $senator_location['zip'] !== '' ? $senator_location['zip'] : $zip;
            $senator_location['count'] = isset($senator_location['count']) ? intval($senator_location['count']) + 1 : 1;
            $senator_location['firstSent'] = isset($senator_location['firstSent']) && $senator_location['firstSent'] !== '' ? $senator_location['firstSent'] : $time;
            $senator_location['lastSent'] = $time;

            $senator_entry['locations'][$location_key] = $senator_location;
            $data['senators'][$senator_key] = $senator_entry;
        }

        update_option(self::OPTION_KEY, $data, false);

        return rest_ensure_response(array(
            'success' => true,
            'count' => $data['count'],
            'locations' => $data['locations'],
            'senators' => $data['senators'],
        ));
    }
}

new ERFA_ACA7_Email_Tool();
