<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

$s = WPCS_Settings::get();
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wpcs_save_settings">
	<?php wp_nonce_field( 'wpcs_admin_action' ); ?>

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Banner Position', 'wp-cookie-shield' ); ?></th>
			<td>
				<select name="wpcs_settings[banner_position]">
					<option value="top"    <?php selected( $s['banner_position'], 'top' ); ?>><?php esc_html_e( 'Top', 'wp-cookie-shield' ); ?></option>
					<option value="bottom" <?php selected( $s['banner_position'], 'bottom' ); ?>><?php esc_html_e( 'Bottom', 'wp-cookie-shield' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Banner Text', 'wp-cookie-shield' ); ?></th>
			<td>
				<textarea name="wpcs_settings[banner_text]" rows="3" class="large-text"><?php echo esc_textarea( $s['banner_text'] ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Buttons', 'wp-cookie-shield' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpcs_settings[show_reject_button]" value="1" <?php checked( $s['show_reject_button'] ); ?>>
					<?php esc_html_e( 'Show Reject button', 'wp-cookie-shield' ); ?>
				</label><br>
				<label>
					<input type="checkbox" name="wpcs_settings[show_preferences_button]" value="1" <?php checked( $s['show_preferences_button'] ); ?>>
					<?php esc_html_e( 'Show Preferences button', 'wp-cookie-shield' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Policy Version', 'wp-cookie-shield' ); ?></th>
			<td>
				<input type="text" name="wpcs_settings[policy_version]" value="<?php echo esc_attr( $s['policy_version'] ); ?>" class="small-text">
				<p class="description"><?php esc_html_e( 'Bump this number to re-ask consent from all visitors.', 'wp-cookie-shield' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Consent Expiry (days)', 'wp-cookie-shield' ); ?></th>
			<td>
				<input type="number" name="wpcs_settings[consent_expiry_days]" value="<?php echo esc_attr( $s['consent_expiry_days'] ); ?>" class="small-text" min="1" max="730">
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Script Blocking', 'wp-cookie-shield' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpcs_settings[script_blocking_enabled]" value="1" <?php checked( $s['script_blocking_enabled'] ); ?>>
					<?php esc_html_e( 'Block non-essential scripts until consent given', 'wp-cookie-shield' ); ?>
				</label>
			</td>
		</tr>
	</table>

	<?php submit_button( __( 'Save Settings', 'wp-cookie-shield' ) ); ?>
</form>
