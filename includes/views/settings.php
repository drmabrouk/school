<?php
/**
 * View: System Settings
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function school_render_system_settings_view() {
	if ( ! current_user_can( 'manage_options' ) ) {
		echo '<p>عذراً، هذا القسم متاح فقط لمديري النظام.</p>';
		return;
	}
	$school_name = get_option( 'school_name', 'مدرستي' );
	$sub_tab = isset($_GET['sub_tab']) ? sanitize_text_field($_GET['sub_tab']) : 'general';
	?>
	<div class="content-section">
		<h2 style="margin-bottom: 25px;">إعدادات النظام المتقدمة</h2>

		<div class="settings-sub-nav" style="display: flex; gap: 15px; margin-bottom: 30px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
			<a href="<?php echo esc_url( add_query_arg('sub_tab', 'general') ); ?>" class="sub-nav-item <?php echo $sub_tab === 'general' ? 'active' : ''; ?>">معلومات المؤسسة</a>
			<a href="<?php echo esc_url( add_query_arg('sub_tab', 'notifications') ); ?>" class="sub-nav-item <?php echo $sub_tab === 'notifications' ? 'active' : ''; ?>">التنبيهات والتواصل</a>
			<a href="<?php echo esc_url( add_query_arg('sub_tab', 'design') ); ?>" class="sub-nav-item <?php echo $sub_tab === 'design' ? 'active' : ''; ?>">التصميم</a>
			<a href="<?php echo esc_url( add_query_arg('sub_tab', 'data') ); ?>" class="sub-nav-item <?php echo $sub_tab === 'data' ? 'active' : ''; ?>">إدارة البيانات</a>
		</div>

		<?php if ( $sub_tab === 'general' ) : 
			$school_logo = get_option('school_logo', '');
			$school_address = get_option('school_address', '');
			$school_phone = get_option('school_phone', '');
		?>
			<div class="card">
				<h3>بيانات المؤسسة التعليمية</h3>
				<form method="post">
					<?php wp_nonce_field( 'school_settings_action', 'school_settings_nonce' ); ?>
					
					<div class="design-section-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px;">
						<div class="form-row">
							<label style="display: block; margin-bottom: 8px; font-weight: 700;">اسم المدرسة:</label>
							<input type="text" name="school_name_val" value="<?php echo esc_attr($school_name); ?>" style="width: 100%;">
						</div>

						<div class="form-row">
							<label style="display: block; margin-bottom: 8px; font-weight: 700;">شعار المدرسة:</label>
							<div style="display: flex; gap: 10px; align-items: center;">
								<input type="text" name="school_logo" id="school_logo_url" value="<?php echo esc_attr($school_logo); ?>" style="flex: 1;">
								<button type="button" class="button school-upload-logo-btn" data-target="#school_logo_url">رفع شعار</button>
							</div>
							<?php if($school_logo): ?>
								<img src="<?php echo esc_url($school_logo); ?>" style="max-height: 80px; margin-top: 10px; display: block; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
							<?php endif; ?>
						</div>

						<div class="form-row">
							<label style="display: block; margin-bottom: 8px; font-weight: 700;">عنوان المدرسة:</label>
							<input type="text" name="school_address" value="<?php echo esc_attr($school_address); ?>" style="width: 100%;">
						</div>

						<div class="form-row">
							<label style="display: block; margin-bottom: 8px; font-weight: 700;">رقم هاتف المدرسة:</label>
							<input type="text" name="school_phone" value="<?php echo esc_attr($school_phone); ?>" style="width: 100%;">
						</div>
					</div>

					<button type="submit" name="school_save_institution" class="button button-primary" style="padding: 12px 30px; font-weight: 700;">حفظ بيانات المؤسسة</button>
				</form>
			</div>

		<?php elseif ( $sub_tab === 'notifications' ) :
			school_render_notification_settings_view();
		?>
		<?php elseif ( $sub_tab === 'data' ) :
			school_render_data_management_view();
		?>
		<?php elseif ( $sub_tab === 'design' ) :
			$primary = get_option('school_design_primary', '#F63049');
			$secondary = get_option('school_design_secondary', '#D02752');
			$accent1 = get_option('school_design_accent_1', '#8A244B');
			$accent2 = get_option('school_design_accent_2', '#111F35');
			$bg_color = get_option('school_design_bg_color', '#ffffff');
			$highlight = get_option('school_design_highlight', '#fff5f5');
			$font_size = get_option('school_design_font_size', '16px');
			$logo = get_option('school_logo', '');
			$monochromatic = get_option('school_design_monochromatic', '1');
			$dark_mode = get_option('school_design_dark_mode', '0');
		?>
			<div class="card school-design-settings-card">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
					<h3 style="margin: 0;">إعدادات مظهر النظام</h3>
					<form method="post" style="display: inline;">
						<?php wp_nonce_field( 'school_settings_action', 'school_settings_nonce' ); ?>
						<button type="submit" name="school_reset_design" class="button" onclick="return confirm('هل أنت متأكد من استعادة الألوان الافتراضية؟');">استعادة الألوان الافتراضية</button>
					</form>
				</div>

				<form method="post" id="school-design-form">
					<?php wp_nonce_field( 'school_settings_action', 'school_settings_nonce' ); ?>

					<div class="design-section-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">

						<div class="design-col">
							<h4 style="border-bottom: 2px solid #eee; padding-bottom: 10px;">لوحة الألوان</h4>

							<div class="form-row" style="margin-bottom: 20px;">
								<label style="display: block; margin-bottom: 8px; font-weight: 700;">اللون الأساسي:</label>
								<input type="color" name="school_design_primary" class="live-preview-color" data-var="--school-primary" value="<?php echo esc_attr($primary); ?>">
							</div>

							<div class="form-row" style="margin-bottom: 20px;">
								<label style="display: block; margin-bottom: 8px; font-weight: 700;">اللون الثانوي:</label>
								<input type="color" name="school_design_secondary" class="live-preview-color" data-var="--school-secondary" value="<?php echo esc_attr($secondary); ?>">
							</div>

							<div class="form-row" style="margin-bottom: 20px;">
								<label style="display: block; margin-bottom: 8px; font-weight: 700;">لون التمييز:</label>
								<input type="color" name="school_design_highlight" class="live-preview-color" data-var="--school-highlight" value="<?php echo esc_attr($highlight); ?>">
							</div>

							<div class="form-row" style="margin-bottom: 20px;">
								<label style="display: block; margin-bottom: 8px; font-weight: 700;">لون الخلفية:</label>
								<input type="color" name="school_design_bg_color" class="live-preview-color" data-var="--school-bg-white" value="<?php echo esc_attr($bg_color); ?>">
							</div>
						</div>

						<div class="design-col">
							<h4 style="border-bottom: 2px solid #eee; padding-bottom: 10px;">الهوية والبناء البصري</h4>

							<div class="form-row" style="margin-bottom: 20px;">
								<label style="display: block; margin-bottom: 8px; font-weight: 700;">شعار النظام:</label>
								<div style="display: flex; gap: 10px; align-items: center;">
									<input type="text" name="school_logo" id="school_design_logo_url" value="<?php echo esc_attr($logo); ?>" style="flex: 1;">
									<button type="button" class="button school-upload-logo-btn" data-target="#school_design_logo_url">رفع شعار</button>
								</div>
								<?php if($logo): ?>
									<img src="<?php echo esc_url($logo); ?>" class="logo-preview" style="max-height: 50px; margin-top: 10px; display: block;">
								<?php endif; ?>
							</div>

							<div class="form-row" style="margin-bottom: 20px;">
								<label style="display: block; margin-bottom: 8px; font-weight: 700;">حجم الخط الأساسي:</label>
								<input type="text" name="school_design_font_size" class="live-preview-input" data-var="--school-font-size" value="<?php echo esc_attr($font_size); ?>" placeholder="مثال: 16px" style="width: 100%;">
							</div>

							<div class="form-row" style="margin-bottom: 20px;">
								<label style="display: flex; align-items: center; gap: 10px; font-weight: 700;">
									<input type="checkbox" name="school_design_dark_mode" value="1" <?php checked($dark_mode, '1'); ?>>
									تفعيل الوضع الليلي
								</label>
							</div>
						</div>
					</div>

					<div class="form-row" style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px;">
						<label style="display: flex; align-items: center; gap: 10px; font-weight: 700;">
							<input type="checkbox" name="school_design_monochromatic" value="1" <?php checked($monochromatic, '1'); ?>>
							تفعيل النمط الاحترافي الأحادي
						</label>
						<p style="font-size: 12px; color: #666; margin-right: 25px;">عند تفعيل هذا الخيار، سيتم استخدام اللون الأبيض كخلفية أساسية مع تدرجات هادئة من الألوان المختارة.</p>
					</div>

					<div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
						<button type="submit" name="school_save_design" class="button button-primary" style="padding: 12px 40px; font-size: 16px; font-weight: 700;">حفظ كافة التغييرات</button>
					</div>
				</form>
			</div>

			<script>
			jQuery(document).ready(function($){
				// Live preview logic
				$('.live-preview-color').on('input', function() {
					var val = $(this).val();
					var variable = $(this).data('var');
					document.documentElement.style.setProperty(variable, val);
				});

				$('.live-preview-select, .live-preview-input').on('change keyup', function() {
					var val = $(this).val();
					var variable = $(this).data('var');
					document.documentElement.style.setProperty(variable, val);
				});

				// Logo upload with live preview
				$('.school-upload-logo-btn').click(function(e) {
					e.preventDefault();
					var target = $(this).data('target');
					var image = wp.media({
						title: 'رفع شعار النظام',
						multiple: false
					}).open()
					.on('select', function(e){
						var uploaded_image = image.state().get('selection').first();
						var image_url = uploaded_image.toJSON().url;
						$(target).val(image_url);
						$('.logo-preview, .top-bar-logo').attr('src', image_url).show();
					});
				});
			});
			</script>
		<?php endif; ?>
	</div>

	<style>
		.sub-nav-item { text-decoration: none; color: #64748b; padding: 8px 16px; border-radius: 8px; font-weight: 600; transition: all 0.2s; }
		.sub-nav-item:hover { background: #f1f5f9; color: var(--school-primary); }
		.sub-nav-item.active { background: var(--school-primary); color: #fff; }
	</style>
	<?php
}
