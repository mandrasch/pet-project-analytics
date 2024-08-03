<?php

/**
 * @package koko-analytics
 * @license GPL-3.0+
 * @author Danny van Kooten
 *
 * Perform the necessary steps to completely uninstall Koko Analytics
 */

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) die;

// delete wp-options
delete_option("pp_analytics_settings");
delete_option("PP_ANALYTICS_VERSION");
delete_option("pp_analytics_use_custom_endpoint");
delete_option("pp_analytics_realtime_pageview_count");

// drop koko tables
global $wpdb;
$wpdb->query(
    "DROP TABLE IF EXISTS
    {$wpdb->prefix}pp_analytics_site_stats,
    {$wpdb->prefix}pp_analytics_post_stats,
    {$wpdb->prefix}pp_analytics_referrer_stats,
    {$wpdb->prefix}pp_analytics_dates,
    {$wpdb->prefix}pp_analytics_referrer_urls"
);

// delete custom endpoint file
if (file_exists(ABSPATH . '/pp-analytics-collect.php')) {
    unlink(ABSPATH . '/pp-analytics-collect.php');
}
