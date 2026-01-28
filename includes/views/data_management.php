<?php
/**
 * View: Data Management (Backup, Sync, Reset)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function school_render_data_management_view() {
	if ( isset($_GET['school_data_synced']) ) {
		echo '<div class="updated"><p>تمت مزامنة البيانات وتحديث الإحصائيات بنجاح.</p></div>';
	}
	if ( isset($_GET['school_data_imported']) ) {
		echo '<div class="updated"><p>تم استيراد البيانات بنجاح.</p></div>';
	}
	if ( isset($_GET['school_data_reset']) ) {
		echo '<div class="updated"><p>تمت إعادة تعيين النظام بنجاح.</p></div>';
	}
	if ( $error = get_transient('school_data_error') ) {
		echo '<div class="error"><p>' . esc_html($error) . '</p></div>';
		delete_transient('school_data_error');
	}
	?>
	<div class="data-management-view">

		<!-- System Synchronization -->
		<div class="card" style="border-right: 4px solid #3b82f6;">
			<h3>مزامنة النظام (System Synchronization)</h3>
			<p style="color: #64748b;">تقوم هذه العملية بمراجعة كافة التسليمات الحالية وإعادة حساب الإحصائيات لضمان مطابقتها للجداول الزمنية المحدثة.</p>
			<form method="post">
				<?php wp_nonce_field( 'school_data_action', 'school_data_nonce' ); ?>
				<button type="submit" name="school_sync_data" class="button button-primary">بدء المزامنة الآن</button>
			</form>
		</div>

		<div class="grid-2-cols" style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
			<!-- Export Data -->
			<div class="card" style="border-right: 4px solid #10b981;">
				<h3>تصدير البيانات (Backup / Export)</h3>
				<p style="color: #64748b;">قم بتحميل نسخة احتياطية كاملة من بيانات النظام (المعلمون، الطلاب، التحضيرات، الإعدادات) بتنسيق JSON.</p>
				<form method="post">
					<?php wp_nonce_field( 'school_data_action', 'school_data_nonce' ); ?>
					<button type="submit" name="school_export_data" class="button" style="background: #10b981; color: #fff; border: none; padding: 10px 20px;">تصدير كافة البيانات (JSON)</button>
				</form>
			</div>

			<!-- Import Data -->
			<div class="card" style="border-right: 4px solid #f59e0b;">
				<h3>استيراد البيانات (Restore / Import)</h3>
				<p style="color: #64748b;">اختر ملف JSON الذي قمت بتصديره سابقاً لاستعادة البيانات. <strong>تنبيه: سيتم دمج البيانات الجديدة مع الحالية.</strong></p>
				<form method="post" enctype="multipart/form-data">
					<?php wp_nonce_field( 'school_data_action', 'school_data_nonce' ); ?>
					<input type="file" name="import_file" accept=".json" required style="margin-bottom: 15px; display: block;">
					<button type="submit" name="school_import_data" class="button" style="background: #f59e0b; color: #fff; border: none; padding: 10px 20px;">بدء عملية الاستيراد</button>
				</form>
			</div>
		</div>

		<!-- Full System Reset -->
		<div class="card" style="border: 2px solid #ef4444; background: #fff1f2; margin-top: 40px;">
			<h3 style="color: #991b1b;">إعادة تعيين النظام بالكامل (Factory Reset)</h3>
			<p style="color: #7f1d1d; font-weight: 700;">تحذير: هذا الإجراء سيقوم بحذف كافة البيانات نهائياً، بما في ذلك:</p>
			<ul style="color: #b91c1c; margin-right: 20px; list-style-type: disc;">
				<li>كافة المعلمين والطلاب المسجلين.</li>
				<li>جميع سجلات تحضير الدروس والملفات المرفقة.</li>
				<li>تقارير الالتزام والإحصائيات التاريخية.</li>
				<li>سجلات النشاط والتنبيهات.</li>
				<li>إعادة ضبط إعدادات النظام للحالة الافتراضية.</li>
			</ul>
			<p style="color: #991b1b; margin-top: 15px;">لتنفيذ هذا الإجراء، يرجى إدخال كلمة مرور مدير النظام الحالية للتأكيد:</p>

			<form method="post" onsubmit="return confirm('هل أنت متأكد تماماً؟ سيتم حذف كافة البيانات ولا يمكن التراجع عن هذا الإجراء.');">
				<?php wp_nonce_field( 'school_data_action', 'school_data_nonce' ); ?>
				<div style="display: flex; gap: 15px; align-items: center;">
					<input type="password" name="admin_password" placeholder="كلمة المرور..." required style="flex: 1; max-width: 300px;">
					<button type="submit" name="school_factory_reset" class="button" style="background: #ef4444; color: #fff; border: none; padding: 10px 30px; font-weight: 800;">تنفيذ مسح شامل للبيانات</button>
				</div>
			</form>
		</div>

	</div>
	<?php
}
