<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

$s   = WPCS_Settings::get();
$gcm = new WPCS_GCMHandler();
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wpcs_save_settings">
	<?php wp_nonce_field( 'wpcs_admin_action' ); ?>

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Google Consent Mode v2', 'wp-cookie-shield' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpcs_settings[gcm_enabled]" value="1" <?php checked( $s['gcm_enabled'] ); ?>>
					<?php esc_html_e( 'Output GCM v2 default consent snippet in &lt;head&gt;', 'wp-cookie-shield' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Default: Analytics Storage', 'wp-cookie-shield' ); ?></th>
			<td>
				<select name="wpcs_settings[gcm_default_analytics]">
					<option value="denied"  <?php selected( $s['gcm_default_analytics'], 'denied' ); ?>><?php esc_html_e( 'Denied', 'wp-cookie-shield' ); ?></option>
					<option value="granted" <?php selected( $s['gcm_default_analytics'], 'granted' ); ?>><?php esc_html_e( 'Granted', 'wp-cookie-shield' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Default: Ad Storage', 'wp-cookie-shield' ); ?></th>
			<td>
				<select name="wpcs_settings[gcm_default_ads]">
					<option value="denied"  <?php selected( $s['gcm_default_ads'], 'denied' ); ?>><?php esc_html_e( 'Denied', 'wp-cookie-shield' ); ?></option>
					<option value="granted" <?php selected( $s['gcm_default_ads'], 'granted' ); ?>><?php esc_html_e( 'Granted', 'wp-cookie-shield' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Wait for Update (ms)', 'wp-cookie-shield' ); ?></th>
			<td>
				<input type="number" name="wpcs_settings[gcm_wait_for_update_ms]" value="<?php echo esc_attr( $s['gcm_wait_for_update_ms'] ); ?>" class="small-text" min="0" max="5000">
			</td>
		</tr>
	</table>

	<h3><?php esc_html_e( 'Snippet Preview', 'wp-cookie-shield' ); ?></h3>
	<pre class="wpcs-snippet-preview"><?php
		$defaults = $gcm->build_defaults();
		echo esc_html( "<!-- WP Cookie Shield — Google Consent Mode v2 Defaults -->\n<script>\n  window.dataLayer = window.dataLayer || [];\n  function gtag(){dataLayer.push(arguments);}\n  gtag('consent', 'default', " . json_encode( $defaults, JSON_PRETTY_PRINT ) . ");\n  gtag('set', 'ads_data_redaction', true);\n  gtag('set', 'url_passthrough', false);\n</script>" );
	?></pre>

	<?php submit_button( __( 'Save Settings', 'wp-cookie-shield' ) ); ?>
</form>
