<?php

defined('ABSPATH') or exit;

$role = get_role('administrator');
if ($role) {
    $role->add_cap('view_pp_analytics');
    $role->add_cap('manage_pp_analytics');
}
