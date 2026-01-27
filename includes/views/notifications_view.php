<?php
/**
 * View: Notification Settings
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function school_render_notification_settings_view() {
	$notif_settings = get_option( 'school_notification_settings', array(
		'email_reminders' => true,
		'browser_alerts'  => true,
		'whatsapp_manual' => true,
		'coordinator_notif' => true,
	) );

	if ( isset( $_POST['save_notif_settings'] ) && check_admin_referer( 'school_settings_action', 'school_settings_nonce' ) ) {
		$notif_settings = array(
			'email_reminders'  => isset( $_POST['email_reminders'] ),
			'browser_alerts'   => isset( $_POST['browser_alerts'] ),
			'whatsapp_manual'  => isset( $_POST['whatsapp_manual'] ),
			'coordinator_notif' => isset( $_POST['coordinator_notif'] ),
		);
		update_option( 'school_notification_settings', $notif_settings );
		echo '<div class="updated"><p>تم حفظ إعدادات التنبيهات بنجاح.</p></div>';
	}
	?>
	<div class="content-section">
		<h2>إعدادات التنبيهات ونظام التواصل</h2>
		<div class="card">
			<form method="post">
				<?php wp_nonce_field( 'school_settings_action', 'school_settings_nonce' ); ?>
				
				<div class="settings-group" style="display: flex; flex-direction: column; gap: 15px;">
					<label style="display: flex; align-items: center; gap: 10px; font-size: 16px;">
						<input type="checkbox" name="email_reminders" <?php checked($notif_settings['email_reminders']); ?>>
						تفعيل رسائل البريد الإلكتروني التذكيرية للمعلمين
					</label>
					
					<label style="display: flex; align-items: center; gap: 10px; font-size: 16px;">
						<input type="checkbox" name="browser_alerts" <?php checked($notif_settings['browser_alerts']); ?>>
						تفعيل تنبيهات المتصفح الفورية (Real-time Alerts)
					</label>
					
					<label style="display: flex; align-items: center; gap: 10px; font-size: 16px;">
						<input type="checkbox" name="whatsapp_manual" <?php checked($notif_settings['whatsapp_manual']); ?>>
						إظهار أزرار التواصل عبر واتساب للمتأخرين
					</label>

					<label style="display: flex; align-items: center; gap: 10px; font-size: 16px;">
						<input type="checkbox" name="coordinator_notif" <?php checked($notif_settings['coordinator_notif']); ?>>
						تنبيه المنسقين عند وصول تحضيرات جديدة للمراجعة
					</label>
				</div>

				<button type="submit" name="save_notif_settings" class="button button-primary" style="margin-top: 30px; padding: 12px 24px;">حفظ التغييرات</button>
			</form>
		</div>

		<div class="card" style="background: #fdf2f2; border-color: #fecaca;">
			<h3 style="color: #991b1b;">ملاحظة حول واتساب</h3>
			<p style="color: #7f1d1d;">يعتمد نظام الواتساب حالياً على التواصل المباشر عبر روابط "wa.me" لضمان الخصوصية وسرعة التواصل اليدوي من قبل المشرفين. سيتم فتح نافذة جديدة في المتصفح أو تطبيق الواتساب مع رسالة جاهزة للإرسال.</p>
		</div>
	</div>
	<?php
}
