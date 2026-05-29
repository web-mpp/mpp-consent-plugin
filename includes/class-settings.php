<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class WPCS_Settings {

	public static function get_defaults(): array {
		return [
			// General
			'banner_position'        => 'top',
			'banner_text'            => 'We use cookies to improve your experience on our site. By using our site, you consent to cookies.',
			'policy_version'         => '1.0',
			'consent_expiry_days'    => 30,
			'prior_consent_required' => true,

			// Appearance
			'appearance' => [
				'bg_primary'         => '#0a1628',
				'bg_secondary'       => '#0d1f3c',
				'border'             => '#1e3254',
				'text_primary'       => '#ffffff',
				'text_muted'         => '#a0aec0',
				'btn_accept'         => '#e53e3e',
				'btn_accept_hover'   => '#c53030',
				'btn_outline_border' => '#4a5568',
				'toggle_active'      => '#3182ce',
				'border_radius'      => 4,
				'font_size'          => 14,
			],

			// Categories
			'categories' => [
				'essential'   => [ 'label' => 'Essential',   'description' => 'Essential cookies are required for the website to function and cannot be disabled.', 'enabled' => true,  'locked' => true,  'expiry_days' => 365 ],
				'statistics'  => [ 'label' => 'Statistics',  'description' => 'Statistical cookies help us understand how visitors interact with our website by collecting and reporting information anonymously.', 'enabled' => false, 'locked' => false, 'expiry_days' => 30 ],
				'marketing'   => [ 'label' => 'Marketing',   'description' => 'Marketing cookies are used to track visitors across websites to display relevant advertisements.', 'enabled' => false, 'locked' => false, 'expiry_days' => 30 ],
				'preferences' => [ 'label' => 'Preferences', 'description' => 'Preference cookies allow the website to remember information that changes the way the website behaves or looks, like your preferred language.', 'enabled' => false, 'locked' => false, 'expiry_days' => 30 ],
			],

			// Google Consent Mode
			'gcm_enabled'            => false,
			'gcm_default_analytics'  => 'denied',
			'gcm_default_ads'        => 'denied',
			'gcm_region'             => [],
			'gcm_wait_for_update_ms' => 500,

			// Script Blocking
			'script_blocking_enabled' => true,
			'blocked_patterns'        => [],

			// Geolocation
			'geo_enabled'       => false,
			'geo_jurisdictions' => [ 'EU', 'CA', 'US-CA' ],

			// Compliance
			'dnt_respect'              => true,
			'gpc_respect'              => true,
			'cookie_policy_page_id'    => 0,
			'privacy_policy_page_id'   => 0,

			// Locale-specific banner texts (keyed by WP locale, e.g. 'fr_FR')
			'locale_texts'             => [],

			// Advanced
			'show_floating_button'     => false,
			'iframe_blocking_enabled'  => false,
			'gcm_url_passthrough'      => false,
			'gcm_ads_data_redaction'   => true,
			'shared_consent'           => false,
			'consent_logs_enabled'     => true,
			'remove_data_on_uninstall' => false,

			// Scanner
			'last_scan_time'      => 0,
			'scan_frequency_days' => 30,
		];
	}

	public static function get( string $key = '' ): mixed {
		$settings = get_option( 'wpcs_settings', self::get_defaults() );
		$settings = array_merge( self::get_defaults(), (array) $settings );

		if ( '' === $key ) {
			return $settings;
		}

		return $settings[ $key ] ?? null;
	}

	public static function update( array $new_values ): bool {
		$current = self::get();
		$updated = array_merge( $current, $new_values );
		return update_option( 'wpcs_settings', $updated );
	}
}
