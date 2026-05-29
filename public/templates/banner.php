<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

$settings     = WPCS_Settings::get();
$position     = $settings['banner_position'] === 'bottom' ? 'wpcs-banner--bottom' : 'wpcs-banner--top';
$locale       = get_locale();
$locale_texts = (array) ( $settings['locale_texts'] ?? [] );
$text         = $locale_texts[ $locale ] ?? $settings['banner_text'];

$policy_page_id = (int) $settings['cookie_policy_page_id'];
$policy_url     = $policy_page_id > 0 ? get_permalink( $policy_page_id ) : '';

$banner_html = sprintf(
	'<div id="wpcs-banner" class="wpcs-banner %s" role="banner" aria-label="%s">
		<div class="wpcs-banner__inner">
			<p class="wpcs-banner__text">%s%s</p>
			<div class="wpcs-banner__actions">
				<button type="button" id="wpcs-open-prefs" class="wpcs-btn wpcs-btn--outline" aria-label="%s">%s</button>
				<button type="button" id="wpcs-reject-all" class="wpcs-btn wpcs-btn--outline" aria-label="%s">%s</button>
				<button type="button" id="wpcs-accept-all" class="wpcs-btn wpcs-btn--accept"  aria-label="%s">%s</button>
			</div>
		</div>
	</div>',
	esc_attr( $position ),
	esc_attr__( 'Cookie consent banner', 'wp-cookie-shield' ),
	esc_html( $text ),
	$policy_url ? ' <a href="' . esc_url( $policy_url ) . '" class="wpcs-banner__policy-link">' . esc_html__( 'Cookie Policy', 'wp-cookie-shield' ) . '</a>' : '',
	esc_attr__( 'Open cookie preferences', 'wp-cookie-shield' ),
	esc_html__( 'Preferences', 'wp-cookie-shield' ),
	esc_attr__( 'Reject all non-essential cookies', 'wp-cookie-shield' ),
	esc_html__( 'Reject', 'wp-cookie-shield' ),
	esc_attr__( 'Accept all cookies', 'wp-cookie-shield' ),
	esc_html__( 'Accept All', 'wp-cookie-shield' )
);

echo apply_filters( 'wpcs_banner_html', $banner_html );
