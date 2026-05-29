<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

$s          = WPCS_Settings::get();
$categories = $s['categories'];
$defaults   = WPCS_Settings::get_defaults()['categories'];
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wpcs_save_settings">
	<?php wp_nonce_field( 'wpcs_admin_action' ); ?>

	<p><?php esc_html_e( 'Edit the label, description, and consent expiry for each category shown in the preferences modal.', 'wp-cookie-shield' ); ?></p>

	<table class="widefat fixed striped">
		<thead>
			<tr>
				<th style="width:110px;"><?php esc_html_e( 'Key', 'wp-cookie-shield' ); ?></th>
				<th style="width:160px;"><?php esc_html_e( 'Label', 'wp-cookie-shield' ); ?></th>
				<th><?php esc_html_e( 'Description', 'wp-cookie-shield' ); ?></th>
				<th style="width:130px;"><?php esc_html_e( 'Expiry (days)', 'wp-cookie-shield' ); ?></th>
				<th style="width:90px;"><?php esc_html_e( 'Type', 'wp-cookie-shield' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $categories as $key => $cat ) :
				$expiry = $cat['expiry_days'] ?? ( $defaults[ $key ]['expiry_days'] ?? 30 );
			?>
			<tr>
				<td><code><?php echo esc_html( $key ); ?></code></td>
				<td>
					<input type="text"
					       name="wpcs_settings[categories][<?php echo esc_attr( $key ); ?>][label]"
					       value="<?php echo esc_attr( $cat['label'] ); ?>"
					       class="regular-text">
				</td>
				<td>
					<textarea name="wpcs_settings[categories][<?php echo esc_attr( $key ); ?>][description]"
					          rows="2" class="large-text"><?php echo esc_textarea( $cat['description'] ); ?></textarea>
				</td>
				<td>
					<?php if ( ! empty( $cat['locked'] ) ) : ?>
						<span style="color:#6b7280;font-size:12px;"><?php esc_html_e( 'Does not expire', 'wp-cookie-shield' ); ?></span>
						<input type="hidden" name="wpcs_settings[categories][<?php echo esc_attr( $key ); ?>][expiry_days]" value="<?php echo esc_attr( $expiry ); ?>">
					<?php else : ?>
						<input type="number"
						       name="wpcs_settings[categories][<?php echo esc_attr( $key ); ?>][expiry_days]"
						       value="<?php echo esc_attr( $expiry ); ?>"
						       class="small-text" min="1" max="730">
						<p class="description" style="font-size:11px;"><?php esc_html_e( 'days', 'wp-cookie-shield' ); ?></p>
					<?php endif; ?>
				</td>
				<td>
					<?php if ( ! empty( $cat['locked'] ) ) : ?>
						<span class="wpcs-badge wpcs-badge-locked"><?php esc_html_e( 'Always On', 'wp-cookie-shield' ); ?></span>
					<?php else : ?>
						<span class="wpcs-badge"><?php esc_html_e( 'Opt-In', 'wp-cookie-shield' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php submit_button( __( 'Save Settings', 'wp-cookie-shield' ) ); ?>
</form>
