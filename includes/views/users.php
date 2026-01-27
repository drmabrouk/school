<?php
/**
 * View: User Management
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function school_render_user_management_view() {
	$roles = array(
		'school_coordinator' => 'منسقو المواد',
		'school_supervisor'  => 'المشرفون',
		'school_manager'     => 'المدراء',
		'administrator'      => 'مديرو النظام',
	);

	$error = get_transient( 'school_user_error' );
	if ( $error ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
		delete_transient( 'school_user_error' );
	}
	if ( isset($_GET['user_added']) ) {
		echo '<div class="updated"><p>تم إضافة المستخدم بنجاح.</p></div>';
	}

	?>
	<div class="content-section">
		<h2>إدارة مستخدمي النظام</h2>

		<div class="card">
			<h3>إضافة مستخدم جديد</h3>
			<form method="post">
				<?php wp_nonce_field( 'school_add_user_action', 'school_add_user_nonce' ); ?>
				<div class="grid-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
					<div class="form-row">
						<label>اسم المستخدم (Login):</label>
						<input type="text" name="user_login" required style="width: 100%;">
					</div>
					<div class="form-row">
						<label>الاسم المعروض:</label>
						<input type="text" name="display_name" required style="width: 100%;">
					</div>
					<div class="form-row">
						<label>رقم الهاتف (واتساب):</label>
						<input type="text" name="phone_number" placeholder="9665xxxxxxxx" style="width: 100%;">
					</div>
					<div class="form-row">
						<label>البريد الإلكتروني:</label>
						<input type="email" name="user_email" required style="width: 100%;">
					</div>
					<div class="form-row">
						<label>كلمة المرور:</label>
						<input type="password" name="user_pass" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
					</div>
					<div class="form-row">
						<label>الدور الوظيفي:</label>
						<select name="user_role" required style="width: 100%;">
							<?php foreach ( $roles as $slug => $label ) : ?>
								<option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($label); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<button type="submit" class="button button-primary" style="margin-top: 20px;">إضافة المستخدم</button>
			</form>
		</div>
		
		<?php foreach ( $roles as $role_slug => $role_label ) : 
			$users = get_users( array( 'role' => $role_slug ) );
			// Filter out System Administrator (user with ID 1)
			$users = array_filter( $users, function($u) {
				return $u->ID != 1;
			});
			if ( empty($users) ) continue;
		?>
			<div class="card">
				<h3><?php echo $role_label; ?> (<?php echo count($users); ?>)</h3>
				<table class="wp-list-table widefat striped">
					<thead><tr><th>الاسم</th><th>البريد الإلكتروني</th><th>الإجراءات</th></tr></thead>
					<tbody>
						<?php foreach($users as $u) : ?>
							<tr>
								<td style="font-weight: 600;"><?php echo esc_html($u->display_name); ?></td>
								<td><?php echo esc_html($u->user_email); ?></td>
								<td>
									<a href="<?php echo get_edit_user_link($u->ID); ?>" class="button button-small">تعديل</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
}
