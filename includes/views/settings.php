<?php
/**
 * View: System Settings
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function school_render_system_settings_view() {
	$school_name = get_option( 'school_name', 'مدرستي' );
	$sub_tab = isset($_GET['sub_tab']) ? sanitize_text_field($_GET['sub_tab']) : 'general';
	?>
	<div class="content-section">
		<h2 style="margin-bottom: 25px;">إعدادات النظام المتقدمة</h2>

		<div class="settings-sub-nav" style="display: flex; gap: 15px; margin-bottom: 30px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
			<a href="<?php echo esc_url( add_query_arg('sub_tab', 'general') ); ?>" class="sub-nav-item <?php echo $sub_tab === 'general' ? 'active' : ''; ?>">معلومات المؤسسة</a>
			<a href="<?php echo esc_url( add_query_arg('sub_tab', 'advanced') ); ?>" class="sub-nav-item <?php echo $sub_tab === 'advanced' ? 'active' : ''; ?>">إعدادات النظام المتقدمة</a>
			<a href="<?php echo esc_url( add_query_arg('sub_tab', 'notifications') ); ?>" class="sub-nav-item <?php echo $sub_tab === 'notifications' ? 'active' : ''; ?>">التنبيهات والتواصل</a>
			<a href="<?php echo esc_url( add_query_arg('sub_tab', 'print') ); ?>" class="sub-nav-item <?php echo $sub_tab === 'print' ? 'active' : ''; ?>">مركز الطباعة</a>
			<a href="<?php echo esc_url( add_query_arg('sub_tab', 'design') ); ?>" class="sub-nav-item <?php echo $sub_tab === 'design' ? 'active' : ''; ?>">Design</a>
		</div>

		<?php if ( $sub_tab === 'general' ) : 
			$school_logo = get_option('school_logo', '');
			$school_address = get_option('school_address', '');
			$school_phone = get_option('school_phone', '');
		?>
			<div class="card">
				<h3>بيانات المؤسسة التعليمية (Institution Info)</h3>
				<form method="post" style="max-width: 600px;">
					<?php wp_nonce_field( 'school_settings_action', 'school_settings_nonce' ); ?>
					
					<div class="form-row" style="margin-bottom: 20px;">
						<label style="display: block; margin-bottom: 8px; font-weight: 700;">اسم المدرسة:</label>
						<input type="text" name="school_name_val" value="<?php echo esc_attr($school_name); ?>" style="width: 100%;">
					</div>

					<div class="form-row" style="margin-bottom: 20px;">
						<label style="display: block; margin-bottom: 8px; font-weight: 700;">شعار المدرسة (Logo):</label>
						<div style="display: flex; gap: 10px; align-items: center;">
							<input type="text" name="school_logo" id="school_logo_url" value="<?php echo esc_attr($school_logo); ?>" style="flex: 1;">
							<button type="button" class="button school-upload-logo-btn" data-target="#school_logo_url">رفع شعار</button>
						</div>
						<?php if($school_logo): ?>
							<img src="<?php echo esc_url($school_logo); ?>" style="max-height: 80px; margin-top: 10px; display: block; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
						<?php endif; ?>
					</div>

					<div class="form-row" style="margin-bottom: 20px;">
						<label style="display: block; margin-bottom: 8px; font-weight: 700;">عنوان المدرسة:</label>
						<input type="text" name="school_address" value="<?php echo esc_attr($school_address); ?>" style="width: 100%;">
					</div>

					<div class="form-row" style="margin-bottom: 20px;">
						<label style="display: block; margin-bottom: 8px; font-weight: 700;">رقم هاتف المدرسة:</label>
						<input type="text" name="school_phone" value="<?php echo esc_attr($school_phone); ?>" style="width: 100%;">
					</div>

					<button type="submit" name="school_save_institution" class="button button-primary" style="padding: 12px 30px; font-weight: 700;">حفظ بيانات المؤسسة</button>
				</form>
			</div>

		<?php elseif ( $sub_tab === 'advanced' ) : 
			$sub_days = get_option('school_submission_days', array('Monday', 'Tuesday', 'Wednesday', 'Thursday'));
			$deadline = get_option('school_submission_deadline', '07:00');
			$weekly_depts = get_option('school_weekly_departments', array('pe', 'health'));
		?>
			<div class="card">
				<h3>جدول مواعيد تسليم التحضير (Submission Schedule)</h3>
				<form method="post" style="max-width: 600px;">
					<?php wp_nonce_field( 'school_settings_action', 'school_settings_nonce' ); ?>
					
					<div class="form-row" style="margin-bottom: 20px;">
						<label style="display: block; margin-bottom: 8px; font-weight: 700;">أيام التسليم الأسبوعية:</label>
						<div style="display: flex; gap: 15px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; flex-wrap: wrap;">
							<?php 
							$days = array('Monday' => 'الإثنين', 'Tuesday' => 'الثلاثاء', 'Wednesday' => 'الأربعاء', 'Thursday' => 'الخميس', 'Friday' => 'الجمعة', 'Saturday' => 'السبت', 'Sunday' => 'الأحد');
							foreach($days as $key => $label): ?>
								<label style="display: flex; align-items: center; gap: 5px; font-size: 13px;">
									<input type="checkbox" name="school_submission_days[]" value="<?php echo $key; ?>" <?php checked(in_array($key, $sub_days)); ?>>
									<?php echo $label; ?>
								</label>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="form-row" style="margin-bottom: 20px;">
						<label style="display: block; margin-bottom: 8px; font-weight: 700;">وقت الموعد النهائي (Deadline):</label>
						<input type="time" name="school_submission_deadline" value="<?php echo esc_attr($deadline); ?>" style="width: 200px;">
						<p style="font-size: 12px; color: #64748b; margin-top: 5px;">يجب تسليم التحضير قبل هذا الوقت في الأيام المحددة.</p>
					</div>

					<div class="form-row" style="margin-bottom: 20px;">
						<label style="display: block; margin-bottom: 8px; font-weight: 700;">الأقسام المستثناة (تسليم مرة واحدة أسبوعياً - الإثنين):</label>
						<div style="display: flex; gap: 15px; background: #fef2f2; padding: 15px; border-radius: 8px; border: 1px solid #fee2e2;">
							<label style="display: flex; align-items: center; gap: 5px; font-size: 13px;">
								<input type="checkbox" name="school_weekly_departments[]" value="pe" <?php checked(in_array('pe', $weekly_depts)); ?>>
								التربية البدنية
							</label>
							<label style="display: flex; align-items: center; gap: 5px; font-size: 13px;">
								<input type="checkbox" name="school_weekly_departments[]" value="health" <?php checked(in_array('health', $weekly_depts)); ?>>
								المهارات الحياتية والأسرية (صحية)
							</label>
						</div>
					</div>

					<button type="submit" name="school_save_advanced" class="button button-primary" style="padding: 12px 30px; font-weight: 700;">حفظ إعدادات الجدول</button>
				</form>
			</div>
		<?php elseif ( $sub_tab === 'notifications' ) : 
			school_render_notification_settings_view();
		?>
		<?php elseif ( $sub_tab === 'print' ) : 
			school_render_print_template_settings();
		?>
		<?php elseif ( $sub_tab === 'design' ) :
			$primary = get_option('school_design_primary', '#F63049');
			$secondary = get_option('school_design_secondary', '#D02752');
			$accent1 = get_option('school_design_accent_1', '#8A244B');
			$accent2 = get_option('school_design_accent_2', '#111F35');
			$monochromatic = get_option('school_design_monochromatic', '1');
		?>
			<div class="card">
				<h3>إعدادات التصميم (Design Settings)</h3>
				<form method="post" style="max-width: 600px;">
					<?php wp_nonce_field( 'school_settings_action', 'school_settings_nonce' ); ?>

					<div class="form-row" style="margin-bottom: 20px;">
						<label style="display: block; margin-bottom: 8px; font-weight: 700;">اللون الأساسي (Primary Color):</label>
						<input type="color" name="school_design_primary" value="<?php echo esc_attr($primary); ?>">
					</div>

					<div class="form-row" style="margin-bottom: 20px;">
						<label style="display: block; margin-bottom: 8px; font-weight: 700;">اللون الثانوي (Secondary Color):</label>
						<input type="color" name="school_design_secondary" value="<?php echo esc_attr($secondary); ?>">
					</div>

					<div class="form-row" style="margin-bottom: 20px;">
						<label style="display: block; margin-bottom: 8px; font-weight: 700;">اللون التكميلي 1 (Accent 1):</label>
						<input type="color" name="school_design_accent_1" value="<?php echo esc_attr($accent1); ?>">
					</div>

					<div class="form-row" style="margin-bottom: 20px;">
						<label style="display: block; margin-bottom: 8px; font-weight: 700;">اللون التكميلي 2 (Accent 2):</label>
						<input type="color" name="school_design_accent_2" value="<?php echo esc_attr($accent2); ?>">
					</div>

					<div class="form-row" style="margin-bottom: 20px;">
						<label style="display: flex; align-items: center; gap: 10px; font-weight: 700;">
							<input type="checkbox" name="school_design_monochromatic" value="1" <?php checked($monochromatic, '1'); ?>>
							تفعيل التصميم الأحادي (Monochromatic White Base)
						</label>
					</div>

					<button type="submit" name="school_save_design" class="button button-primary" style="padding: 12px 30px; font-weight: 700;">حفظ إعدادات التصميم</button>
				</form>
			</div>
		<?php endif; ?>
	</div>

	<script>
	jQuery(document).ready(function($){
		$('.school-upload-logo-btn').click(function(e) {
			e.preventDefault();
			var target = $(this).data('target');
			var image = wp.media({ 
				title: 'رفع شعار المدرسة',
				multiple: false
			}).open()
			.on('select', function(e){
				var uploaded_image = image.state().get('selection').first();
				var image_url = uploaded_image.toJSON().url;
				$(target).val(image_url);
			});
		});
	});
	</script>

	<style>
		.sub-nav-item { text-decoration: none; color: #64748b; padding: 8px 16px; border-radius: 8px; font-weight: 600; transition: all 0.2s; }
		.sub-nav-item:hover { background: #f1f5f9; color: var(--school-primary); }
		.sub-nav-item.active { background: var(--school-primary); color: #fff; }
	</style>
	<?php
}
