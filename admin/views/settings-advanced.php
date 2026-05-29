<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

$s = WPCS_Settings::get();

if ( isset( $_GET['reset'] ) ) {
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings reset to defaults.', 'wp-cookie-shield' ) . '</p></div>';
}
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wpcs_save_settings">
	<?php wp_nonce_field( 'wpcs_admin_action' ); ?>

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Shared Consent (Subdomains)', 'wp-cookie-shield' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpcs_settings[shared_consent]" value="1" <?php checked( $s['shared_consent'] ?? false ); ?>>
					<?php esc_html_e( 'Share cookie preferences across all subdomains of this site', 'wp-cookie-shield' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'When enabled, the consent cookie domain is set to the root domain so subdomains share the same consent state.', 'wp-cookie-shield' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Remove All Data on Uninstall', 'wp-cookie-shield' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpcs_settings[remove_data_on_uninstall]" value="1" <?php checked( $s['remove_data_on_uninstall'] ?? false ); ?>>
					<?php esc_html_e( 'Delete all plugin data (settings, consent logs, cookie declarations) when the plugin is uninstalled', 'wp-cookie-shield' ); ?>
				</label>
				<p class="description" style="color:#d63638;"><?php esc_html_e( 'Warning: This action cannot be undone. Leave unchecked to preserve data if you reinstall.', 'wp-cookie-shield' ); ?></p>
			</td>
		</tr>
	</table>

	<?php submit_button( __( 'Save Settings', 'wp-cookie-shield' ) ); ?>
</form>

<hr>
<h2><?php esc_html_e( 'Reset to Defaults', 'wp-cookie-shield' ); ?></h2>
<p><?php esc_html_e( 'This will reset all banner content, categories, button text, and configuration back to the default English state. Consent logs and cookie declarations are not affected. This action cannot be undone.', 'wp-cookie-shield' ); ?></p>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Reset all settings to defaults? This cannot be undone.', 'wp-cookie-shield' ) ); ?>')">
	<input type="hidden" name="action" value="wpcs_reset_defaults">
	<?php wp_nonce_field( 'wpcs_reset_defaults' ); ?>
	<input type="submit" class="button button-secondary" value="<?php esc_attr_e( 'Reset Content to Defaults', 'wp-cookie-shield' ); ?>">
</form>
