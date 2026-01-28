<?php
/**
 * View: Subjects Management
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function school_render_subjects_view() {
	$all_subjects = get_option( 'school_subjects', array() );

	$limit = 5;
	$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
	$offset = ($paged - 1) * $limit;

	$total_items = count($all_subjects);
	$total_pages = ceil($total_items / $limit);

	// Slice the array for pagination
	$subjects = array_slice($all_subjects, $offset, $limit, true);

	?>
	<div class="content-section">
		<h2>إدارة المواد الدراسية</h2>
		<div class="card">
			<h3>إضافة مادة جديدة</h3>
			<form method="post" class="inline-form" style="display: flex; gap: 10px; margin-bottom: 25px;">
				<?php wp_nonce_field( 'school_settings_action', 'school_settings_nonce' ); ?>
				<input type="text" name="subject_name" placeholder="اسم المادة (مثال: الفيزياء)" required style="flex: 1;">
				<button type="submit" name="school_add_subject" class="button button-primary">حفظ المادة</button>
			</form>
			
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th>#</th>
						<th>اسم المادة</th>
						<th>الإجراء</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty($subjects) ) : ?>
						<tr><td colspan="3" style="text-align: center;">لا توجد مواد دراسية مضافة حالياً.</td></tr>
					<?php else: ?>
						<?php foreach ( $subjects as $id => $data ) :
							$name = is_array($data) ? $data['name'] : $data;
						?>
							<tr>
								<td><?php echo $id + 1; ?></td>
								<td style="font-weight: 700; color: var(--school-primary);"><?php echo esc_html( $name ); ?></td>
								<td>
									<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array('tab' => 'teacher_mgmt', 'sub' => 'subjects', 'remove_subject' => $id) ), 'school_remove_subject' ) ); ?>" style="color: #ef4444; text-decoration: none;" onclick="return confirm('حذف المادة؟');">حذف المادة</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<?php if($total_pages > 1): ?>
				<div class="pagination" style="margin-top: 20px; display: flex; gap: 5px; justify-content: center;">
					<?php for($i=1; $i<=$total_pages; $i++): ?>
						<a href="<?php echo esc_url( add_query_arg('paged', $i) ); ?>" class="button <?php echo ($i === $paged) ? 'button-primary' : ''; ?>"><?php echo $i; ?></a>
					<?php endfor; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
