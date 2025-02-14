<?php

/**
 * @package koko-analytics
 * @license GPL-3.0+
 * @author Danny van Kooten
 */

namespace PetProjectAnalytics;

// TODO: we need to change $page (id) to be url

class Stats
{
    public function get_totals(int $site_id, string $start_date, string $end_date, int $page = 0): ?object
    {
        global $wpdb;

        // if end date is a future date, cap it at today so that relative differences to previous period are fair
        $today = create_local_datetime('now')->format('Y-m-d');
        if ($end_date > $today) {
            $end_date = $today;
        }
        $previous_start_date = gmdate('Y-m-d', strtotime($start_date) - (strtotime($end_date . ' 23:59:59') - strtotime($start_date)));

        $table = $wpdb->prefix . 'pp_analytics_site_stats';
        $where_a = 's.date >= %s AND s.date <= %s AND s.site_id = %d';
        $args_a = array($start_date, $end_date, $site_id);
        $where_b = 's.date >= %s AND s.date < %s AND s.site_id = %d';
        $args_b = array($previous_start_date, $start_date, $site_id);

        if ($page > 0) {
            $table = $wpdb->prefix . 'pp_analytics_post_stats';
            $where_a .= ' AND s.id = %d';
            $where_b .= ' AND s.id = %d';
            $args_a[] = $page;
            $args_b[] = $page;
        }

        $sql = $wpdb->prepare(
            "SELECT
                cur.*,
                cur.visitors - prev.visitors AS visitors_change,
                cur.pageviews - prev.pageviews AS pageviews_change,
                cur.visitors / prev.visitors - 1 AS visitors_change_rel,
                cur.pageviews / prev.pageviews - 1 AS pageviews_change_rel
            FROM
                (SELECT COALESCE(SUM(visitors), 0) AS visitors, COALESCE(SUM(pageviews), 0) AS pageviews FROM {$table} s WHERE $where_a) AS cur,
                (SELECT COALESCE(SUM(visitors), 0) AS visitors, COALESCE(SUM(pageviews), 0) AS pageviews FROM {$table} s WHERE $where_b) AS prev;
            ",
            array_merge($args_a, $args_b)
        );
        $result = $wpdb->get_row($sql);

        // sometimes there are pageviews, but no counted visitors
        // this happens when the cookie was valid over a period of 2 calendar days
        // we can make this less obviously wrong by always specifying there was at least 1 visitors
        // whenever we have any pageviews
        if ($result && $result->pageviews > 0 && $result->visitors == 0) {
            $result->visitors = 1;
            $result->visitors_change += $result->visitors_change > 0 ? -1 : 1;
        }

        return $result;
    }

    /**
     * Get aggregated statistics (per day or per month) between the two given dates.
     * Without the $page parameter this returns the site-wide statistics.
     *
     * @param string $start_date
     * @param string $end_date
     * @param string $group
     * @param int $site_id
     * @param int $page
     * @return array
     */
    public function get_stats(int $site_id, string $start_date, string $end_date, string $group, int $page = 0): array
    {
        global $wpdb;

        // Determine date format based on the grouping
        if ($group === 'month') {
            $date_format = '%Y-%m';
        } else {
            $date_format = '%Y-%m-%d';
        }

        // TODO: this needs to be changed to be url (or removed completely)
        // Define the table and join conditions based on whether a page is specified
        if ($page > 0) {
            $table = $wpdb->prefix . 'pp_analytics_post_stats';
            // Adjust join condition based on actual column names
            $join_on = 's.date = d.date AND s.page_id = %d AND s.site_id = %d'; // Adjust column names as needed
            $args = array($date_format, $page, $site_id, $start_date, $end_date);
        } else {
            $table = $wpdb->prefix . 'pp_analytics_site_stats';
            // Adjust join condition based on actual column names
            $join_on = 's.date = d.date AND s.site_id = %d'; // Adjust column names as needed
            $args = array($date_format, $site_id, $start_date, $end_date);
        }

        // Prepare and execute the SQL query
        $sql = $wpdb->prepare(
            "
            SELECT DATE_FORMAT(d.date, %s) AS _date, COALESCE(SUM(visitors), 0) AS visitors, COALESCE(SUM(pageviews), 0) AS pageviews
            FROM {$wpdb->prefix}pp_analytics_dates d
                LEFT JOIN {$table} s ON {$join_on}
            WHERE d.date >= %s AND d.date <= %s
            GROUP BY _date",
            $args
        );
        $result = $wpdb->get_results($sql);

        // Map results to desired format
        return array_map(function ($row) {
            $row->date = $row->_date;
            unset($row->_date);

            $row->pageviews = (int) $row->pageviews;
            $row->visitors  = (int) $row->visitors;
            return $row;
        }, $result);
    }

    // TODO:  url AS post_id not really needed, refactor this
    // modified to use url instead of post/page id as main identifier
    public function get_posts(int $site_id, string $start_date, string $end_date, int $offset = 0, int $limit = 10): array
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            "
            SELECT url, SUM(visitors) AS visitors, SUM(pageviews) AS pageviews
            FROM {$wpdb->prefix}pp_analytics_post_stats
            WHERE date >= %s AND date <= %s AND site_id = %d
            GROUP BY url
            ORDER BY pageviews DESC
            LIMIT %d, %d",
            $start_date,
            $end_date,
            $site_id,
            $offset,
            $limit
        );

        $results = $wpdb->get_results($sql);

        return array_map(function ($row) {

            // special handling of records with URL as post_id
            /*if ($row->post_id === home_url()) {
                $row->post_permalink = home_url();
                $row->post_title     = get_bloginfo('name');
            } else {
                $post = get_page_by_path($row->post_id); // Using get_page_by_path to retrieve the post
                if ($post) {
                    $row->post_title = isset($post->post_title) ? $post->post_title : $post->post_name;
                    $row->post_permalink = get_permalink($post);
                } else {
                    $row->post_title = '(deleted post)';
                    $row->post_permalink = '';
                }
            }*/

            $row->pageviews = (int) $row->pageviews;
            $row->visitors  = (int) $row->visitors;
            return $row;
        }, $results);
    }

    // TODO: converted by chatgtp, needs testing with referrers
    public function get_referrers(int $site_id, string $start_date, string $end_date, int $offset = 0, int $limit = 10): array
    {
        global $wpdb;
        $sql = $wpdb->prepare(
            "
                SELECT s.id, url, SUM(visitors) AS visitors, SUM(pageviews) AS pageviews
                FROM {$wpdb->prefix}pp_analytics_referrer_stats s
                    JOIN {$wpdb->prefix}pp_analytics_referrer_urls r ON r.id = s.id
                WHERE s.date >= %s
                  AND s.date <= %s
                  AND s.site_id = %d
                GROUP BY s.id
                ORDER BY pageviews DESC, r.id ASC
                LIMIT %d, %d",
            array($start_date, $end_date, $site_id, $offset, $limit)
        );
        return $wpdb->get_results($sql);
    }
}
