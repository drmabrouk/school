<?php
/**
 * Admin settings page for the School plugin.
 *
 * @package School
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the settings page.
 */
function school_render_settings_page() {
	if ( ! current_user_can( 'school_manage_settings' ) ) {
		wp_die( 'عذراً، ليس لديك صلاحيات كافية للوصول إلى هذه الصفحة.' );
	}

	global $wpdb;
	$table_schedule = $wpdb->prefix . 'school_schedule';

	// Handle saving subjects.
	if ( isset( $_POST['school_add_subject'] ) && check_admin_referer( 'school_settings_action', 'school_settings_nonce' ) ) {
		$subjects = get_option( 'school_subjects', array() );
		$new_subject = sanitize_text_field( $_POST['subject_name'] );
		if ( ! empty( $new_subject ) ) {
			$subjects[] = $new_subject;
			update_option( 'school_subjects', $subjects );
			echo '<div class="updated"><p>تم إضافة المادة بنجاح.</p></div>';
		}
	}

	// Handle removing subject.
	if ( isset( $_GET['remove_subject'] ) && check_admin_referer( 'school_remove_subject' ) ) {
		$subjects = get_option( 'school_subjects', array() );
		$id = intval( $_GET['remove_subject'] );
		if ( isset( $subjects[ $id ] ) ) {
			unset( $subjects[ $id ] );
			update_option( 'school_subjects', $subjects );
			echo '<div class="updated"><p>تم حذف المادة بنجاح.</p></div>';
		}
	}

	// Handle saving schedule.
	if ( isset( $_POST['school_add_schedule'] ) && check_admin_referer( 'school_settings_action', 'school_settings_nonce' ) ) {
		$wpdb->insert(
			$table_schedule,
			array(
				'subject_id' => intval( $_POST['subject_id'] ),
				'teacher_id' => intval( $_POST['teacher_id'] ),
				'due_day'    => sanitize_text_field( $_POST['due_day'] ),
				'due_time'   => sanitize_text_field( $_POST['due_time'] ),
			)
		);
		echo '<div class="updated"><p>تم حفظ الجدول بنجاح.</p></div>';
	}

	// Handle removing schedule.
	if ( isset( $_GET['remove_schedule'] ) && check_admin_referer( 'school_remove_schedule' ) ) {
		$wpdb->delete( $table_schedule, array( 'schedule_id' => intval( $_GET['remove_schedule'] ) ) );
		echo '<div class="updated"><p>تم حذف القيد من الجدول.</p></div>';
	}

	$subjects = get_option( 'school_subjects', array() );
	$custom_fields = get_option( 'school_custom_fields', array() );
	$teachers = get_users( array( 'role' => 'school_teacher' ) );
	$coordinators = get_users( array( 'role' => 'school_coordinator' ) );

	// Handle saving coordinator assignment.
	if ( isset( $_POST['school_assign_coordinator'] ) && check_admin_referer( 'school_settings_action', 'school_settings_nonce' ) ) {
		$coord_id = intval( $_POST['coord_id'] );
		$subj_ids = array_map( 'intval', $_POST['assigned_subjects'] );
		update_user_meta( $coord_id, 'school_assigned_subjects', $subj_ids );
		echo '<div class="updated"><p>تم تحديث تكليفات المنسقين.</p></div>';
	}

	// Handle saving custom form fields.
	if ( isset( $_POST['school_add_field'] ) && check_admin_referer( 'school_settings_action', 'school_settings_nonce' ) ) {
		$custom_fields = get_option( 'school_custom_fields', array() );
		$new_field = array(
			'label' => sanitize_text_field( $_POST['field_label'] ),
			'type'  => sanitize_text_field( $_POST['field_type'] ),
			'required' => isset( $_POST['field_required'] ) ? true : false,
		);
		$custom_fields[] = $new_field;
		update_option( 'school_custom_fields', $custom_fields );
		echo '<div class="updated"><p>تم إضافة الحقل بنجاح.</p></div>';
	}

	if ( isset( $_GET['remove_field'] ) && check_admin_referer( 'school_remove_field' ) ) {
		$custom_fields = get_option( 'school_custom_fields', array() );
		$id = intval( $_GET['remove_field'] );
		if ( isset( $custom_fields[ $id ] ) ) {
			unset( $custom_fields[ $id ] );
			update_option( 'school_custom_fields', array_values( $custom_fields ) );
			echo '<div class="updated"><p>تم حذف الحقل بنجاح.</p></div>';
		}
	}

	$schedules = $wpdb->get_results( "SELECT * FROM $table_schedule" );

	$days_translation = array(
		'Monday'    => 'الاثنين',
		'Tuesday'   => 'الثلاثاء',
		'Wednesday' => 'الأربعاء',
		'Thursday'  => 'الخميس',
		'Friday'    => 'الجمعة',
		'Saturday'  => 'السبت',
		'Sunday'    => 'الأحد',
	);

	?>
	<div class="wrap school-admin-wrap">
		<h1>إعدادات نظام المدرسة</h1>

		<div class="card">
			<h2>إدارة المواد</h2>
			<form method="post" style="margin-bottom: 20px;">
				<?php wp_nonce_field( 'school_settings_action', 'school_settings_nonce' ); ?>
				<input type="text" name="subject_name" placeholder="اسم المادة" required class="regular-text">
				<input type="submit" name="school_add_subject" class="button button-primary" value="إضافة مادة">
			</form>
			
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>اسم المادة</th>
						<th>الإجراءات</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $subjects ) ) : ?>
						<tr><td colspan="2">لا توجد مواد مضافة بعد.</td></tr>
					<?php else : ?>
						<?php foreach ( $subjects as $id => $name ) : ?>
							<tr>
								<td><?php echo esc_html( $name ); ?></td>
								<td>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=school-settings&remove_subject=' . $id ), 'school_remove_subject' ) ); ?>" class="button button-link-delete" onclick="return confirm('هل أنت متأكد؟');">حذف</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<div class="card" style="margin-top: 20px;">
			<h2>إعدادات الجدولة والمواعيد النهائية</h2>
			<form method="post" style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
				<?php wp_nonce_field( 'school_settings_action', 'school_settings_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="subject_id">المادة</label></th>
						<td>
							<select name="subject_id" id="subject_id" required>
								<option value="">اختر المادة</option>
								<?php foreach ( $subjects as $id => $name ) : ?>
									<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="teacher_id">المعلم</label></th>
						<td>
							<select name="teacher_id" id="teacher_id" required>
								<option value="">اختر المعلم</option>
								<?php foreach ( $teachers as $teacher ) : ?>
									<option value="<?php echo esc_attr( $teacher->ID ); ?>"><?php echo esc_html( $teacher->display_name ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="due_day">يوم التسليم</label></th>
						<td>
							<select name="due_day" id="due_day" required>
								<?php foreach ( $days_translation as $eng => $ara ) : ?>
									<option value="<?php echo esc_attr( $eng ); ?>"><?php echo esc_html( $ara ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="due_time">وقت التسليم</label></th>
						<td>
							<input type="time" name="due_time" id="due_time" required>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" name="school_add_schedule" class="button button-primary" value="إضافة للجدول">
				</p>
			</form>

			<h3>الجدول الحالي</h3>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>المادة</th>
						<th>المعلم</th>
						<th>اليوم</th>
						<th>الوقت</th>
						<th>الإجراءات</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $schedules ) ) : ?>
						<tr><td colspan="5">لا يوجد جدول محدد بعد.</td></tr>
					<?php else : ?>
						<?php foreach ( $schedules as $s ) : ?>
							<tr>
								<td><?php echo isset( $subjects[ $s->subject_id ] ) ? esc_html( $subjects[ $s->subject_id ] ) : 'غير معروف'; ?></td>
								<td><?php $t = get_userdata( $s->teacher_id ); echo $t ? esc_html( $t->display_name ) : 'غير معروف'; ?></td>
								<td><?php echo esc_html( $days_translation[ $s->due_day ] ?? $s->due_day ); ?></td>
								<td><?php echo esc_html( $s->due_time ); ?></td>
								<td>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=school-settings&remove_schedule=' . $s->schedule_id ), 'school_remove_schedule' ) ); ?>" class="button button-link-delete" onclick="return confirm('هل أنت متأكد؟');">حذف</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<div class="card" style="margin-top: 20px;">
			<h2>تكليف منسقي المواد</h2>
			<form method="post">
				<?php wp_nonce_field( 'school_settings_action', 'school_settings_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="coord_id">المنسق</label></th>
						<td>
							<select name="coord_id" id="coord_id" required>
								<option value="">اختر المنسق</option>
								<?php foreach ( $coordinators as $coord ) : ?>
									<option value="<?php echo esc_attr( $coord->ID ); ?>"><?php echo esc_html( $coord->display_name ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th>المواد المسؤولة</th>
						<td>
							<?php foreach ( $subjects as $id => $name ) : ?>
								<label style="display: block;">
									<input type="checkbox" name="assigned_subjects[]" value="<?php echo esc_attr( $id ); ?>">
									<?php echo esc_html( $name ); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" name="school_assign_coordinator" class="button button-primary" value="حفظ التكليف">
				</p>
			</form>
		</div>

		<div class="card" style="margin-top: 20px;">
			<h2>تخصيص نموذج تحضير الدروس</h2>
			<form method="post" style="margin-bottom: 20px;">
				<?php wp_nonce_field( 'school_settings_action', 'school_settings_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="field_label">تسمية الحقل</label></th>
						<td><input type="text" name="field_label" id="field_label" required class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="field_type">نوع الحقل</label></th>
						<td>
							<select name="field_type" id="field_type">
								<option value="text">نص قصير</option>
								<option value="textarea">نص طويل</option>
								<option value="number">رقم</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="field_required">مطلوب؟</label></th>
						<td><input type="checkbox" name="field_required" id="field_required" value="1"></td>
					</tr>
				</table>
				<input type="submit" name="school_add_field" class="button button-primary" value="إضافة حقل للنموذج">
			</form>

			<h3>الحقول المخصصة الحالية</h3>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>التسمية</th>
						<th>النوع</th>
						<th>مطلوب</th>
						<th>الإجراءات</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $custom_fields ) ) : ?>
						<tr><td colspan="4">لا توجد حقول مخصصة بعد.</td></tr>
					<?php else : ?>
						<?php foreach ( $custom_fields as $id => $f ) : ?>
							<tr>
								<td><?php echo esc_html( $f['label'] ); ?></td>
								<td><?php echo esc_html( $f['type'] ); ?></td>
								<td><?php echo $f['required'] ? 'نعم' : 'لا'; ?></td>
								<td>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=school-settings&remove_field=' . $id ), 'school_remove_field' ) ); ?>" class="button button-link-delete" onclick="return confirm('هل أنت متأكد؟');">حذف</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
