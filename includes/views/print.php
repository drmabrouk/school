<?php
/**
 * View: Print Center
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function school_render_print_center() {
	global $wpdb;
	$table_lessons = $wpdb->prefix . 'school_lessons';
	$table_teachers = $wpdb->prefix . 'school_teachers';
	
	// Handle Filters
	$teacher_filter = isset($_GET['print_teacher']) ? intval($_GET['print_teacher']) : 0;
	$period_filter  = isset($_GET['print_period']) ? sanitize_text_field($_GET['print_period']) : '';
	
	$query = "SELECT * FROM $table_lessons WHERE status = 'approved'";
	if ( $teacher_filter ) {
		$query .= $wpdb->prepare(" AND teacher_id = %d", $teacher_filter);
	}
	
	if ( $period_filter ) {
		$date_limit = '';
		if ( $period_filter === 'week' ) $date_limit = date('Y-m-d', strtotime('-1 week'));
		elseif ( $period_filter === 'month' ) $date_limit = date('Y-m-d', strtotime('-1 month'));
		elseif ( $period_filter === 'year' ) $date_limit = date('Y-m-d', strtotime('-1 year'));
		
		if ( $date_limit ) {
			$query .= $wpdb->prepare(" AND submission_date >= %s", $date_limit);
		}
	}
	
	$query .= " ORDER BY approval_date DESC LIMIT 100";
	$lessons = $wpdb->get_results( $query );
	
	$all_teachers = $wpdb->get_results("SELECT id, name FROM $table_teachers");
	
	$print_template = get_option( 'school_print_template', array( 'header' => 'مدرستنا العامرة', 'footer' => 'نحو مستقبل أفضل', 'logo' => '', 'font_size' => '16' ) );
	?>
	<div class="content-section">
		<h2>مركز الطباعة والتقارير</h2>
		
		<div class="card">
			<h3>تصفية التحضيرات للطباعة والتحميل</h3>
			<form method="get" style="display: flex; gap: 15px; margin-bottom: 25px; align-items: flex-end;">
				<input type="hidden" name="tab" value="<?php echo $_GET['tab'] ?? 'print'; ?>">
				<?php if(isset($_GET['sub_tab'])) echo '<input type="hidden" name="sub_tab" value="print">'; ?>
				
				<div style="flex: 1;">
					<label>اختر المعلم:</label>
					<select name="print_teacher" style="width: 100%;">
						<option value="">كل المعلمين</option>
						<?php foreach($all_teachers as $at): ?>
							<option value="<?php echo $at->id; ?>" <?php selected($teacher_filter, $at->id); ?>><?php echo esc_html($at->name); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				
				<div style="flex: 1;">
					<label>الفترة الزمنية:</label>
					<select name="print_period" style="width: 100%;">
						<option value="">كل الأوقات</option>
						<option value="week" <?php selected($period_filter, 'week'); ?>>الأسبوع الأخير</option>
						<option value="month" <?php selected($period_filter, 'month'); ?>>الشهر الأخير</option>
						<option value="year" <?php selected($period_filter, 'year'); ?>>السنة الأخيرة</option>
					</select>
				</div>
				
				<button type="submit" class="button button-primary">تطبيق الفلترة</button>
				
				<?php if ( $teacher_filter && class_exists('ZipArchive') ) : ?>
					<a href="<?php echo esc_url( add_query_arg('school_download_zip', $teacher_filter) ); ?>" class="button" style="background: #2563eb; color: #fff; border: none;">تحميل الملفات (ZIP)</a>
				<?php endif; ?>
			</form>

			<hr style="margin-bottom: 25px;">

			<table class="wp-list-table widefat striped">
				<thead><tr><th>العنوان</th><th>المعلم</th><th>المادة</th><th>تاريخ الاعتماد</th><th>الإجراء</th></tr></thead>
				<tbody>
					<?php 
					$subjects = get_option('school_subjects', array());
					foreach ( $lessons as $l ) : 
						$reg_t = $wpdb->get_row($wpdb->prepare("SELECT name FROM $table_teachers WHERE id = %d", $l->teacher_id));
						$t_name = $reg_t ? $reg_t->name : 'N/A';
					?>
						<tr>
							<td style="font-weight: 600;"><?php echo esc_html( $l->lesson_title ); ?></td>
							<td><?php echo esc_html($t_name); ?></td>
							<td><?php 
								$sdata = $subjects[$l->subject_id] ?? 'N/A';
								echo esc_html( is_array($sdata) ? $sdata['name'] : $sdata ); 
							?></td>
							<td style="font-size: 13px;"><?php echo $l->approval_date; ?></td>
							<td>
								<button onclick="schoolPrintLesson(<?php echo esc_attr(json_encode($l)); ?>, '<?php echo esc_js($t_name); ?>')" class="button button-primary button-small">طباعة</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

	<script>
	function schoolPrintLesson(lesson, teacherName) {
		const printWindow = window.open('', '_blank');
		const logo = '<?php echo esc_js($print_template['logo']); ?>';
		const fontSize = '<?php echo esc_js($print_template['font_size']); ?>px';
		const primaryColor = '<?php echo esc_js($print_template['primary_color'] ?? '#a00000'); ?>';
		const align = '<?php echo esc_js($print_template['header_align'] ?? 'center'); ?>';
		
		printWindow.document.write('<html><head><title>' + (lesson ? lesson.lesson_title : 'معاينة القالب') + '</title>');
		printWindow.document.write('<style>@import url("https://fonts.googleapis.com/css2?family=Rubik:ital,wght@0,300..900;1,300..900&display=swap"); body{direction:rtl; font-family: "Rubik", sans-serif; padding: 40px; color: #111; font-size: ' + fontSize + ';} .header{text-align:'+align+'; border-bottom: 3px solid '+primaryColor+'; margin-bottom: 30px; padding-bottom: 20px;} .logo{max-height: 100px; margin-bottom: 15px;} .meta{display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; padding: 20px; border: 1px solid #eee; border-radius: 8px;} .footer{text-align:center; border-top: 1px solid #ddd; margin-top: 60px; padding-top: 20px; font-size: 14px; color: #555;} .content-box{line-height: 1.8; min-height: 400px;} h1{margin:0; color:'+primaryColor+';}</style>');
		printWindow.document.write('</head><body>');
		printWindow.document.write('<div class="header">');
		if(logo) printWindow.document.write('<img src="' + logo + '" class="logo"><br>');
		printWindow.document.write('<h1><?php echo esc_js($print_template['header']); ?></h1><p>سجل تحضير الدروس التعليمي المعتمد</p></div>');
		
		if (lesson) {
			printWindow.document.write('<div class="meta"><div><strong>عنوان الدرس:</strong> ' + lesson.lesson_title + '</div><div><strong>اسم المعلم:</strong> ' + teacherName + '</div><div><strong>تاريخ الاعتماد:</strong> ' + lesson.approval_date + '</div><div><strong>حالة المستند:</strong> رسمي / معتمد</div></div>');
			printWindow.document.write('<div class="content-box"><h3>محتوى الدرس:</h3>' + (lesson.lesson_content || 'لا يوجد محتوى نصي') + '</div>');
		} else {
			printWindow.document.write('<div class="meta"><div><strong>عنوان الدرس:</strong> مثال لعنوان الدرس</div><div><strong>اسم المعلم:</strong> اسم المعلم هنا</div><div><strong>تاريخ الاعتماد:</strong> 2023/10/27</div><div><strong>الحالة:</strong> معاينة</div></div>');
			printWindow.document.write('<div class="content-box"><p>هذا النص هو مثال لمحتوى الدرس الذي سيظهر في هذا المكان عند الطباعة الفعلية.</p></div>');
		}
		
		printWindow.document.write('<div class="footer"><?php echo esc_js($print_template['footer']); ?></div>');
		printWindow.document.write('</body></html>');
		
		printWindow.document.close();
		if (lesson) setTimeout(() => { printWindow.print(); }, 600);
	}

	</script>
	<?php
}
