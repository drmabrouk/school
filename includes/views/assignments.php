<?php
/**
 * View: Teacher & Coordinator Assignments
 */
if ( ! defined( 'ABSPATH' ) ) exit;

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
