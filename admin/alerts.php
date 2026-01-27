<?php
/**
 * Admin alerts for the School plugin.
 *
 * @package School
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Show admin notices for pending tasks.
 */
function school_admin_notices() {
	$current_user = wp_get_current_user();
	
	if ( in_array( 'school_teacher', (array) $current_user->roles ) ) {
		global $wpdb;
		$table_lessons = $wpdb->prefix . 'school_lessons';
		$drafts = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_lessons WHERE teacher_id = %d AND status = 'draft'", $current_user->ID ) );
		if ( $drafts > 0 ) {
			?>
			<div class="notice notice-info is-dismissible">
				<p>
					<?php printf( 'لديك %d تحضير (مسودة) بانتظار التقديم. <a href="%s">عرض لوحة التحكم</a>', intval( $drafts ), admin_url( 'admin.php?page=school-dashboard' ) ); ?>
				</p>
			</div>
			<?php
		}
	}

	if ( in_array( 'school_coordinator', (array) $current_user->roles ) ) {
		global $wpdb;
		$table_lessons = $wpdb->prefix . 'school_lessons';
		$assigned_subjects = get_user_meta( $current_user->ID, 'school_assigned_subjects', true );
		if ( is_array( $assigned_subjects ) && ! empty( $assigned_subjects ) ) {
			$subjects_in = implode( ',', array_map( 'intval', $assigned_subjects ) );
			$pending = $wpdb->get_var( "SELECT COUNT(*) FROM $table_lessons WHERE subject_id IN ($subjects_in) AND status = 'submitted'" );
			if ( $pending > 0 ) {
				?>
				<div class="notice notice-warning is-dismissible">
					<p>
						<?php printf( 'هناك %d تحضير دروس بانتظار مراجعتك واعتمادك. <a href="%s">انتقل إلى لوحة المنسق</a>', intval( $pending ), admin_url( 'admin.php?page=school-coordinator' ) ); ?>
					</p>
				</div>
				<?php
			}
		}
	}
}
add_action( 'admin_notices', 'school_admin_notices' );
