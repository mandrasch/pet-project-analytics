<?php

defined('ABSPATH') or exit;

global $wpdb;

// This was forked, we added a new _sites table with foreign keys. I tried to gather all changes from migrations here to start fresh

// Drop existing tables if they exist
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pp_analytics_sites");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pp_analytics_site_stats");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pp_analytics_referrer_urls");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pp_analytics_referrer_stats");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pp_analytics_dates");

// Create the updated sites table
$wpdb->query(
    "CREATE TABLE {$wpdb->prefix}pp_analytics_sites (
       id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
       title VARCHAR(255) NOT NULL,
       domain VARCHAR(255) NOT NULL,
       UNIQUE INDEX (domain),
       PRIMARY KEY (id)
    ) ENGINE=INNODB CHARACTER SET=ascii"
);

// Create the updated site_stats table with foreign key reference
$wpdb->query(
    "CREATE TABLE {$wpdb->prefix}pp_analytics_site_stats (
       date DATE NOT NULL,
       visitors MEDIUMINT UNSIGNED NOT NULL,
       pageviews MEDIUMINT UNSIGNED NOT NULL,
       site_id MEDIUMINT UNSIGNED NOT NULL,
       PRIMARY KEY (date, site_id),
       FOREIGN KEY (site_id) REFERENCES {$wpdb->prefix}pp_analytics_sites (id) ON DELETE CASCADE
    ) ENGINE=INNODB CHARACTER SET=ascii"
);

// Create the updated referrer_urls table with foreign key reference
$wpdb->query(
    "CREATE TABLE {$wpdb->prefix}pp_analytics_referrer_urls (
       id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
       url VARCHAR(255) NOT NULL,
       site_id MEDIUMINT UNSIGNED NOT NULL,
       PRIMARY KEY (id),
       UNIQUE INDEX (url),
       FOREIGN KEY (site_id) REFERENCES {$wpdb->prefix}pp_analytics_sites (id) ON DELETE CASCADE
    ) ENGINE=INNODB CHARACTER SET=ascii"
);

// Create the updated referrer_stats table with foreign key reference
$wpdb->query(
    "CREATE TABLE {$wpdb->prefix}pp_analytics_referrer_stats (
       date DATE NOT NULL,
       id MEDIUMINT UNSIGNED NOT NULL,
       visitors MEDIUMINT UNSIGNED NOT NULL,
       pageviews MEDIUMINT UNSIGNED NOT NULL,
       site_id MEDIUMINT UNSIGNED NOT NULL,
       PRIMARY KEY (date, id, site_id),
       FOREIGN KEY (site_id) REFERENCES {$wpdb->prefix}pp_analytics_sites (id) ON DELETE CASCADE
    ) ENGINE=INNODB CHARACTER SET=ascii"
);

// Create the dates table
$wpdb->query(
    "CREATE TABLE {$wpdb->prefix}pp_analytics_dates (
       date DATE PRIMARY KEY NOT NULL
    ) ENGINE=INNODB CHARACTER SET=ascii"
);

// Populate the dates table
$date   = new \DateTime('2000-01-01');
$end    = new \DateTime('2100-01-01');
$values = array();
while ($date < $end) {
    $values[] = $date->format('Y-m-d');
    $date->modify('+1 day');

    if (count($values) === 365) {
        $placeholders = rtrim(str_repeat('(%s),', count($values)), ',');
        $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}pp_analytics_dates(date) VALUES {$placeholders}", $values));
        $values = array();
    }
}
if (!empty($values)) {
    $placeholders = rtrim(str_repeat('(%s),', count($values)), ',');
    $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}pp_analytics_dates(date) VALUES {$placeholders}", $values));
}

// Set AUTO_INCREMENT to a higher value than the max id in the referrer_urls table if needed
$max_id = (int) $wpdb->get_var("SELECT MAX(id) FROM {$wpdb->prefix}pp_analytics_referrer_urls");
$max_id++;
$query = $wpdb->prepare("ALTER TABLE {$wpdb->prefix}pp_analytics_referrer_urls AUTO_INCREMENT = %d", $max_id);
$wpdb->query($query);
