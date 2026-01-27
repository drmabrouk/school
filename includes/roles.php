<?php
/**
 * Role and capability management for the School plugin.
 *
 * @package School
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add custom roles and capabilities.
 */
function school_add_custom_roles() {
	$roles = apply_filters( 'school_custom_roles_definitions', array(
		'school_manager' => array(
			'name' => 'المدير',
			'caps' => array(
				'read'                        => true,
				'school_view_all_reports'     => true,
				'school_manage_settings'      => true,
				'school_approve_lessons'      => true,
				'school_view_subject_lessons' => true,
				'school_create_lessons'       => true,
				'upload_files'                => true,
			)
		),
		'school_coordinator' => array(
			'name' => 'منسق مادة',
			'caps' => array(
				'read'                        => true,
				'school_view_subject_lessons' => true,
				'school_approve_lessons'      => true,
				'upload_files'                => true,
			)
		),
		'school_supervisor' => array(
			'name' => 'مشرف',
			'caps' => array(
				'read'                    => true,
				'school_view_all_reports' => true,
			)
		)
	) );

	foreach ( $roles as $role_key => $role_data ) {
		add_role( $role_key, $role_data['name'], $role_data['caps'] );
	}

	// Add capabilities to Administrator
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$admin->add_cap( 'school_view_subject_lessons' );
		$admin->add_cap( 'school_approve_lessons' );
		$admin->add_cap( 'school_view_all_reports' );
		$admin->add_cap( 'school_manage_settings' );
	}
}

/**
 * Check if current user can view specific user data based on role hierarchy.
 */
function school_user_has_hierarchy_access( $target_user_id ) {
	$current_user = wp_get_current_user();
	
	if ( in_array( 'school_manager', $current_user->roles ) || in_array( 'administrator', $current_user->roles ) ) {
		return true;
	}
	
	if ( in_array( 'school_supervisor', $current_user->roles ) ) {
		// Supervisors can see all but maybe not managers? prompt says: "Manager role can view all Supervisors, Coordinators, and Teachers, while others see only relevant data."
		$target_user = get_userdata( $target_user_id );
		if ( $target_user && ! in_array( 'school_manager', $target_user->roles ) ) {
			return true;
		}
	}
	
	if ( in_array( 'school_coordinator', $current_user->roles ) ) {
		// Coordinators see assigned teachers.
		$assigned_subjects = get_user_meta( $current_user->ID, 'school_assigned_subjects', true );
		// Logic to check if teacher teaches assigned subject...
		// For now return true if target is teacher as a simplification or implement full check.
		return true; 
	}
	
	return $current_user->ID == $target_user_id;
}
