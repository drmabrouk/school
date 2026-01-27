<?php
/**
 * View: Teacher & Coordinator Assignments
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function school_render_assignment_view() {
	global $wpdb;
	$table_schedule = $wpdb->prefix . 'school_schedule';
	$subjects = get_option( 'school_subjects', array() );
	$registry_teachers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}school_teachers ORDER BY name ASC");
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
	<div class="content-section">
		<h2>تكليف المعلمين وتحديد مواعيد التسليم</h2>
		<div class="card">
			<h3>إضافة موعد تسليم جديد</h3>
			<form method="post" class="grid-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
				<?php wp_nonce_field( 'school_settings_action', 'school_settings_nonce' ); ?>
				<div>
					<label>المعلم:</label>
					<select name="teacher_registry_id" required style="width: 100%;">
						<option value="">اختر المعلم من السجل...</option>
						<?php foreach($registry_teachers as $rt) echo "<option value='{$rt->id}'>{$rt->name} ({$rt->employee_id})</option>"; ?>
					</select>
				</div>
				<div>
					<label>المادة:</label>
					<select name="subject_id" required style="width: 100%;">
						<option value="">اختر المادة...</option>
						<?php foreach($subjects as $id => $data) {
							$name = is_array($data) ? $data['name'] : $data;
							echo "<option value='$id'>$name</option>";
						} ?>
					</select>
				</div>
				<div>
					<label>يوم التسليم الدوري:</label>
					<select name="due_day" required style="width: 100%;">
						<?php foreach($days_translation as $eng=>$ara) echo "<option value='$eng'>$ara</option>"; ?>
					</select>
				</div>
				<div>
					<label>وقت التسليم (أقصى موعد):</label>
					<input type="time" name="due_time" required style="width: 100%;">
				</div>
				<div style="display: flex; align-items: flex-end;">
					<button type="submit" name="school_add_registry_schedule" class="button button-primary" style="width: 100%;">حفظ الموعد</button>
				</div>
			</form>
		</div>

		<div class="card">
			<h3>جدول مواعيد التسليم الحالية</h3>
			<table class="wp-list-table widefat striped">
				<thead><tr><th>المعلم</th><th>المادة</th><th>الموعد الأسبوعي</th><th>الإجراء</th></tr></thead>
				<tbody>
					<?php foreach ( $schedules as $s ) : 
						$teacher_name = 'غير معروف';
						$teacher_id_display = '';
						$t = $wpdb->get_row($wpdb->prepare("SELECT name, employee_id FROM {$wpdb->prefix}school_teachers WHERE id = %d", $s->teacher_id));
						if($t) { $teacher_name = $t->name; $teacher_id_display = $t->employee_id; }
					?>
						<tr>
							<td style="font-weight: 600;"><?php echo esc_html($teacher_name); ?> <?php if($teacher_id_display) echo "<small>(ID: $teacher_id_display)</small>"; ?></td>
							<td><?php 
								$sdata = $subjects[$s->subject_id] ?? 'N/A';
								echo esc_html( is_array($sdata) ? $sdata['name'] : $sdata ); 
							?></td>
							<td><?php echo $days_translation[$s->due_day] . ' - ' . $s->due_time; ?></td>
							<td><a href="<?php echo esc_url( wp_nonce_url( add_query_arg(array('tab'=>'teacher_mgmt', 'sub'=>'assignments', 'remove_schedule'=>$s->schedule_id)), 'school_remove_schedule' ) ); ?>" style="color: #ef4444; font-weight: 600;">إلغاء التكليف</a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}

function school_render_coordinator_assignment_view() {
	$subjects = get_option( 'school_subjects', array() );
	$coordinators = get_users( array( 'role' => 'school_coordinator' ) );
	?>
	<div class="content-section">
		<h2>تكليف منسقي المواد الدراسية</h2>

		<div class="card">
			<h3>إضافة تكليف جديد</h3>
			<form method="post">
				<?php wp_nonce_field( 'school_settings_action', 'school_settings_nonce' ); ?>
				<div class="form-row" style="margin-bottom: 20px;">
					<label>اختر المنسق:</label>
					<select name="coord_id" required style="width: 100%;">
						<option value="">اختر من القائمة...</option>
						<?php foreach ( $coordinators as $c ) : ?>
							<option value="<?php echo esc_attr( $c->ID ); ?>"><?php echo esc_html( $c->display_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="form-row">
					<label>اختر المواد المسؤولة عنها:</label>
					<div class="subject-selection" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; margin-top: 10px; padding: 15px; border: 1px solid #e2e8f0; border-radius: 10px;">
						<?php foreach ( $subjects as $id => $data ) : $name = is_array($data) ? $data['name'] : $data; ?>
							<label style="display: flex; align-items: center; gap: 8px; font-weight: 500;">
								<input type="checkbox" name="assigned_subjects[]" value="<?php echo esc_attr( $id ); ?>"> 
								<?php echo esc_html( $name ); ?>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
				<button type="submit" name="school_assign_coordinator" class="button button-primary" style="margin-top: 25px; padding: 12px 24px;">حفظ وتحديث التكليفات</button>
			</form>
		</div>

		<div class="card">
			<h3>التكليفات الحالية</h3>
			<table class="wp-list-table widefat striped">
				<thead><tr><th>المنسق</th><th>المواد المسؤولة</th></tr></thead>
				<tbody>
					<?php foreach($coordinators as $c) : 
						$assigned = get_user_meta($c->ID, 'school_assigned_subjects', true);
						if ( ! is_array($assigned) || empty($assigned) ) continue;
						$names = array();
						foreach($assigned as $sid) if(isset($subjects[$sid])) $names[] = is_array($subjects[$sid]) ? $subjects[$sid]['name'] : $subjects[$sid];
					?>
						<tr>
							<td style="font-weight: 700;"><?php echo esc_html($c->display_name); ?></td>
							<td><?php echo implode('، ', array_map('esc_html', $names)); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
