<?php
/**
 * Management request handler for the School plugin.
 *
 * @package School
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle management actions from frontend or admin.
 */
function school_handle_management_actions() {
	if ( isset( $_POST['school_coordinator_nonce'] ) && wp_verify_nonce( $_POST['school_coordinator_nonce'], 'school_coordinator_action' ) ) {
		school_process_coordinator_action();
	}

	if ( isset( $_POST['school_manager_nonce'] ) && wp_verify_nonce( $_POST['school_manager_nonce'], 'school_manager_action' ) ) {
		school_process_manager_action();
	}
	
	if ( isset( $_POST['school_settings_nonce'] ) && wp_verify_nonce( $_POST['school_settings_nonce'], 'school_settings_action' ) ) {
		school_process_admin_settings_action();
	}

	if ( isset( $_GET['remove_teacher_registry'] ) && check_admin_referer( 'school_remove_teacher_registry' ) ) {
		school_process_remove_teacher_registry();
	}

	if ( isset( $_POST['school_add_user_nonce'] ) && wp_verify_nonce( $_POST['school_add_user_nonce'], 'school_add_user_action' ) ) {
		school_process_add_user();
	}

	if ( isset( $_GET['remove_subject'] ) && check_admin_referer( 'school_remove_subject' ) ) {
		school_process_remove_subject();
	}

	if ( isset( $_GET['remove_schedule'] ) && check_admin_referer( 'school_remove_schedule' ) ) {
		school_process_remove_schedule();
	}


	if ( isset( $_GET['remove_field'] ) && check_admin_referer( 'school_remove_field' ) ) {
		school_process_remove_field();
	}

	if ( isset( $_GET['school_action'] ) && $_GET['school_action'] === 'system_update' && check_admin_referer( 'school_system_update' ) ) {
		school_process_system_update();
	}

	if ( isset( $_GET['export_csv'] ) ) {
		school_handle_report_export();
	}

	if ( isset( $_GET['school_download_zip'] ) ) {
		school_handle_zip_download();
	}
}
add_action( 'init', 'school_handle_management_actions' );

/**
 * Log a system notification.
 */
function school_add_notification( $title, $type = 'info', $user_id = null ) {
	global $wpdb;
	$wpdb->insert(
		$wpdb->prefix . 'school_notifications',
		array(
			'title'   => $title,
			'type'    => $type,
			'user_id' => $user_id,
		)
	);
}

/**
 * AJAX handler for realtime notifications.
 */
function school_ajax_get_realtime_notifications() {
	check_ajax_referer( 'school_realtime_nonce', 'nonce' );
	
	if ( ! current_user_can( 'school_view_all_reports' ) && ! current_user_can( 'school_view_subject_lessons' ) ) {
		wp_send_json_error( 'Forbidden' );
	}

	global $wpdb;
	$last_id = isset( $_POST['last_id'] ) ? intval( $_POST['last_id'] ) : 0;
	$table = $wpdb->prefix . 'school_notifications';

	$notifs = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM $table WHERE id > %d ORDER BY id DESC LIMIT 10",
		$last_id
	) );

	$data = array();
	foreach ( $notifs as $n ) {
		$data[] = array(
			'id'        => (int)$n->id,
			'title'     => esc_html( $n->title ),
			'type'      => esc_attr( $n->type ),
			'timestamp' => esc_html( date_i18n( 'H:i:s (j F)', strtotime( $n->created_at ) ) ),
			'diff'      => human_time_diff( strtotime( $n->created_at ), current_time( 'timestamp' ) ) . ' مضت'
		);
	}

	wp_send_json_success( $data );
}
add_action( 'wp_ajax_school_get_realtime_notifications', 'school_ajax_get_realtime_notifications' );

/**
 * Handle CSV Export for Supervisors.
 */
function school_handle_zip_download() {
	if ( ! current_user_can( 'school_view_all_reports' ) && ! current_user_can( 'school_view_subject_lessons' ) ) {
		return;
	}

	$teacher_id = intval( $_GET['school_download_zip'] );
	if ( !$teacher_id ) return;

	global $wpdb;
	$table_lessons = $wpdb->prefix . 'school_lessons';
	$lessons = $wpdb->get_results( $wpdb->prepare( "SELECT pdf_attachment, lesson_title FROM $table_lessons WHERE teacher_id = %d AND pdf_attachment != ''", $teacher_id ) );

	if ( empty($lessons) || !class_exists('ZipArchive') ) return;

	$zip = new ZipArchive();
	$filename = "teacher_" . $teacher_id . "_lessons.zip";
	$filepath = sys_get_temp_dir() . '/' . $filename;

	if ( $zip->open($filepath, ZipArchive::CREATE) !== TRUE ) {
		return;
	}

	$upload_dir = wp_upload_dir();
	$base_url = $upload_dir['baseurl'];
	$base_path = $upload_dir['basedir'];

	foreach ( $lessons as $l ) {
		$file_url = $l->pdf_attachment;
		$relative_path = str_replace($base_url, '', $file_url);
		$full_path = $base_path . $relative_path;

		if ( file_exists($full_path) ) {
			$zip->addFile($full_path, sanitize_file_name($l->lesson_title) . '.pdf');
		}
	}
	$zip->close();

	header('Content-Type: application/zip');
	header('Content-disposition: attachment; filename=' . $filename);
	header('Content-Length: ' . filesize($filepath));
	readfile($filepath);
	unlink($filepath);
	exit;
}

function school_handle_report_export() {
	if ( ! current_user_can( 'school_view_all_reports' ) ) {
		return;
	}

	global $wpdb;
	$table_submissions = $wpdb->prefix . 'school_submissions';
	$all_subjects = get_option( 'school_subjects', array() );

	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=school_report.csv' );
	
	$output = fopen( 'php://output', 'w' );
	fprintf( $output, chr(0xEF).chr(0xBB).chr(0xBF) ); // BOM for Arabic
	
	fputcsv( $output, array( 'المعلم', 'المادة', 'الأسبوع', 'الحالة', 'وقت الفحص' ) );
	
	$rows = $wpdb->get_results( "SELECT * FROM $table_submissions ORDER BY checked_at DESC", ARRAY_A );
	foreach ( $rows as $row ) {
		$reg_t = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}school_teachers WHERE id = %d", $row['teacher_id']));
		$teacher_name = $reg_t ? $reg_t->name : 'N/A';

		fputcsv( $output, array(
			$teacher_name,
			$all_subjects[ $row['subject_id'] ] ?? 'N/A',
			$row['week_start_date'],
			$row['status'],
			$row['checked_at']
		) );
	}
	fclose( $output );
	exit;
}

/**
 * Process coordinator approval/rejection.
 */
function school_process_coordinator_action() {
	if ( ! current_user_can( 'school_approve_lessons' ) ) {
		return;
	}

	global $wpdb;
	$table_lessons = $wpdb->prefix . 'school_lessons';
	$lesson_id = intval( $_POST['lesson_id'] );
	$notes = sanitize_textarea_field( $_POST['notes'] );

	if ( isset( $_POST['school_approve_lesson'] ) ) {
		$wpdb->update(
			$table_lessons,
			array(
				'status'        => 'coordinator_approved',
				'approval_date' => current_time( 'mysql' ),
				'notes'         => $notes,
				'approved_by'   => 'Coordinator',
			),
			array( 'lesson_id' => $lesson_id )
		);

		$lesson = $wpdb->get_row($wpdb->prepare("SELECT lesson_title, teacher_id FROM $table_lessons WHERE lesson_id = %d", $lesson_id));
		$t = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}school_teachers WHERE id = %d", $lesson->teacher_id));
		school_add_notification( sprintf( 'تمت مراجعة درس المعلم %s (%s) بواسطة المنسق وهي بانتظار الاعتماد النهائي', $t->name, $lesson->lesson_title ), 'info' );

	} elseif ( isset( $_POST['school_reject_lesson'] ) ) {
		$wpdb->update(
			$table_lessons,
			array(
				'status' => 'modification_requested',
				'notes'  => $notes,
			),
			array( 'lesson_id' => $lesson_id )
		);
	}
	
	// Redirect to avoid resubmission.
	wp_redirect( remove_query_arg( array( 'school_coordinator_nonce', 'school_coordinator_action' ) ) );
	exit;
}

function school_process_manager_action() {
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'school_manage_settings' ) && ! current_user_can( 'school_view_all_reports' ) ) {
		return;
	}

	global $wpdb;
	$table_lessons = $wpdb->prefix . 'school_lessons';
	$lesson_id = intval( $_POST['lesson_id'] );
	
	$current_user = wp_get_current_user();
	$role_label = 'Manager';
	if ( in_array( 'school_supervisor', $current_user->roles ) ) {
		$role_label = 'Supervisor';
	}

	if ( isset( $_POST['school_manager_approve'] ) ) {
		$wpdb->update(
			$table_lessons,
			array(
				'status'        => 'approved',
				'approval_date' => current_time( 'mysql' ),
				'approved_by'   => $role_label,
			),
			array( 'lesson_id' => $lesson_id )
		);
	} elseif ( isset( $_POST['school_manager_reject'] ) ) {
		$wpdb->update(
			$table_lessons,
			array(
				'status' => 'modification_requested',
			),
			array( 'lesson_id' => $lesson_id )
		);
	}
	
	wp_redirect( remove_query_arg( array( 'school_manager_nonce', 'school_manager_action' ) ) );
	exit;
}

/**
 * Process admin settings actions.
 */
function school_process_admin_settings_action() {
	// Only System Admin or Supervisor (as per latest prompt) can manage settings.
	if ( ! current_user_can( 'school_manage_settings' ) && ! current_user_can( 'school_view_all_reports' ) ) {
		return;
	}

	global $wpdb;
	$table_schedule = $wpdb->prefix . 'school_schedule';

	if ( isset( $_POST['school_add_subject'] ) ) {
		$subjects = get_option( 'school_subjects', array() );
		$new_subject = sanitize_text_field( $_POST['subject_name'] );
		if ( ! empty( $new_subject ) ) {
			$subjects[] = array('name' => $new_subject);
			update_option( 'school_subjects', $subjects );
		}
	}

	
	if ( isset( $_POST['school_assign_coordinator'] ) ) {
		$coord_id = intval( $_POST['coord_id'] );
		$subj_ids = isset($_POST['assigned_subjects']) ? array_map( 'intval', $_POST['assigned_subjects'] ) : array();
		update_user_meta( $coord_id, 'school_assigned_subjects', $subj_ids );
	}

	if ( isset( $_POST['school_add_field'] ) ) {
		$custom_fields = get_option( 'school_custom_fields', array() );
		$custom_fields[] = array(
			'label' => sanitize_text_field( $_POST['field_label'] ),
			'type'  => sanitize_text_field( $_POST['field_type'] ),
			'required' => isset( $_POST['field_required'] ) ? true : false,
		);
		update_option( 'school_custom_fields', $custom_fields );
	}

	if ( isset( $_POST['school_save_institution'] ) ) {
		update_option( 'school_name', sanitize_text_field( $_POST['school_name_val'] ) );
		update_option( 'school_logo', esc_url_raw( $_POST['school_logo'] ) );
		update_option( 'school_address', sanitize_text_field( $_POST['school_address'] ) );
		update_option( 'school_phone', sanitize_text_field( $_POST['school_phone'] ) );
	}

	if ( isset( $_POST['school_save_advanced'] ) ) {
		if ( isset($_POST['school_submission_days']) ) {
			update_option( 'school_submission_days', array_map('sanitize_text_field', $_POST['school_submission_days']) );
		} else {
			update_option( 'school_submission_days', array() );
		}
		update_option( 'school_submission_deadline', sanitize_text_field( $_POST['school_submission_deadline'] ) );
		if ( isset($_POST['school_weekly_departments']) ) {
			update_option( 'school_weekly_departments', array_map('sanitize_text_field', $_POST['school_weekly_departments']) );
		} else {
			update_option( 'school_weekly_departments', array() );
		}
	}

	if ( isset( $_POST['school_add_teacher_to_registry'] ) ) {
		$name   = sanitize_text_field( $_POST['teacher_name'] );
		$emp_id = sanitize_text_field( $_POST['teacher_employee_id'] );
		$email  = sanitize_email( $_POST['teacher_email'] );
		$phone  = sanitize_text_field( $_POST['teacher_phone'] );
		$dept   = sanitize_text_field( $_POST['teacher_department'] );
		$s_ids  = isset($_POST['teacher_subjects']) ? array_map('intval', $_POST['teacher_subjects']) : array();
		
		$result = $wpdb->insert(
			$wpdb->prefix . 'school_teachers',
			array(
				'employee_id'     => $emp_id,
				'name'            => $name,
				'email'           => $email,
				'phone'           => $phone,
				'department'      => $dept,
				'subject_ids'     => implode(',', $s_ids),
			)
		);

		if ( false !== $result ) {
			wp_redirect( add_query_arg( array( 'tab' => 'teacher_mgmt', 'teacher_added' => '1' ), remove_query_arg( array( 'school_settings_nonce', 'school_settings_action' ) ) ) );
			exit;
		} else {
			set_transient( 'school_mgmt_error', 'فشل في حفظ بيانات المعلم. قد يكون الرقم الوظيفي مسجلاً مسبقاً.', 30 );
		}
	}

	if ( isset( $_POST['school_edit_teacher_registry'] ) ) {
		$teacher_id = intval( $_POST['teacher_id'] );
		$name   = sanitize_text_field( $_POST['teacher_name'] );
		$emp_id = sanitize_text_field( $_POST['teacher_employee_id'] );
		$email  = sanitize_email( $_POST['teacher_email'] );
		$phone  = sanitize_text_field( $_POST['teacher_phone'] );
		$dept   = sanitize_text_field( $_POST['teacher_department'] );
		$s_ids  = isset($_POST['teacher_subjects']) ? array_map('intval', $_POST['teacher_subjects']) : array();
		
		$result = $wpdb->update(
			$wpdb->prefix . 'school_teachers',
			array(
				'employee_id' => $emp_id,
				'name'        => $name,
				'email'       => $email,
				'phone'       => $phone,
				'department'  => $dept,
				'subject_ids' => implode(',', $s_ids),
			),
			array( 'id' => $teacher_id )
		);

		if ( false !== $result ) {
			wp_redirect( add_query_arg( array( 'tab' => 'teacher_mgmt', 'teacher_updated' => '1' ), remove_query_arg( array( 'school_settings_nonce', 'school_settings_action', 'edit_teacher' ) ) ) );
			exit;
		} else {
			set_transient( 'school_mgmt_error', 'فشل في تحديث بيانات المعلم.', 30 );
		}
	}

	if ( isset( $_POST['school_add_registry_schedule'] ) ) {
		$teacher_id = intval( $_POST['teacher_registry_id'] );
		$wpdb->insert(
			$wpdb->prefix . 'school_schedule',
			array(
				'subject_id'         => intval( $_POST['subject_id'] ),
				'teacher_id'         => $teacher_id,
				'due_day'            => sanitize_text_field( $_POST['due_day'] ),
				'due_time'           => sanitize_text_field( $_POST['due_time'] ),
			)
		);
	}
}

function school_process_remove_teacher_registry() {
	if ( ! current_user_can( 'school_manage_settings' ) && ! current_user_can( 'school_view_all_reports' ) ) return;
	global $wpdb;
	$wpdb->delete( $wpdb->prefix . 'school_teachers', array( 'id' => intval( $_GET['remove_teacher_registry'] ) ) );
	wp_redirect( remove_query_arg( array( 'remove_teacher_registry', '_wpnonce' ) ) );
	exit;
}

function school_process_remove_subject() {
	if ( ! current_user_can( 'school_manage_settings' ) && ! current_user_can( 'school_view_all_reports' ) ) return;
	$subjects = get_option( 'school_subjects', array() );
	$id = intval( $_GET['remove_subject'] );
	if ( isset( $subjects[ $id ] ) ) {
		unset( $subjects[ $id ] );
		update_option( 'school_subjects', $subjects );
	}
	wp_redirect( remove_query_arg( array( 'remove_subject', '_wpnonce' ) ) );
	exit;
}


function school_process_remove_schedule() {
	if ( ! current_user_can( 'school_manage_settings' ) && ! current_user_can( 'school_view_all_reports' ) ) return;
	global $wpdb;
	$wpdb->delete( $wpdb->prefix . 'school_schedule', array( 'schedule_id' => intval( $_GET['remove_schedule'] ) ) );
	wp_redirect( remove_query_arg( array( 'remove_schedule', '_wpnonce' ) ) );
	exit;
}

function school_process_remove_field() {
	if ( ! current_user_can( 'school_manage_settings' ) && ! current_user_can( 'school_view_all_reports' ) ) return;
	$custom_fields = get_option( 'school_custom_fields', array() );
	$id = intval( $_GET['remove_field'] );
	if ( isset( $custom_fields[ $id ] ) ) {
		unset( $custom_fields[ $id ] );
		update_option( 'school_custom_fields', array_values( $custom_fields ) );
	}
	wp_redirect( remove_query_arg( array( 'remove_field', '_wpnonce' ) ) );
	exit;
}

function school_process_system_update() {
	if ( ! current_user_can( 'school_view_all_reports' ) ) return;
	// Trigger the cron check manually.
	school_perform_submission_check();
	// Fully refresh by redirecting to current URL without the action param
	wp_redirect( remove_query_arg( array( 'school_action', '_wpnonce' ) ) );
	exit;
}

/**
 * Process adding a new user.
 */
function school_process_add_user() {
	if ( ! current_user_can( 'school_manage_settings' ) && ! current_user_can( 'school_view_all_reports' ) ) {
		return;
	}

	$user_login = sanitize_user( $_POST['user_login'] );
	$user_email = sanitize_email( $_POST['user_email'] );
	$user_pass  = $_POST['user_pass'];
	$user_role  = sanitize_text_field( $_POST['user_role'] );
	$display_name = sanitize_text_field( $_POST['display_name'] );
	$phone_number = sanitize_text_field( $_POST['phone_number'] );

	$user_id = wp_insert_user( array(
		'user_login'   => $user_login,
		'user_email'   => $user_email,
		'user_pass'    => $user_pass,
		'display_name' => $display_name,
		'role'         => $user_role,
	) );

	if ( ! is_wp_error( $user_id ) ) {
		if ( ! empty( $phone_number ) ) {
			update_user_meta( $user_id, 'school_phone_number', $phone_number );
		}
		wp_redirect( add_query_arg( array('tab' => 'teacher_mgmt', 'sub' => 'users', 'user_added' => '1'), remove_query_arg( array( 'school_add_user_nonce', 'school_add_user_action' ) ) ) );
		exit;
	} else {
		set_transient( 'school_user_error', $user_id->get_error_message(), 30 );
	}
}
