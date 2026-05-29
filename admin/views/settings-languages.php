<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

$s            = WPCS_Settings::get();
$site_locale  = get_locale();
$locale_texts = (array) ( $s['locale_texts'] ?? [] );

if ( isset( $_GET['applied'] ) ) {
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Language enabled and translations saved.', 'wp-cookie-shield' ) . '</p></div>';
}
if ( isset( $_GET['cleared'] ) ) {
	echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Language disabled — falling back to default text.', 'wp-cookie-shield' ) . '</p></div>';
}

// All translatable strings with defaults per locale
$translations = [
	'en_US' => [ 'label' => 'English (US)', 'flag' => '🇺🇸',
		'banner_text' => 'We use cookies to improve your experience on our site. By using our site, you consent to cookies.',
		'btn_preferences' => 'Preferences', 'btn_reject' => 'Reject', 'btn_accept' => 'Accept All',
		'modal_title' => 'Cookie Preferences', 'modal_intro' => 'Manage your cookie preferences below:',
		'modal_accept' => 'Accept All', 'modal_close' => 'Close', 'modal_save' => 'Save and Close',
	],
	'en_GB' => [ 'label' => 'English (UK)', 'flag' => '🇬🇧',
		'banner_text' => 'We use cookies to improve your experience on our site. By using our site, you consent to cookies.',
		'btn_preferences' => 'Preferences', 'btn_reject' => 'Reject', 'btn_accept' => 'Accept All',
		'modal_title' => 'Cookie Preferences', 'modal_intro' => 'Manage your cookie preferences below:',
		'modal_accept' => 'Accept All', 'modal_close' => 'Close', 'modal_save' => 'Save and Close',
	],
	'fr_FR' => [ 'label' => 'Français (France)', 'flag' => '🇫🇷',
		'banner_text' => 'Nous utilisons des cookies pour améliorer votre expérience sur notre site. En utilisant notre site, vous consentez à l\'utilisation de cookies.',
		'btn_preferences' => 'Préférences', 'btn_reject' => 'Refuser', 'btn_accept' => 'Tout accepter',
		'modal_title' => 'Préférences en matière de cookies', 'modal_intro' => 'Gérez vos préférences en matière de cookies ci-dessous :',
		'modal_accept' => 'Tout accepter', 'modal_close' => 'Fermer', 'modal_save' => 'Enregistrer et fermer',
	],
	'fr_CA' => [ 'label' => 'Français (Canada)', 'flag' => '🇨🇦',
		'banner_text' => 'Nous utilisons des cookies pour améliorer votre expérience sur notre site. En utilisant notre site, vous consentez à l\'utilisation de cookies.',
		'btn_preferences' => 'Préférences', 'btn_reject' => 'Refuser', 'btn_accept' => 'Tout accepter',
		'modal_title' => 'Préférences en matière de cookies', 'modal_intro' => 'Gérez vos préférences en matière de cookies ci-dessous :',
		'modal_accept' => 'Tout accepter', 'modal_close' => 'Fermer', 'modal_save' => 'Enregistrer et fermer',
	],
	'de_DE' => [ 'label' => 'Deutsch', 'flag' => '🇩🇪',
		'banner_text' => 'Wir verwenden Cookies, um Ihre Erfahrung auf unserer Website zu verbessern. Durch die Nutzung unserer Website stimmen Sie der Verwendung von Cookies zu.',
		'btn_preferences' => 'Einstellungen', 'btn_reject' => 'Ablehnen', 'btn_accept' => 'Alle akzeptieren',
		'modal_title' => 'Cookie-Einstellungen', 'modal_intro' => 'Verwalten Sie Ihre Cookie-Einstellungen unten:',
		'modal_accept' => 'Alle akzeptieren', 'modal_close' => 'Schließen', 'modal_save' => 'Speichern und schließen',
	],
	'es_ES' => [ 'label' => 'Español', 'flag' => '🇪🇸',
		'banner_text' => 'Utilizamos cookies para mejorar su experiencia en nuestro sitio. Al utilizar nuestro sitio, usted acepta el uso de cookies.',
		'btn_preferences' => 'Preferencias', 'btn_reject' => 'Rechazar', 'btn_accept' => 'Aceptar todo',
		'modal_title' => 'Preferencias de cookies', 'modal_intro' => 'Gestione sus preferencias de cookies a continuación:',
		'modal_accept' => 'Aceptar todo', 'modal_close' => 'Cerrar', 'modal_save' => 'Guardar y cerrar',
	],
	'it_IT' => [ 'label' => 'Italiano', 'flag' => '🇮🇹',
		'banner_text' => 'Utilizziamo i cookie per migliorare la tua esperienza sul nostro sito. Utilizzando il nostro sito, acconsenti all\'uso dei cookie.',
		'btn_preferences' => 'Preferenze', 'btn_reject' => 'Rifiuta', 'btn_accept' => 'Accetta tutto',
		'modal_title' => 'Preferenze sui cookie', 'modal_intro' => 'Gestisci le tue preferenze sui cookie di seguito:',
		'modal_accept' => 'Accetta tutto', 'modal_close' => 'Chiudi', 'modal_save' => 'Salva e chiudi',
	],
	'nl_NL' => [ 'label' => 'Nederlands', 'flag' => '🇳🇱',
		'banner_text' => 'We gebruiken cookies om uw ervaring op onze website te verbeteren. Door onze website te gebruiken, stemt u in met het gebruik van cookies.',
		'btn_preferences' => 'Voorkeuren', 'btn_reject' => 'Weigeren', 'btn_accept' => 'Alles accepteren',
		'modal_title' => 'Cookie-voorkeuren', 'modal_intro' => 'Beheer hieronder uw cookievoorkeuren:',
		'modal_accept' => 'Alles accepteren', 'modal_close' => 'Sluiten', 'modal_save' => 'Opslaan en sluiten',
	],
	'pt_PT' => [ 'label' => 'Português', 'flag' => '🇵🇹',
		'banner_text' => 'Utilizamos cookies para melhorar a sua experiência no nosso site. Ao utilizar o nosso site, está a consentir o uso de cookies.',
		'btn_preferences' => 'Preferências', 'btn_reject' => 'Rejeitar', 'btn_accept' => 'Aceitar tudo',
		'modal_title' => 'Preferências de cookies', 'modal_intro' => 'Gerencie as suas preferências de cookies abaixo:',
		'modal_accept' => 'Aceitar tudo', 'modal_close' => 'Fechar', 'modal_save' => 'Guardar e fechar',
	],
	'pt_BR' => [ 'label' => 'Português (BR)', 'flag' => '🇧🇷',
		'banner_text' => 'Usamos cookies para melhorar sua experiência em nosso site. Ao usar nosso site, você concorda com o uso de cookies.',
		'btn_preferences' => 'Preferências', 'btn_reject' => 'Recusar', 'btn_accept' => 'Aceitar tudo',
		'modal_title' => 'Preferências de cookies', 'modal_intro' => 'Gerencie suas preferências de cookies abaixo:',
		'modal_accept' => 'Aceitar tudo', 'modal_close' => 'Fechar', 'modal_save' => 'Salvar e fechar',
	],
	'pl_PL' => [ 'label' => 'Polski', 'flag' => '🇵🇱',
		'banner_text' => 'Używamy plików cookie, aby poprawić Twoje doświadczenia na naszej stronie. Korzystając z naszej strony, wyrażasz zgodę na używanie plików cookie.',
		'btn_preferences' => 'Preferencje', 'btn_reject' => 'Odrzuć', 'btn_accept' => 'Akceptuj wszystkie',
		'modal_title' => 'Preferencje dotyczące plików cookie', 'modal_intro' => 'Zarządzaj swoimi preferencjami dotyczącymi plików cookie poniżej:',
		'modal_accept' => 'Akceptuj wszystkie', 'modal_close' => 'Zamknij', 'modal_save' => 'Zapisz i zamknij',
	],
	'sv_SE' => [ 'label' => 'Svenska', 'flag' => '🇸🇪',
		'banner_text' => 'Vi använder cookies för att förbättra din upplevelse på vår webbplats. Genom att använda vår webbplats godkänner du användningen av cookies.',
		'btn_preferences' => 'Inställningar', 'btn_reject' => 'Avvisa', 'btn_accept' => 'Acceptera alla',
		'modal_title' => 'Cookie-inställningar', 'modal_intro' => 'Hantera dina cookie-inställningar nedan:',
		'modal_accept' => 'Acceptera alla', 'modal_close' => 'Stäng', 'modal_save' => 'Spara och stäng',
	],
	'da_DK' => [ 'label' => 'Dansk', 'flag' => '🇩🇰',
		'banner_text' => 'Vi bruger cookies til at forbedre din oplevelse på vores hjemmeside. Ved at bruge vores hjemmeside accepterer du brugen af cookies.',
		'btn_preferences' => 'Præferencer', 'btn_reject' => 'Afvis', 'btn_accept' => 'Accepter alle',
		'modal_title' => 'Cookie-præferencer', 'modal_intro' => 'Administrer dine cookie-præferencer nedenfor:',
		'modal_accept' => 'Accepter alle', 'modal_close' => 'Luk', 'modal_save' => 'Gem og luk',
	],
];

$string_labels = [
	'banner_text'     => __( 'Banner Text',         'wp-cookie-shield' ),
	'btn_preferences' => __( 'Preferences Button',  'wp-cookie-shield' ),
	'btn_reject'      => __( 'Reject Button',        'wp-cookie-shield' ),
	'btn_accept'      => __( 'Accept All Button',    'wp-cookie-shield' ),
	'modal_title'     => __( 'Modal Title',          'wp-cookie-shield' ),
	'modal_intro'     => __( 'Modal Intro Text',     'wp-cookie-shield' ),
	'modal_accept'    => __( 'Modal Accept Button',  'wp-cookie-shield' ),
	'modal_close'     => __( 'Modal Close Button',   'wp-cookie-shield' ),
	'modal_save'      => __( 'Modal Save Button',    'wp-cookie-shield' ),
];

$enabled_locales = array_keys( $locale_texts );
$enabled_count   = count( $enabled_locales );
?>

<style>
.wpcs-lang-summary { background:#fff; border:1px solid #c3c4c7; border-radius:4px; padding:14px 18px; margin-bottom:20px; max-width:960px; }
.wpcs-lang-summary h3 { margin:0 0 8px; font-size:14px; }
.wpcs-lang-chips { display:flex; flex-wrap:wrap; gap:7px; align-items:center; }
.wpcs-lang-chip { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:600; background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }
.wpcs-lang-chip--current { background:#dbeafe; color:#1e40af; border-color:#93c5fd; }
.wpcs-lang-chip--none { color:#6b7280; font-style:italic; font-weight:400; }
.wpcs-lang-how { margin-top:8px; padding-top:8px; border-top:1px solid #e5e7eb; font-size:12px; color:#6b7280; }
.wpcs-lang-row--enabled { background:#f0fdf4 !important; }
.wpcs-lang-row--enabled td:first-child { border-left:3px solid #22c55e; }
.wpcs-lang-row--current td:first-child { border-left:3px solid #3b82f6; }
.wpcs-lang-edit-area { display:none; margin-top:10px; }
.wpcs-lang-edit-area.is-open { display:block; }
.wpcs-lang-fields { display:grid; grid-template-columns:160px 1fr; gap:6px 12px; align-items:start; margin-bottom:10px; }
.wpcs-lang-fields label { font-size:12px; color:#444; padding-top:5px; }
.wpcs-lang-fields input[type=text], .wpcs-lang-fields textarea { width:100%; font-size:12px; box-sizing:border-box; }
.wpcs-lang-fields textarea { resize:vertical; }
</style>

<?php /* ── Enabled languages summary ── */ ?>
<div class="wpcs-lang-summary">
	<h3><?php esc_html_e( 'Enabled Languages', 'wp-cookie-shield' ); ?></h3>
	<div class="wpcs-lang-chips">
		<?php if ( $enabled_count > 0 ) :
			foreach ( $enabled_locales as $loc ) :
				$chip_label = isset( $translations[$loc] ) ? $translations[$loc]['flag'] . ' ' . $translations[$loc]['label'] : $loc;
				$chip_class = ( $loc === $site_locale ) ? 'wpcs-lang-chip wpcs-lang-chip--current' : 'wpcs-lang-chip';
				echo '<span class="' . esc_attr( $chip_class ) . '">' . esc_html( $chip_label );
				if ( $loc === $site_locale ) echo ' <em style="font-weight:400">(active)</em>';
				echo '</span>';
			endforeach;
		else : ?>
			<span class="wpcs-lang-chip--none"><?php esc_html_e( 'None — all visitors see the default English text.', 'wp-cookie-shield' ); ?></span>
		<?php endif; ?>
	</div>
	<div class="wpcs-lang-how">
		<?php esc_html_e( 'When TranslatePress, WPML, or Polylang switches the page language, the banner and modal text auto-swap to the matching language below.', 'wp-cookie-shield' ); ?>
	</div>
</div>

<table class="widefat fixed" style="max-width:960px;">
	<thead>
		<tr>
			<th style="width:170px;"><?php esc_html_e( 'Language', 'wp-cookie-shield' ); ?></th>
			<th><?php esc_html_e( 'Translations', 'wp-cookie-shield' ); ?></th>
			<th style="width:120px;"></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ( $translations as $locale => $trans ) :
		$is_site    = ( $locale === $site_locale );
		$saved      = isset( $locale_texts[ $locale ] ) ? (array) $locale_texts[ $locale ] : null;
		$is_enabled = ( $saved !== null );
		$row_class  = $is_enabled ? 'wpcs-lang-row--enabled' : '';
		if ( $is_site ) $row_class .= ' wpcs-lang-row--current';
	?>
	<tr class="<?php echo esc_attr( trim( $row_class ) ); ?>">

		<td style="vertical-align:top; padding-top:12px;">
			<?php if ( $is_enabled ) : ?><span style="color:#16a34a;font-weight:700;margin-right:3px;">✓</span><?php endif; ?>
			<strong><?php echo esc_html( $trans['flag'] . ' ' . $trans['label'] ); ?></strong><br>
			<code style="font-size:11px;color:#888;"><?php echo esc_html( $locale ); ?></code>
			<?php if ( $is_site ) : ?>
				<br><span class="wpcs-badge wpcs-badge-statistics" style="margin-top:3px;display:inline-block;font-size:10px;"><?php esc_html_e( 'current', 'wp-cookie-shield' ); ?></span>
			<?php endif; ?>
		</td>

		<td style="vertical-align:top; padding-top:10px;">
			<?php if ( $is_enabled ) : ?>
				<div style="font-size:12px;color:#444;margin-bottom:6px;">
					<strong><?php echo esc_html( $trans['flag'] ); ?> <?php esc_html_e( 'Active:', 'wp-cookie-shield' ); ?></strong>
					<?php echo esc_html( wp_trim_words( $saved['banner_text'] ?? $trans['banner_text'], 10 ) ); ?> &nbsp;
					<span style="color:#6b7280;">| <?php echo esc_html( $saved['btn_preferences'] ?? $trans['btn_preferences'] ); ?> / <?php echo esc_html( $saved['btn_reject'] ?? $trans['btn_reject'] ); ?> / <?php echo esc_html( $saved['btn_accept'] ?? $trans['btn_accept'] ); ?></span>
				</div>
			<?php else : ?>
				<div style="font-size:12px;color:#999;font-style:italic;margin-bottom:6px;"><?php esc_html_e( 'Not enabled — uses default text', 'wp-cookie-shield' ); ?></div>
			<?php endif; ?>

			<div class="wpcs-lang-edit-area" id="edit-<?php echo esc_attr( $locale ); ?>">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="wpcs_apply_language">
					<input type="hidden" name="locale" value="<?php echo esc_attr( $locale ); ?>">
					<?php wp_nonce_field( 'wpcs_admin_action' ); ?>

					<div class="wpcs-lang-fields">
						<?php foreach ( $string_labels as $key => $field_label ) :
							$current_val = $saved[ $key ] ?? $trans[ $key ] ?? '';
							$is_textarea = in_array( $key, [ 'banner_text', 'modal_intro' ], true );
						?>
							<label><?php echo esc_html( $field_label ); ?></label>
							<?php if ( $is_textarea ) : ?>
								<textarea name="<?php echo esc_attr( $key ); ?>" rows="2"><?php echo esc_textarea( $current_val ); ?></textarea>
							<?php else : ?>
								<input type="text" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $current_val ); ?>">
							<?php endif; ?>
						<?php endforeach; ?>
					</div>

					<input type="submit" class="button button-primary button-small" value="<?php echo $is_enabled ? esc_attr__( 'Update', 'wp-cookie-shield' ) : esc_attr__( 'Enable', 'wp-cookie-shield' ); ?>">
					<button type="button" class="button button-small wpcs-cancel-edit" data-locale="<?php echo esc_attr( $locale ); ?>"><?php esc_html_e( 'Cancel', 'wp-cookie-shield' ); ?></button>
				</form>
			</div>
		</td>

		<td style="vertical-align:top; padding-top:12px; white-space:nowrap;">
			<button type="button" class="button button-small wpcs-toggle-edit" data-locale="<?php echo esc_attr( $locale ); ?>">
				<?php echo $is_enabled ? esc_html__( 'Edit', 'wp-cookie-shield' ) : esc_html__( 'Enable', 'wp-cookie-shield' ); ?>
			</button>
			<?php if ( $is_enabled ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
					<input type="hidden" name="action" value="wpcs_clear_language">
					<input type="hidden" name="locale" value="<?php echo esc_attr( $locale ); ?>">
					<?php wp_nonce_field( 'wpcs_admin_action' ); ?>
					<input type="submit" class="button button-small" style="color:#b91c1c;border-color:#fca5a5;" value="<?php esc_attr_e( 'Disable', 'wp-cookie-shield' ); ?>"
						onclick="return confirm('<?php echo esc_js( __( 'Disable this language?', 'wp-cookie-shield' ) ); ?>')">
				</form>
			<?php endif; ?>
		</td>
	</tr>
	<?php endforeach; ?>
	</tbody>
</table>

<script>
(function () {
	document.querySelectorAll('.wpcs-toggle-edit').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var area = document.getElementById('edit-' + this.dataset.locale);
			if (area) { area.classList.add('is-open'); this.style.display = 'none'; }
		});
	});
	document.querySelectorAll('.wpcs-cancel-edit').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var area   = document.getElementById('edit-' + this.dataset.locale);
			var toggle = document.querySelector('.wpcs-toggle-edit[data-locale="' + this.dataset.locale + '"]');
			if (area)   area.classList.remove('is-open');
			if (toggle) toggle.style.display = '';
		});
	});
}());
</script>
