<?php
/**
 * Database operations for the School plugin.
 *
 * @package School
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create custom database tables.
 */
function school_create_db_tables() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	// Lessons Table
	$table_lessons = $wpdb->prefix . 'school_lessons';
	$sql_lessons = "CREATE TABLE $table_lessons (
		lesson_id bigint(20) NOT NULL AUTO_INCREMENT,
		teacher_id bigint(20) NOT NULL,
		subject_id bigint(20) NOT NULL,
		lesson_title varchar(255) NOT NULL,
		lesson_content longtext NOT NULL,
		pdf_attachment varchar(255) DEFAULT '',
		status varchar(20) DEFAULT 'draft' NOT NULL,
		submission_date datetime DEFAULT '0000-00-00 00:00:00',
		approval_date datetime DEFAULT '0000-00-00 00:00:00',
		notes text,
		approved_by varchar(100) DEFAULT '',
		timeliness varchar(100) DEFAULT '',
		custom_fields_data longtext,
		PRIMARY KEY  (lesson_id)
	) $charset_collate;";

	// Schedule & Deadlines Table
	$table_schedule = $wpdb->prefix . 'school_schedule';
	$sql_schedule = "CREATE TABLE $table_schedule (
		schedule_id bigint(20) NOT NULL AUTO_INCREMENT,
		subject_id bigint(20) NOT NULL,
		teacher_id bigint(20) NOT NULL,
		due_day varchar(20) NOT NULL,
		due_time time NOT NULL,
		PRIMARY KEY  (schedule_id)
	) $charset_collate;";

	// Submission Status Table (to track compliance)
	$table_submissions = $wpdb->prefix . 'school_submissions';
	$sql_submissions = "CREATE TABLE $table_submissions (
		submission_id bigint(20) NOT NULL AUTO_INCREMENT,
		lesson_id bigint(20) DEFAULT NULL,
		teacher_id bigint(20) NOT NULL,
		subject_id bigint(20) NOT NULL,
		status varchar(20) NOT NULL, -- submitted, pending, late
		week_start_date date NOT NULL,
		checked_at timestamp DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (submission_id)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	// Notifications Table
	$table_notifs = $wpdb->prefix . 'school_notifications';
	$sql_notifs = "CREATE TABLE $table_notifs (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		user_id bigint(20) DEFAULT NULL,
		title text NOT NULL,
		type varchar(20) DEFAULT 'info',
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id)
	) $charset_collate;";

	// Teachers Table (for non-authenticated indexing)
	$table_teachers = $wpdb->prefix . 'school_teachers';
	$sql_teachers = "CREATE TABLE $table_teachers (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		employee_id varchar(50) NOT NULL,
		name varchar(255) NOT NULL,
		email varchar(255) DEFAULT '',
		phone varchar(255) DEFAULT '',
		department varchar(255) DEFAULT 'standard',
		job_title varchar(255) DEFAULT '',
		city varchar(255) DEFAULT '',
		region varchar(255) DEFAULT '',
		subject_ids text DEFAULT '',
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY employee_id (employee_id)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql_lessons );
	dbDelta( $sql_schedule );
	dbDelta( $sql_submissions );
	dbDelta( $sql_notifs );
	dbDelta( $sql_teachers );
}
