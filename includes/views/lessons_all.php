<?php
/**
 * View: All Lessons
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function school_render_all_lessons_view() {
	global $wpdb;
	$table_lessons = $wpdb->prefix . 'school_lessons';
	$all_subjects = get_option( 'school_subjects', array() );
	
	$s_query = isset($_GET['s_query']) ? sanitize_text_field($_GET['s_query']) : '';
	$f_subject = isset($_GET['f_subject']) ? intval($_GET['f_subject']) : 0;
	$f_status = isset($_GET['f_status']) ? sanitize_text_field($_GET['f_status']) : '';

	$where = array("1=1");
	if ($s_query) {
		$where[] = $wpdb->prepare("(lesson_title LIKE %s OR lesson_content LIKE %s)", '%' . $wpdb->esc_like($s_query) . '%', '%' . $wpdb->esc_like($s_query) . '%');
	}
	if ($f_subject) {
		$where[] = $wpdb->prepare("subject_id = %d", $f_subject);
	}
	if ($f_status) {
		$where[] = $wpdb->prepare("status = %s", $f_status);
	}

	$limit = 5;
	$paged = isset($_GET['paged_lessons']) ? max(1, intval($_GET['paged_lessons'])) : 1;
	$offset = ($paged - 1) * $limit;
	$where_str = implode(" AND ", $where);

	$total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_lessons WHERE $where_str");
	$total_pages = ceil($total_items / $limit);

	$lessons = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $table_lessons WHERE $where_str ORDER BY submission_date DESC LIMIT %d OFFSET %d", $limit, $offset) );

	?>
	<div class="content-section">
		<h2>تحضيرات الدروس</h2>

		<div class="card search-filter-card" style="margin-bottom: 25px;">
			<form method="get" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
				<input type="hidden" name="tab" value="lessons">

				<div class="form-group">
					<label style="display: block; margin-bottom: 5px; font-weight: 600;">البحث:</label>
					<input type="text" name="s_query" value="<?php echo esc_attr($s_query); ?>" placeholder="عنوان الدرس أو المحتوى..." style="width: 250px;">
				</div>

				<div class="form-group">
					<label style="display: block; margin-bottom: 5px; font-weight: 600;">المادة:</label>
					<select name="f_subject">
						<option value="">كل المواد</option>
						<?php foreach($all_subjects as $id => $data): $name = is_array($data) ? $data['name'] : $data; ?>
							<option value="<?php echo $id; ?>" <?php selected($f_subject, $id); ?>><?php echo esc_html($name); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="form-group">
					<label style="display: block; margin-bottom: 5px; font-weight: 600;">الحالة:</label>
					<select name="f_status">
						<option value="">كل الحالات</option>
						<option value="submitted" <?php selected($f_status, 'submitted'); ?>>بانتظار المنسق</option>
						<option value="approved" <?php selected($f_status, 'approved'); ?>>معتمد</option>
						<option value="late" <?php selected($f_status, 'late'); ?>>متأخر</option>
					</select>
				</div>

				<button type="submit" class="button button-primary" style="height: 42px; padding: 0 25px;">تصفية النتائج</button>
				<a href="<?php echo esc_url(remove_query_arg(array('s_query', 'f_subject', 'f_status'))); ?>" class="button" style="height: 42px; line-height: 42px; text-decoration: none;">إعادة تعيين</a>
			</form>
		</div>

		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th>العنوان</th><th>المعلم</th><th>المادة</th><th>الحالة</th><th>التاريخ</th><th>الإجراءات</th></tr></thead>
			<tbody>
				<?php 
				$status_map = array(
					'draft'                  => 'مسودة',
					'submitted'              => 'بانتظار المنسق',
					'coordinator_approved'   => 'بانتظار المدير',
					'approved'               => 'قام المعلم بتسليم التحضير',
					'modification_requested' => 'طلب تعديل',
					'late'                   => 'المعلم متأخر في التسليم',
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
		<?php if($total_pages > 1): ?>
			<div class="pagination" style="margin-top: 20px; display: flex; gap: 5px; justify-content: center;">
				<?php for($i=1; $i<=$total_pages; $i++): ?>
					<a href="<?php echo esc_url( add_query_arg('paged_lessons', $i) ); ?>" class="button <?php echo ($i === $paged) ? 'button-primary' : ''; ?>"><?php echo $i; ?></a>
				<?php endfor; ?>
			</div>
		<?php endif; ?>

		<div class="card" style="margin-top: 40px;">
			<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
				<h3 style="margin: 0;">نشاطات التسليم التفصيلية (الوقت والامتثال)</h3>
				<a href="<?php echo esc_url( add_query_arg( 'export_csv', '1' ) ); ?>" class="button button-primary">تصدير التقرير الكامل</a>
			</div>
			<table class="wp-list-table widefat fixed striped sortable-table">
				<thead>
					<tr>
						<th>المعلم</th>
						<th>المادة</th>
						<th>وقت التسليم</th>
						<th>الحالة الزمنية</th>
						<th>الفرق بالدقائق</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$table_schedule = $wpdb->prefix . 'school_schedule';
					$table_submissions = $wpdb->prefix . 'school_submissions';

					$limit_activity = 5;
					$paged_activity = isset($_GET['paged_activity']) ? max(1, intval($_GET['paged_activity'])) : 1;
					$offset_activity = ($paged_activity - 1) * $limit_activity;
					$total_activity = $wpdb->get_var("SELECT COUNT(*) FROM $table_submissions");
					$total_pages_activity = ceil($total_activity / $limit_activity);

					$recent_submissions = $wpdb->get_results( $wpdb->prepare("
						SELECT s.*, l.submission_date, sch.due_time, sch.due_day
						FROM $table_submissions s
						LEFT JOIN $table_lessons l ON s.lesson_id = l.lesson_id
						LEFT JOIN $table_schedule sch ON s.teacher_id = sch.teacher_id AND s.subject_id = sch.subject_id
						ORDER BY s.checked_at DESC LIMIT %d OFFSET %d
					", $limit_activity, $offset_activity) );

					foreach ( $recent_submissions as $r ) :
						$t_idx = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}school_teachers WHERE id = %d", $r->teacher_id));
						$teacher_name = $t_idx ? $t_idx->name : 'معلم غير معروف';

						$diff_html = '-';
						$timeliness_label = 'غير محدد';
						$badge_class = 'status-pending';

						if ( $r->submission_date && $r->due_time ) {
							$submitted_time = strtotime( $r->submission_date );
							$due_timestamp = strtotime( date('Y-m-d', $submitted_time) . ' ' . $r->due_time );

							$diff_seconds = $due_timestamp - $submitted_time;
							$diff_minutes = round( abs($diff_seconds) / 60 );

							if ( $diff_seconds >= 0 ) {
								$timeliness_label = 'مبكر';
								$diff_html = "<span style='color: var(--school-success);'>$diff_minutes دقيقة مبكراً</span>";
								$badge_class = 'status-submitted';
							} else {
								$timeliness_label = 'متأخر';
								$diff_html = "<span style='color: var(--school-danger);'>$diff_minutes دقيقة تأخير</span>";
								$badge_class = 'status-late';
							}
						}
					?>
						<tr>
							<td style="font-weight: 600;"><?php echo esc_html( $teacher_name ); ?></td>
							<td><?php
								$sdata = $all_subjects[ $r->subject_id ] ?? 'N/A';
								echo esc_html( is_array($sdata) ? $sdata['name'] : $sdata );
							?></td>
							<td style="font-size: 13px;"><?php echo $r->submission_date ? esc_html( date( 'H:i (Y/m/d)', strtotime( $r->submission_date ) ) ) : 'لم يتم التسليم'; ?></td>
							<td><span class="status-badge <?php echo $badge_class; ?>"><?php echo $timeliness_label; ?></span></td>
							<td style="font-weight: bold;"><?php echo $diff_html; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if($total_pages_activity > 1): ?>
				<div class="pagination" style="margin-top: 20px; display: flex; gap: 5px; justify-content: center;">
					<?php for($i=1; $i<=$total_pages_activity; $i++): ?>
						<a href="<?php echo esc_url( add_query_arg('paged_activity', $i) ); ?>" class="button <?php echo ($i === $paged_activity) ? 'button-primary' : ''; ?>"><?php echo $i; ?></a>
					<?php endfor; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
