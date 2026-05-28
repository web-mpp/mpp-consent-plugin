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
			<th scope="row"><?php esc_html_e( 'Honour Do Not Track', 'wp-cookie-shield' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpcs_settings[dnt_respect]" value="1" <?php checked( $s['dnt_respect'] ); ?>>
					<?php esc_html_e( 'Auto-deny Statistics &amp; Marketing when DNT header is set', 'wp-cookie-shield' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Geolocation', 'wp-cookie-shield' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpcs_settings[geo_enabled]" value="1" <?php checked( $s['geo_enabled'] ); ?>>
					<?php esc_html_e( 'Only show banner to visitors in regulated jurisdictions', 'wp-cookie-shield' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Cookie Policy Page', 'wp-cookie-shield' ); ?></th>
			<td>
				<?php
				wp_dropdown_pages( [
					'name'              => 'wpcs_settings[cookie_policy_page_id]',
					'show_option_none'  => __( '— Select page —', 'wp-cookie-shield' ),
					'option_none_value' => '0',
					'selected'          => $s['cookie_policy_page_id'],
				] );
				?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Privacy Policy Page', 'wp-cookie-shield' ); ?></th>
			<td>
				<?php
				wp_dropdown_pages( [
					'name'              => 'wpcs_settings[privacy_policy_page_id]',
					'show_option_none'  => __( '— Select page —', 'wp-cookie-shield' ),
					'option_none_value' => '0',
					'selected'          => $s['privacy_policy_page_id'],
				] );
				?>
			</td>
		</tr>
	</table>

	<?php submit_button( __( 'Save Settings', 'wp-cookie-shield' ) ); ?>
</form>
