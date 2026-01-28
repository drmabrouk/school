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

	// Hide admin bar for all roles except System Administrator.
	if ( ! current_user_can( 'administrator' ) ) {
		show_admin_bar( false );
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
	wp_enqueue_style( 'google-fonts-rubik', 'https://fonts.googleapis.com/css2?family=Rubik:ital,wght@0,300..900;1,300..900&display=swap', array(), null );
	wp_enqueue_style( 'school-style', SCHOOL_PLUGIN_URL . 'assets/css/style.css', array( 'dashicons', 'google-fonts-rubik' ), SCHOOL_PLUGIN_VERSION );
	
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
 * Output dynamic design CSS based on settings.
 */
function school_output_design_css() {
	$primary = get_option('school_design_primary', '#F63049');
	$secondary = get_option('school_design_secondary', '#D02752');
	$accent1 = get_option('school_design_accent_1', '#8A244B');
	$accent2 = get_option('school_design_accent_2', '#111F35');
	$bg_color = get_option('school_design_bg_color', '#ffffff');
	$highlight = get_option('school_design_highlight', '#fff5f5');
	$font_size = get_option('school_design_font_size', '16px');
	$monochromatic = get_option('school_design_monochromatic', '1');
	$dark_mode = get_option('school_design_dark_mode', '0');

	?>
	<style id="school-dynamic-design">
		:root {
			--school-primary: <?php echo esc_attr($primary); ?>;
			--school-secondary: <?php echo esc_attr($secondary); ?>;
			--school-accent-1: <?php echo esc_attr($accent1); ?>;
			--school-accent-2: <?php echo esc_attr($accent2); ?>;
			--school-primary-hover: <?php echo esc_attr($secondary); ?>;
			--school-danger: <?php echo esc_attr($primary); ?>;
			--school-text-main: <?php echo esc_attr($accent2); ?>;
			--school-bg-white: <?php echo esc_attr($bg_color); ?>;
			--school-highlight: <?php echo esc_attr($highlight); ?>;
			--school-font-family: 'Rubik', sans-serif;
			--school-font-size: <?php echo esc_attr($font_size); ?>;
			<?php if ( $monochromatic === '1' ) : ?>
			--school-bg-white: #ffffff;
			<?php endif; ?>
			<?php if ( $dark_mode === '1' ) : ?>
			--school-bg-white: #1a273e;
			--school-bg-alt: #111F35;
			--school-text-main: #ffffff;
			--school-text-muted: #cbd5e1;
			--school-border: #2d3a4f;
			--school-card-shadow: 0 4px 12px rgba(0,0,0,0.5);
			--school-highlight: #1e293b;
			<?php endif; ?>
		}
		body, .school-advanced-dashboard, .school-dashboard-container {
			font-family: var(--school-font-family);
			font-size: var(--school-font-size);
		}
	</style>
	<?php
}
add_action( 'wp_head', 'school_output_design_css', 100 );
add_action( 'admin_head', 'school_output_design_css', 100 );

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
