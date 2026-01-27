<?php
/**
 * Admin menu registration for the School plugin.
 *
 * @package School
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the admin menu.
 */
function school_register_admin_menu() {
	add_menu_page(
		'المدرسة',
		'المدرسة',
		'read',
		'school-dashboard',
		'school_render_teacher_dashboard',
		'dashicons-welcome-learn-more',
		6
	);

	add_submenu_page(
		'school-dashboard',
		'لوحة تحكم المعلم',
		'لوحة المعلم',
		'school_view_own_lessons',
		'school-dashboard',
		'school_render_teacher_dashboard'
	);

	add_submenu_page(
		'school-dashboard',
		'لوحة تحكم المنسق',
		'لوحة المنسق',
		'school_view_subject_lessons',
		'school-coordinator',
		'school_admin_render_coordinator_dashboard'
	);

	add_submenu_page(
		'school-dashboard',
		'لوحة تحكم المشرف',
		'لوحة المشرف',
		'school_view_all_reports',
		'school-supervisor',
		'school_admin_render_supervisor_dashboard'
	);

	add_submenu_page(
		'school-dashboard',
		'الإعدادات',
		'الإعدادات',
		'school_manage_settings',
		'school-settings',
		'school_render_settings_page'
	);
}
add_action( 'admin_menu', 'school_register_admin_menu' );

/**
 * Placeholder for Teacher Dashboard rendering.
 */
function school_render_teacher_dashboard() {
	include SCHOOL_PLUGIN_DIR . 'templates/teacher-dashboard.php';
}
