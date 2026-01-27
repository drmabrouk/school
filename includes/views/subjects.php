<?php
/**
 * View: Subjects Management
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function school_render_subjects_view() {
	$subjects = get_option( 'school_subjects', array() );
	?>
	<div class="content-section">
		<h2>إدارة المواد الدراسية</h2>
		<div class="card">
			<h3>إضافة مادة جديدة</h3>
			<form method="post" class="inline-form" style="display: flex; gap: 10px; margin-bottom: 20px;">
				<?php wp_nonce_field( 'school_settings_action', 'school_settings_nonce' ); ?>
				<input type="text" name="subject_name" placeholder="اسم المادة (مثال: الفيزياء)" required style="flex: 1;">
				<button type="submit" name="school_add_subject" class="button button-primary">حفظ المادة</button>
			</form>
			
			<div class="subject-tags-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px;">
				<?php foreach ( $subjects as $id => $data ) : 
					$name = is_array($data) ? $data['name'] : $data;
				?>
					<div class="subject-tag-card" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 15px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center;">
						<span style="font-weight: 700; color: var(--school-primary);"><?php echo esc_html( $name ); ?></span>
						<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array('tab' => 'teacher_mgmt', 'sub' => 'subjects', 'remove_subject' => $id) ), 'school_remove_subject' ) ); ?>" style="color: #94a3b8; text-decoration: none; font-size: 20px;" onclick="return confirm('حذف المادة؟');">&times;</a>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php
}
