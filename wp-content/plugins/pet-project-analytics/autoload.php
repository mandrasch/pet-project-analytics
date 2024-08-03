<?php

require __DIR__ . '/src/functions.php';
require __DIR__ . '/src/global-functions.php';

spl_autoload_register(function($class) {
    static $classmap = [
        'PetProjectAnalytics\\Admin' => __DIR__ . '/src/class-admin.php',
        // our own site management
        'PetProjectAnalytics\\AdminSiteManagement' => __DIR__ . '/src/class-admin-site-management.php',
        'PetProjectAnalytics\\Aggregator' => __DIR__ . '/src/class-aggregator.php',
        'PetProjectAnalytics\\Command' => __DIR__ . '/src/class-command.php',
        'PetProjectAnalytics\\Dashboard' => __DIR__ . '/src/class-dashboard.php',
        'PetProjectAnalytics\\Dashboard_Widget' => __DIR__ . '/src/class-dashboard-widget.php',
        'PetProjectAnalytics\\Dates' => __DIR__ . '/src/class-dates.php',
        'PetProjectAnalytics\\Endpoint_Installer' => __DIR__ . '/src/class-endpoint-installer.php',
        'PetProjectAnalytics\\Migrations' => __DIR__ . '/src/class-migrations.php',
        'PetProjectAnalytics\\Pageview_Aggregator' => __DIR__ . '/src/class-pageview-aggregator.php',
        'PetProjectAnalytics\\Plugin' => __DIR__ . '/src/class-plugin.php',
        'PetProjectAnalytics\\Pruner' => __DIR__ . '/src/class-pruner.php',
        'PetProjectAnalytics\\Rest' => __DIR__ . '/src/class-rest.php',
        'PetProjectAnalytics\\Script_Loader' => __DIR__ . '/src/class-script-loader.php',
        'PetProjectAnalytics\\ShortCode_Site_Counter' => __DIR__ . '/src/class-shortcode-site-counter.php',
        'PetProjectAnalytics\\Shortcode_Most_Viewed_Posts' => __DIR__ . '/src/class-shortcode-most-viewed-posts.php',
        'PetProjectAnalytics\\Stats' => __DIR__ . '/src/class-stats.php',
        'PetProjectAnalytics\\Widget_Most_Viewed_Posts' => __DIR__ . '/src/class-widget-most-viewed-posts.php',
    ];

    if (isset($classmap[$class])) {
        require $classmap[$class];
    }
});
