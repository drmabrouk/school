<?php
/**
 * Shortcodes for the School plugin.
 *
 * @package School
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode for the login page.
 */
function school_login_shortcode() {
	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		$dashboard_url = home_url( '/dashboard' );
		return '<div class="school-login-msg"><p>مرحباً ' . esc_html( $user->display_name ) . '. أنت مسجل الدخول بالفعل.</p><p><a href="' . esc_url( $dashboard_url ) . '" class="button button-primary">انتقل إلى لوحة التحكم</a></p></div>';
	}

	ob_start();
	?>
	<div class="school-login-form">
		<h2>تسجيل الدخول للنظام</h2>
		<?php wp_login_form(); ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'school_login', 'school_login_shortcode' );

/**
 * Shortcode for the universal dashboard.
 */
function school_dashboard_shortcode() {
	if ( ! is_user_logged_in() ) {
		return '<p>يرجى <a href="' . esc_url( home_url( '/login' ) ) . '">تسجيل الدخول</a> للوصول إلى لوحة التحكم.</p>';
	}

	ob_start();
	echo '<div class="school-dashboard-container">';
	
	if ( current_user_can( 'school_view_all_reports' ) || current_user_can( 'manage_options' ) ) {
		// Supervisor / Admin view
		school_render_supervisor_dashboard();
		// Also show settings on frontend for Supervisor/Admin
		school_render_frontend_settings();
	} elseif ( current_user_can( 'school_view_subject_lessons' ) ) {
		// Coordinator view
		school_render_coordinator_dashboard();
	} elseif ( current_user_can( 'school_view_own_lessons' ) ) {
		// Teacher view
		include SCHOOL_PLUGIN_DIR . 'templates/teacher-dashboard.php';
	} else {
		echo '<p>عذراً، ليس لديك صلاحيات كافية.</p>';
	}
	
	echo '</div>';
	return ob_get_clean();
}
add_shortcode( 'school_dashboard', 'school_dashboard_shortcode' );

/**
 * Shortcode for the file upload system.
 */
function school_file_upload_shortcode() {
	// Redesign: This shortcode now simply renders the public teacher portal
	// to ensure unified open-access without requiring login.
	return school_teacher_portal_shortcode();
}
add_shortcode( 'school_file_upload', 'school_file_upload_shortcode' );

/**
 * Public Teacher Portal (No login required).
 */
function school_teacher_portal_shortcode() {
	ob_start();
	school_render_teacher_portal();
	return ob_get_clean();
}
add_shortcode( 'school_teacher_portal', 'school_teacher_portal_shortcode' );

/**
 * Render settings on the frontend for Supervisors/Admins.
 */
function school_render_frontend_settings() {
	// Redundant link removed as requested.
}
