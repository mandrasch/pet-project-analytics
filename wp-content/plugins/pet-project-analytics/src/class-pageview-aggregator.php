<?php

/**
 * @package koko-analytics
 * @license GPL-3.0+
 * @author Danny van Kooten
 */

namespace PetProjectAnalytics;

class Pageview_Aggregator
{
    protected $site_id = 0;
    protected $site_stats     = array(
        'visitors' => 0,
        'pageviews' => 0,
    );
    protected $post_stats     = array();
    protected $referrer_stats = array();

    public function line(string $type, array $params)
    {
        // bail if this record doesn't contain data for a pageview
        if ($type !== 'p') {
            return;
        }

        // TODO: this needs refactoring! url instead of post id
        $site_id         = (int) $params[0];
        $page_url         = (string) $params[1]; // TODO: needs to change to url, string
        $new_visitor     = (int) $params[2];
        $unique_pageview = (int) $params[3];
        $referrer_url    = trim((string) $params[4]);

        // Ignore entire line (request) if referrer URL is on blocklist
        if ($referrer_url !== '' && $this->ignore_referrer_url($referrer_url)) {
            return;
        }

        $this->site_id = $site_id;

        // update site stats
        $this->site_stats['pageviews'] += 1;
        if ($new_visitor) {
            $this->site_stats['visitors'] += 1;
        }

        if (!empty($page_url)) {
            if (! isset($this->post_stats[ $page_url ])) {
                $this->post_stats[ $page_url ] = array(
                    'visitors'  => 0,
                    'pageviews' => 0,
                );
            }

            $this->post_stats[ $page_url ]['pageviews'] += 1;

            if ($unique_pageview) {
                $this->post_stats[ $page_url ]['visitors'] += 1;
            }
        }

        // increment referrals
        if ($this->is_valid_url($referrer_url)) {
            $referrer_url = $this->clean_url($referrer_url);
            $referrer_url = $this->normalize_url($referrer_url);

            if (!isset($this->referrer_stats[$referrer_url])) {
                $this->referrer_stats[$referrer_url] = array(
                    'pageviews' => 0,
                    'visitors'  => 0,
                );
            }

            $this->referrer_stats[$referrer_url]['pageviews'] += 1;
            if ($new_visitor) {
                $this->referrer_stats[$referrer_url]['visitors'] += 1;
            }
        }
    }

    public function finish()
    {
        global $wpdb;

        // bail if nothing happened
        if ($this->site_stats['pageviews'] === 0) {
            return;
        }

        // store as local date using the timezone specified in WP settings
        $date = create_local_datetime('now')->format('Y-m-d');

        // insert site stats
        $sql = $wpdb->prepare("INSERT INTO {$wpdb->prefix}pp_analytics_site_stats(site_id, date, visitors, pageviews) VALUES(%d, %s, %d, %d) ON DUPLICATE KEY UPDATE visitors = visitors + VALUES(visitors), pageviews = pageviews + VALUES(pageviews)", array($this->site_id, $date, $this->site_stats['visitors'], $this->site_stats['pageviews']));
        $wpdb->query($sql);

        if (count($this->post_stats) > 0) {
            global $wpdb;
            $values = array();

            foreach ($this->post_stats as $url => $s) {
                // Assuming $this->site_id is properly set and valid
                array_push($values, $date, $url, $this->site_id, $s['visitors'], $s['pageviews']);
            }

            $placeholders = rtrim(str_repeat('(%s,%s,%d,%d,%d),', count($this->post_stats)), ',');

            $sql = $wpdb->prepare(
                "INSERT INTO {$wpdb->prefix}pp_analytics_post_stats(date, url, site_id, visitors, pageviews) VALUES {$placeholders}
                ON DUPLICATE KEY UPDATE visitors = visitors + VALUES(visitors), pageviews = pageviews + VALUES(pageviews)",
                $values
            );

            $wpdb->query($sql);
        }

        // TODO: add site id
        if (count($this->referrer_stats) > 0) {
            // retrieve ID's for known referrer urls
            $referrer_urls = array_keys($this->referrer_stats);
            $placeholders  = rtrim(str_repeat('%s,', count($referrer_urls)), ',');
            $sql           = $wpdb->prepare("SELECT id, url FROM {$wpdb->prefix}pp_analytics_referrer_urls r WHERE r.url IN({$placeholders}) AND r.site_id = %d", array_merge($referrer_urls, [$this->site_id]));
            $results       = $wpdb->get_results($sql);
            foreach ($results as $r) {
                $this->referrer_stats[$r->url]['id'] = $r->id;
            }

            // build query for new referrer urls
            $new_referrer_urls = array();
            foreach ($this->referrer_stats as $url => $r) {
                if (!isset($r['id'])) {
                    $new_referrer_urls[] = $url;
                }
            }

            // TODO: check if this works
            // insert new referrer urls and set ID in map
            if (count($new_referrer_urls) > 0) {
                $values       = $new_referrer_urls;
                $placeholders = rtrim(str_repeat('(%s),', count($values)), ',');
                $sql          = $wpdb->prepare(
                    "INSERT INTO {$wpdb->prefix}pp_analytics_referrer_urls(url, site_id) VALUES {$placeholders}",
                    array_merge(...array_map(fn ($url) => [$url, $this->site_id], $values))
                );
                $wpdb->query($sql);
                $last_insert_id = $wpdb->insert_id;
                foreach (array_reverse($values) as $url) {
                    $this->referrer_stats[$url]['id'] = $last_insert_id--;
                }
            }

            // Insert referrer stats
            $values = array();
            foreach ($this->referrer_stats as $referrer_url => $r) {
                array_push($values, $date, $this->site_id, $r['id'], $r['visitors'], $r['pageviews']);
            }
            $placeholders = rtrim(str_repeat('(%s,%d,%d,%d),', count($this->referrer_stats)), ',');
            $sql          = $wpdb->prepare(
                "INSERT INTO {$wpdb->prefix}pp_analytics_referrer_stats(date, site_id, visitors, pageviews) VALUES {$placeholders} ON DUPLICATE KEY UPDATE visitors = visitors + VALUES(visitors), pageviews = pageviews + VALUES(pageviews)",
                $values
            );
            $wpdb->query($sql);
        }

        $this->update_realtime_pageview_count($this->site_stats['pageviews']);

        // reset properties in case aggregation runs again in current request lifecycle
        $this->site_stats = array(
            'visitors' => 0,
            'pageviews' => 0,
        );
        $this->referrer_stats = array();
        $this->post_stats     = array();
    }

    private function update_realtime_pageview_count(int $pageviews)
    {
        $counts       = (array) get_option('pp_analytics_realtime_pageview_count', array());
        $one_hour_ago = strtotime('-60 minutes');

        foreach ($counts as $timestamp => $count) {
            // delete all data older than one hour
            if ((int) $timestamp < $one_hour_ago) {
                unset($counts[$timestamp]);
            }
        }

        // add pageviews for this minute
        $counts[(string) time()] = $pageviews;
        update_option('pp_analytics_realtime_pageview_count', $counts, false);
    }

    private function ignore_referrer_url(string $url)
    {
        // read blocklist into array
        static $blocklist = null;
        if ($blocklist === null) {
            $blocklist = file(PP_ANALYTICS_PLUGIN_DIR . '/data/referrer-blocklist', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            // add result of filter hook to blocklist so user can provide custom domains to block through simple array
            // @see https://github.com/ibericode/koko-analytics/blob/master/code-snippets/add-domains-to-referrer-blocklist.php
            $custom_blocklist = apply_filters('pp_analytics_referrer_blocklist', array());
            $blocklist        = array_merge($blocklist, $custom_blocklist);
        }

        foreach ($blocklist as $blocklisted_domain) {
            if (false !== stripos($url, $blocklisted_domain)) {
                return true;
            }
        }

        // run return value through filter so user can apply more advanced logic to determine whether to ignore referrer  url
        // @see https://github.com/ibericode/koko-analytics/blob/master/code-snippets/ignore-some-referrer-traffic-using-regex.php
        return apply_filters('pp_analytics_ignore_referrer_url', false, $url);
    }

    public function clean_url(string $url)
    {
        // remove # from URL
        $pos = strpos($url, '#');
        if ($pos !== false) {
            $url = substr($url, 0, $pos);
        }

        // if URL contains query string, parse it and only keep certain parameters
        $pos = strpos($url, '?');
        if ($pos !== false) {
            $query_str = substr($url, $pos + 1);

            $params = array();
            parse_str($query_str, $params);

            // strip all but the following query parameters from the URL
            $allowed_params = array('page_id', 'p', 'cat', 'product');
            $new_params     = array_intersect_key($params, array_flip($allowed_params));
            $new_query_str  = http_build_query($new_params);
            $new_url        = substr($url, 0, $pos + 1) . $new_query_str;

            // trim trailing question mark & replace url with new sanitized url
            $url = rtrim($new_url, '?');
        }

        // trim trailing slash
        return rtrim($url, '/');
    }

    public function normalize_url(string $url)
    {
        // if URL has no protocol, assume HTTP
        // we change this to HTTPS for sites that are known to support it
        if (strpos($url, '://') === false) {
            $url = 'http://' . $url;
        }

        $aggregations = array(
            '/^android-app:\/\/com\.(www\.)?google\.android\.googlequicksearchbox(\/.+)?$/' => 'https://www.google.com',
            '/^android-app:\/\/com\.www\.google\.android\.gm$/' => 'https://www.google.com',
            '/^https?:\/\/(?:www\.)?(google|bing|ecosia)\.([a-z]{2,3}(?:\.[a-z]{2,3})?)(?:\/search|\/url)?/' => 'https://www.$1.$2',
            '/^android-app:\/\/com\.facebook\.(.+)/' => 'https://facebook.com',
            '/^https?:\/\/(?:[a-z-]+)?\.?l?facebook\.com(?:\/l\.php)?/' => 'https://facebook.com',
            '/^https?:\/\/(?:[a-z-]+)?\.?l?instagram\.com(?:\/l\.php)?/' => 'https://www.instagram.com',
            '/^https?:\/\/(?:www\.)?linkedin\.com\/feed.*/' => 'https://www.linkedin.com',
            '/^https?:\/\/(?:www\.)?pinterest\.com\//' => 'https://pinterest.com/',
            '/(?:www|m)\.baidu\.com.*/' => 'www.baidu.com',
            '/yandex\.ru\/clck.*/' => 'yandex.ru',
            '/^https?:\/\/(?:[a-z-]+)?\.?search\.yahoo\.com\/(?:search)?[^?]*(.*)/' => 'https://search.yahoo.com/search$1',
            '/^https?:\/\/(out|new|old)\.reddit\.com(.*)/' => 'https://reddit.com$2',
            '/^https?:\/\/(?:[a-z0-9]+\.?)*\.sendib(?:m|t)[0-9].com(?:.*)/' => 'https://www.brevo.com',
        );

        $aggregations = apply_filters('pp_analytics_url_aggregations', $aggregations);

        return preg_replace(array_keys($aggregations), array_values($aggregations), $url, 1);
    }

    public function is_valid_url(string $url)
    {
        if ($url === '' || strlen($url) < 4) {
            return false;
        }

        return filter_var($url, FILTER_VALIDATE_URL);
    }
}
