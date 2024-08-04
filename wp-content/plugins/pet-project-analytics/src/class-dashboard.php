<?php

/**
 * @package koko-analytics
 * @license GPL-3.0+
 * @author Danny van Kooten
 */

namespace PetProjectAnalytics;

class Dashboard
{
    public function __construct()
    {
        add_action('init', array($this, 'maybe_show_dashboard'), 10, 0);
    }

    public function maybe_show_dashboard(): void
    {
        if (!isset($_GET['koko-analytics-dashboard'])) {
            return;
        }

        $settings = get_settings();
        if (!$settings['is_dashboard_public'] && !current_user_can('view_pp_analytics')) {
            return;
        }

        $this->show_standalone_dashboard_page();
    }

    public function show_standalone_dashboard_page(): void
    {
        require __DIR__ . '/views/standalone.php';
        exit;
    }

    public function show(): void
    {
        $settings   = get_settings();
        $dates = new Dates();
        $stats = new Stats();
        $dateRange = $dates->get_range($settings['default_view']);
        $site_id = isset($_GET['siteId']) ? $_GET['siteId'] : null; // TODO: show error if null

        // If no siteId is provided, get the first siteId from the database and redirect
        if (!$site_id) {
            // Retrieve the first available siteId
            $sites = $this->get_sites();
            if (!empty($sites)) {
                $first_site_id = $sites[0]->id; // Assuming 'site_id' is the field name

                // TODO: this did not work? header error? Warning: Cannot modify header information - headers already sent by (output started at /var/www/html/wp-includes/script-loader.php:2936) in /var/www/html/wp-includes/pluggable.php on line 1435
                // Redirect to the URL with the default siteId
                // wp_safe_redirect(add_query_arg('siteId', $first_site_id));

                // For now we use JS redirect ¯\_(ツ)_/¯
                echo '<script type="text/javascript">window.location = "' . add_query_arg('siteId', $first_site_id) . '";</script>';

                exit;
            } else {
                // Handle case where there are no sites available
                wp_die(__('No sites available to set as default.', 'your-text-domain'));
            }
        }

        $dateStart  = isset($_GET['start_date']) ? create_local_datetime($_GET['start_date']) : $dateRange[0];
        $dateEnd    = isset($_GET['end_date']) ? create_local_datetime($_GET['end_date']) : $dateRange[1];
        $dateFormat = get_option('date_format');
        $preset     = !isset($_GET['start_date']) && !isset($_GET['end_date']) ? $settings['default_view'] : 'custom';
        $totals = $stats->get_totals($site_id, $dateStart->format('Y-m-d'), $dateEnd->format('Y-m-d'));
        $realtime = get_realtime_pageview_count('-1 hour');

        require __DIR__ . '/views/dashboard-page.php';
    }

    private function get_script_data(int $site_id, \DateTimeInterface $dateStart, \DateTimeInterface $dateEnd): array
    {
        $stats = new Stats();
        $items_per_page = (int) apply_filters('pp_analytics_items_per_page', 20);
        $groupChartBy = 'day';

        // TODO: sanitize site_id?

        if ($dateEnd->getTimestamp() - $dateStart->getTimestamp() >= 86400 * 364) {
            $groupChartBy = 'month';
        }

        return apply_filters('pp_analytics_dashboard_script_data', array(
            'root'             => rest_url(),
            'nonce'            => wp_create_nonce('wp_rest'),
            'items_per_page'   => $items_per_page,
            'startDate' => $_GET['start_date'] ?? $dateStart->format('Y-m-d'),
            'endDate' => $_GET['end_date'] ?? $dateEnd->format('Y-m-d'),
            'i18n' => array(
                'Visitors' => __('Visitors', 'pp-analytics'),
                'Pageviews' => __('Pageviews', 'pp-analytics'),
            ),
            'data' => array(
                'chart' => $stats->get_stats($site_id, $dateStart->format("Y-m-d"), $dateEnd->format('Y-m-d'), $groupChartBy),
                'posts' => $stats->get_posts($site_id, $dateStart->format("Y-m-d"), $dateEnd->format('Y-m-d'), 0, $items_per_page),
                'referrers' => $stats->get_referrers($site_id, $dateStart->format("Y-m-d"), $dateEnd->format('Y-m-d'), 0, $items_per_page),
            )
        ), $dateStart, $dateEnd);
    }

    public function get_date_presets(): array
    {
        return [
            'today' => __('Today', 'koko-analytics'),
            'yesterday' => __('Yesterday', 'koko-analytics'),
            'this_week' => __('This week', 'koko-analytics'),
            'last_week' => __('Last week', 'koko-analytics'),
            'last_14_days' => __('Last 14 days', 'koko-analytics'),
            'last_28_days' => __('Last 28 days', 'koko-analytics'),
            'this_month' => __('This month', 'koko-analytics'),
            'last_month' => __('Last month', 'koko-analytics'),
            'this_year' => __('This year', 'koko-analytics'),
            'last_year' => __('Last year', 'koko-analytics'),
        ];
    }


    /**
     * Get a list of sites.
     *
     * @return array Array of objects containing site_id and site_name.
     */
    public function get_sites(): array
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pp_analytics_sites';

        // Query to retrieve site_id and site_name from the table
        $sql = "SELECT id, title FROM $table_name ORDER BY title ASC";

        // Execute the query
        $results = $wpdb->get_results($sql);

        return $results;
    }

    private function get_usage_tip(): string
    {
        $tips = [
            esc_html__('Tip: use the arrow keys on your keyboard to cycle through date ranges.', 'koko-analytics'),
            esc_html__('Tip: you can set a default date range in the plugin settings.', 'koko-analytics'),
            sprintf(__('Tip: did you know there is a widget, shortcode and template function to <a href="%1s">show a list of the most viewed posts</a> on your site?', 'koko-analytics'), 'https://www.kokoanalytics.com/kb/showing-most-viewed-posts-on-your-wordpress-site/'),
            sprintf(__('Tip: Use <a href="%1s">Koko Analytics Pro</a> to set up custom event tracking.', 'koko-analytics'), 'https://www.kokoanalytics.com/pricing/'),
        ];
        return $tips[array_rand($tips)];
    }

    private function maybe_show_adblocker_notice(): void
    {
?>
        <div class="notice notice-warning is-dismissible" id="koko-analytics-adblock-notice" style="display: none;">
            <p>
                <?php echo esc_html__('You appear to be using an ad-blocker that has Koko Analytics on its blocklist. Please whitelist this domain in your ad-blocker setting if your dashboard does not seem to be working correctly.', 'koko-analytics'); ?>
            </p>
        </div>
        <script src="<?php echo plugins_url('/assets/dist/js/koko-analytics-script-test.js', PP_ANALYTICS_PLUGIN_FILE); ?>?v=<?php echo PP_ANALYTICS_VERSION; ?>" defer onerror="document.getElementById('koko-analytics-adblock-notice').style.display = '';"></script>
<?php
    }
}
