<?php
/**
 * View: All Lessons
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function school_render_all_lessons_view() {
	global $wpdb;
	$table_lessons = $wpdb->prefix . 'school_lessons';
	$all_subjects = get_option( 'school_subjects', array() );
	
	$query = "SELECT * FROM $table_lessons";
	if ( isset( $_GET['status'] ) && $_GET['status'] === 'late' ) {
		echo "<h3>قائمة المعلمين المتأخرين (بناءً على آخر فحص)</h3>";
	}
	
	$lessons = $wpdb->get_results( "SELECT * FROM $table_lessons ORDER BY submission_date DESC LIMIT 50" );
	?>
	<div class="content-section">
		<h2>عرض تحضيرات المعلمين</h2>
		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th>العنوان</th><th>المعلم</th><th>المادة</th><th>الحالة</th><th>التاريخ</th><th>الإجراءات</th></tr></thead>
			<tbody>
				<?php 
				$status_map = array(
					'draft'                  => 'مسودة',
					'submitted'              => 'بانتظار المنسق',
					'coordinator_approved'   => 'بانتظار المدير',
					'approved'               => 'معتمد نهائياً',
					'modification_requested' => 'طلب تعديل',
					'late'                   => 'متأخر',
				);
				$is_admin_manager = current_user_can('manage_options') || current_user_can('school_manage_settings');

				foreach ( $lessons as $l ) : 
					$reg_teacher = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}school_teachers WHERE id = %d", $l->teacher_id));
					$teacher_name = $reg_teacher ? $reg_teacher->name : 'N/A';
				?>
					<tr>
						<td style="font-weight: 600;"><?php echo esc_html( $l->lesson_title ); ?></td>
						<td><?php echo esc_html( $teacher_name ); ?></td>
						<td><?php 
							$sdata = $all_subjects[ $l->subject_id ] ?? 'N/A';
							echo esc_html( is_array($sdata) ? $sdata['name'] : $sdata ); 
						?></td>
						<td>
							<span class="status-badge status-<?php echo esc_attr($l->status); ?>">
								<?php echo $status_map[$l->status] ?? $l->status; ?>
							</span>
						</td>
						<td style="font-size: 13px;"><?php echo esc_html( $l->submission_date ); ?></td>
						<td>
							<div style="display: flex; flex-direction: column; gap: 8px;">
								<div style="display: flex; gap: 5px;">
									<?php if ( !empty($l->pdf_attachment) ) : ?>
										<a href="<?php echo esc_url($l->pdf_attachment); ?>" target="_blank" class="button button-small" title="عرض الملف">عرض</a>
										<a href="<?php echo esc_url($l->pdf_attachment); ?>" download class="button button-small" style="background: #64748b; color: #fff;" title="تحميل الملف">تحميل</a>
									<?php endif; ?>
								</div>
								
								<?php if ( $is_admin_manager && in_array($l->status, array('submitted', 'coordinator_approved')) ) : ?>
									<form method="post" style="margin-top: 5px; border-top: 1px solid #eee; padding-top: 5px;">
										<?php wp_nonce_field( 'school_manager_action', 'school_manager_nonce' ); ?>
										<input type="hidden" name="lesson_id" value="<?php echo $l->lesson_id; ?>">
										<div style="display: flex; gap: 5px;">
											<button type="submit" name="school_manager_approve" class="button button-primary button-small" style="font-size: 10px;">اعتماد نهائي</button>
											<button type="submit" name="school_manager_reject" class="button button-small" style="font-size: 10px; background: #fee2e2; color: #991b1b;">تعديل</button>
										</div>
									</form>
								<?php endif; ?>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}
