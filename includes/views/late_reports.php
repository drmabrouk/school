<?php
/**
 * View: Late Reports
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function school_render_late_reports_view() {
	global $wpdb;
	$table_submissions = $wpdb->prefix . 'school_submissions';
	$table_lessons = $wpdb->prefix . 'school_lessons';
	$all_subjects = get_option( 'school_subjects', array() );
	$week_start = date( 'Y-m-d', strtotime( 'last monday' ) );

	$status_filter = isset( $_GET['status_filter'] ) ? sanitize_text_field( $_GET['status_filter'] ) : '';

	// Most Delayed Teachers
	$most_delayed = $wpdb->get_results( "
		SELECT teacher_id, COUNT(*) as delay_count 
		FROM $table_submissions 
		WHERE status = 'late' 
		GROUP BY teacher_id 
		ORDER BY delay_count DESC 
		LIMIT 10
	" );

	// Teachers who have not submitted at all this week
	$table_schedule = $wpdb->prefix . 'school_schedule';
	$not_submitted = $wpdb->get_results( $wpdb->prepare( "
		SELECT sch.* FROM $table_schedule sch
		LEFT JOIN $table_submissions sub ON sch.teacher_id = sub.teacher_id 
			AND sch.subject_id = sub.subject_id 
			AND sub.week_start_date = %s
		WHERE sub.submission_id IS NULL
	", $week_start ) );

	?>
	<div class="content-section">
		<h2>تقارير التأخير والالتزام</h2>

		<div class="card">
			<h3>نظام الفلترة المتقدم لنتائج التأخير</h3>
			<form method="get" class="grid-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
				<input type="hidden" name="tab" value="late_reports">
				
				<div>
					<label>حالة التسليم:</label>
					<select name="status_filter" style="width: 100%;">
						<option value="">كل الحالات</option>
						<option value="submitted" <?php selected($status_filter, 'submitted'); ?>>مكتمل</option>
						<option value="late" <?php selected($status_filter, 'late'); ?>>متأخر</option>
						<option value="pending" <?php selected($status_filter, 'pending'); ?>>قيد الانتظار</option>
					</select>
				</div>

				<div>
					<label>المادة الدراسية:</label>
					<select name="subject_filter" style="width: 100%;">
						<option value="">كل المواد</option>
						<?php foreach($all_subjects as $sid => $sdata) {
							$sname = is_array($sdata) ? $sdata['name'] : $sdata;
							echo "<option value='$sid' ".selected($_GET['subject_filter'] ?? '', $sid, false).">$sname</option>";
						} ?>
					</select>
				</div>

				<div>
					<label>من تاريخ:</label>
					<input type="date" name="date_from" value="<?php echo esc_attr($_GET['date_from'] ?? ''); ?>" style="width: 100%;">
				</div>

				<div>
					<label>إلى تاريخ:</label>
					<input type="date" name="date_to" value="<?php echo esc_attr($_GET['date_to'] ?? ''); ?>" style="width: 100%;">
				</div>

				<div>
					<label>بحث باسم المعلم:</label>
					<input type="text" name="teacher_search" value="<?php echo esc_attr($_GET['teacher_search'] ?? ''); ?>" placeholder="اسم المعلم..." style="width: 100%;">
				</div>

				<div style="display: flex; align-items: flex-end;">
					<button type="submit" class="button button-primary" style="width: 100%;">تطبيق الفترة</button>
				</div>
			</form>
		</div>

		<div class="grid-2-cols" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
			<div class="card">
				<h3>الأكثر تأخيراً (إجمالي)</h3>
				<ul class="professional-list">
					<?php foreach ( $most_delayed as $md ) : 
						$it = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}school_teachers WHERE id = %d", $md->teacher_id));
						$name = $it ? $it->name : 'معلم غير معروف';
					?>
						<li class="list-item">
							<span><?php echo esc_html($name); ?></span>
							<span class="badge badge-danger"><?php echo $md->delay_count; ?> تأخير</span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="card">
				<h3>لم يتم التسليم هذا الأسبوع</h3>
				<ul class="professional-list">
					<?php foreach ( $not_submitted as $ns ) : 
						$it = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}school_teachers WHERE id = %d", $ns->teacher_id));
						$name = $it ? $it->name : 'معلم غير معروف';
						$sdata = $all_subjects[$ns->subject_id] ?? 'N/A';
						$sname = is_array($sdata) ? $sdata['name'] : $sdata;
					?>
						<li class="list-item">
							<span><?php echo esc_html($name); ?> (<?php echo esc_html($sname); ?>)</span>
							<span class="badge badge-warning">مفقود</span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>

		<div class="card" style="margin-top: 20px;">
			<h3>السجل الكامل للامتثال وإجراءات التنبيه</h3>
			<table class="wp-list-table widefat striped">
				<thead><tr><th>المعلم</th><th>المادة</th><th>الحالة</th><th>الأسبوع</th><th>الإجراء</th></tr></thead>
				<tbody>
					<?php
					$query = "SELECT * FROM $table_submissions WHERE 1=1";
					if ( ! empty( $status_filter ) ) {
						$query .= $wpdb->prepare( " AND status = %s", $status_filter );
					}
					if ( ! empty( $_GET['subject_filter'] ) ) {
						$query .= $wpdb->prepare( " AND subject_id = %d", intval($_GET['subject_filter']) );
					}
					if ( ! empty( $_GET['date_from'] ) ) {
						$query .= $wpdb->prepare( " AND checked_at >= %s", $_GET['date_from'] . ' 00:00:00' );
					}
					if ( ! empty( $_GET['date_to'] ) ) {
						$query .= $wpdb->prepare( " AND checked_at <= %s", $_GET['date_to'] . ' 23:59:59' );
					}
					if ( ! empty( $_GET['teacher_search'] ) ) {
						$search = '%' . $wpdb->esc_like($_GET['teacher_search']) . '%';
						$found_teacher_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}school_teachers WHERE name LIKE %s", $search));
						if ( !empty($found_teacher_ids) ) {
							$query .= " AND teacher_id IN (" . implode(',', array_map('intval', $found_teacher_ids)) . ")";
						} else {
							$query .= " AND 1=0"; // No results
						}
					}

					$limit = 5;
					$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
					$offset = ($paged - 1) * $limit;

					$count_query = str_replace("SELECT *", "SELECT COUNT(*)", $query);
					$total_items = $wpdb->get_var($count_query);
					$total_pages = ceil($total_items / $limit);

					$query .= $wpdb->prepare(" ORDER BY checked_at DESC LIMIT %d OFFSET %d", $limit, $offset);
					$results = $wpdb->get_results( $query );

					$status_map = array(
						'submitted' => 'قام المعلم بتسليم التحضير',
						'late'      => 'المعلم متأخر في التسليم',
						'pending'   => 'قيد الانتظار',
					);
					$notif_settings = get_option( 'school_notification_settings', array( 'whatsapp_manual' => true ) );
					foreach ( $results as $r ) : 
						$it = $wpdb->get_row($wpdb->prepare("SELECT name, phone FROM {$wpdb->prefix}school_teachers WHERE id = %d", $r->teacher_id));
						$name = $it ? $it->name : 'معلم غير معروف';
						$phone = $it ? $it->phone : '';
						
						$wa_url = '';
						if ( !empty($notif_settings['whatsapp_manual']) && $phone && $r->status === 'late' ) {
							$template = $notif_settings['whatsapp_template'] ?? "السلام عليكم أستاذ {teacher_name}، نود تذكيركم بتأخر تسليم تحضير مادة {subject_name} للأسبوع {week_date}. يرجى المبادرة بالتسليم.";
							$sname = ($all_subjects[$r->subject_id]['name'] ?? ($all_subjects[$r->subject_id] ?? ''));

							$msg = str_replace(
								array('{teacher_name}', '{subject_name}', '{week_date}'),
								array($name, $sname, $r->week_start_date),
								$template
							);

							$wa_url = "https://wa.me/" . preg_replace('/[^0-9]/', '', $phone) . "?text=" . rawurlencode($msg);
						}
					?>
						<tr>
							<td style="font-weight: 600;"><?php echo esc_html($name); ?></td>
							<td><?php 
								$sdata = $all_subjects[$r->subject_id] ?? 'N/A';
								echo esc_html(is_array($sdata) ? $sdata['name'] : $sdata); 
							?></td>
							<td><span class="status-badge status-<?php echo esc_attr($r->status); ?>"><?php echo $status_map[$r->status] ?? $r->status; ?></span></td>
							<td style="font-size: 13px;"><?php echo esc_html($r->week_start_date); ?></td>
							<td>
								<?php if ( $wa_url ) : ?>
									<a href="<?php echo esc_url($wa_url); ?>" target="_blank" class="button" style="background: #25D366; color: #fff; border: none; font-size: 11px;">واتساب</a>
								<?php else : ?>
									-
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
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
