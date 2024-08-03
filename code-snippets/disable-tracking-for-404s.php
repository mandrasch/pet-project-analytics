<?php

add_filter('pp_analytics_load_tracking_script', function ($load) {
    return $load && ! is_404();
});
