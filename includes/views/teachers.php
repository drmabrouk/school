<?php
/**
 * View: Teacher Registry Management
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function school_render_teacher_registry_view() {
	global $wpdb;
	$t_table = $wpdb->prefix . 'school_teachers';
	$teachers = $wpdb->get_results("SELECT * FROM $t_table ORDER BY id DESC");
	$subjects = get_option('school_subjects', array());

	if ( isset( $_GET['teacher_added'] ) ) {
		echo '<div class="updated"><p>تم إضافة المعلم إلى السجل بنجاح.</p></div>';
	}
	if ( isset( $_GET['teacher_updated'] ) ) {
		echo '<div class="updated"><p>تم تحديث بيانات المعلم بنجاح.</p></div>';
	}
	if ( $error = get_transient( 'school_mgmt_error' ) ) {
		echo '<div class="error"><p>' . esc_html( $error ) . '</p></div>';
		delete_transient( 'school_mgmt_error' );
	}

	$edit_teacher = null;
	if ( isset( $_GET['edit_teacher'] ) ) {
		$edit_id = intval( $_GET['edit_teacher'] );
		foreach ( $teachers as $t ) {
			if ( $t->id === $edit_id ) {
				$edit_teacher = $t;
				break;
			}
		}
	}
	?>
	<div class="content-section">
		<h2 style="margin-bottom: 25px;">إدارة سجل المعلمين</h2>

		<?php if ( $edit_teacher ) : 
			$e_s_ids = maybe_unserialize($edit_teacher->subject_ids);
			if(!is_array($e_s_ids)) $e_s_ids = explode(',', $edit_teacher->subject_ids);
		?>
			<div class="card" style="border-right: 4px solid var(--school-primary);">
				<h3>تعديل بيانات المعلم: <?php echo esc_html($edit_teacher->name); ?></h3>
				<form method="post" class="grid-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
					<?php wp_nonce_field( 'school_settings_action', 'school_settings_nonce' ); ?>
					<input type="hidden" name="teacher_id" value="<?php echo $edit_teacher->id; ?>">
					<div>
						<label>الرقم الوظيفي (Employee ID):</label>
						<input type="text" name="teacher_employee_id" value="<?php echo esc_attr($edit_teacher->employee_id); ?>" required style="width: 100%;">
					</div>
					<div>
						<label>الاسم الكامل:</label>
						<input type="text" name="teacher_name" value="<?php echo esc_attr($edit_teacher->name); ?>" required style="width: 100%;">
					</div>
					<div>
						<label>البريد الإلكتروني (اختياري):</label>
						<input type="email" name="teacher_email" value="<?php echo esc_attr($edit_teacher->email); ?>" style="width: 100%;">
					</div>
					<div>
						<label>رقم الهاتف (اختياري):</label>
						<input type="text" name="teacher_phone" value="<?php echo esc_attr($edit_teacher->phone); ?>" style="width: 100%;">
					</div>
					<div>
						<label>القسم / التخصص:</label>
						<select name="teacher_department" style="width: 100%;">
							<option value="standard" <?php selected($edit_teacher->department, 'standard'); ?>>معلم مواد أساسية</option>
							<option value="pe" <?php selected($edit_teacher->department, 'pe'); ?>>تربية بدنية</option>
							<option value="health" <?php selected($edit_teacher->department, 'health'); ?>>مهارات حياتية وأسرية (صحية)</option>
						</select>
					</div>
					<div style="grid-column: 1 / -1;">
						<label>المواد المسندة:</label>
						<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; padding: 15px; background: #f8fafc; border: 1px solid #ddd; border-radius: 8px; margin-top: 5px;">
							<?php foreach($subjects as $id => $data): $name = is_array($data) ? $data['name'] : $data; ?>
								<label style="font-size: 13px; display: flex; align-items: center; gap: 5px;"><input type="checkbox" name="teacher_subjects[]" value="<?php echo $id; ?>" <?php checked(in_array($id, $e_s_ids)); ?>> <?php echo $name; ?></label>
							<?php endforeach; ?>
						</div>
					</div>
					<div style="grid-column: 1 / -1; display: flex; gap: 10px;">
						<button type="submit" name="school_edit_teacher_registry" class="button button-primary" style="padding: 10px 30px;">تحديث بيانات المعلم</button>
						<a href="<?php echo esc_url( remove_query_arg('edit_teacher') ); ?>" class="button">إلغاء</a>
					</div>
				</form>
			</div>
		<?php else : ?>
			<div class="card">
				<h3>تسجيل معلم جديد</h3>
				<form method="post" class="grid-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
					<?php wp_nonce_field( 'school_settings_action', 'school_settings_nonce' ); ?>
					<div>
						<label>الرقم الوظيفي (Employee ID):</label>
						<input type="text" name="teacher_employee_id" required style="width: 100%;">
					</div>
					<div>
						<label>الاسم الكامل:</label>
						<input type="text" name="teacher_name" required style="width: 100%;">
					</div>
					<div>
						<label>البريد الإلكتروني (اختياري):</label>
						<input type="email" name="teacher_email" style="width: 100%;">
					</div>
					<div>
						<label>رقم الهاتف (اختياري):</label>
						<input type="text" name="teacher_phone" style="width: 100%;">
					</div>
					<div>
						<label>القسم / التخصص:</label>
						<select name="teacher_department" style="width: 100%;">
							<option value="standard">معلم مواد أساسية</option>
							<option value="pe">تربية بدنية</option>
							<option value="health">مهارات حياتية وأسرية (صحية)</option>
						</select>
					</div>
					<div style="grid-column: 1 / -1;">
						<label>المواد المسندة:</label>
						<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; padding: 15px; background: #f8fafc; border: 1px solid #ddd; border-radius: 8px; margin-top: 5px;">
							<?php foreach($subjects as $id => $data): $name = is_array($data) ? $data['name'] : $data; ?>
								<label style="font-size: 13px; display: flex; align-items: center; gap: 5px;"><input type="checkbox" name="teacher_subjects[]" value="<?php echo $id; ?>"> <?php echo $name; ?></label>
							<?php endforeach; ?>
						</div>
					</div>
					<div style="grid-column: 1 / -1;">
						<button type="submit" name="school_add_teacher_to_registry" class="button button-primary" style="padding: 10px 30px;">إضافة المعلم للسجل</button>
					</div>
				</form>
			</div>
		<?php endif; ?>

		<div class="card">
			<h3>قائمة المعلمين المسجلين</h3>
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th>الرقم الوظيفي</th>
						<th>الاسم</th>
						<th>الهاتف</th>
						<th>القسم</th>
						<th>المواد</th>
						<th>تاريخ التسجيل</th>
						<th>الإجراء</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$dept_map = array('standard' => 'أساسي', 'pe' => 'تربية بدنية', 'health' => 'صحية');
					foreach($teachers as $t): 
						$s_ids = maybe_unserialize($t->subject_ids);
						if(!is_array($s_ids)) $s_ids = explode(',', $t->subject_ids);
						$s_names = array();
						foreach($s_ids as $sid) if(isset($subjects[$sid])) $s_names[] = (is_array($subjects[$sid]) ? $subjects[$sid]['name'] : $subjects[$sid]);
					?>
						<tr>
							<td><code><?php echo esc_html($t->employee_id); ?></code></td>
							<td style="font-weight: 700;"><?php echo esc_html($t->name); ?></td>
							<td><?php echo esc_html($t->phone); ?></td>
							<td><?php echo $dept_map[$t->department] ?? $t->department; ?></td>
							<td style="font-size: 12px;"><?php echo implode(', ', $s_names); ?></td>
							<td style="font-size: 11px; color: #64748b;"><?php echo date_i18n('Y/m/d', strtotime($t->created_at)); ?></td>
							<td>
								<a href="<?php echo esc_url( add_query_arg('edit_teacher', $t->id) ); ?>" class="button button-small">تعديل</a>
								<a href="<?php echo esc_url( wp_nonce_url( add_query_arg(array('tab'=>'teacher_mgmt', 'remove_teacher_registry'=>$t->id)), 'school_remove_teacher_registry' ) ); ?>" style="color: #ef4444; margin-right: 10px;" onclick="return confirm('حذف المعلم نهائياً؟');">حذف</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

	<style>
		.sub-nav-item { text-decoration: none; color: #64748b; padding: 8px 16px; border-radius: 8px; font-weight: 600; transition: all 0.2s; }
		.sub-nav-item:hover { background: #f1f5f9; color: var(--school-primary); }
		.sub-nav-item.active { background: var(--school-primary); color: #fff; }
	</style>
	<?php
}
