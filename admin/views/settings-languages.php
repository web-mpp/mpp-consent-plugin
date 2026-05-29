<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

$s            = WPCS_Settings::get();
$site_locale  = get_locale();
$locale_texts = (array) ( $s['locale_texts'] ?? [] );

if ( isset( $_GET['applied'] ) ) {
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Language enabled and banner text saved.', 'wp-cookie-shield' ) . '</p></div>';
}
if ( isset( $_GET['cleared'] ) ) {
	echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Language disabled — that locale will now use the default banner text.', 'wp-cookie-shield' ) . '</p></div>';
}

$translations = [
	'en_US' => [ 'label' => 'English (US)',    'flag' => '🇺🇸', 'banner_text' => 'We use cookies to improve your experience on our site. By using our site, you consent to cookies.' ],
	'en_GB' => [ 'label' => 'English (UK)',    'flag' => '🇬🇧', 'banner_text' => 'We use cookies to improve your experience on our site. By using our site, you consent to cookies.' ],
	'fr_FR' => [ 'label' => 'Français',        'flag' => '🇫🇷', 'banner_text' => 'Nous utilisons des cookies pour améliorer votre expérience sur notre site. En utilisant notre site, vous consentez à l\'utilisation de cookies.' ],
	'fr_CA' => [ 'label' => 'Français (CA)',   'flag' => '🇨🇦', 'banner_text' => 'Nous utilisons des cookies pour améliorer votre expérience sur notre site. En utilisant notre site, vous consentez à l\'utilisation de cookies.' ],
	'de_DE' => [ 'label' => 'Deutsch',         'flag' => '🇩🇪', 'banner_text' => 'Wir verwenden Cookies, um Ihre Erfahrung auf unserer Website zu verbessern. Durch die Nutzung unserer Website stimmen Sie der Verwendung von Cookies zu.' ],
	'es_ES' => [ 'label' => 'Español',         'flag' => '🇪🇸', 'banner_text' => 'Utilizamos cookies para mejorar su experiencia en nuestro sitio. Al utilizar nuestro sitio, usted acepta el uso de cookies.' ],
	'it_IT' => [ 'label' => 'Italiano',        'flag' => '🇮🇹', 'banner_text' => 'Utilizziamo i cookie per migliorare la tua esperienza sul nostro sito. Utilizzando il nostro sito, acconsenti all\'uso dei cookie.' ],
	'nl_NL' => [ 'label' => 'Nederlands',      'flag' => '🇳🇱', 'banner_text' => 'We gebruiken cookies om uw ervaring op onze website te verbeteren. Door onze website te gebruiken, stemt u in met het gebruik van cookies.' ],
	'pt_PT' => [ 'label' => 'Português',       'flag' => '🇵🇹', 'banner_text' => 'Utilizamos cookies para melhorar a sua experiência no nosso site. Ao utilizar o nosso site, está a consentir o uso de cookies.' ],
	'pt_BR' => [ 'label' => 'Português (BR)',  'flag' => '🇧🇷', 'banner_text' => 'Usamos cookies para melhorar sua experiência em nosso site. Ao usar nosso site, você concorda com o uso de cookies.' ],
	'pl_PL' => [ 'label' => 'Polski',          'flag' => '🇵🇱', 'banner_text' => 'Używamy plików cookie, aby poprawić Twoje doświadczenia na naszej stronie. Korzystając z naszej strony, wyrażasz zgodę na używanie plików cookie.' ],
	'sv_SE' => [ 'label' => 'Svenska',         'flag' => '🇸🇪', 'banner_text' => 'Vi använder cookies för att förbättra din upplevelse på vår webbplats. Genom att använda vår webbplats godkänner du användningen av cookies.' ],
	'da_DK' => [ 'label' => 'Dansk',           'flag' => '🇩🇰', 'banner_text' => 'Vi bruger cookies til at forbedre din oplevelse på vores hjemmeside. Ved at bruge vores hjemmeside accepterer du brugen af cookies.' ],
];

$enabled_locales = array_keys( $locale_texts );
$enabled_count   = count( $enabled_locales );
?>

<style>
.wpcs-lang-summary {
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 4px;
	padding: 16px 20px;
	margin-bottom: 20px;
	max-width: 900px;
}
.wpcs-lang-summary h3 { margin: 0 0 10px; font-size: 14px; color: #1d2327; }
.wpcs-lang-chips { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.wpcs-lang-chip {
	display: inline-flex;
	align-items: center;
	gap: 5px;
	padding: 4px 10px;
	border-radius: 12px;
	font-size: 12px;
	font-weight: 600;
	background: #d1fae5;
	color: #065f46;
	border: 1px solid #6ee7b7;
}
.wpcs-lang-chip--current {
	background: #dbeafe;
	color: #1e40af;
	border-color: #93c5fd;
}
.wpcs-lang-chip--none { color: #6b7280; font-style: italic; font-weight: 400; }
.wpcs-lang-how {
	margin-top: 10px;
	padding-top: 10px;
	border-top: 1px solid #e5e7eb;
	font-size: 12px;
	color: #6b7280;
}
.wpcs-lang-row--enabled { background: #f0fdf4 !important; }
.wpcs-lang-row--enabled td:first-child { border-left: 3px solid #22c55e; }
.wpcs-lang-row--current td:first-child { border-left: 3px solid #3b82f6; }
.wpcs-lang-enabled-mark { color: #16a34a; font-size: 16px; font-weight: bold; margin-right: 4px; }
.wpcs-lang-preview {
	font-size: 12px;
	color: #555;
	font-style: italic;
	margin-top: 4px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	max-width: 340px;
}
.wpcs-lang-edit-area { display: none; margin-top: 8px; }
.wpcs-lang-edit-area.is-open { display: block; }
</style>

<?php /* ── Enabled languages summary ── */ ?>
<div class="wpcs-lang-summary">
	<h3><?php esc_html_e( 'Enabled Languages', 'wp-cookie-shield' ); ?></h3>
	<div class="wpcs-lang-chips">
		<?php if ( $enabled_count > 0 ) :
			foreach ( $enabled_locales as $loc ) :
				$is_current = ( $loc === $site_locale );
				$chip_label = isset( $translations[ $loc ] ) ? $translations[ $loc ]['flag'] . ' ' . $translations[ $loc ]['label'] : $loc;
				$chip_class = $is_current ? 'wpcs-lang-chip wpcs-lang-chip--current' : 'wpcs-lang-chip';
				echo '<span class="' . esc_attr( $chip_class ) . '">' . esc_html( $chip_label );
				if ( $is_current ) echo ' <em style="font-weight:400;">(active)</em>';
				echo '</span>';
			endforeach;
		else : ?>
			<span class="wpcs-lang-chip--none"><?php esc_html_e( 'None — all visitors see the Default Banner Text from General settings.', 'wp-cookie-shield' ); ?></span>
		<?php endif; ?>
	</div>
	<div class="wpcs-lang-how">
		<?php esc_html_e( 'When TranslatePress, WPML, or Polylang switches the page language, the matching banner text is served automatically. Languages not listed here fall back to General → Banner Text.', 'wp-cookie-shield' ); ?>
		&nbsp;
		<?php
		$current_label = isset( $translations[ $site_locale ] ) ? $translations[ $site_locale ]['label'] : $site_locale;
		if ( isset( $locale_texts[ $site_locale ] ) ) {
			printf( esc_html__( 'Current admin locale (%s): serving custom text ✓', 'wp-cookie-shield' ), esc_html( $current_label ) );
		} else {
			printf( esc_html__( 'Current admin locale (%s): using default text.', 'wp-cookie-shield' ), esc_html( $current_label ) );
		}
		?>
	</div>
</div>

<?php /* ── Per-language table ── */ ?>
<table class="widefat fixed" style="max-width:900px;">
	<thead>
		<tr>
			<th style="width:180px;"><?php esc_html_e( 'Language', 'wp-cookie-shield' ); ?></th>
			<th><?php esc_html_e( 'Banner Text', 'wp-cookie-shield' ); ?></th>
			<th style="width:110px;"></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $translations as $locale => $trans ) :
			$is_site    = ( $locale === $site_locale );
			$saved_text = $locale_texts[ $locale ] ?? null;
			$is_enabled = ( $saved_text !== null );
			$row_class  = $is_enabled ? 'wpcs-lang-row--enabled' : '';
			if ( $is_site ) $row_class .= ' wpcs-lang-row--current';
			$edit_text  = $saved_text ?? $trans['banner_text'];
		?>
		<tr class="<?php echo esc_attr( trim( $row_class ) ); ?>">

			<td style="vertical-align:top;padding-top:12px;">
				<?php if ( $is_enabled ) : ?>
					<span class="wpcs-lang-enabled-mark">✓</span>
				<?php endif; ?>
				<strong><?php echo esc_html( $trans['flag'] . ' ' . $trans['label'] ); ?></strong><br>
				<code style="font-size:11px;color:#888;"><?php echo esc_html( $locale ); ?></code>
				<?php if ( $is_site ) : ?>
					<br><span class="wpcs-badge wpcs-badge-statistics" style="margin-top:3px;display:inline-block;font-size:10px;"><?php esc_html_e( 'current locale', 'wp-cookie-shield' ); ?></span>
				<?php endif; ?>
			</td>

			<td style="vertical-align:top;padding-top:12px;">
				<?php if ( $is_enabled ) : ?>
					<div class="wpcs-lang-preview"><?php echo esc_html( $saved_text ); ?></div>
					<div class="wpcs-lang-edit-area" id="edit-<?php echo esc_attr( $locale ); ?>">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="wpcs_apply_language">
							<input type="hidden" name="locale" value="<?php echo esc_attr( $locale ); ?>">
							<?php wp_nonce_field( 'wpcs_admin_action' ); ?>
							<textarea name="banner_text" rows="2" style="width:100%;font-size:12px;box-sizing:border-box;"><?php echo esc_textarea( $edit_text ); ?></textarea>
							<input type="submit" class="button button-small button-primary" value="<?php esc_attr_e( 'Save', 'wp-cookie-shield' ); ?>">
							<button type="button" class="button button-small wpcs-cancel-edit" data-locale="<?php echo esc_attr( $locale ); ?>"><?php esc_html_e( 'Cancel', 'wp-cookie-shield' ); ?></button>
						</form>
					</div>
				<?php else : ?>
					<div style="color:#999;font-size:12px;font-style:italic;padding-top:2px;"><?php esc_html_e( 'Not enabled — uses default banner text', 'wp-cookie-shield' ); ?></div>
					<div class="wpcs-lang-edit-area" id="edit-<?php echo esc_attr( $locale ); ?>">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="wpcs_apply_language">
							<input type="hidden" name="locale" value="<?php echo esc_attr( $locale ); ?>">
							<?php wp_nonce_field( 'wpcs_admin_action' ); ?>
							<textarea name="banner_text" rows="2" style="width:100%;font-size:12px;box-sizing:border-box;"><?php echo esc_textarea( $edit_text ); ?></textarea>
							<input type="submit" class="button button-small button-primary" value="<?php esc_attr_e( 'Enable', 'wp-cookie-shield' ); ?>">
							<button type="button" class="button button-small wpcs-cancel-edit" data-locale="<?php echo esc_attr( $locale ); ?>"><?php esc_html_e( 'Cancel', 'wp-cookie-shield' ); ?></button>
						</form>
					</div>
				<?php endif; ?>
			</td>

			<td style="vertical-align:top;padding-top:12px;white-space:nowrap;">
				<?php if ( $is_enabled ) : ?>
					<button type="button" class="button button-small wpcs-toggle-edit" data-locale="<?php echo esc_attr( $locale ); ?>"><?php esc_html_e( 'Edit', 'wp-cookie-shield' ); ?></button>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
						<input type="hidden" name="action" value="wpcs_clear_language">
						<input type="hidden" name="locale" value="<?php echo esc_attr( $locale ); ?>">
						<?php wp_nonce_field( 'wpcs_admin_action' ); ?>
						<input type="submit" class="button button-small" style="color:#b91c1c;border-color:#fca5a5;" value="<?php esc_attr_e( 'Disable', 'wp-cookie-shield' ); ?>"
							onclick="return confirm('<?php echo esc_js( __( 'Disable this language? Visitors will see the default banner text instead.', 'wp-cookie-shield' ) ); ?>')">
					</form>
				<?php else : ?>
					<button type="button" class="button button-small button-primary wpcs-toggle-edit" data-locale="<?php echo esc_attr( $locale ); ?>"><?php esc_html_e( 'Enable', 'wp-cookie-shield' ); ?></button>
				<?php endif; ?>
			</td>

		</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<p style="margin-top:12px;color:#6b7280;font-size:12px;">
	<?php esc_html_e( 'Don\'t see your language? The Default Banner Text in General settings covers any unlisted locale.', 'wp-cookie-shield' ); ?>
</p>

<script>
(function () {
	document.querySelectorAll('.wpcs-toggle-edit').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var locale = this.dataset.locale;
			var area   = document.getElementById('edit-' + locale);
			if (area) {
				area.classList.add('is-open');
				this.style.display = 'none';
			}
		});
	});

	document.querySelectorAll('.wpcs-cancel-edit').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var locale   = this.dataset.locale;
			var area     = document.getElementById('edit-' + locale);
			var editBtn  = document.querySelector('.wpcs-toggle-edit[data-locale="' + locale + '"]');
			if (area)    area.classList.remove('is-open');
			if (editBtn) editBtn.style.display = '';
		});
	});
}());
</script>
