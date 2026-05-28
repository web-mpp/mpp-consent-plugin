<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class WPCS_ScriptBlocker {

	private const DEFAULT_PATTERNS = [
		'googletagmanager\.com',
		'google-analytics\.com',
		'googlesyndication\.com',
		'connect\.facebook\.net',
		'facebook\.com\/tr',
		'snap\.licdn\.com',
		'linkedin\.com\/insight',
		'static\.hotjar\.com',
		'widget\.intercom\.io',
		'js\.driftt\.com',
	];

	public function __construct() {
		if ( ! WPCS_Settings::get( 'script_blocking_enabled' ) ) {
			return;
		}

		$consent = WPCS_ConsentManager::get_instance();
		if ( $consent->has_consent() ) {
			return;
		}

		add_action( 'template_redirect', [ $this, 'start_buffer' ], 1 );
		add_action( 'shutdown',          [ $this, 'end_buffer' ], 0 );
	}

	public function start_buffer(): void {
		ob_start( [ $this, 'process_buffer' ] );
	}

	public function end_buffer(): void {
		if ( ob_get_level() > 0 ) {
			ob_end_flush();
		}
	}

	public function process_buffer( string $html ): string {
		$patterns = apply_filters(
			'wpcs_blocked_script_patterns',
			array_merge( self::DEFAULT_PATTERNS, (array) WPCS_Settings::get( 'blocked_patterns' ) )
		);

		$combined = implode( '|', $patterns );

		// Block external scripts whose src matches a blocked pattern
		$html = preg_replace_callback(
			'/<script([^>]*)\ssrc=["\']([^"\']*(?:' . $combined . ')[^"\']*)["\']([^>]*)>/i',
			function ( array $m ): string {
				$before  = $m[1];
				$src     = esc_url( $m[2] );
				$after   = $m[3];
				$category = $this->detect_category( $src );
				return '<script' . $before . ' type="text/plain" data-wpcs-src="' . $src . '" data-wpcs-category="' . esc_attr( $category ) . '"' . $after . '>';
			},
			$html
		);

		return $html;
	}

	private function detect_category( string $src ): string {
		$marketing_patterns = [
			'facebook\.net', 'facebook\.com\/tr', 'googlesyndication',
			'snap\.licdn', 'linkedin\.com\/insight',
		];
		foreach ( $marketing_patterns as $p ) {
			if ( preg_match( '/' . $p . '/i', $src ) ) {
				return 'marketing';
			}
		}

		$stats_patterns = [
			'googletagmanager', 'google-analytics', 'hotjar', 'intercom', 'driftt',
		];
		foreach ( $stats_patterns as $p ) {
			if ( preg_match( '/' . $p . '/i', $src ) ) {
				return 'statistics';
			}
		}

		return 'statistics';
	}
}
