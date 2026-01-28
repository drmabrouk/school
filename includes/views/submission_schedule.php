<?php
/**
 * View: Submission Schedule Management
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function school_render_submission_schedule_view() {
	if ( ! current_user_can( 'manage_options' ) ) {
		echo '<p>عذراً، هذا القسم متاح فقط لمديري النظام.</p>';
		return;
	}
	$sub_days = get_option('school_submission_days', array('Monday', 'Tuesday', 'Wednesday', 'Thursday'));
	$deadline = get_option('school_submission_deadline', '07:00');
	$weekly_depts = get_option('school_weekly_departments', array('pe', 'health'));
	?>
	<div class="content-section">
		<h2>جدول مواعيد تسليم التحضير</h2>
		<div class="card">
			<h3>إعدادات الجدول</h3>
			<form method="post" style="max-width: 600px;">
				<?php wp_nonce_field( 'school_settings_action', 'school_settings_nonce' ); ?>

				<div class="form-row" style="margin-bottom: 25px;">
					<label style="display: block; margin-bottom: 10px; font-weight: 700;">أيام التسليم الأسبوعية:</label>
					<div style="display: flex; gap: 20px; background: var(--school-bg-alt); padding: 20px; border-radius: 12px; border: 1px solid var(--school-border); flex-wrap: wrap;">
						<?php
						$days = array('Monday' => 'الإثنين', 'Tuesday' => 'الثلاثاء', 'Wednesday' => 'الأربعاء', 'Thursday' => 'الخميس', 'Friday' => 'الجمعة', 'Saturday' => 'السبت', 'Sunday' => 'الأحد');
						foreach($days as $key => $label): ?>
							<label style="display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600;">
								<input type="checkbox" name="school_submission_days[]" value="<?php echo $key; ?>" <?php checked(in_array($key, $sub_days)); ?>>
								<?php echo $label; ?>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="form-row" style="margin-bottom: 25px;">
					<label style="display: block; margin-bottom: 10px; font-weight: 700;">وقت الموعد النهائي:</label>
					<input type="time" name="school_submission_deadline" value="<?php echo esc_attr($deadline); ?>" style="width: 200px; padding: 10px; border-radius: 8px; border: 1px solid var(--school-border);">
					<p style="font-size: 13px; color: var(--school-text-muted); margin-top: 8px;">يجب تسليم التحضير قبل هذا الوقت في الأيام المحددة.</p>
				</div>

				<div class="form-row" style="margin-bottom: 25px;">
					<label style="display: block; margin-bottom: 10px; font-weight: 700;">الأقسام المستثناة (تسليم مرة واحدة أسبوعياً - الإثنين):</label>
					<div style="display: flex; gap: 20px; background: #fff1f2; padding: 20px; border-radius: 12px; border: 1px solid #fecaca;">
						<label style="display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; color: #991b1b;">
							<input type="checkbox" name="school_weekly_departments[]" value="pe" <?php checked(in_array('pe', $weekly_depts)); ?>>
							التربية البدنية
						</label>
						<label style="display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; color: #991b1b;">
							<input type="checkbox" name="school_weekly_departments[]" value="health" <?php checked(in_array('health', $weekly_depts)); ?>>
							المهارات الحياتية والأسرية (صحية)
						</label>
					</div>
				</div>

				<button type="submit" name="school_save_advanced" class="button button-primary" style="padding: 14px 40px; font-weight: 700; font-size: 16px; border-radius: 10px;">حفظ إعدادات الجدول</button>
			</form>
		</div>
	</div>
	<?php
}
