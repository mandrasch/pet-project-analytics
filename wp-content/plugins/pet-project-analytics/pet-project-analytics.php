<?php

/*
Plugin Name: Pet Project Analytics
Plugin URI:
Version: 0.9.0
Description: Privacy-friendly analytics for your pet projects, easily within WordPress.
Author: mandrasch
Author URI: https://mandrasch.dev
Author Email:
Text Domain: pp-analytics
License: GPL-3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.txt

Pet Project Analytics - website analytics plugin for WordPress

This is a fork of

Koko Analytics - website analytics plugin for WordPress

Copyright (C) 2019 - 2024, Danny van Kooten, hi@dannyvankooten.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

phpcs:disable PSR1.Files.SideEffects
*/

namespace PetProjectAnalytics;

\define('PP_ANALYTICS_VERSION', '0.9.0');
\define('pp_analytics_PLUGIN_FILE', __FILE__);
\define('pp_analytics_PLUGIN_DIR', __DIR__);

// Load autoloader
require __DIR__ . '/autoload.php';

if (\defined('DOING_AJAX') && DOING_AJAX) {
    maybe_collect_request();
} elseif (is_admin()) {
    new Admin();
    new Dashboard_Widget();
} else {
    new Script_Loader();
    add_action('admin_bar_menu', 'PetProjectAnalytics\admin_bar_menu', 40, 1);
}

new Dashboard();
$aggregator = new Aggregator();
new Plugin($aggregator);
new Rest();
new Shortcode_Most_Viewed_Posts();
new ShortCode_Site_Counter();
new Pruner();

if (\class_exists('WP_CLI')) {
    \WP_CLI::add_command('pp-analytics', 'PetProjectAnalytics\Command');
}

add_action('widgets_init', 'PetProjectAnalytics\widgets_init');
add_action('pp_analytics_test_custom_endpoint', 'PetProjectAnalytics\test_custom_endpoint');
