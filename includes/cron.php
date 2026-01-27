<?php
/**
 * Cron tasks for the School plugin.
 *
 * @package School
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schedule the weekly cron task.
 */
function school_schedule_cron() {
	if ( ! wp_next_scheduled( 'school_daily_submission_check' ) ) {
		$deadline = get_option('school_submission_deadline', '07:00');
		$time = strtotime( 'today ' . $deadline );
		if ( $time < time() ) {
			$time = strtotime( 'tomorrow ' . $deadline );
		}
		// Add 1 minute buffer
		$time += 60;
		wp_schedule_event( $time, 'daily', 'school_daily_submission_check' );
	}
}

/**
 * Clear cron tasks on deactivation.
 */
function school_clear_cron_tasks() {
	$timestamp = wp_next_scheduled( 'school_daily_submission_check' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'school_daily_submission_check' );
	}
}

/**
 * The actual check logic.
 */
function school_perform_submission_check() {
	global $wpdb;
	$table_lessons  = $wpdb->prefix . 'school_lessons';
	$table_submissions = $wpdb->prefix . 'school_submissions';
	$table_teachers = $wpdb->prefix . 'school_teachers';
	$table_schedule = $wpdb->prefix . 'school_schedule';

	$sub_days = get_option('school_submission_days', array('Monday', 'Tuesday', 'Wednesday', 'Thursday'));
	$weekly_depts = get_option('school_weekly_departments', array('pe', 'health'));
	$today_name = date('l');

	// If today is not a submission day, we might still need to check if it's a Weekly day.
	$is_sub_day = in_array($today_name, $sub_days);
	$is_monday  = ($today_name === 'Monday');

	if ( !$is_sub_day && !$is_monday ) return;

	$teachers = $wpdb->get_results( "SELECT * FROM $table_teachers" );
	$subjects_data = get_option('school_subjects', array());
	$today_date = date('Y-m-d');

	foreach ( $teachers as $t ) {
		$is_weekly_dept = in_array($t->department, $weekly_depts);
		
		// Logic:
		// 1. If teacher is Weekly Dept: only check on Mondays.
		// 2. If teacher is Standard: check on all Sub Days.
		
		$should_check = false;
		if ( $is_weekly_dept && $is_monday ) {
			$should_check = true;
		} elseif ( !$is_weekly_dept && $is_sub_day ) {
			$should_check = true;
		}

		if ( !$should_check ) continue;

		// Get assigned subjects for this teacher from the schedule
		$assigned = $wpdb->get_results( $wpdb->prepare("SELECT subject_id FROM $table_schedule WHERE teacher_id = %d", $t->id) );
		
		foreach ( $assigned as $as ) {
			// Check for submission TODAY (before 7 AM)
			// Actually since cron runs at 7:01, we check if they submitted since midnight?
			// Or since the start of the "preparation period". 
			// Let's assume they must submit between yesterday 7 AM and today 7 AM.
			$start_time = date('Y-m-d H:i:s', strtotime('yesterday 07:00:00'));
			
			$lesson = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM $table_lessons WHERE teacher_id = %d AND subject_id = %d AND status IN ('submitted', 'approved') AND submission_date >= %s",
				$t->id,
				$as->subject_id,
				$start_time
			) );

			$status = $lesson ? 'submitted' : 'late';

			$wpdb->insert(
				$table_submissions,
				array(
					'lesson_id'          => $lesson ? $lesson->lesson_id : null,
					'teacher_id'         => $t->id,
					'subject_id'         => $as->subject_id,
					'status'             => $status,
					'week_start_date'    => date('Y-m-d', strtotime('last monday')),
					'checked_at'         => current_time('mysql'),
				)
			);
			
			if ( $status === 'late' ) {
				$sdata = $subjects_data[$as->subject_id] ?? 'مادة غير معروفة';
				$sub_name = is_array($sdata) ? $sdata['name'] : $sdata;
				school_add_notification( sprintf( 'تأخر المعلم %s في تسليم تحضير %s (اليوم: %s)', $t->name, $sub_name, date_i18n('l') ), 'late', $t->id );
				
				// Send email notification
				school_notify_teacher_late_submission( $t->id, $as->subject_id );
			}
		}
	}
}
add_action( 'school_daily_submission_check', 'school_perform_submission_check' );

/**
 * Add weekly interval to cron schedules.
 */
add_filter( 'cron_schedules', 'school_add_weekly_cron_schedule' );
function school_add_weekly_cron_schedule( $schedules ) {
	if ( ! isset( $schedules['weekly'] ) ) {
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display'  => 'مرة أسبوعياً',
		);
	}
	return $schedules;
}
