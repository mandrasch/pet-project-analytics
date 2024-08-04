<?php

/**
 * @package koko-analytics
 * @license GPL-3.0+
 * @author Danny van Kooten
 */

namespace PetProjectAnalytics;

class Endpoint_Installer
{
    public function get_file_name(): string
    {
        return rtrim(ABSPATH, '/') . '/pp-analytics-collect.php';
    }

    public function get_file_contents(): string
    {
        $buffer_filename    = get_buffer_filename();
        $functions_filename = PP_ANALYTICS_PLUGIN_DIR . '/src/functions.php';
        return <<<EOT
<?php
/**
 * @package koko-analytics
 * @license GPL-3.0+
 * @author Danny van Kooten
 *
 * This file acts as an optimized endpoint file for the Koko Analytics plugin.
 */

// path to pageviews.php file in uploads directory
define('PP_ANALYTICS_BUFFER_FILE', '$buffer_filename');

// path to functions.php file in Koko Analytics plugin directory
require '$functions_filename';

// function call to collect the request data
PetProjectAnalytics\collect_request();
EOT;
    }

    public function verify(): bool
    {
        $works = $this->verify_internal();
        update_option('pp_analytics_use_custom_endpoint', $works, true);
        return $works;
    }

    private function verify_internal(): bool
    {
        $tracker_url = site_url('/pp-analytics-collect.php?nv=1&p=0&up=1&test=1');
        $response    = wp_remote_get($tracker_url);
        if (is_wp_error($response)) {
            return false;
        }

        $status  = wp_remote_retrieve_response_code($response);
        $headers = wp_remote_retrieve_headers($response);
        if ($status !== 200 || ! isset($headers['Content-Type']) || ! str_contains($headers['Content-Type'], 'text/plain')) {
            return false;
        }

        return true;
    }

    public function install(): bool
    {
        /* If we made it this far we ideally want to use the custom endpoint file */
        /* Therefore we schedule a recurring health check event to periodically re-attempt and re-test */
        if (! wp_next_scheduled('pp_analytics_test_custom_endpoint')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', 'pp_analytics_test_custom_endpoint');
        }

        /* Check if path to buffer file changed */
        $file_name = $this->get_file_name();
        if (file_exists($file_name)) {
            $content = file_get_contents($file_name);
            if (strpos($content, get_buffer_filename()) === false) {
                unlink(ABSPATH . '/pp-analytics-collect.php');
            }
        }

        /* Attempt to put the file into place if it does not exist already */
        if (! file_exists($file_name)) {
            $success = file_put_contents($file_name, $this->get_file_contents());
            if (false === $success) {
                return false;
            }
        }

        /* Send an HTTP request to the custom endpoint to see if it's working properly */
        $works = $this->verify();
        if (! $works) {
            unlink($file_name);
            return false;
        }

        /* All looks good! Custom endpoint file exists and returns the correct response */
        return true;
    }

    public function is_eligibile(): bool
    {
        /* Do nothing if running Multisite (because Multisite has separate uploads directory per site) */
        if (is_multisite()) {
            return false;
        }

        /* Do nothing if PP_ANALYTICS_CUSTOM_ENDPOINT is defined (means users disabled this feature or is using their own version of it) */
        if (defined('PP_ANALYTICS_CUSTOM_ENDPOINT')) {
            return false;
        }

        return true;
    }
}
