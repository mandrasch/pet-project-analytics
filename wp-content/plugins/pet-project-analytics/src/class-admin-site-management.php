<?php

namespace PetProjectAnalytics;

function generate_unique_token(): string
{
    return wp_generate_password(32, false, false); // Generates a 32-character random string
}

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
            esc_html__('PP Analytics', 'pp-analytics'),
            esc_html__('PP Analytics', 'pp-analytics'),
            'manage_options', // Capability
            'pp-analytics', // Menu slug
            array($this, 'show_page'), // Callback function to display the page content
            'dashicons-pets' // Icon URL
        );

        add_submenu_page(
            'pp-analytics', // Parent slug
            esc_html__('All Sites', 'pp-analytics'),
            esc_html__('All Sites', 'pp-analytics'),
            'manage_options',
            'pp-analytics', // Submenu slug
            array($this, 'show_page') // Callback function to display the page content
        );

        // TODO: rename to pp-analytics-add-site, check other plugins?

        // Add sub-menu page for adding a new site
        add_submenu_page(
            'pp-analytics', // Parent slug
            esc_html__('Add New Site', 'pp-analytics'),
            esc_html__('Add New Site', 'pp-analytics'),
            'manage_options',
            'pp-analytics-add-new-site', // Submenu slug
            array($this, 'add_new_site_page') // Callback function to display the page content
        );
    }

    public function show_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'pp_analytics_sites';

        // Handle delete action
        if (isset($_GET['action']) && $_GET['action'] === 'deleteSite' && isset($_GET['site_id']) && isset($_GET['_wpnonce'])) {
            if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_site_nonce')) {
                wp_die(__('Security check failed.', 'pp-analytics'));
            }

            $site_id = intval($_GET['site_id']);
            $this->delete_site($site_id);
            wp_safe_redirect(add_query_arg('page', 'pp-analytics', admin_url('admin.php')));
            exit;
        }

        // Handle view action
        if (isset($_GET['action']) && $_GET['action'] === 'viewSite' && isset($_GET['site_id'])) {
            $site_id = intval($_GET['site_id']);
            $site = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $site_id));

            if ($site) {
                echo '<div class="wrap">';
                echo '<h1>' . esc_html__('View Site', 'pp-analytics') . '</h1>';
                echo '<table class="form-table">';
                echo '<tr><th>' . esc_html__('Site Title', 'pp-analytics') . '</th><td>' . esc_html($site->title) . '</td></tr>';
                echo '<tr><th>' . esc_html__('Site Domain', 'pp-analytics') . '</th><td>' . esc_html($site->domain) . '</td></tr>';
                echo '<tr><th>' . esc_html__('Tracking Token', 'pp-analytics') . '</th><td>' . esc_html($site->tracking_token) . '</td></tr>';
                echo '</table>';
                echo '<p><a href="' . esc_url(add_query_arg(array('action' => 'viewSite', 'page' => 'pp-analytics'), admin_url('admin.php'))) . '">' . esc_html__('Back to Site Management', 'pp-analytics') . '</a></p>';
                echo '</div>';
            } else {
                echo '<div class="wrap"><h1>' . esc_html__('Site Not Found', 'pp-analytics') . '</h1>';
                echo '<p>' . esc_html__('The site you are trying to view does not exist.', 'pp-analytics') . '</p>';
                echo '<p><a href="' . esc_url(admin_url('admin.php?page=pp-analytics')) . '">' . esc_html__('Back to Site Management', 'pp-analytics') . '</a></p>';
                echo '</div>';
            }
            return; // Exit after displaying the view page
        }

        // Display the list of sites
        $sites = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pp_analytics_sites");

        echo '<div class="wrap"><h1>' . esc_html__('Site Management', 'pp-analytics') . '</h1>';
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>' . esc_html__('Title', 'pp-analytics') . '</th><th>' . esc_html__('Domain', 'pp-analytics') . '</th><th>' . esc_html__('Actions', 'pp-analytics') . '</th></tr></thead>';
        echo '<tbody>';
        foreach ($sites as $site) {
            echo '<tr>';
            echo '<td>' . esc_html($site->title) . '</td>';
            echo '<td>' . esc_html($site->domain) . '</td>';
            echo '<td>';
            echo '<a href="' . esc_url(add_query_arg(array('action' => 'viewSite', 'site_id' => $site->id), admin_url('admin.php?page=pp-analytics'))) . '">' . esc_html__('View', 'pp-analytics') . '</a> | ';
            echo '<a href="' . esc_url(add_query_arg(array('action' => 'deleteSite', 'site_id' => $site->id, '_wpnonce' => wp_create_nonce('delete_site_nonce')), admin_url('admin.php?page=pp-analytics'))) . '" onclick="return confirm(\'' . esc_js(__('Are you sure you want to delete this site?', 'pp-analytics')) . '\');">' . esc_html__('Delete', 'pp-analytics') . '</a>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }


    public function add_new_site_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pp_analytics_site_title'], $_POST['pp_analytics_site_domain'], $_POST['_wpnonce'])) {
            if (!wp_verify_nonce($_POST['_wpnonce'], 'add_new_site_nonce')) {
                wp_die(__('Security check failed.', 'pp-analytics'));
            }

            $title = sanitize_text_field($_POST['pp_analytics_site_title']);
            $domain = sanitize_text_field($_POST['pp_analytics_site_domain']);
            $tracking_token = generate_unique_token(); // Generate a unique token

            // Insert new site into the database
            global $wpdb;
            $wpdb->insert(
                "{$wpdb->prefix}pp_analytics_sites",
                array(
                    'title' => $title,
                    'domain' => $domain,
                    'tracking_token' => $tracking_token,
                ),
                array('%s', '%s', '%s')
            );

            // TODO: this did not work? header error?
            // Start output buffering
            //ob_start();
            // Redirect to prevent form resubmission
            // wp_safe_redirect(admin_url('admin.php?page=pp-analytics'));

            echo '<script type="text/javascript">window.location = "' . admin_url('admin.php?page=pp-analytics') . '";</script>';
            exit;

        }

        echo '<div class="wrap"><h1>' . esc_html__('Add New Site', 'pp-analytics') . '</h1>';
        echo '<form method="post">';
        wp_nonce_field('add_new_site_nonce');
        echo '<table class="form-table">';
        echo '<tr valign="top"><th scope="row">' . esc_html__('Title', 'pp-analytics') . '</th><td><input type="text" name="pp_analytics_site_title" required /></td></tr>';
        echo '<tr valign="top"><th scope="row">' . esc_html__('Domain', 'pp-analytics') . '</th><td><input type="text" name="pp_analytics_site_domain" required /></td></tr>';
        echo '</table>';
        echo '<p><input type="submit" value="' . esc_attr__('Add Site', 'pp-analytics') . '" class="button-primary" /></p>';
        echo '</form>';
        echo '</div>';
    }


    public function view_site_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

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
                echo '<tr><th>' . esc_html__('Tracking Token', 'pp-analytics') . '</th><td>' . esc_html($site->tracking_token) . '</td></tr>';
                echo '</table>';
                echo '</div>';
            } else {
                echo '<div class="wrap"><h1>' . esc_html__('Site Not Found', 'pp-analytics') . '</h1>';
                echo '<p>' . esc_html__('The site you are trying to view does not exist.', 'pp-analytics') . '</p></div>';
            }
        } else {
            wp_safe_redirect('admin.php?page=pp-analytics');
            exit; // Ensure no further code is executed after redirect
        }
    }

    private function delete_site(int $site_id): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'pp_analytics_sites';
        $wpdb->delete($table_name, array('id' => $site_id), array('%d'));

    }
}
