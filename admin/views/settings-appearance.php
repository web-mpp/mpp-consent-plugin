<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

$s   = WPCS_Settings::get();
$app = array_merge( WPCS_Settings::get_defaults()['appearance'], (array) ( $s['appearance'] ?? [] ) );
?>
<style>
.wpcs-color-row { display:flex; align-items:center; gap:8px; }
.wpcs-color-row input[type="color"] { width:40px; height:32px; padding:2px; border:1px solid #c3c4c7; border-radius:3px; cursor:pointer; }
.wpcs-color-row input[type="text"]  { width:90px; font-family:monospace; }
.wpcs-app-section { margin-bottom:28px; }
.wpcs-app-section h3 { border-bottom:1px solid #e0e0e0; padding-bottom:6px; margin-bottom:14px; font-size:14px; }
.wpcs-preview-wrap { border:1px solid #c3c4c7; border-radius:4px; overflow:hidden; margin-top:24px; }
.wpcs-preview-label { background:#f6f7f7; padding:8px 12px; font-size:12px; color:#666; border-bottom:1px solid #e0e0e0; }
#wpcs-live-preview { padding:12px 20px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
#wpcs-preview-text { flex:1; font-size:14px; }
.wpcs-preview-actions { display:flex; gap:8px; }
.wpcs-preview-btn { padding:6px 16px; border-radius:4px; font-size:12px; font-weight:600; cursor:default; border:1px solid transparent; }
</style>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wpcs_save_settings">
	<?php wp_nonce_field( 'wpcs_admin_action' ); ?>

	<div class="wpcs-app-section">
		<h3><?php esc_html_e( 'Background & Borders', 'wp-cookie-shield' ); ?></h3>
		<table class="form-table" role="presentation">
			<?php
			$color_fields = [
				[ 'bg_primary',         __( 'Banner / Modal Background', 'wp-cookie-shield' ) ],
				[ 'bg_secondary',       __( 'Modal Row Background',       'wp-cookie-shield' ) ],
				[ 'border',             __( 'Border / Divider Colour',    'wp-cookie-shield' ) ],
			];
			foreach ( $color_fields as [ $key, $label ] ) : ?>
			<tr>
				<th scope="row"><?php echo esc_html( $label ); ?></th>
				<td>
					<div class="wpcs-color-row">
						<input type="color"
						       class="wpcs-color-swatch"
						       data-target="<?php echo esc_attr( $key ); ?>"
						       value="<?php echo esc_attr( $app[ $key ] ); ?>">
						<input type="text"
						       name="wpcs_settings[appearance][<?php echo esc_attr( $key ); ?>]"
						       id="wpcs-app-<?php echo esc_attr( $key ); ?>"
						       value="<?php echo esc_attr( $app[ $key ] ); ?>"
						       class="small-text wpcs-color-hex"
						       maxlength="7">
					</div>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
	</div>

	<div class="wpcs-app-section">
		<h3><?php esc_html_e( 'Text', 'wp-cookie-shield' ); ?></h3>
		<table class="form-table" role="presentation">
			<?php
			$text_fields = [
				[ 'text_primary', __( 'Primary Text', 'wp-cookie-shield' ) ],
				[ 'text_muted',   __( 'Muted / Description Text', 'wp-cookie-shield' ) ],
			];
			foreach ( $text_fields as [ $key, $label ] ) : ?>
			<tr>
				<th scope="row"><?php echo esc_html( $label ); ?></th>
				<td>
					<div class="wpcs-color-row">
						<input type="color" class="wpcs-color-swatch" data-target="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $app[ $key ] ); ?>">
						<input type="text"  name="wpcs_settings[appearance][<?php echo esc_attr( $key ); ?>]" id="wpcs-app-<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $app[ $key ] ); ?>" class="small-text wpcs-color-hex" maxlength="7">
					</div>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
	</div>

	<div class="wpcs-app-section">
		<h3><?php esc_html_e( 'Buttons', 'wp-cookie-shield' ); ?></h3>
		<table class="form-table" role="presentation">
			<?php
			$btn_fields = [
				[ 'btn_accept',         __( 'Accept All — Background',      'wp-cookie-shield' ) ],
				[ 'btn_accept_hover',   __( 'Accept All — Hover Background', 'wp-cookie-shield' ) ],
				[ 'btn_outline_border', __( 'Outline Buttons — Border',      'wp-cookie-shield' ) ],
			];
			foreach ( $btn_fields as [ $key, $label ] ) : ?>
			<tr>
				<th scope="row"><?php echo esc_html( $label ); ?></th>
				<td>
					<div class="wpcs-color-row">
						<input type="color" class="wpcs-color-swatch" data-target="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $app[ $key ] ); ?>">
						<input type="text"  name="wpcs_settings[appearance][<?php echo esc_attr( $key ); ?>]" id="wpcs-app-<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $app[ $key ] ); ?>" class="small-text wpcs-color-hex" maxlength="7">
					</div>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
	</div>

	<div class="wpcs-app-section">
		<h3><?php esc_html_e( 'Toggles', 'wp-cookie-shield' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Toggle Active Colour', 'wp-cookie-shield' ); ?></th>
				<td>
					<div class="wpcs-color-row">
						<input type="color" class="wpcs-color-swatch" data-target="toggle_active" value="<?php echo esc_attr( $app['toggle_active'] ); ?>">
						<input type="text"  name="wpcs_settings[appearance][toggle_active]" id="wpcs-app-toggle_active" value="<?php echo esc_attr( $app['toggle_active'] ); ?>" class="small-text wpcs-color-hex" maxlength="7">
					</div>
				</td>
			</tr>
		</table>
	</div>

	<div class="wpcs-app-section">
		<h3><?php esc_html_e( 'Layout', 'wp-cookie-shield' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Button Border Radius (px)', 'wp-cookie-shield' ); ?></th>
				<td>
					<input type="number" name="wpcs_settings[appearance][border_radius]" id="wpcs-app-border_radius" value="<?php echo esc_attr( $app['border_radius'] ); ?>" class="small-text" min="0" max="50">
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Font Size (px)', 'wp-cookie-shield' ); ?></th>
				<td>
					<input type="number" name="wpcs_settings[appearance][font_size]" id="wpcs-app-font_size" value="<?php echo esc_attr( $app['font_size'] ); ?>" class="small-text" min="10" max="24">
				</td>
			</tr>
		</table>
	</div>

	<?php /* Live preview */ ?>
	<div class="wpcs-preview-wrap">
		<div class="wpcs-preview-label"><?php esc_html_e( 'Live Preview', 'wp-cookie-shield' ); ?></div>
		<div id="wpcs-live-preview">
			<p id="wpcs-preview-text" style="margin:0;"><?php echo esc_html( $s['banner_text'] ); ?></p>
			<div class="wpcs-preview-actions">
				<button class="wpcs-preview-btn" id="wpcs-prev-outline" style="background:transparent;color:#fff;"><?php esc_html_e( 'Preferences', 'wp-cookie-shield' ); ?></button>
				<button class="wpcs-preview-btn" id="wpcs-prev-reject"  style="background:transparent;color:#fff;"><?php esc_html_e( 'Reject', 'wp-cookie-shield' ); ?></button>
				<button class="wpcs-preview-btn" id="wpcs-prev-accept"><?php esc_html_e( 'Accept All', 'wp-cookie-shield' ); ?></button>
			</div>
		</div>
	</div>

	<?php submit_button( __( 'Save Appearance', 'wp-cookie-shield' ) ); ?>
</form>

<script>
(function () {
	var preview   = document.getElementById('wpcs-live-preview');
	var prevText  = document.getElementById('wpcs-preview-text');
	var prevAccept  = document.getElementById('wpcs-prev-accept');
	var prevReject  = document.getElementById('wpcs-prev-reject');
	var prevOutline = document.getElementById('wpcs-prev-outline');
	var borderInput = document.getElementById('wpcs-app-border_radius');
	var fontInput   = document.getElementById('wpcs-app-font_size');

	function applyPreview() {
		var vals = {};
		document.querySelectorAll('.wpcs-color-hex').forEach(function (el) {
			var key = el.id.replace('wpcs-app-', '');
			vals[key] = el.value;
		});

		preview.style.background   = vals.bg_primary   || '#0a1628';
		preview.style.borderTop    = '1px solid ' + (vals.border || '#1e3254');
		prevText.style.color       = vals.text_primary  || '#ffffff';
		prevText.style.fontSize    = (fontInput.value || '14') + 'px';

		// Accept button
		prevAccept.style.background   = vals.btn_accept || '#e53e3e';
		prevAccept.style.borderColor  = vals.btn_accept || '#e53e3e';
		prevAccept.style.color        = '#ffffff';
		prevAccept.style.borderRadius = (borderInput.value || '4') + 'px';

		// Outline buttons
		[prevReject, prevOutline].forEach(function (btn) {
			btn.style.background   = 'transparent';
			btn.style.borderColor  = vals.btn_outline_border || '#4a5568';
			btn.style.color        = vals.text_primary || '#ffffff';
			btn.style.borderRadius = (borderInput.value || '4') + 'px';
		});
	}

	// Sync color swatch → text input
	document.querySelectorAll('.wpcs-color-swatch').forEach(function (swatch) {
		var target = swatch.dataset.target;
		var textEl = document.getElementById('wpcs-app-' + target);
		swatch.addEventListener('input', function () {
			if (textEl) { textEl.value = swatch.value; }
			applyPreview();
		});
	});

	// Sync text input → color swatch
	document.querySelectorAll('.wpcs-color-hex').forEach(function (el) {
		var target = el.id.replace('wpcs-app-', '');
		var swatch = document.querySelector('.wpcs-color-swatch[data-target="' + target + '"]');
		el.addEventListener('input', function () {
			if (/^#[0-9a-fA-F]{6}$/.test(el.value)) {
				if (swatch) swatch.value = el.value;
			}
			applyPreview();
		});
	});

	[borderInput, fontInput].forEach(function (el) {
		if (el) el.addEventListener('input', applyPreview);
	});

	applyPreview();
}());
</script>
