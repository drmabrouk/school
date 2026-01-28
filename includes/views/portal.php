<?php
/**
 * View: Public Teacher Portal
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function school_render_teacher_portal() {
	global $wpdb;
	$subjects   = get_option( 'school_subjects', array() );
	$subject_id = isset( $_GET['subject_id'] ) ? intval( $_GET['subject_id'] ) : null;
	$teacher_id = isset( $_GET['teacher_id'] ) ? intval( $_GET['teacher_id'] ) : null;
	
	$school_logo = get_option('school_logo', '');
	$school_name = get_option('school_name', 'مدرستي');

	echo '<div class="school-portal-wrapper">';
	
	// Branding: Top Logo
	if ( $school_logo ) {
		echo '<div class="portal-branding-top" style="text-align: center; margin-bottom: 40px;">';
		echo '<img src="' . esc_url($school_logo) . '" style="max-height: 120px; width: auto;">';
		echo '</div>';
	}

	echo '<div class="school-teacher-portal school-dashboard-container standalone-portal-refined">';
	
	if ( is_null($subject_id) ) {
		// Step 1: Search Bar & Subject Cards
		echo '<div class="portal-header" style="text-align: center; margin-bottom: 50px;">';
		echo '<h2 style="font-weight: 900; color: var(--school-primary); font-size: 32px; margin-bottom: 10px;">بوابة المعلمين - تحضير الدروس</h2>';
		echo '<p style="color: #64748b; font-size: 18px;">يرجى اختيار المادة الدراسية للمتابعة</p>';
		echo '</div>';

		echo '<div class="card" style="margin-bottom: 40px; padding: 5px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">';
		echo '<input type="text" id="subject-teacher-search" placeholder="ابحث عن المادة أو المعلم..." style="width: 100%; border: none !important; font-size: 20px; padding: 25px;">';
		echo '</div>';

		echo '<div id="search-results-teachers" style="display:none; margin-bottom: 30px;">';
		echo '<h3>نتائج البحث عن المعلمين:</h3>';
		echo '<div class="portal-grid" id="teacher-search-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;"></div>';
		echo '<hr style="margin: 40px 0;">';
		echo '</div>';

		echo '<div class="portal-grid subjects-modern-grid" id="subject-grid">';
		$all_teachers_registry = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}school_teachers");
		
		$icons = array('dashicons-book-alt', 'dashicons-welcome-learn-more', 'dashicons-calculator', 'dashicons-art', 'dashicons-media-document', 'dashicons-analytics');
		$i = 0;

		foreach ( $subjects as $id => $data ) {
			$name = is_array($data) ? $data['name'] : $data;
			$icon = $icons[$i % count($icons)];
			$i++;
			
			// Get teacher count for this subject
			$subject_teachers = array();
			foreach($all_teachers_registry as $rt) {
				$s_ids = maybe_unserialize($rt->subject_ids);
				if(!is_array($s_ids)) $s_ids = explode(',', $rt->subject_ids);
				if(in_array($id, $s_ids)) $subject_teachers[] = $rt;
			}
			$teacher_count = count($subject_teachers);
			
			// Find coordinator for this subject
			$coordinator_name = 'غير محدد';
			$coords = get_users( array( 'role' => 'school_coordinator' ) );
			foreach ( $coords as $c ) {
				$assigned = get_user_meta( $c->ID, 'school_assigned_subjects', true );
				if ( is_array($assigned) && in_array($id, $assigned) ) {
					$coordinator_name = $c->display_name;
					break;
				}
			}

			$url = add_query_arg( 'subject_id', $id );
			echo "<a href='".esc_url($url)."' class='card portal-card-link subject-card modern-subject-card' data-name='".esc_attr($name)."'>
					<div class='card-header-flex'>
						<div class='subject-icon-box'><span class='dashicons $icon'></span></div>
						<div class='badge badge-subject-count'>$teacher_count معلم</div>
					</div>
					<div class='subject-name-main'>$name</div>
					<div class='coordinator-info'><strong>منسق المادة:</strong> $coordinator_name</div>
				  </a>";
		}
		echo '</div>';

		// Prepare teachers JS data for search
		$teachers_js = array();
		foreach($all_teachers_registry as $rt) {
			$s_ids = maybe_unserialize($rt->subject_ids);
			if(!is_array($s_ids)) $s_ids = explode(',', $rt->subject_ids);
			$first_sid = !empty($s_ids) ? $s_ids[0] : 0;
			$teachers_js[] = array(
				'id' => $rt->id,
				'name' => $rt->name,
				'sid' => $first_sid,
				'url' => add_query_arg(array('subject_id' => $first_sid, 'teacher_id' => $rt->id))
			);
		}
		?>
		<script>
		const teachersData = <?php echo json_encode($teachers_js); ?>;
		document.getElementById('subject-teacher-search').addEventListener('input', function(e) {
			const term = e.target.value.toLowerCase();
			const teacherResults = document.getElementById('search-results-teachers');
			const teacherGrid = document.getElementById('teacher-search-grid');
			
			// Search subjects
			document.querySelectorAll('.subject-card').forEach(card => {
				const name = card.getAttribute('data-name').toLowerCase();
				card.style.display = name.includes(term) ? 'block' : 'none';
			});

			// Search teachers
			if (term.length > 1) {
				const filtered = teachersData.filter(t => t.name.toLowerCase().includes(term));
				if (filtered.length > 0) {
					teacherResults.style.display = 'block';
					teacherGrid.innerHTML = filtered.map(t => `
						<a href="${t.url}" class="card teacher-item" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 15px; padding: 15px; border-radius: 12px;">
							<div style="background: #a00000; color: #fff; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">${t.name.charAt(0)}</div>
							<div style="font-weight: 700;">${t.name}</div>
						</a>
					`).join('');
				} else {
					teacherResults.style.display = 'none';
				}
			} else {
				teacherResults.style.display = 'none';
			}
		});
		</script>
		<?php
	} elseif ( is_null($teacher_id) ) {
		// Step 2: Select Teacher Name Cards
		$t_table = $wpdb->prefix . 'school_teachers';
		$teachers = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t_table WHERE FIND_IN_SET(%d, subject_ids)", $subject_id ) );
		$s_name = is_array($subjects[$subject_id]) ? $subjects[$subject_id]['name'] : $subjects[$subject_id];
		
		echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">';
		echo '<h2 style="font-weight: 800;">اختر اسمك من قائمة المعلمين - ' . esc_html( $s_name ) . '</h2>';
		echo '<a href="' . esc_url( remove_query_arg( 'subject_id' ) ) . '" class="button" style="background: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1;">العودة للمواد</a>';
		echo '</div>';
		
		echo '<div class="card" style="margin-bottom: 30px; padding: 5px; border-radius: 12px; overflow: hidden;">
				<input type="text" id="teacher-search" placeholder="ابحث عن اسمك هنا للوصول السريع..." style="width: 100%; border: none !important; font-size: 18px; padding: 20px;">
			  </div>';
		
		echo '<div class="portal-grid" id="teacher-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">';
		foreach ( $teachers as $t ) {
			$url = add_query_arg( 'teacher_id', $t->id );
			echo "<a href='".esc_url($url)."' class='card teacher-item' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 25px; padding: 25px; border-radius: 15px;' data-name='".esc_attr($t->name)."'>
					<div style='background: var(--school-primary); color: #fff; width: 55px; height: 55px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 24px;'>".mb_substr($t->name, 0, 1)."</div>
					<div>
						<div style='font-weight: 800; font-size: 18px; color: var(--school-text-main);'>".esc_html($t->name)."</div>
						<small style='color: #64748b; font-weight: 500;'>".esc_html($t->department)."</small>
					</div>
				  </a>";
		}
		echo '</div>';
		?>
		<script>
		document.getElementById('teacher-search').addEventListener('input', function(e) {
			const term = e.target.value.toLowerCase();
			document.querySelectorAll('.teacher-item').forEach(item => {
				const name = item.getAttribute('data-name').toLowerCase();
				item.style.display = name.includes(term) ? 'flex' : 'none';
			});
		});
		</script>
		<?php
	} else {
		// Step 3: Upload & History
		$t_table = $wpdb->prefix . 'school_teachers';
		$teacher = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t_table WHERE id = %d", $teacher_id ) );
		if ( ! $teacher ) {
			echo '<p>المعلم غير موجود في الفهرس.</p>';
			return;
		}

		echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
		echo '<div>
				<h2 style="margin: 0;">بوابة المعلم: ' . esc_html( $teacher->name ) . '</h2>
				<p style="margin: 5px 0; color: #64748b;">المادة: ' . esc_html( (is_array($subjects[$subject_id]) ? $subjects[$subject_id]['name'] : $subjects[$subject_id]) ?? '' ) . '</p>
			  </div>';
		echo '<a href="' . esc_url( remove_query_arg( 'teacher_id' ) ) . '" class="button">تغيير المعلم</a>';
		echo '</div>';

		if ( isset($_GET['success']) ) {
			echo '<div class="card" style="background: #dcfce7; border-color: #86efac; color: #166534; margin-bottom: 20px;">تم استلام التحضير بنجاح، شكراً لك.</div>';
		}

		// Render the Upload Form
		?>
		<div class="portal-upload-container">
			<div class="card upload-card-modern">
				<h3 class="upload-title">إضافة تحضير درس جديد</h3>
				<form method="post" enctype="multipart/form-data" class="upload-form-simplified">
					<?php wp_nonce_field( 'school_public_upload', 'school_public_nonce' ); ?>
					<input type="hidden" name="teacher_id" value="<?php echo $teacher_id; ?>">
					<input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
					
					<p style="color: #64748b; margin-bottom: 20px;">سيتم توليد عنوان الدرس تلقائياً بناءً على تاريخ اليوم وترتيب التحضير.</p>
					
					<div class="form-row" style="margin-bottom: 25px;">
						<label style="font-weight: 700; display: block; margin-bottom: 10px;">ملف التحضير (PDF أو صور):</label>
						<div class="file-upload-wrapper">
							<input type="file" name="pdf_attachment" accept=".pdf,image/*" required style="width: 100%;">
						</div>
					</div>

					<button type="submit" name="school_submit_public" class="button button-primary button-upload-large">تقديم التحضير الآن</button>
				</form>
			</div>

			<div class="history-section-modern" style="margin-top: 40px;">
				<h3 style="margin-bottom: 20px; font-weight: 800;">سجل تسليماتك السابقة</h3>
				<div class="card" style="padding: 0; overflow: hidden; border-radius: 12px;">
					<?php 
					$table_lessons = $wpdb->prefix . 'school_lessons';
					$history = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_lessons WHERE teacher_id = %d AND subject_id = %d AND status != 'draft' ORDER BY submission_date DESC LIMIT 15", $teacher_id, $subject_id ) );
					if ( empty($history) ) {
						echo '<div style="padding: 40px; text-align: center; color: #94a3b8;">لا يوجد تسليمات سابقة لهذه المادة.</div>';
					} else {
						$status_map = array(
							'submitted' => 'قام المعلم بتسليم التحضير',
							'approved'  => 'قام المعلم بتسليم التحضير',
							'late'      => 'المعلم متأخر في التسليم',
						);
						?>
						<table class="wp-list-table widefat striped history-table-portal">
							<thead>
								<tr>
									<th>عنوان التحضير</th>
									<th>وقت التسليم</th>
									<th>الحالة</th>
									<th>الملف</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $history as $h ) : 
									$status_class = 'status-' . $h->status;
									$status_label = $status_map[$h->status] ?? $h->status;
								?>
									<tr>
										<td style="font-weight: 700;"><?php echo esc_html($h->lesson_title); ?></td>
										<td style="color: #64748b; font-size: 13px;"><?php echo esc_html($h->submission_date); ?></td>
										<td><span class="status-badge <?php echo $status_class; ?>"><?php echo esc_html($status_label); ?></span></td>
										<td>
											<a href="<?php echo esc_url($h->pdf_attachment); ?>" target="_blank" class="button button-small">معاينة</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<?php
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}
	
	echo '</div>'; // standalone-portal-refined

	// Branding: Bottom Name
	echo '<div class="portal-branding-bottom" style="text-align: center; margin-top: 60px; padding: 40px 0; border-top: 1px solid #e2e8f0;">';
	echo '<h3 style="color: #64748b; font-weight: 700;">' . esc_html($school_name) . '</h3>';
	echo '<p style="color: #94a3b8; font-size: 14px;">نظام إدارة تحضير الدروس الذكي</p>';
	echo '</div>';

	echo '</div>'; // school-portal-wrapper
}
