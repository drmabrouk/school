<?php
/**
 * View: Student Management
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function school_render_students_view() {
	global $wpdb;
	$table = $wpdb->prefix . 'school_students';

	if ( isset($_POST['school_add_student']) && check_admin_referer('school_settings_action', 'school_settings_nonce') ) {
		$wpdb->insert($table, array(
			'student_id' => sanitize_text_field($_POST['student_id']),
			'name'       => sanitize_text_field($_POST['student_name']),
			'class'      => sanitize_text_field($_POST['student_class']),
		));
	}

	if ( isset($_GET['remove_student']) && check_admin_referer('school_remove_student') ) {
		$wpdb->delete($table, array('id' => intval($_GET['remove_student'])));
	}

	$limit = 5;
	$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
	$offset = ($paged - 1) * $limit;
	$total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table");
	$total_pages = ceil($total_items / $limit);
	$students = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table ORDER BY id DESC LIMIT %d OFFSET %d", $limit, $offset));

	?>
	<div class="content-section">
		<h2>إدارة بيانات الطلاب</h2>
		<div class="card">
			<h3>إضافة طالب جديد</h3>
			<form method="post" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
				<?php wp_nonce_field( 'school_settings_action', 'school_settings_nonce' ); ?>
				<input type="text" name="student_id" placeholder="رقم الطالب..." required>
				<input type="text" name="student_name" placeholder="اسم الطالب..." required>
				<input type="text" name="student_class" placeholder="الفصل الدراسي...">
				<button type="submit" name="school_add_student" class="button button-primary">إضافة الطالب</button>
			</form>
		</div>

		<div class="card">
			<table class="wp-list-table widefat striped">
				<thead><tr><th>رقم الطالب</th><th>الاسم</th><th>الفصل</th><th>الإجراء</th></tr></thead>
				<tbody>
					<?php foreach($students as $s): ?>
						<tr>
							<td><code><?php echo esc_html($s->student_id); ?></code></td>
							<td><?php echo esc_html($s->name); ?></td>
							<td><?php echo esc_html($s->class); ?></td>
							<td>
								<a href="<?php echo esc_url( wp_nonce_url( add_query_arg('remove_student', $s->id), 'school_remove_student' ) ); ?>" style="color: #ef4444;" onclick="return confirm('حذف الطالب؟');">حذف</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if($total_pages > 1): ?>
				<div class="pagination" style="margin-top: 20px; display: flex; gap: 5px;">
					<?php for($i=1; $i<=$total_pages; $i++): ?>
						<a href="<?php echo esc_url( add_query_arg('paged', $i) ); ?>" class="button <?php echo ($i === $paged) ? 'button-primary' : ''; ?>"><?php echo $i; ?></a>
					<?php endfor; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
