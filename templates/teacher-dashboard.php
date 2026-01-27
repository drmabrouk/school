<?php
/**
 * Teacher dashboard template for the School plugin.
 *
 * @package School
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$teacher_id = get_current_user_id();
$lessons = school_get_teacher_lessons( $teacher_id );
$subjects = get_option( 'school_subjects', array() );
$custom_fields = get_option( 'school_custom_fields', array() );

$edit_lesson = null;
if ( isset( $_GET['edit'] ) ) {
	$edit_id = intval( $_GET['edit'] );
	foreach ( $lessons as $l ) {
		if ( $l->lesson_id === $edit_id ) {
			$edit_lesson = $l;
			break;
		}
	}
}

$status_map = array(
	'draft'     => 'مسودة',
	'submitted' => 'تم التسليم',
	'approved'  => 'معتمد',
);

$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'my_lessons';
?>

<div class="school-advanced-dashboard">
	<?php if (function_exists('school_render_dashboard_top_bar')) school_render_dashboard_top_bar('لوحة تحكم المعلم'); ?>

	<div class="dashboard-body">
		<aside class="dashboard-sidebar">
			<nav class="sidebar-nav">
				<ul>
					<li class="nav-analytics <?php echo $current_tab === 'my_lessons' ? 'active' : ''; ?>">
						<a href="<?php echo esc_url( add_query_arg( 'tab', 'my_lessons' ) ); ?>">تحضيراتي السابقة</a>
					</li>
					<li class="nav-lessons <?php echo $current_tab === 'new_lesson' ? 'active' : ''; ?>">
						<a href="<?php echo esc_url( add_query_arg( 'tab', 'new_lesson' ) ); ?>">إضافة تحضير جديد</a>
					</li>
				</ul>
			</nav>
		</aside>

		<main class="dashboard-content">
			<?php if ( $current_tab === 'new_lesson' || $edit_lesson ) : ?>
				<div class="content-section">
					<h2><?php echo $edit_lesson ? 'تعديل تحضير الدرس' : 'تحضير درس جديد'; ?></h2>
					
					<div class="card">
						<form method="post" enctype="multipart/form-data" id="lesson-preparation-form">
							<?php wp_nonce_field( 'school_save_lesson', 'school_lesson_nonce' ); ?>
							<?php if ( $edit_lesson ) : ?>
								<input type="hidden" name="lesson_id" value="<?php echo esc_attr( $edit_lesson->lesson_id ); ?>">
								<input type="hidden" name="existing_pdf" value="<?php echo esc_attr( $edit_lesson->pdf_attachment ); ?>">
							<?php endif; ?>

							<div class="form-row" style="margin-bottom: 20px;">
								<label>عنوان الدرس:</label>
								<input type="text" name="lesson_title" value="<?php echo $edit_lesson ? esc_attr( $edit_lesson->lesson_title ) : ''; ?>" required style="width: 100%;">
							</div>

							<div class="form-row" style="margin-bottom: 20px;">
								<label>المادة:</label>
								<select name="subject_id" required style="width: 100%;">
									<option value="">اختر المادة...</option>
									<?php foreach ( $subjects as $id => $name ) : ?>
										<option value="<?php echo esc_attr( $id ); ?>" <?php echo ( $edit_lesson && $edit_lesson->subject_id == $id ) ? 'selected' : ''; ?>><?php echo esc_html( $name ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="method-selector card" style="background: #f8fafc; border: 1px dashed #cbd5e1; margin-bottom: 25px;">
								<h3 style="font-size: 16px; margin-bottom: 15px;">اختر طريقة التحضير:</h3>
								<div style="display: flex; gap: 20px;">
									<label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
										<input type="radio" name="prep_method" value="electronic" checked onclick="togglePrepMethod('electronic')">
										تحضير إلكتروني (نصي)
									</label>
									<label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
										<input type="radio" name="prep_method" value="upload" onclick="togglePrepMethod('upload')">
										رفع ملف (PDF/صور)
									</label>
								</div>
							</div>

							<div id="method-electronic" class="prep-method-section">
								<div class="form-row" style="margin-bottom: 20px;">
									<label>محتوى الدرس:</label>
									<?php
									$content = $edit_lesson ? $edit_lesson->lesson_content : '';
									wp_editor( $content, 'lesson_content', array( 'textarea_name' => 'lesson_content', 'media_buttons' => false, 'textarea_rows' => 10 ) );
									?>
								</div>
							</div>

							<div id="method-upload" class="prep-method-section" style="display: none;">
								<div class="form-row" style="margin-bottom: 20px;">
									<label>ارفاق ملف التحضير:</label>
									<input type="file" name="pdf_attachment" accept=".pdf,.doc,.docx,image/*">
									<?php if ( $edit_lesson && $edit_lesson->pdf_attachment ) : ?>
										<p style="font-size: 13px; margin-top: 5px;">الملف الحالي: <a href="<?php echo esc_url( $edit_lesson->pdf_attachment ); ?>" target="_blank">معاينة</a></p>
									<?php endif; ?>
								</div>
							</div>

							<div class="custom-fields-area">
								<?php 
								$existing_custom_data = $edit_lesson ? maybe_unserialize($edit_lesson->custom_fields_data) : array();
								foreach ( $custom_fields as $f ) : 
									$key = 'custom_field_' . sanitize_title( $f['label'] );
									$val = isset( $existing_custom_data[ $f['label'] ] ) ? $existing_custom_data[ $f['label'] ] : '';
								?>
									<div class="form-row" style="margin-bottom: 15px;">
										<label><?php echo esc_html( $f['label'] ); ?>:</label>
										<?php if ( $f['type'] === 'textarea' ) : ?>
											<textarea name="<?php echo esc_attr( $key ); ?>" <?php echo $f['required'] ? 'required' : ''; ?> rows="3" style="width: 100%;"><?php echo esc_textarea( $val ); ?></textarea>
										<?php else : ?>
											<input type="<?php echo esc_attr( $f['type'] ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $val ); ?>" <?php echo $f['required'] ? 'required' : ''; ?> style="width: 100%;">
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>

							<div class="form-actions" style="margin-top: 30px; display: flex; gap: 10px;">
								<button type="submit" name="submit_final" class="button button-primary">تقديم التحضير النهائي</button>
								<button type="submit" name="save_draft" class="button">حفظ كمسودة</button>
								<?php if ( $edit_lesson ) : ?>
									<a href="<?php echo esc_url( add_query_arg( 'tab', 'my_lessons', remove_query_arg('edit') ) ); ?>" class="button">إلغاء</a>
								<?php endif; ?>
							</div>
						</form>
					</div>
				</div>

				<script>
				function togglePrepMethod(method) {
					document.getElementById('method-electronic').style.display = (method === 'electronic') ? 'block' : 'none';
					document.getElementById('method-upload').style.display = (method === 'upload') ? 'block' : 'none';
				}
				</script>

			<?php else : ?>
				<div class="content-section">
					<h2>تحضيراتي السابقة</h2>
					<div class="card">
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th>العنوان</th>
									<th>المادة</th>
									<th>الحالة</th>
									<th>التاريخ</th>
									<th>الإجراءات</th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $lessons ) ) : ?>
									<tr><td colspan="5">لا يوجد تحضيرات مسجلة حالياً.</td></tr>
								<?php else : ?>
									<?php foreach ( $lessons as $l ) : ?>
										<tr>
											<td style="font-weight: 600;"><?php echo esc_html( $l->lesson_title ); ?></td>
											<td><?php echo esc_html( $subjects[ $l->subject_id ] ?? 'غير معروف' ); ?></td>
											<td><span class="status-badge status-<?php echo esc_attr( $l->status ); ?>"><?php echo $status_map[$l->status] ?? $l->status; ?></span></td>
											<td style="font-size: 13px; color: var(--school-text-muted);"><?php echo $l->submission_date ?: '-'; ?></td>
											<td>
												<?php if ( $l->status !== 'approved' ) : ?>
													<a href="<?php echo esc_url( add_query_arg( 'edit', $l->lesson_id ) ); ?>" class="button button-small">تعديل</a>
												<?php else : ?>
													<span style="color: var(--school-success); font-weight: bold;">✓ معتمد</span>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			<?php endif; ?>
		</main>
	</div>
</div>
