<?php

/**
 * @package koko-analytics
 * @license GPL-3.0+
 * @author Danny van Kooten
 */

 // TODO: remove - not needed ...
/**
 * Prints the Koko Analytics tracking script.
 *
 * You should only need to call this manually if your theme does not use the `wp_head()` and `wp_footer()` functions.
 *
 * @since 1.0.25
 */
function koko_analyics_tracking_script(): void
{
    $script_loader = new PetProjectAnalytics\Script_Loader();
    $script_loader->maybe_enqueue_script(true);
}

/**
 * Returns an array of the most viewed posts/pages or other post types.
 *
 * Arguments:
 *  `number`    => The number of results to return
 *  `post_type` => A single post type or an array of post types to return
 *  `days`      => Specified the last X number of days for which the most viewed posts should be returned
 *
 * @param array $args
 * @return array
 * @since 1.1
 */
function pp_analytics_get_most_viewed_posts(array $args = array()): array
{
    return PetProjectAnalytics\get_most_viewed_posts($args);
}


/**
 * Returns the number of realtime pageviews, for example in the last hour or in the last 5 minutes.
 * Does not work with timestamps over 1 hour ago.
 *
 * Examples:
 *  pp_analytics_get_realtime_pageview_count('-5 minutes');
 *  pp_analytics_get_realtime_pageview_count('-1 hour');
 *
 * @since 1.1
 * @param null|string|int $since An integer timestamp (seconds since Unix epoch) or a relative time string in the format that strtotime() understands. Defaults to "-5 minutes"
 * @return int
 * @see strtotime
 */
function pp_analytics_get_realtime_pageview_count($since = '-5 minutes'): int
{
    return PetProjectAnalytics\get_realtime_pageview_count($since);
}

/**
 * Writes a new pageview to the buffer file, to be aggregated during the next time `pp_analytics_aggregate_stats` runs.
 *
 * @param int $post_id The post ID to increment the pageviews count for.
 * @param bool $new_visitor Whether this is a new site visitor.
 * @param bool $unique_pageview Whether this was an unique pageview. (Ie the first time this visitor views this page today).
 * @param string $referrer_url External URL that this visitor came from, or empty string if direct traffic or coming from internal link.
 * @return bool
 * @since 1.1
 */
function pp_analytics_track_pageview(int $post_id, bool $new_visitor = false, bool $unique_pageview = false, string $referrer_url = ''): bool
{
    $data = array(
        'p',
        $post_id,
        (int) $new_visitor,
        (int) $unique_pageview,
        $referrer_url,
    );
    return PetProjectAnalytics\collect_in_file($data);
}
