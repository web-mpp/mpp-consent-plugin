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

		// General
		$output['banner_position']       = in_array( $input['banner_position'] ?? '', [ 'top', 'bottom' ], true ) ? $input['banner_position'] : 'top';
		$output['banner_text']           = sanitize_textarea_field( $input['banner_text'] ?? $defaults['banner_text'] );
		$output['policy_version']        = sanitize_text_field( $input['policy_version'] ?? '1.0' );
		$output['consent_expiry_days']   = absint( $input['consent_expiry_days'] ?? 30 );
		$output['prior_consent_required'] = ! empty( $input['prior_consent_required'] );

		// Appearance — read from $input (same reason as locale_texts: update_option fires sanitize
		// before the write commits, so reading $current would return stale data).
		$def_app   = $defaults['appearance'];
		$input_app = isset( $input['appearance'] ) && is_array( $input['appearance'] ) ? $input['appearance'] : $def_app;
		$output['appearance'] = [
			'bg_primary'         => sanitize_hex_color( $input_app['bg_primary'] ?? '' )         ?: $def_app['bg_primary'],
			'bg_secondary'       => sanitize_hex_color( $input_app['bg_secondary'] ?? '' )       ?: $def_app['bg_secondary'],
			'border'             => sanitize_hex_color( $input_app['border'] ?? '' )             ?: $def_app['border'],
			'text_primary'       => sanitize_hex_color( $input_app['text_primary'] ?? '' )       ?: $def_app['text_primary'],
			'text_muted'         => sanitize_hex_color( $input_app['text_muted'] ?? '' )         ?: $def_app['text_muted'],
			'btn_accept'         => sanitize_hex_color( $input_app['btn_accept'] ?? '' )         ?: $def_app['btn_accept'],
			'btn_accept_hover'   => sanitize_hex_color( $input_app['btn_accept_hover'] ?? '' )   ?: $def_app['btn_accept_hover'],
			'btn_outline_border' => sanitize_hex_color( $input_app['btn_outline_border'] ?? '' ) ?: $def_app['btn_outline_border'],
			'toggle_active'      => sanitize_hex_color( $input_app['toggle_active'] ?? '' )      ?: $def_app['toggle_active'],
			'border_radius'      => absint( $input_app['border_radius'] ?? $def_app['border_radius'] ),
			'font_size'          => min( 32, max( 10, absint( $input_app['font_size'] ?? $def_app['font_size'] ) ) ),
		];

		// Categories — read from $input
		$input_cats = isset( $input['categories'] ) && is_array( $input['categories'] ) ? $input['categories'] : [];
		$output['categories'] = [];
		foreach ( $defaults['categories'] as $key => $def_cat ) {
			$in = $input_cats[ $key ] ?? [];
			$output['categories'][ $key ] = [
				'label'       => sanitize_text_field( $in['label'] ?? $def_cat['label'] ),
				'description' => sanitize_textarea_field( $in['description'] ?? $def_cat['description'] ),
				'enabled'     => $def_cat['enabled'],
				'locked'      => $def_cat['locked'],
				'expiry_days' => absint( $in['expiry_days'] ?? $def_cat['expiry_days'] ?? 30 ),
			];
		}

		// GCM
		$output['gcm_enabled']            = ! empty( $input['gcm_enabled'] );
		$output['gcm_default_analytics']  = in_array( $input['gcm_default_analytics'] ?? '', [ 'denied', 'granted' ], true ) ? $input['gcm_default_analytics'] : 'denied';
		$output['gcm_default_ads']        = in_array( $input['gcm_default_ads'] ?? '', [ 'denied', 'granted' ], true ) ? $input['gcm_default_ads'] : 'denied';
		$output['gcm_region']             = array_map( 'sanitize_key', (array) ( $input['gcm_region'] ?? [] ) );
		$output['gcm_wait_for_update_ms'] = absint( $input['gcm_wait_for_update_ms'] ?? 500 );
		$output['gcm_url_passthrough']    = ! empty( $input['gcm_url_passthrough'] );
		$output['gcm_ads_data_redaction'] = ! empty( $input['gcm_ads_data_redaction'] );

		// Script / content blocking
		$output['script_blocking_enabled'] = ! empty( $input['script_blocking_enabled'] );
		$output['iframe_blocking_enabled'] = ! empty( $input['iframe_blocking_enabled'] );

		// Geolocation
		$output['geo_enabled']       = ! empty( $input['geo_enabled'] );
		$output['geo_jurisdictions'] = array_map( 'sanitize_key', (array) ( $input['geo_jurisdictions'] ?? [] ) );

		// Compliance
		$output['dnt_respect']            = ! empty( $input['dnt_respect'] );
		$output['gpc_respect']            = ! empty( $input['gpc_respect'] );
		$output['cookie_policy_page_id']  = absint( $input['cookie_policy_page_id'] ?? 0 );
		$output['privacy_policy_page_id'] = absint( $input['privacy_policy_page_id'] ?? 0 );

		// Advanced
		$output['show_floating_button']     = ! empty( $input['show_floating_button'] );
		$output['shared_consent']           = ! empty( $input['shared_consent'] );
		$output['consent_logs_enabled']     = ! empty( $input['consent_logs_enabled'] );
		$output['remove_data_on_uninstall'] = ! empty( $input['remove_data_on_uninstall'] );

		// Locale texts — read from $input (stale-current bug — see appearance comment above)
		$output['locale_texts'] = [];
		if ( isset( $input['locale_texts'] ) && is_array( $input['locale_texts'] ) ) {
			foreach ( $input['locale_texts'] as $loc => $data ) {
				if ( ! preg_match( '/^[a-z]{2}(_[A-Z]{2})?$/', $loc ) ) continue;

				if ( is_array( $data ) ) {
					$output['locale_texts'][ $loc ] = [
						'banner_text'     => sanitize_textarea_field( $data['banner_text']     ?? '' ),
						'btn_preferences' => sanitize_text_field(     $data['btn_preferences'] ?? '' ),
						'btn_reject'      => sanitize_text_field(     $data['btn_reject']      ?? '' ),
						'btn_accept'      => sanitize_text_field(     $data['btn_accept']      ?? '' ),
						'modal_title'     => sanitize_text_field(     $data['modal_title']     ?? '' ),
						'modal_intro'     => sanitize_textarea_field( $data['modal_intro']     ?? '' ),
						'modal_accept'    => sanitize_text_field(     $data['modal_accept']    ?? '' ),
						'modal_close'     => sanitize_text_field(     $data['modal_close']     ?? '' ),
						'modal_save'      => sanitize_text_field(     $data['modal_save']      ?? '' ),
					];
				} else {
					// Backwards compat: old plain-string format
					$output['locale_texts'][ $loc ] = [ 'banner_text' => sanitize_textarea_field( $data ) ];
				}
			}
		}

		// Preserve scanner timestamps (these are never in a form, always server-set)
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

		$raw_locale = $_POST['locale'] ?? '';
		$locale     = preg_match( '/^[a-z]{2}(_[A-Z]{2})?$/', $raw_locale ) ? $raw_locale : '';

		if ( $locale ) {
			$locale_data = [
				'banner_text'     => sanitize_textarea_field( $_POST['banner_text']     ?? '' ),
				'btn_preferences' => sanitize_text_field(     $_POST['btn_preferences'] ?? '' ),
				'btn_reject'      => sanitize_text_field(     $_POST['btn_reject']      ?? '' ),
				'btn_accept'      => sanitize_text_field(     $_POST['btn_accept']      ?? '' ),
				'modal_title'     => sanitize_text_field(     $_POST['modal_title']     ?? '' ),
				'modal_intro'     => sanitize_textarea_field( $_POST['modal_intro']     ?? '' ),
				'modal_accept'    => sanitize_text_field(     $_POST['modal_accept']    ?? '' ),
				'modal_close'     => sanitize_text_field(     $_POST['modal_close']     ?? '' ),
				'modal_save'      => sanitize_text_field(     $_POST['modal_save']      ?? '' ),
			];

			$locale_texts           = (array) WPCS_Settings::get( 'locale_texts' );
			$locale_texts[ $locale ] = $locale_data;
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
