<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class WPCS_RestAPI {

	private const NAMESPACE = 'wp-cookie-shield/v1';

	public function __construct() {
		add_action( 'rest_api_init',      [ $this, 'register_routes' ] );
		add_action( 'wpcs_run_scan_job',  [ $this, 'run_scan_job' ] );
	}

	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/consent', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'save_consent' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( self::NAMESPACE, '/consent/(?P<uuid>[a-f0-9\-]+)', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_consent' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( self::NAMESPACE, '/categories', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_categories' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( self::NAMESPACE, '/scan', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'trigger_scan' ],
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		] );
	}

	public function save_consent( WP_REST_Request $request ): WP_REST_Response {
		$nonce = $request->get_param( 'nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wpcs_consent' ) ) {
			return $this->error_response( 'invalid_nonce', 'Security check failed.', 403 );
		}

		$uuid       = sanitize_text_field( $request->get_param( 'uuid' ) ?: wp_generate_uuid4() );
		$method     = sanitize_key( $request->get_param( 'method' ) ?: 'custom' );
		$version    = sanitize_text_field( $request->get_param( 'version' ) ?: WPCS_Settings::get( 'policy_version' ) );
		$categories = $request->get_param( 'categories' );

		if ( ! is_array( $categories ) ) {
			return $this->error_response( 'invalid_categories', 'Categories must be an object.', 400 );
		}

		$allowed = array_keys( WPCS_Settings::get( 'categories' ) );
		$clean   = [];
		foreach ( $allowed as $key ) {
			$clean[ $key ] = isset( $categories[ $key ] ) ? (bool) $categories[ $key ] : false;
		}
		$clean['essential'] = true; // always

		$geo          = new WPCS_Geolocation();
		$jurisdiction = $geo->get_jurisdiction();

		$logger      = new WPCS_ConsentLogger();
		$logger->log( $uuid, $clean, $method, $jurisdiction );

		$expiry_days = absint( WPCS_Settings::get( 'consent_expiry_days' ) );
		$expires_at  = gmdate( 'Y-m-d\TH:i:s\Z', time() + $expiry_days * DAY_IN_SECONDS );

		$response = new WP_REST_Response( [
			'success'    => true,
			'uuid'       => $uuid,
			'expires_at' => $expires_at,
		], 200 );

		$response->header( 'Cache-Control', 'no-store, no-cache' );

		return $response;
	}

	public function get_consent( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$uuid = sanitize_text_field( $request->get_param( 'uuid' ) );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wpcs_consent_log WHERE consent_uuid = %s ORDER BY id DESC LIMIT 1",
				$uuid
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return $this->error_response( 'not_found', 'No consent record found.', 404 );
		}

		$response = new WP_REST_Response( [
			'uuid'       => $row['consent_uuid'],
			'method'     => $row['method'],
			'version'    => $row['version'],
			'categories' => json_decode( $row['consent_json'], true ),
			'created_at' => $row['created_at'],
			'expires_at' => $row['expires_at'],
		], 200 );

		$response->header( 'Cache-Control', 'no-store, no-cache' );

		return $response;
	}

	public function get_categories( WP_REST_Request $request ): WP_REST_Response {
		$cats = WPCS_Settings::get( 'categories' );

		$response = new WP_REST_Response( [ 'categories' => $cats ], 200 );
		$response->header( 'Cache-Control', 'no-store, no-cache' );

		return $response;
	}

	public function trigger_scan( WP_REST_Request $request ): WP_REST_Response {
		$scanner = new WPCS_CookieScanner();
		$job_id  = uniqid( 'scan_', true );

		// Schedule async via cron (fires on next page load)
		wp_schedule_single_event( time(), 'wpcs_run_scan_job', [ $job_id ] );

		return new WP_REST_Response( [ 'job_id' => $job_id, 'status' => 'scheduled' ], 202 );
	}

	public function run_scan_job( string $job_id ): void {
		$scanner = new WPCS_CookieScanner();
		$scanner->scan();
	}

	private function error_response( string $code, string $message, int $status ): WP_REST_Response {
		$response = new WP_REST_Response( [ 'code' => $code, 'message' => $message ], $status );
		$response->header( 'Cache-Control', 'no-store, no-cache' );
		return $response;
	}
}
