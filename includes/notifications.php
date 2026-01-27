<?php
/**
 * Notifications logic for the School plugin.
 *
 * @package School
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notify teacher about a late submission.
 */
function school_notify_teacher_late_submission( $teacher_id, $subject_id ) {
	$notif_settings = get_option( 'school_notification_settings', array( 'email_reminders' => true ) );
	if ( empty( $notif_settings['email_reminders'] ) ) return;

	global $wpdb;
	$teacher = $wpdb->get_row($wpdb->prepare("SELECT name, email FROM {$wpdb->prefix}school_teachers WHERE id = %d", $teacher_id));
	if ( ! $teacher || empty($teacher->email) ) return;

	$subjects = get_option( 'school_subjects', array() );
	$sdata = $subjects[ $subject_id ] ?? 'غير معروف';
	$subject_name = is_array($sdata) ? $sdata['name'] : $sdata;

	$to = $teacher->email;
	$display_name = $teacher->name;
	$subject = 'تذكير: لم يتم تسليم تحضير الدرس';
	$message = sprintf( "عزيزي المعلم %s,\n\nنود تذكيرك بأن موعد تسليم تحضير درس مادة (%s) قد مضى ولم يتم استلام التحضير بعد.\n\nيرجى تسليم التحضير في أقرب وقت ممكن.", $display_name, $subject_name );

	wp_mail( $to, $subject, $message );
}

/**
 * Notify coordinators about lessons awaiting approval.
 * Triggered when a teacher submits a lesson.
 */
function school_notify_coordinator_pending_approval( $lesson_id ) {
	$notif_settings = get_option( 'school_notification_settings', array( 'coordinator_notif' => true ) );
	if ( empty( $notif_settings['coordinator_notif'] ) ) return;

	global $wpdb;
	$table_lessons = $wpdb->prefix . 'school_lessons';
	$lesson = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_lessons WHERE lesson_id = %d", $lesson_id ) );
	if ( ! $lesson ) return;

	// Find coordinators assigned to this subject.
	$coordinators = get_users( array(
		'role'       => 'school_coordinator',
		'meta_key'   => 'school_assigned_subjects',
		'meta_value' => sprintf( ':%d;', $lesson->subject_id ), // This is tricky with serialized array, let's use a simpler query.
		'meta_compare' => 'LIKE'
	) );
	
	// Better query for serialized meta:
	$coordinators = get_users( array( 'role' => 'school_coordinator' ) );
	foreach ( $coordinators as $coord ) {
		$assigned = get_user_meta( $coord->ID, 'school_assigned_subjects', true );
		if ( is_array( $assigned ) && in_array( $lesson->subject_id, $assigned ) ) {
			$to = $coord->user_email;
			$reg_teacher = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}school_teachers WHERE id = %d", $lesson->teacher_id));
			$teacher_name = $reg_teacher ? $reg_teacher->name : 'غير معروف';
			$subject = 'درس جديد بانتظار الاعتماد';
			$message = sprintf( "المنسق العزيز %s,\n\nهناك تحضير درس جديد لمادة (%s) تم تقديمه بواسطة المعلم (%s) وهو بانتظار مراجعتك واعتمادك.", $coord->display_name, $lesson->lesson_title, $teacher_name );
			wp_mail( $to, $subject, $message );
		}
	}
}

/**
 * Send weekly summary to supervisors.
 */
function school_send_weekly_supervisor_summary() {
	global $wpdb;
	$table_submissions = $wpdb->prefix . 'school_submissions';
	$week_start = date( 'Y-m-d', strtotime( 'last monday' ) );
	
	$stats = $wpdb->get_results( $wpdb->prepare( "SELECT status, COUNT(*) as count FROM $table_submissions WHERE week_start_date = %s GROUP BY status", $week_start ) );
	
	$summary = "ملخص التسليمات الأسبوعي:\n\n";
	foreach ( $stats as $s ) {
		$status_label = ($s->status === 'submitted') ? 'تم التسليم' : 'متأخر';
		$summary .= sprintf( "- %s: %d\n", $status_label, $s->count );
	}

	$supervisors = get_users( array( 'role' => 'school_supervisor' ) );
	foreach ( $supervisors as $sup ) {
		wp_mail( $sup->user_email, 'الملخص الأسبوعي لتحضير الدروس', $summary );
	}
}
