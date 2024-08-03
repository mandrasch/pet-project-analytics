<?php

/**
 * Sets the default value of the "use cookie" setting to false
 */

add_filter('default_option_pp_analytics_settings', function ($options) {
    $options['use_cookie'] = 0;
    return $options;
});
