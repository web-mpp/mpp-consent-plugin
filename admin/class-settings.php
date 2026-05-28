<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class WPCS_SettingsAdmin {

	public function __construct() {
		add_action( 'admin_init', [ $this, 'register' ] );
		add_action( 'admin_post_wpcs_save_settings', [ $this, 'handle_save' ] );
	}

	public function register(): void {
		register_setting( 'wpcs_settings_group', 'wpcs_settings', [
			'sanitize_callback' => [ $this, 'sanitize' ],
		] );
	}

	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-cookie-shield' ) );
		}

		check_admin_referer( 'wpcs_admin_action' );

		$posted = $_POST['wpcs_settings'] ?? [];
		WPCS_Settings::update( $this->sanitize( $posted ) );

		wp_redirect( add_query_arg( [ 'page' => 'wpcs-settings', 'saved' => '1' ], admin_url( 'options-general.php' ) ) );
		exit;
	}

	public function sanitize( array $input ): array {
		$defaults = WPCS_Settings::get_defaults();
		$output   = [];

		$output['banner_position']         = in_array( $input['banner_position'] ?? '', [ 'top', 'bottom' ], true ) ? $input['banner_position'] : 'top';
		$output['banner_text']             = sanitize_textarea_field( $input['banner_text'] ?? $defaults['banner_text'] );
		$output['show_reject_button']      = ! empty( $input['show_reject_button'] );
		$output['show_preferences_button'] = ! empty( $input['show_preferences_button'] );
		$output['policy_version']          = sanitize_text_field( $input['policy_version'] ?? '1.0' );
		$output['consent_expiry_days']     = absint( $input['consent_expiry_days'] ?? 365 );
		$output['prior_consent_required']  = ! empty( $input['prior_consent_required'] );

		$output['gcm_enabled']            = ! empty( $input['gcm_enabled'] );
		$output['gcm_default_analytics']  = in_array( $input['gcm_default_analytics'] ?? '', [ 'denied', 'granted' ], true ) ? $input['gcm_default_analytics'] : 'denied';
		$output['gcm_default_ads']        = in_array( $input['gcm_default_ads'] ?? '', [ 'denied', 'granted' ], true ) ? $input['gcm_default_ads'] : 'denied';
		$output['gcm_region']             = array_map( 'sanitize_key', (array) ( $input['gcm_region'] ?? [] ) );
		$output['gcm_wait_for_update_ms'] = absint( $input['gcm_wait_for_update_ms'] ?? 500 );

		$output['script_blocking_enabled'] = ! empty( $input['script_blocking_enabled'] );

		$output['geo_enabled']       = ! empty( $input['geo_enabled'] );
		$output['geo_jurisdictions'] = array_map( 'sanitize_key', (array) ( $input['geo_jurisdictions'] ?? [] ) );

		$output['dnt_respect']            = ! empty( $input['dnt_respect'] );
		$output['cookie_policy_page_id']  = absint( $input['cookie_policy_page_id'] ?? 0 );
		$output['privacy_policy_page_id'] = absint( $input['privacy_policy_page_id'] ?? 0 );

		// Preserve scanner timestamps
		$current = WPCS_Settings::get();
		$output['last_scan_time']      = $current['last_scan_time'];
		$output['scan_frequency_days'] = absint( $input['scan_frequency_days'] ?? 30 );

		return $output;
	}
}
