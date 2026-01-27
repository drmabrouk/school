<?php
/**
 * Coordinator dashboard for the School plugin (Admin Wrapper).
 *
 * @package School
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin wrapper for coordinator dashboard.
 */
function school_admin_render_coordinator_dashboard() {
	echo '<div class="wrap school-admin-wrap">';
	echo '<h1>' . get_admin_page_title() . '</h1>';
	school_render_coordinator_dashboard();
	echo '</div>';
}
