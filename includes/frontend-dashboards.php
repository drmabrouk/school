<?php
/**
 * Frontend dashboard rendering for the School plugin.
 * Acts as a module loader for different views.
 *
 * @package School
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load component views
require_once SCHOOL_PLUGIN_DIR . 'includes/views/analytics.php';
require_once SCHOOL_PLUGIN_DIR . 'includes/views/teachers.php';
require_once SCHOOL_PLUGIN_DIR . 'includes/views/assignments.php';
require_once SCHOOL_PLUGIN_DIR . 'includes/views/subjects.php';
require_once SCHOOL_PLUGIN_DIR . 'includes/views/portal.php';
require_once SCHOOL_PLUGIN_DIR . 'includes/views/users.php';
require_once SCHOOL_PLUGIN_DIR . 'includes/views/print.php';
require_once SCHOOL_PLUGIN_DIR . 'includes/views/settings.php';
require_once SCHOOL_PLUGIN_DIR . 'includes/views/notifications_view.php';
require_once SCHOOL_PLUGIN_DIR . 'includes/views/lessons_all.php';
require_once SCHOOL_PLUGIN_DIR . 'includes/views/late_reports.php';
require_once SCHOOL_PLUGIN_DIR . 'includes/views/coordinator.php';

/**
 * Render the Supervisor Dashboard.
 */
function school_render_supervisor_dashboard() {
	if ( ! current_user_can( 'school_view_all_reports' ) ) {
		echo '<p>عذراً، ليس لديك صلاحيات كافية.</p>';
		return;
	}

	$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'analytics';
	$is_admin = current_user_can('manage_options');
	$dashboard_title = $is_admin ? 'لوحة تحكم مدير النظام' : 'لوحة تحكم المشرف';
	?>
	<div class="school-advanced-dashboard">
		<?php school_render_dashboard_top_bar( $dashboard_title ); ?>
		
		<div class="dashboard-body">
			<aside class="dashboard-sidebar">
				<nav class="sidebar-nav">
					<ul>
						<li class="nav-analytics <?php echo $current_tab === 'analytics' ? 'active' : ''; ?>">
							<a href="<?php echo esc_url( add_query_arg( 'tab', 'analytics' ) ); ?>">لوحة المعلومات</a>
						</li>
						<li class="nav-teacher-mgmt <?php echo $current_tab === 'teacher_mgmt' ? 'active' : ''; ?>">
							<a href="<?php echo esc_url( add_query_arg( 'tab', 'teacher_mgmt' ) ); ?>">إدارة شؤون المعلمين</a>
						</li>
						<li class="nav-teacher-assign <?php echo $current_tab === 'assignments' ? 'active' : ''; ?>">
							<a href="<?php echo esc_url( add_query_arg( 'tab', 'assignments' ) ); ?>">توزيع المواد</a>
						</li>
						<li class="nav-coord-assign <?php echo $current_tab === 'coordinators' ? 'active' : ''; ?>">
							<a href="<?php echo esc_url( add_query_arg( 'tab', 'coordinators' ) ); ?>">تنسيق المواد</a>
						</li>
						<li class="nav-subjects <?php echo $current_tab === 'subjects' ? 'active' : ''; ?>">
							<a href="<?php echo esc_url( add_query_arg( 'tab', 'subjects' ) ); ?>">قائمة المواد</a>
						</li>
						<li class="nav-users <?php echo $current_tab === 'users' ? 'active' : ''; ?>">
							<a href="<?php echo esc_url( add_query_arg( 'tab', 'users' ) ); ?>">مستخدمي النظام</a>
						</li>
						<li class="nav-lessons <?php echo $current_tab === 'lessons' ? 'active' : ''; ?>">
							<a href="<?php echo esc_url( add_query_arg( 'tab', 'lessons' ) ); ?>">تحضيرات الدروس</a>
						</li>
						<li class="nav-print <?php echo $current_tab === 'print' ? 'active' : ''; ?>">
							<a href="<?php echo esc_url( add_query_arg( 'tab', 'print' ) ); ?>">مركز الطباعة</a>
						</li>
						<li class="nav-late-reports <?php echo $current_tab === 'late_reports' ? 'active' : ''; ?>">
							<a href="<?php echo esc_url( add_query_arg( 'tab', 'late_reports' ) ); ?>">تقارير التأخير</a>
						</li>
						<li class="nav-settings <?php echo $current_tab === 'settings' ? 'active' : ''; ?>">
							<a href="<?php echo esc_url( add_query_arg( 'tab', 'settings' ) ); ?>">إعدادات النظام</a>
						</li>
					</ul>
				</nav>
			</aside>
			
			<main class="dashboard-content">
				<?php 
				switch ( $current_tab ) {
					case 'teacher_mgmt':
						school_render_teacher_management_unified();
						break;
					case 'assignments':
						school_render_assignment_view();
						break;
					case 'coordinators':
						school_render_coordinator_assignment_view();
						break;
					case 'subjects':
						school_render_subjects_view();
						break;
					case 'users':
						school_render_user_management_view();
						break;
					case 'late_reports':
						school_render_late_reports_view();
						break;
					case 'lessons':
						school_render_all_lessons_view();
						break;
					case 'print':
						school_render_print_center();
						break;
					case 'settings':
						school_render_system_settings_view();
						break;
					case 'analytics':
					default:
						school_render_supervisor_analytics();
						break;
				}
				?>
			</main>
		</div>
	</div>
	<?php
}

/**
 * Get the count of late submissions for the current week.
 */
function school_get_late_count() {
	global $wpdb;
	$table_submissions = $wpdb->prefix . 'school_submissions';
	$week_start = date( 'Y-m-d', strtotime( 'last monday' ) );
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_submissions WHERE week_start_date = %s AND status = 'late'", $week_start ) );
}

/**
 * Render the Top Bar.
 */
function school_render_dashboard_top_bar( $title ) {
	if ( ! is_user_logged_in() ) return;

	$current_user = wp_get_current_user();
	$today = date_i18n( 'l j F Y' );
	$late_count = school_get_late_count();
	?>
	<div class="dashboard-top-bar-enhanced">
		<div class="top-bar-brand-section">
			<h1 class="dashboard-main-title"><?php echo esc_html( $title ); ?></h1>
			<div class="vertical-separator"></div>
			<div class="welcome-greeting">السلام عليكم، <?php echo esc_html( $current_user->display_name ); ?></div>
		</div>

		<div class="top-bar-info-actions">
			<span class="header-date-enhanced"><?php echo esc_html( $today ); ?></span>
			<div class="action-buttons-group">
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'late_reports' ) ); ?>" class="button btn-late-list">
					<?php if ( $late_count > 0 ) : ?>
						<span class="notif-badge"><?php echo $late_count; ?></span>
					<?php endif; ?>
					المتأخرين
				</a>
				<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'school_action', 'system_update' ), 'school_system_update' ) ); ?>" class="button btn-system-update">تحديث النظام</a>
				<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="button btn-logout">تسجيل الخروج</a>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Coordinator Dashboard.
 */
/**
 * Unified Teacher Management view with sub-tabs.
 */
function school_render_teacher_management_unified() {
	school_render_teacher_registry_view();
}

function school_render_coordinator_dashboard() {
	if ( ! current_user_can( 'school_view_subject_lessons' ) ) {
		echo '<p>عذراً، ليس لديك صلاحيات كافية.</p>';
		return;
	}
	$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'pending';
	?>
	<div class="school-advanced-dashboard">
		<?php school_render_dashboard_top_bar( 'لوحة تحكم المنسق' ); ?>
		<div class="dashboard-body">
			<aside class="dashboard-sidebar">
				<nav class="sidebar-nav">
					<ul>
						<li class="nav-lessons <?php echo $current_tab === 'pending' ? 'active' : ''; ?>">
							<a href="<?php echo esc_url( add_query_arg( 'tab', 'pending' ) ); ?>">بانتظار الاعتماد</a>
						</li>
						<li class="nav-analytics <?php echo $current_tab === 'history' ? 'active' : ''; ?>">
							<a href="<?php echo esc_url( add_query_arg( 'tab', 'history' ) ); ?>">سجل المواد</a>
						</li>
						<li class="nav-print <?php echo $current_tab === 'print' ? 'active' : ''; ?>">
							<a href="<?php echo esc_url( add_query_arg( 'tab', 'print' ) ); ?>">مركز الطباعة</a>
						</li>
					</ul>
				</nav>
			</aside>
			<main class="dashboard-content">
				<?php 
				if ( $current_tab === 'history' ) {
					school_render_coordinator_history();
				} elseif ( $current_tab === 'print' ) {
					school_render_print_center();
				} else {
					school_render_coordinator_content();
				}
				?>
			</main>
		</div>
	</div>
	<?php
}
