<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class WPCS_Frontend {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_footer',          [ $this, 'output_banner' ] );
		add_action( 'wp_footer',          [ $this, 'output_modal' ] );

		add_shortcode( 'wpcs_preferences',    [ $this, 'shortcode_preferences' ] );
		add_shortcode( 'wpcs_cookie_table',   [ $this, 'shortcode_cookie_table' ] );
		add_shortcode( 'wpcs_consent_status', [ $this, 'shortcode_consent_status' ] );
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

		wp_localize_script( 'wp-cookie-shield', 'wpcSettings', [
			'restUrl'        => rest_url( 'wp-cookie-shield/v1' ),
			'nonce'          => wp_create_nonce( 'wpcs_consent' ),
			'policyVersion'  => $settings['policy_version'],
			'expiryDays'     => (int) $settings['consent_expiry_days'],
			'showBanner'     => ! $consent->has_consent() && $geo->should_show_banner(),
			'autoDenyMarketing' => $consent->should_auto_deny_marketing(),
			'categories'     => array_keys( $settings['categories'] ),
			'categoryLabels' => array_map( fn( $c ) => $c['label'], $settings['categories'] ),
			'bannerText'     => $settings['banner_text'],
			'showReject'     => (bool) $settings['show_reject_button'],
			'showPreferences'=> (bool) $settings['show_preferences_button'],
		] );
	}

	public function output_banner(): void {
		$consent = WPCS_ConsentManager::get_instance();
		$geo     = new WPCS_Geolocation();

		if ( $consent->has_consent() || ! $geo->should_show_banner() ) {
			return;
		}

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
