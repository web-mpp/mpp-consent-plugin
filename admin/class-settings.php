<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class WPCS_SettingsAdmin {

	public function __construct() {
		add_action( 'admin_init',                          [ $this, 'register' ] );
		add_action( 'admin_post_wpcs_save_settings',       [ $this, 'handle_save' ] );
		add_action( 'admin_post_wpcs_generate_policy',     [ $this, 'handle_generate_policy' ] );
		add_action( 'admin_post_wpcs_reset_defaults',      [ $this, 'handle_reset_defaults' ] );
		add_action( 'admin_post_wpcs_apply_language',      [ $this, 'handle_apply_language' ] );
		add_action( 'admin_post_wpcs_clear_language',      [ $this, 'handle_clear_language' ] );
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
		$output['gpc_respect']            = ! empty( $input['gpc_respect'] );
		$output['cookie_policy_page_id']  = absint( $input['cookie_policy_page_id'] ?? 0 );
		$output['privacy_policy_page_id'] = absint( $input['privacy_policy_page_id'] ?? 0 );

		// Advanced
		$output['show_floating_button']     = ! empty( $input['show_floating_button'] );
		$output['iframe_blocking_enabled']  = ! empty( $input['iframe_blocking_enabled'] );
		$output['gcm_url_passthrough']      = ! empty( $input['gcm_url_passthrough'] );
		$output['gcm_ads_data_redaction']   = ! empty( $input['gcm_ads_data_redaction'] );
		$output['shared_consent']           = ! empty( $input['shared_consent'] );
		$output['consent_logs_enabled']     = ! empty( $input['consent_logs_enabled'] );
		$output['remove_data_on_uninstall'] = ! empty( $input['remove_data_on_uninstall'] );

		// Locale texts — read from $input which is already merged with current by WPCS_Settings::update().
		// Do NOT read from $current here: update_option() triggers this callback before the write
		// commits, so get_option() still returns the old value and would discard the new locale_texts.
		$output['locale_texts'] = [];
		if ( isset( $input['locale_texts'] ) && is_array( $input['locale_texts'] ) ) {
			foreach ( $input['locale_texts'] as $loc => $text ) {
				if ( preg_match( '/^[a-z]{2}(_[A-Z]{2})?$/', $loc ) ) {
					$output['locale_texts'][ $loc ] = sanitize_textarea_field( $text );
				}
			}
		}

		// Preserve scanner timestamps
		$current = WPCS_Settings::get();
		$output['last_scan_time']      = $current['last_scan_time'];
		$output['scan_frequency_days'] = absint( $input['scan_frequency_days'] ?? 30 );

		return $output;
	}

	public function handle_generate_policy(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-cookie-shield' ) );
		}
		check_admin_referer( 'wpcs_admin_action' );

		$settings    = WPCS_Settings::get();
		$existing_id = (int) $settings['cookie_policy_page_id'];

		$content  = "<!-- wp:paragraph -->\n";
		$content .= '<p>' . esc_html__( 'This Cookie Policy explains how we use cookies and similar technologies on our website.', 'wp-cookie-shield' ) . "</p>\n";
		$content .= "<!-- /wp:paragraph -->\n\n";
		$content .= "[wpcs_cookie_policy]\n";

		if ( $existing_id > 0 && get_post( $existing_id ) ) {
			wp_update_post( [
				'ID'           => $existing_id,
				'post_content' => $content,
			] );
			$page_id = $existing_id;
		} else {
			$page_id = wp_insert_post( [
				'post_title'   => __( 'Cookie Policy', 'wp-cookie-shield' ),
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'page',
			] );
		}

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			WPCS_Settings::update( [ 'cookie_policy_page_id' => $page_id ] );
		}

		wp_redirect( add_query_arg( [ 'page' => 'wpcs-settings', 'tab' => 'compliance', 'generated' => '1' ], admin_url( 'options-general.php' ) ) );
		exit;
	}

	public function handle_apply_language(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-cookie-shield' ) );
		}
		check_admin_referer( 'wpcs_admin_action' );

		$raw_locale  = $_POST['locale'] ?? '';
		$locale      = preg_match( '/^[a-z]{2}(_[A-Z]{2})?$/', $raw_locale ) ? $raw_locale : '';
		$banner_text = sanitize_textarea_field( $_POST['banner_text'] ?? '' );

		if ( $locale && $banner_text ) {
			$locale_texts           = (array) WPCS_Settings::get( 'locale_texts' );
			$locale_texts[ $locale ] = $banner_text;
			WPCS_Settings::update( [ 'locale_texts' => $locale_texts ] );
		}

		wp_redirect( add_query_arg( [ 'page' => 'wpcs-settings', 'tab' => 'languages', 'applied' => '1' ], admin_url( 'options-general.php' ) ) );
		exit;
	}

	public function handle_clear_language(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-cookie-shield' ) );
		}
		check_admin_referer( 'wpcs_admin_action' );

		$raw_locale = $_POST['locale'] ?? '';
		$locale     = preg_match( '/^[a-z]{2}(_[A-Z]{2})?$/', $raw_locale ) ? $raw_locale : '';

		if ( $locale ) {
			$locale_texts = (array) WPCS_Settings::get( 'locale_texts' );
			unset( $locale_texts[ $locale ] );
			WPCS_Settings::update( [ 'locale_texts' => $locale_texts ] );
		}

		wp_redirect( add_query_arg( [ 'page' => 'wpcs-settings', 'tab' => 'languages', 'cleared' => '1' ], admin_url( 'options-general.php' ) ) );
		exit;
	}

	public function handle_reset_defaults(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-cookie-shield' ) );
		}
		check_admin_referer( 'wpcs_reset_defaults' );

		update_option( 'wpcs_settings', WPCS_Settings::get_defaults() );

		wp_redirect( add_query_arg( [ 'page' => 'wpcs-settings', 'tab' => 'advanced', 'reset' => '1' ], admin_url( 'options-general.php' ) ) );
		exit;
	}
}
