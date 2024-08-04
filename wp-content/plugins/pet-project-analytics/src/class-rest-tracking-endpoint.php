<?php

/**
 * @package koko-analytics
 * @license GPL-3.0+
 * @author Danny van Kooten
 */

namespace PetProjectAnalytics;

class RestTrackingEndpoint
{
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'), 10, 0);
    }

    public function register_routes()
    {
        // TODO: no nonce?
        register_rest_route('pp-analytics/v1', '/event', array(
            'methods' => 'POST',
            'callback' => array($this, 'pp_analytics_event_handler'),
            'permission_callback' => '__return_true', // For testing purposes, allow all requests
        ));
    }

    // TODO: rename to track?
    // TODO: rework this!
    function pp_analytics_event_handler(\WP_REST_Request $request)
    {
        // Get the parameters from the request
        $params = $request->get_json_params();
        // var_dump($params);

        $result = $this->collect_request($params);

        // Check if $result is an instance of WP_Error
        if (is_wp_error($result)) {
            // Return the WP_Error details
            return $result;
        }

        $response = new \WP_REST_Response('OK', 200);
        $response->header('Content-Type', 'text/plain');
        // Prevent this response from being cached
        $response->header('Cache-Control', 'no-cache, must-revalidate, max-age=0');
        // indicate that we are not tracking user specifically, see https://www.w3.org/TR/tracking-dnt/
        $response->header('Tk', 'N');

        return new \WP_REST_Response(array('status' => 'success', 'message' => 'Pageview recorded'), 200);
    }

    // Our altered function to collect request via POST
    function collect_request($params)
    {
        global $wpdb;

        // Check for bot or crawler User-Agent
        if (empty($_SERVER['HTTP_USER_AGENT']) || preg_match("/bot|crawl|spider|seo|lighthouse|facebookexternalhit|preview/i", $_SERVER['HTTP_USER_AGENT'])) {
            return new \WP_Error('forbidden', __('Bots and crawlers are not allowed.', 'pp-analytics'), array('status' => 403));
        }

        $domain = sanitize_text_field($params['d']);
        if (empty($domain) || $domain == '') {
            return new \WP_Error('missing_parameter', __('The "d" parameter is required.', 'pp-analytics'), array('status' => 400));
        }

        // Query the database for the site with the given domain
        $table_name = $wpdb->prefix . 'pp_analytics_sites';
        $site = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE domain = %s", $domain));
        if (is_null($site)) {
            // TODO: is cache here disabled as well? ($response->header('Cache-Control', 'no-cache, must-revalidate, max-age=0');)
            return new \WP_Error('site_not_found', __('The site with the provided domain does not exist.', 'pp-analytics'), array('status' => 400));
        }

        $url = sanitize_text_field($params['u']);
        $referrerUrl = sanitize_text_field($params['r']);


        // TODO: implement by cookie? (originally $_GET['nv'],)
        $newVisitor = true;
        // TODO: implement by cookie/localstorage? (originally  $_GET['up'])
        $uniquePageview = false;

        // TODO: cookie setting?

        // TODO: add site id
        // Collect the visit / pageview:
        $data = [
            'p',              // type indicator
            $site->id,        // 0: site id
            $url,             // 1: url
            $newVisitor,      // 2: is new visitor?
            $uniquePageview,  // 3: is unique pageview?
            !empty($referrerUrl) ? trim($referrerUrl) : '',   // 4: referrer URL
        ];

        // TODO: introduce test mode param like here? currently not implemented in our JS
        $success = isset($params['test']) ? test_collect_in_file() : collect_in_file($data);

        return true;
        // TODO: the original method - see what we need


        // ignore requests from bots, crawlers and link previews
        if (empty($_SERVER['HTTP_USER_AGENT']) || preg_match("/bot|crawl|spider|seo|lighthouse|facebookexternalhit|preview/i", $_SERVER['HTTP_USER_AGENT'])) {
            return;
        }

        if (isset($_GET['e'])) {
            $data = extract_event_data();
        } else {
            $data = extract_pageview_data();
        }

        if (!empty($data)) {
            $success = isset($_GET['test']) ? test_collect_in_file() : collect_in_file($data);

            // set OK headers & prevent caching
            if (!$success) {
                \header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
            } else {
                \header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
            }
        } else {
            \header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        }

        \header('Content-Type: text/plain');

        // Prevent this response from being cached
        \header('Cache-Control: no-cache, must-revalidate, max-age=0');

        // indicate that we are not tracking user specifically, see https://www.w3.org/TR/tracking-dnt/
        \header('Tk: N');

        // set cookie server-side if requested (eg for AMP requests)
        if (isset($_GET['p']) && isset($_GET['nv']) && isset($_GET['sc']) && (int) $_GET['sc'] === 1) {
            $posts_viewed = isset($_COOKIE['_pp_analytics_pages_viewed']) ? \explode(',', $_COOKIE['_pp_analytics_pages_viewed']) : array('');
            if ((int) $_GET['nv']) {
                $posts_viewed[] = (int) $_GET['p'];
            }
            $cookie = \join(',', $posts_viewed);
            \setcookie('_pp_analytics_pages_viewed', $cookie, time() + 6 * 3600, '/');
        }

        exit;
    }
}
