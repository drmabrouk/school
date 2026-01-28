<?php
/**
 * View: Supervisor Analytics
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get comprehensive system statistics.
 */
function school_get_comprehensive_stats() {
	global $wpdb;
	$table_lessons     = $wpdb->prefix . 'school_lessons';
	$table_submissions = $wpdb->prefix . 'school_submissions';
	
	// Submission stats
	$sub_stats = $wpdb->get_results( "SELECT status, COUNT(*) as count FROM $table_submissions GROUP BY status" );
	$submissions = array('submitted' => 0, 'pending' => 0, 'late' => 0, 'total' => 0);
	foreach ( $sub_stats as $s ) {
		if ( isset( $submissions[ $s->status ] ) ) {
			$submissions[ $s->status ] = (int)$s->count;
			$submissions['total'] += (int)$s->count;
		}
	}
	
	// Performance (submitted / total)
	$performance = ( $submissions['total'] > 0 ) ? round( ( $submissions['submitted'] / $submissions['total'] ) * 100 ) : 0;
	
	// User counts
	$teacher_count    = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}school_teachers" );
	$supervisor_count = count( get_users( array( 'role' => 'school_supervisor' ) ) );
	$coordinator_count = count( get_users( array( 'role' => 'school_coordinator' ) ) );

	// Lesson counts
	$total_lessons = $wpdb->get_var( "SELECT COUNT(*) FROM $table_lessons" );
	$approved_lessons = $wpdb->get_var( "SELECT COUNT(*) FROM $table_lessons WHERE status = 'approved'" );

	return array(
		'submissions' => $submissions,
		'performance' => $performance,
		'users' => array(
			'teachers' => $teacher_count,
			'supervisors' => $supervisor_count,
			'coordinators' => $coordinator_count,
		),
		'lessons' => array(
			'total' => $total_lessons,
			'approved' => $approved_lessons,
		)
	);
}

function school_render_supervisor_analytics() {
	global $wpdb;
	$table_submissions = $wpdb->prefix . 'school_submissions';
	$all_subjects = get_option( 'school_subjects', array() );
	$stats = school_get_comprehensive_stats();
	?>
	<div class="content-section">
		<h2>تحليل أداء النظام الشامل</h2>
		
		<div class="analytics-grid postal-grid">
			<div class="analytics-card postal-card postal-red-dark">
				<h3>أداء النظام العام</h3>
				<span class="analytics-value"><?php echo $stats['performance']; ?>%</span>
				<div class="progress-bar"><div class="progress-bar-fill" style="width: <?php echo $stats['performance']; ?>%; background: #fff;"></div></div>
				<span class="analytics-subtext">بناءً على نسبة التسليمات المكتملة</span>
			</div>
			
			<div class="analytics-card postal-card postal-red-medium">
				<h3>التحضيرات المكتملة</h3>
				<span class="analytics-value"><?php echo $stats['submissions']['submitted']; ?></span>
				<span class="analytics-subtext">إجمالي المستندات المستلمة</span>
			</div>

			<div class="analytics-card postal-card postal-red-light">
				<h3>المهام المتأخرة</h3>
				<span class="analytics-value"><?php echo $stats['submissions']['late']; ?></span>
				<span class="analytics-subtext">تحتاج إلى إجراء فوري</span>
			</div>
		</div>

		<h3 style="margin: 30px 0 15px;">إحصائيات المؤسسة التعليمية</h3>
		<div class="analytics-grid">
			<div class="card analytics-card">
				<h3>إجمالي المعلمين</h3>
				<span class="analytics-value"><?php echo $stats['users']['teachers']; ?></span>
				<span class="analytics-subtext">معلم في السجل</span>
			</div>
			<div class="card analytics-card">
				<h3>إجمالي المنسقين</h3>
				<span class="analytics-value"><?php echo $stats['users']['coordinators']; ?></span>
				<span class="analytics-subtext">منسق مادة</span>
			</div>
			<div class="card analytics-card">
				<h3>التسليمات المعتمدة</h3>
				<span class="analytics-value"><?php echo $stats['lessons']['approved']; ?></span>
				<span class="analytics-subtext">تحضير رسمي</span>
			</div>
			<div class="card analytics-card">
				<h3>الأداء العام</h3>
				<span class="analytics-value" style="color: var(--school-success);"><?php echo $stats['performance']; ?>%</span>
				<span class="analytics-subtext">نسبة الانضباط</span>
			</div>
		</div>

		<div class="grid-2-cols" style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-top: 24px;">
			<div class="card">
				<h3>الرسم البياني للتسليمات</h3>
				<div class="chart-container">
					<canvas id="submissionsChart"></canvas>
				</div>
			</div>
			
			<div class="card">
				<h3>التنبيهات الفورية</h3>
				<div id="school-notifications-center">
					<ul class="notification-list" id="realtime-notifications">
						<li class="notification-item">جاري تحميل التنبيهات...</li>
					</ul>
				</div>
			</div>
		</div>

	</div>
	
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		const ctx = document.getElementById('submissionsChart').getContext('2d');
		const stats = <?php echo json_encode($stats['submissions']); ?>;
		
		new Chart(ctx, {
			type: 'doughnut',
			data: {
				labels: ['مكتمل', 'متأخر', 'قيد الانتظار'],
				datasets: [{
					data: [stats.submitted, stats.late, stats.pending],
					backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
					borderWidth: 0,
					hoverOffset: 10
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: {
						position: 'bottom',
						labels: {
							padding: 20,
							font: { size: 14 }
						}
					}
				},
				cutout: '70%'
			}
		});
	});
	</script>
	<?php
}
