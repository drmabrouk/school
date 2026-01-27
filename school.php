<?php
/**
 * Plugin Name: School
 * Plugin URI:  https://example.com/school
 * Description: نظام إدارة تحضير الدروس اليومية والتقارير التعليمية في المدارس.
 * Version:     1.0.0
 * Author:      Jules
 * Author URI:  https://example.com
 * Text Domain: school-plugin
 * Domain Path: /languages
 * License:     GPL2
 *
 * @package School
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define constants.
define( 'SCHOOL_PLUGIN_VERSION', '1.0.0' );
define( 'SCHOOL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SCHOOL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Initialize the plugin.
 */
function school_init() {
	load_plugin_textdomain( 'school-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	
	// Disable standard WordPress registration system as requested.
	if ( (int) get_option( 'users_can_register' ) !== 0 ) {
		update_option( 'users_can_register', 0 );
	}
}
add_filter( 'option_users_can_register', '__return_zero' );
add_action( 'plugins_loaded', 'school_init' );

// Include necessary files.
require_once SCHOOL_PLUGIN_DIR . 'includes/db.php';
require_once SCHOOL_PLUGIN_DIR . 'includes/roles.php';
require_once SCHOOL_PLUGIN_DIR . 'includes/lessons.php';
require_once SCHOOL_PLUGIN_DIR . 'includes/cron.php';
require_once SCHOOL_PLUGIN_DIR . 'includes/notifications.php';
require_once SCHOOL_PLUGIN_DIR . 'includes/shortcodes.php';
require_once SCHOOL_PLUGIN_DIR . 'includes/frontend-dashboards.php';
require_once SCHOOL_PLUGIN_DIR . 'includes/management-handler.php';

require_once SCHOOL_PLUGIN_DIR . 'admin/coordinator-dashboard.php';
require_once SCHOOL_PLUGIN_DIR . 'admin/supervisor-dashboard.php';

if ( is_admin() ) {
	require_once SCHOOL_PLUGIN_DIR . 'admin/admin-menu.php';
	require_once SCHOOL_PLUGIN_DIR . 'admin/settings.php';
	require_once SCHOOL_PLUGIN_DIR . 'admin/alerts.php';
}

/**
 * Plugin activation hook.
 */
function school_activate() {
	school_create_db_tables();
	school_add_custom_roles();
	school_schedule_cron();
	school_create_pages();
	flush_rewrite_rules();
}

/**
 * Automatically create required pages.
 */
function school_create_pages() {
	$pages = array(
		'login' => array(
			'title'   => 'تسجيل الدخول',
			'content' => '[school_login]',
		),
		'dashboard' => array(
			'title'   => 'لوحة التحكم',
			'content' => '[school_dashboard]',
		),
		'upload-lesson' => array(
			'title'   => 'تحميل التحضير',
			'content' => '[school_file_upload]',
		),
		'teacher-portal' => array(
			'title'   => 'بوابة المعلمين',
			'content' => '[school_teacher_portal]',
		),
	);

	foreach ( $pages as $slug => $data ) {
		$query = new WP_Query( array(
			'post_type'      => 'page',
			'name'           => $slug,
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		) );

		if ( ! $query->have_posts() ) {
			wp_insert_post( array(
				'post_title'   => $data['title'],
				'post_content' => $data['content'],
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_name'    => $slug,
			) );
		}
	}
}
register_activation_hook( __FILE__, 'school_activate' );

/**
 * Plugin deactivation hook.
 */
function school_deactivate() {
	school_clear_cron_tasks();
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'school_deactivate' );

/**
 * Enqueue styles and scripts.
 */
function school_enqueue_assets() {
	wp_enqueue_style( 'dashicons' );
	wp_enqueue_style( 'school-style', SCHOOL_PLUGIN_URL . 'assets/css/style.css', array( 'dashicons' ), SCHOOL_PLUGIN_VERSION );
	
	if ( is_page('dashboard') || is_admin() ) {
		wp_enqueue_media();
		wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.1', true );
		wp_enqueue_script( 'school-notifications', SCHOOL_PLUGIN_URL . 'assets/js/notifications.js', array('jquery'), SCHOOL_PLUGIN_VERSION, true );
		$notif_settings = get_option( 'school_notification_settings', array( 'browser_alerts' => true ) );
		wp_localize_script( 'school-notifications', 'schoolData', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'school_realtime_nonce' ),
			'browser_alerts' => !empty($notif_settings['browser_alerts'])
		));
	}
}
add_action( 'wp_enqueue_scripts', 'school_enqueue_assets' );
add_action( 'admin_enqueue_scripts', 'school_enqueue_assets' );

/**
 * Add a body class for the teacher portal.
 */
function school_portal_body_class( $classes ) {
	if ( is_page( 'teacher-portal' ) || is_page( 'upload-lesson' ) ) {
		$classes[] = 'school-portal-page';
	}
	return $classes;
}
add_filter( 'body_class', 'school_portal_body_class' );
