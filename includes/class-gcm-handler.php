<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class WPCS_GCMHandler {

	const GCM_CATEGORY_MAP = [
		'statistics'  => [ 'analytics_storage' ],
		'marketing'   => [ 'ad_storage', 'ad_user_data', 'ad_personalization' ],
		'preferences' => [ 'functionality_storage', 'personalization_storage' ],
		'essential'   => [ 'security_storage' ],
	];

	public function __construct() {
		if ( WPCS_Settings::get( 'gcm_enabled' ) ) {
			add_action( 'wp_head', [ $this, 'output_defaults' ], 1 );
		}
	}

	public function output_defaults(): void {
		$settings   = WPCS_Settings::get();
		$wait_ms    = absint( $settings['gcm_wait_for_update_ms'] ?? 500 );
		$regions    = (array) ( $settings['gcm_region'] ?? [] );
		$defaults   = $this->build_defaults( $settings );

		echo "\n<!-- WP Cookie Shield — Google Consent Mode v2 Defaults -->\n";
		echo "<script>\n";
		echo "  window.dataLayer = window.dataLayer || [];\n";
		echo "  function gtag(){dataLayer.push(arguments);}\n";

		if ( ! empty( $regions ) ) {
			// Global granted defaults for countries NOT in the region list
			$global_payload = json_encode( [
				'analytics_storage' => 'granted',
				'ad_storage'        => 'granted',
			] );
			echo "  gtag('consent', 'default', {$global_payload});\n";

			// Denied defaults for configured regions
			$region_payload = $defaults;
			$region_payload['region'] = $regions;
			echo "  gtag('consent', 'default', " . json_encode( $region_payload ) . ");\n";
		} else {
			echo "  gtag('consent', 'default', " . json_encode( $defaults ) . ");\n";
		}

		echo "  gtag('set', 'ads_data_redaction', true);\n";
		echo "  gtag('set', 'url_passthrough', false);\n";
		echo "</script>\n";
	}

	public function build_defaults( array $settings = [] ): array {
		if ( empty( $settings ) ) {
			$settings = WPCS_Settings::get();
		}

		$wait_ms = absint( $settings['gcm_wait_for_update_ms'] ?? 500 );

		return [
			'analytics_storage'       => sanitize_key( $settings['gcm_default_analytics'] ?? 'denied' ),
			'ad_storage'              => sanitize_key( $settings['gcm_default_ads'] ?? 'denied' ),
			'ad_user_data'            => 'denied',
			'ad_personalization'      => 'denied',
			'functionality_storage'   => 'denied',
			'personalization_storage' => 'denied',
			'security_storage'        => 'granted',
			'wait_for_update'         => $wait_ms,
		];
	}
}
