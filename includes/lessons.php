<?php
/**
 * Lesson management logic for the School plugin.
 *
 * @package School
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle lesson creation and updates.
 */
function school_handle_lesson_submission() {
	if ( ! isset( $_POST['school_lesson_nonce'] ) || ! wp_verify_nonce( $_POST['school_lesson_nonce'], 'school_save_lesson' ) ) {
		return;
	}

	if ( ! current_user_can( 'school_create_lessons' ) ) {
		return;
	}

	global $wpdb;
	$table_lessons = $wpdb->prefix . 'school_lessons';
	$teacher_id = get_current_user_id();
	
	$lesson_id = isset( $_POST['lesson_id'] ) ? intval( $_POST['lesson_id'] ) : 0;
	$title     = sanitize_text_field( $_POST['lesson_title'] );
	$subject   = intval( $_POST['subject_id'] );
	$content   = wp_kses_post( $_POST['lesson_content'] );
	$status    = isset( $_POST['submit_final'] ) ? 'submitted' : 'draft';
	$status    = apply_filters( 'school_lesson_save_status', $status, $lesson_id );
	
	$custom_fields = get_option( 'school_custom_fields', array() );
	$custom_data = array();
	foreach ( $custom_fields as $f ) {
		$key = 'custom_field_' . sanitize_title( $f['label'] );
		if ( isset( $_POST[ $key ] ) ) {
			$custom_data[ $f['label'] ] = sanitize_textarea_field( $_POST[ $key ] );
		}
	}

	$pdf_url = '';
	if ( ! empty( $_FILES['pdf_attachment']['name'] ) ) {
		$pdf_url = school_handle_pdf_upload( $_FILES['pdf_attachment'] );
	} else {
		$pdf_url = isset( $_POST['existing_pdf'] ) ? esc_url_raw( $_POST['existing_pdf'] ) : '';
	}

	$data = array(
		'teacher_id'     => $teacher_id,
		'subject_id'     => $subject,
		'lesson_title'   => $title,
		'lesson_content' => $content,
		'pdf_attachment' => $pdf_url,
		'status'         => $status,
		'custom_fields_data' => serialize( $custom_data ),
	);

	$data = apply_filters( 'school_lesson_save_data', $data, $lesson_id );

	do_action( 'school_before_save_lesson', $data, $lesson_id );

	if ( $status === 'submitted' ) {
		$data['submission_date'] = current_time( 'mysql' );
	}

	if ( $lesson_id > 0 ) {
		$wpdb->update( $table_lessons, $data, array( 'lesson_id' => $lesson_id, 'teacher_id' => $teacher_id ) );
		$message = ( $status === 'submitted' ) ? 'تم تقديم الدرس بنجاح.' : 'تم حفظ المسودة بنجاح.';
	} else {
		$wpdb->insert( $table_lessons, $data );
		$lesson_id = $wpdb->insert_id;
		$message = ( $status === 'submitted' ) ? 'تم تقديم الدرس بنجاح.' : 'تم حفظ المسودة بنجاح.';
	}

	if ( $status === 'submitted' ) {
		school_notify_coordinator_pending_approval( $lesson_id );
		school_add_notification( sprintf( 'قام المعلم %s بتسليم تحضير: %s', get_userdata($teacher_id)->display_name, $title ), 'submission', $teacher_id );
	}

	do_action( 'school_after_save_lesson', $lesson_id, $data );

	$default_redirect = is_admin() ? menu_page_url( 'school-dashboard', false ) : home_url( '/dashboard' );
	$redirect_url = apply_filters( 'school_lesson_redirect_url', $default_redirect, $lesson_id, $status );

	wp_redirect( add_query_arg( array( 'message' => urlencode( $message ), 'lesson_id' => $lesson_id ), $redirect_url ) );
	exit;
}
add_action( 'init', 'school_handle_lesson_submission' );

/**
 * Handle public (unauthenticated) lesson submission.
 */
function school_handle_public_lesson_submission() {
	if ( ! isset( $_POST['school_public_nonce'] ) || ! wp_verify_nonce( $_POST['school_public_nonce'], 'school_public_upload' ) ) {
		return;
	}

	global $wpdb;
	$table_lessons = $wpdb->prefix . 'school_lessons';
	
	$teacher_id = intval( $_POST['teacher_id'] );
	$subject_id = intval( $_POST['subject_id'] );
	
	// Auto-generate lesson title
	// Format: تحضير {order} - {day} - {date}
	$lesson_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_lessons WHERE teacher_id = %d AND subject_id = %d", $teacher_id, $subject_id ) );
	$order = intval($lesson_count) + 1;
	$day_name = date_i18n('l');
	$date_formatted = date_i18n('j F Y');
	$title = sprintf("تحضير %d - %s - %s", $order, $day_name, $date_formatted);
	
	if ( empty( $_FILES['pdf_attachment']['name'] ) ) {
		return;
	}

	$pdf_url = school_handle_pdf_upload( $_FILES['pdf_attachment'] );
	if ( ! $pdf_url ) {
		return;
	}

	$teacher = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}school_teachers WHERE id = %d", $teacher_id));
	$teacher_name = $teacher ? $teacher->name : 'معلم غير مسجل';

	$now = current_time( 'timestamp' );
	$deadline_time = get_option('school_submission_deadline', '07:00');
	$today_deadline = strtotime( date('Y-m-d') . ' ' . $deadline_time );
	
	$timeliness = '';
	if ( $now < $today_deadline - 3600 ) { // 1 hour before deadline
		$timeliness = 'Submitted Early';
	}

	$data = array(
		'teacher_id'         => $teacher_id,
		'subject_id'         => $subject_id,
		'lesson_title'       => $title,
		'pdf_attachment'     => $pdf_url,
		'status'             => 'submitted',
		'submission_date'    => current_time( 'mysql' ),
		'timeliness'         => $timeliness,
	);

	$wpdb->insert( $table_lessons, $data );
	$lesson_id = $wpdb->insert_id;

	school_notify_coordinator_pending_approval( $lesson_id );
	school_add_notification( sprintf( 'قام المعلم %s بتسليم تحضير جديد: %s', $teacher_name, $title ), 'submission', $teacher_id );

	$redirect_url = add_query_arg( array( 'subject_id' => $subject_id, 'teacher_id' => $teacher_id, 'success' => 1 ), home_url( '/teacher-portal' ) );
	wp_redirect( $redirect_url );
	exit;
}
add_action( 'init', 'school_handle_public_lesson_submission' );

/**
 * Handle PDF upload.
 */
function school_handle_pdf_upload( $file ) {
	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	$upload_overrides = array( 'test_form' => false );
	$movefile = wp_handle_upload( $file, $upload_overrides );

	if ( $movefile && ! isset( $movefile['error'] ) ) {
		return $movefile['url'];
	}
	return '';
}

/**
 * Get lessons for a teacher.
 */
function school_get_teacher_lessons( $teacher_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'school_lessons';
	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE teacher_id = %d ORDER BY lesson_id DESC", $teacher_id ) );
}
