<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

$s = WPCS_Settings::get();

if ( isset( $_GET['generated'] ) ) {
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Cookie Policy page created and linked.', 'wp-cookie-shield' ) . '</p></div>';
}
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
			<th scope="row"><?php esc_html_e( 'Global Privacy Control', 'wp-cookie-shield' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpcs_settings[gpc_respect]" value="1" <?php checked( $s['gpc_respect'] ?? true ); ?>>
					<?php esc_html_e( 'Automatically respect Global Privacy Control (GPC) signals from user browsers — auto-deny Marketing when Sec-GPC: 1 header is present', 'wp-cookie-shield' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Geolocation', 'wp-cookie-shield' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wpcs_settings[geo_enabled]" value="1" <?php checked( $s['geo_enabled'] ); ?>>
					<?php esc_html_e( 'Only show banner to visitors in regulated jurisdictions (EU, UK, Canada, California)', 'wp-cookie-shield' ); ?>
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
				if ( $s['cookie_policy_page_id'] > 0 ) {
					echo ' <a href="' . esc_url( get_permalink( $s['cookie_policy_page_id'] ) ) . '" target="_blank" class="button button-small">' . esc_html__( 'View Page', 'wp-cookie-shield' ) . '</a>';
				}
				?>
				<p class="description"><?php esc_html_e( 'This page should contain the [wpcs_cookie_policy] shortcode to automatically list all configured cookies.', 'wp-cookie-shield' ); ?></p>
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

<hr>
<h2><?php esc_html_e( 'Generate Cookie Policy Page', 'wp-cookie-shield' ); ?></h2>
<p>
	<?php esc_html_e( 'Click the button below to automatically create (or update) a Cookie Policy page containing a full table of all your configured cookies, grouped by category.', 'wp-cookie-shield' ); ?>
</p>
<p>
	<?php esc_html_e( 'The page will contain a [wpcs_cookie_policy] shortcode that always reflects your current cookie configuration.', 'wp-cookie-shield' ); ?>
</p>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wpcs_generate_policy">
	<?php wp_nonce_field( 'wpcs_admin_action' ); ?>
	<input type="submit" class="button button-secondary" value="<?php echo esc_attr( $s['cookie_policy_page_id'] > 0 ? __( 'Regenerate Cookie Policy Page', 'wp-cookie-shield' ) : __( 'Generate Cookie Policy Page', 'wp-cookie-shield' ) ); ?>">
</form>
