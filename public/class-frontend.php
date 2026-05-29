<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class WPCS_Frontend {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_head',            [ $this, 'output_appearance_css' ], 5 );
		add_action( 'wp_footer',          [ $this, 'output_banner' ] );
		add_action( 'wp_footer',          [ $this, 'output_modal' ] );
		add_action( 'wp_footer',          [ $this, 'output_floating_button' ] );

		add_shortcode( 'wpcs_preferences',    [ $this, 'shortcode_preferences' ] );
		add_shortcode( 'wpcs_cookie_table',   [ $this, 'shortcode_cookie_table' ] );
		add_shortcode( 'wpcs_consent_status', [ $this, 'shortcode_consent_status' ] );
		add_shortcode( 'wpcs_cookie_policy',  [ $this, 'shortcode_cookie_policy' ] );
	}

	public function enqueue_assets(): void {
		wp_enqueue_style(
			'wp-cookie-shield',
			WPCS_PLUGIN_URL . 'public/assets/css/cookie-shield.css',
			[],
			WPCS_VERSION
		);

		wp_enqueue_script(
			'wp-cookie-shield',
			WPCS_PLUGIN_URL . 'public/assets/js/cookie-shield.js',
			[],
			WPCS_VERSION,
			true
		);

		$consent  = WPCS_ConsentManager::get_instance();
		$settings = WPCS_Settings::get();
		$geo      = new WPCS_Geolocation();

		$locale       = get_locale();
		$locale_texts = (array) ( $settings['locale_texts'] ?? [] );
		$banner_text  = $locale_texts[ $locale ] ?? $settings['banner_text'];

		// Consent expiry = minimum expiry across all opt-in (non-locked) categories, fallback to global
		$expiry_days = (int) ( $settings['consent_expiry_days'] ?? 30 );
		foreach ( $settings['categories'] as $cat ) {
			if ( empty( $cat['locked'] ) && isset( $cat['expiry_days'] ) ) {
				$expiry_days = min( $expiry_days, (int) $cat['expiry_days'] );
			}
		}

		wp_localize_script( 'wp-cookie-shield', 'wpcSettings', [
			'restUrl'           => rest_url( 'wp-cookie-shield/v1' ),
			'nonce'             => wp_create_nonce( 'wpcs_consent' ),
			'policyVersion'     => $settings['policy_version'],
			'expiryDays'        => $expiry_days,
			'showBanner'        => ! $consent->has_consent() && $geo->should_show_banner(),
			'autoDenyMarketing' => $consent->should_auto_deny_marketing(),
			'categories'        => array_keys( $settings['categories'] ),
			'categoryLabels'    => array_map( fn( $c ) => $c['label'], $settings['categories'] ),
			'bannerText'        => $banner_text,
			'showReject'        => true,
			'showPreferences'   => true,
		] );
	}

	public function output_appearance_css(): void {
		$defaults = WPCS_Settings::get_defaults()['appearance'];
		$app      = array_merge( $defaults, (array) ( WPCS_Settings::get( 'appearance' ) ?? [] ) );

		$vars = [
			'--wpcs-bg-primary'         => $app['bg_primary'],
			'--wpcs-bg-secondary'       => $app['bg_secondary'],
			'--wpcs-border'             => $app['border'],
			'--wpcs-text-primary'       => $app['text_primary'],
			'--wpcs-text-muted'         => $app['text_muted'],
			'--wpcs-btn-accept'         => $app['btn_accept'],
			'--wpcs-btn-accept-hover'   => $app['btn_accept_hover'],
			'--wpcs-btn-outline-border' => $app['btn_outline_border'],
			'--wpcs-toggle-active'      => $app['toggle_active'],
		];

		$css = ':root{';
		foreach ( $vars as $prop => $val ) {
			$css .= esc_attr( $prop ) . ':' . esc_attr( $val ) . ';';
		}
		$css .= '}';

		$radius = absint( $app['border_radius'] );
		$fsize  = absint( $app['font_size'] );
		if ( $fsize > 0 ) {
			$css .= '.wpcs-banner,.wpcs-modal{font-size:' . $fsize . 'px;}';
		}
		if ( $radius !== 4 ) {
			$css .= '.wpcs-btn{border-radius:' . $radius . 'px;}';
		}

		echo '<style id="wpcs-appearance">' . $css . '</style>' . "\n";
	}

	public function output_floating_button(): void {
		if ( ! WPCS_Settings::get( 'show_floating_button' ) ) return;
		echo '<button type="button" class="wpcs-floating-btn wpcs-open-modal" aria-label="' . esc_attr__( 'Cookie Preferences', 'wp-cookie-shield' ) . '" title="' . esc_attr__( 'Cookie Preferences', 'wp-cookie-shield' ) . '">🍪</button>';
	}

	public function output_banner(): void {
		// Always output the banner HTML regardless of consent state.
		// Visibility is controlled by JavaScript (store.isValid() check), not PHP.
		// This is required for full-page caches (WP Engine, etc.) — a PHP-conditional
		// output would bake the "no banner" state into the cached HTML for all visitors.
		include WPCS_PLUGIN_DIR . 'public/templates/banner.php';
	}

	public function output_modal(): void {
		include WPCS_PLUGIN_DIR . 'public/templates/modal.php';
	}

	public function shortcode_preferences( array $atts ): string {
		return '<a href="#" class="wpcs-open-modal" aria-label="' . esc_attr__( 'Manage Cookie Preferences', 'wp-cookie-shield' ) . '">' . esc_html__( 'Manage Cookie Preferences', 'wp-cookie-shield' ) . '</a>';
	}

	public function shortcode_cookie_table( array $atts ): string {
		$atts     = shortcode_atts( [ 'category' => 'statistics' ], $atts, 'wpcs_cookie_table' );
		$category = sanitize_key( $atts['category'] );

		global $wpdb;
		$cookies = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wpcs_cookies WHERE category = %s AND is_active = 1 ORDER BY cookie_name",
				$category
			),
			ARRAY_A
		);

		if ( empty( $cookies ) ) {
			return '<p>' . esc_html__( 'No cookies found in this category.', 'wp-cookie-shield' ) . '</p>';
		}

		$html = '<table class="wpcs-cookie-table"><thead><tr>';
		$html .= '<th>' . esc_html__( 'Cookie', 'wp-cookie-shield' ) . '</th>';
		$html .= '<th>' . esc_html__( 'Provider', 'wp-cookie-shield' ) . '</th>';
		$html .= '<th>' . esc_html__( 'Purpose', 'wp-cookie-shield' ) . '</th>';
		$html .= '<th>' . esc_html__( 'Duration', 'wp-cookie-shield' ) . '</th>';
		$html .= '</tr></thead><tbody>';

		foreach ( $cookies as $c ) {
			$html .= '<tr>';
			$html .= '<td><code>' . esc_html( $c['cookie_name'] ) . '</code></td>';
			$html .= '<td>' . esc_html( $c['provider'] ) . '</td>';
			$html .= '<td>' . esc_html( $c['purpose'] ) . '</td>';
			$html .= '<td>' . esc_html( $c['duration'] ) . '</td>';
			$html .= '</tr>';
		}

		$html .= '</tbody></table>';

		return $html;
	}

	public function shortcode_cookie_policy( array $atts ): string {
		$settings = WPCS_Settings::get();
		$categories = $settings['categories'];

		global $wpdb;
		$all_cookies = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}wpcs_cookies WHERE is_active = 1 ORDER BY category, cookie_name",
			ARRAY_A
		);

		$by_cat = [];
		foreach ( $all_cookies as $c ) {
			$by_cat[ $c['category'] ][] = $c;
		}

		$html = '<div class="wpcs-cookie-policy">';

		foreach ( $categories as $key => $cat ) {
			$cookies = $by_cat[ $key ] ?? [];
			$count   = count( $cookies );

			$html .= '<h3>' . esc_html( $cat['label'] ) . '</h3>';
			$html .= '<p>' . esc_html( $cat['description'] ) . '</p>';

			if ( $count > 0 ) {
				$html .= '<table class="wpcs-cookie-table"><thead><tr>';
				$html .= '<th>' . esc_html__( 'Cookie', 'wp-cookie-shield' ) . '</th>';
				$html .= '<th>' . esc_html__( 'Provider', 'wp-cookie-shield' ) . '</th>';
				$html .= '<th>' . esc_html__( 'Purpose', 'wp-cookie-shield' ) . '</th>';
				$html .= '<th>' . esc_html__( 'Duration', 'wp-cookie-shield' ) . '</th>';
				$html .= '</tr></thead><tbody>';
				foreach ( $cookies as $c ) {
					$html .= '<tr>';
					$html .= '<td><code>' . esc_html( $c['cookie_name'] ) . '</code></td>';
					$html .= '<td>' . esc_html( $c['provider'] ) . '</td>';
					$html .= '<td>' . esc_html( $c['purpose'] ) . '</td>';
					$html .= '<td>' . esc_html( $c['duration'] ) . '</td>';
					$html .= '</tr>';
				}
				$html .= '</tbody></table>';
			} else {
				$html .= '<p><em>' . esc_html__( 'No cookies declared in this category yet.', 'wp-cookie-shield' ) . '</em></p>';
			}
		}

		$html .= '</div>';
		return $html;
	}

	public function shortcode_consent_status( array $atts ): string {
		$consent = WPCS_ConsentManager::get_instance();

		if ( ! $consent->has_consent() ) {
			return '<p>' . esc_html__( 'No consent recorded for this session.', 'wp-cookie-shield' ) . '</p>';
		}

		$cats = $consent->get_categories();
		$html = '<ul class="wpcs-consent-status">';
		foreach ( $cats as $key => $granted ) {
			$label  = $granted ? esc_html__( 'Granted', 'wp-cookie-shield' ) : esc_html__( 'Denied', 'wp-cookie-shield' );
			$html  .= '<li><strong>' . esc_html( ucfirst( $key ) ) . '</strong>: ' . $label . '</li>';
		}
		$html .= '</ul>';

		return $html;
	}
}
