<?php

namespace PetProjectAnalytics;

class AdminSiteManagement
{
    public function __construct()
    {
        // Hook into admin_menu to register the menu and submenu
        add_action('admin_menu', array($this, 'register_menu'));
    }

    public function register_menu(): void
    {
        // Add a top-level menu
        add_menu_page(
            esc_html__('Site Management', 'pp-analytics'),
            esc_html__('Site Management', 'pp-analytics'),
            'manage_options', // Capability
            'site-management', // Menu slug
            array($this, 'show_page'), // Callback function to display the page content
            'dashicons-admin-site' // Icon URL
        );

        // Add sub-menu pages
        add_submenu_page(
            'site-management', // Parent slug
            esc_html__('Add New Site', 'pp-analytics'),
            esc_html__('Add New Site', 'pp-analytics'),
            'manage_options',
            'add-new-site', // Submenu slug
            array($this, 'add_new_site_page') // Callback function to display the page content
        );

        add_submenu_page(
            'site-management', // Parent slug
            esc_html__('View Site', 'pp-analytics'),
            esc_html__('View Site', 'pp-analytics'),
            'manage_options',
            'view-site', // Submenu slug
            array($this, 'view_site_page') // Callback function to display the page content
        );
    }

    public function show_page(): void
    {
        // Handle deletion of a site
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['site_id'])) {
            $site_id = intval($_GET['site_id']);
            global $wpdb;
            $table_name = $wpdb->prefix . 'pp_analytics_sites';

            if ($wpdb->delete($table_name, array('id' => $site_id))) {
                wp_safe_redirect(add_query_arg('msg', 'deleted', 'admin.php?page=site-management'));
                exit; // Ensure no further code is executed after redirect
            } else {
                wp_safe_redirect(add_query_arg('msg', 'error', 'admin.php?page=site-management'));
                exit; // Ensure no further code is executed after redirect
            }
        }

        // Display the main content of the Site Management page
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Site Management', 'pp-analytics') . '</h1>';

        // Check for message query parameter and display corresponding message
        if (isset($_GET['msg'])) {
            if ($_GET['msg'] === 'success') {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Site added successfully.', 'pp-analytics') . '</p></div>';
            } elseif ($_GET['msg'] === 'error') {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Failed to add site. Please try again.', 'pp-analytics') . '</p></div>';
            } elseif ($_GET['msg'] === 'deleted') {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Site deleted successfully.', 'pp-analytics') . '</p></div>';
            }
        }

        // Display the list of sites
        global $wpdb;
        $table_name = $wpdb->prefix . 'pp_analytics_sites';
        $sites = $wpdb->get_results("SELECT * FROM $table_name");

        if ($sites) {
            echo '<table class="widefat fixed">';
            echo '<thead><tr><th>' . esc_html__('Site Title', 'pp-analytics') . '</th><th>' . esc_html__('Site Domain', 'pp-analytics') . '</th><th>' . esc_html__('Actions', 'pp-analytics') . '</th></tr></thead>';
            echo '<tbody>';
            foreach ($sites as $site) {
                echo '<tr>';
                echo '<td>' . esc_html($site->title) . '</td>';
                echo '<td>' . esc_html($site->domain) . '</td>';
                echo '<td>';
                echo '<a href="' . esc_url(add_query_arg(array('action' => 'delete', 'site_id' => $site->id), 'admin.php?page=site-management')) . '" onclick="return confirm(\'' . esc_js(__('Are you sure you want to delete this site?', 'pp-analytics')) . '\');">' . esc_html__('Delete', 'pp-analytics') . '</a> | ';
                echo '<a href="' . esc_url(add_query_arg(array('site_id' => $site->id), 'admin.php?page=view-site')) . '">' . esc_html__('View', 'pp-analytics') . '</a>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . esc_html__('No sites found.', 'pp-analytics') . '</p>';
        }

        echo '</div>';
    }

    public function add_new_site_page(): void
    {
        echo '<div class="wrap"><h1>' . esc_html__('Add New Site', 'pp-analytics') . '</h1>';
        // Content for the "Add New Site" page
        echo '</div>';
    }

    public function view_site_page(): void
    {
        // Handle viewing of a site
        if (isset($_GET['site_id'])) {
            $site_id = intval($_GET['site_id']);
            global $wpdb;
            $table_name = $wpdb->prefix . 'pp_analytics_sites';
            $site = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $site_id));

            if ($site) {
                echo '<div class="wrap">';
                echo '<h1>' . esc_html__('View Site', 'pp-analytics') . '</h1>';
                echo '<table class="form-table">';
                echo '<tr><th>' . esc_html__('Site Title', 'pp-analytics') . '</th><td>' . esc_html($site->title) . '</td></tr>';
                echo '<tr><th>' . esc_html__('Site Domain', 'pp-analytics') . '</th><td>' . esc_html($site->domain) . '</td></tr>';
                echo '<tr><th>' . esc_html__('Site Description', 'pp-analytics') . '</th><td>' . esc_html($site->description) . '</td></tr>';
                echo '</table>';
                echo '</div>';
            } else {
                echo '<div class="wrap"><h1>' . esc_html__('Site Not Found', 'pp-analytics') . '</h1>';
                echo '<p>' . esc_html__('The site you are trying to view does not exist.', 'pp-analytics') . '</p></div>';
            }
        } else {
            wp_safe_redirect('admin.php?page=site-management');
            exit; // Ensure no further code is executed after redirect
        }
    }
}
