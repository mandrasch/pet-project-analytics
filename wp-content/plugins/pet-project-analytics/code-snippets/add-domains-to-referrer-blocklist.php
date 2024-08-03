<?php

add_filter('pp_analytics_referrer_blocklist', function () {
    return array(
        'search.myway.com',
        'bad-website.com',
    );
});
