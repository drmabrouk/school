<?php
/**
 * View: Coordinator Dashboard Components
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function school_render_coordinator_history() {
	global $wpdb;
	$table_lessons = $wpdb->prefix . 'school_lessons';
	$current_user_id = get_current_user_id();
	$assigned_subjects = get_user_meta( $current_user_id, 'school_assigned_subjects', true );
	$all_subjects = get_option( 'school_subjects', array() );

	if ( ! is_array( $assigned_subjects ) || empty( $assigned_subjects ) ) {
		echo '<p>لم يتم تكليفك بأي مواد بعد.</p>';
		return;
	}

	$subjects_in = implode( ',', array_map( 'intval', $assigned_subjects ) );
	$lessons = $wpdb->get_results( "SELECT * FROM $table_lessons WHERE subject_id IN ($subjects_in) AND status = 'approved' ORDER BY approval_date DESC LIMIT 50" );

	?>
	<div class="content-section">
		<h2>سجل التحضيرات المعتمدة لموادك</h2>
		<div class="card">
			<table class="wp-list-table widefat striped">
				<thead><tr><th>العنوان</th><th>المعلم</th><th>المادة</th><th>تاريخ الاعتماد</th></tr></thead>
				<tbody>
					<?php foreach ( $lessons as $l ) : 
						$it = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}school_teachers WHERE id = %d", $l->teacher_id));
						$t_name = $it ? $it->name : 'N/A';
					?>
						<tr>
							<td><?php echo esc_html($l->lesson_title); ?></td>
							<td><?php echo esc_html($t_name); ?></td>
							<td><?php 
								$sdata = $all_subjects[$l->subject_id] ?? 'N/A';
								echo esc_html( is_array($sdata) ? $sdata['name'] : $sdata ); 
							?></td>
							<td><?php echo esc_html($l->approval_date); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}

function school_render_coordinator_content() {
	global $wpdb;
	$table_lessons = $wpdb->prefix . 'school_lessons';
	$current_user_id = get_current_user_id();
	$assigned_subjects = get_user_meta( $current_user_id, 'school_assigned_subjects', true );
	$all_subjects = get_option( 'school_subjects', array() );

	if ( ! is_array( $assigned_subjects ) || empty( $assigned_subjects ) ) {
		echo '<div class="card"><p>لم يتم تكليفك بأي مواد بعد.</p></div>';
		return;
	}

	// Late Teachers Statistics Ranking
	$subjects_in = implode( ',', array_map( 'intval', $assigned_subjects ) );
	$submissions_table = $wpdb->prefix . 'school_submissions';
	$late_stats = $wpdb->get_results( "
		SELECT teacher_id, 
			COUNT(*) as total_checks,
			SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as on_time_count
		FROM $submissions_table
		WHERE subject_id IN ($subjects_in)
		GROUP BY teacher_id
		ORDER BY (on_time_count / total_checks) DESC
	" );

	?>
	<div class="coordinator-stats-section" style="margin-bottom: 40px;">
		<h2 style="margin-bottom: 20px;">إحصائيات التزام المعلمين (الأفضل التزاماً)</h2>
		<div class="grid-form" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
			<?php foreach($late_stats as $stat): 
				$t = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}school_teachers WHERE id = %d", $stat->teacher_id));
				$percent = round(($stat->on_time_count / $stat->total_checks) * 100);
				$color = ($percent > 80) ? '#166534' : (($percent > 50) ? '#854d0e' : '#991b1b');
			?>
				<div class="card" style="border-right: 4px solid <?php echo $color; ?>;">
					<div style="font-weight: 800; font-size: 16px;"><?php echo esc_html($t->name); ?></div>
					<div style="font-size: 24px; font-weight: 900; color: <?php echo $color; ?>; margin: 10px 0;"><?php echo $percent; ?>%</div>
					<div style="font-size: 12px; color: #64748b;">نسبة الالتزام بالمواعيد</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php

	$lessons = $wpdb->get_results( "SELECT * FROM $table_lessons WHERE subject_id IN ($subjects_in) AND status = 'submitted' ORDER BY submission_date DESC" );

	if ( empty( $lessons ) ) {
		echo '<div class="card"><p>لا توجد دروس بانتظار المراجعة حالياً لموادك المكلف بها.</p></div>';
	} else {
		echo '<h2>تحضيرات بانتظار المراجعة (اعتماد المنسق)</h2>';
		echo '<div class="coordinator-lessons-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">';
		foreach ( $lessons as $lesson ) {
			$it = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}school_teachers WHERE id = %d", $lesson->teacher_id));
			$t_name = $it ? $it->name : 'N/A';
			?>
			<div class="card lesson-card">
				<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
					<h3 style="margin: 0; color: var(--school-primary);"><?php echo esc_html( $lesson->lesson_title ); ?></h3>
					<span class="badge badge-warning">بانتظار المراجعة</span>
				</div>
				<p style="margin: 5px 0;"><strong>المعلم:</strong> <?php echo esc_html($t_name); ?></p>
				<p style="margin: 5px 0;"><strong>المادة:</strong> <?php 
					$sdata = $all_subjects[ $lesson->subject_id ] ?? 'غير معروف';
					echo esc_html(is_array($sdata) ? $sdata['name'] : $sdata); 
				?></p>
				<div style="background: #f8fafc; padding: 10px; border-radius: 6px; margin: 15px 0; font-size: 14px;">
					<a href="<?php echo esc_url($lesson->pdf_attachment); ?>" target="_blank" style="color: var(--school-primary); font-weight: 600; text-decoration: underline;">فتح ملف التحضير / المحتوى</a>
				</div>
				<div class="lesson-actions">
					<form method="post">
						<?php wp_nonce_field( 'school_coordinator_action', 'school_coordinator_nonce' ); ?>
						<input type="hidden" name="lesson_id" value="<?php echo esc_attr( $lesson->lesson_id ); ?>">
						<textarea name="notes" placeholder="أضف ملاحظاتك أو توجيهاتك هنا..." style="width: 100%; margin-bottom: 10px;"></textarea>
						<div style="display: flex; gap: 10px;">
							<button type="submit" name="school_approve_lesson" class="button button-primary" style="flex: 1;">اعتماد التحضير</button>
							<button type="submit" name="school_reject_lesson" class="button" style="flex: 1;">طلب تعديل</button>
						</div>
					</form>
				</div>
			</div>
			<?php
		}
		echo '</div>';
	}
}
