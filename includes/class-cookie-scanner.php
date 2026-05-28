<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class WPCS_CookieScanner {

	private array $known_plugins = [];

	public function __construct() {
		$json_path = WPCS_PLUGIN_DIR . 'data/known-plugins.json';
		if ( file_exists( $json_path ) ) {
			$this->known_plugins = json_decode( file_get_contents( $json_path ), true ) ?: [];
		}
	}

	public function scan(): array {
		$results = [];

		$results = array_merge( $results, $this->scan_wp_core() );
		$results = array_merge( $results, $this->scan_known_plugins() );
		$results = array_merge( $results, $this->scan_theme() );

		$results = apply_filters( 'wpcs_scan_results', $results );

		$this->save_results( $results );

		WPCS_Settings::update( [ 'last_scan_time' => time() ] );

		do_action( 'wpcs_scan_complete', $results, time() );

		return $results;
	}

	private function scan_wp_core(): array {
		return [
			[ 'cookie_name' => 'wordpress_*',       'provider' => 'WordPress', 'category' => 'essential', 'purpose' => 'Authentication', 'duration' => 'session', 'source' => 'wp_core', 'plugin_slug' => '' ],
			[ 'cookie_name' => 'wordpress_logged_in_*', 'provider' => 'WordPress', 'category' => 'essential', 'purpose' => 'Login status', 'duration' => '14 days', 'source' => 'wp_core', 'plugin_slug' => '' ],
			[ 'cookie_name' => 'wp-settings-*',     'provider' => 'WordPress', 'category' => 'essential', 'purpose' => 'Admin UI preferences', 'duration' => '1 year', 'source' => 'wp_core', 'plugin_slug' => '' ],
			[ 'cookie_name' => 'wordpress_test_cookie', 'provider' => 'WordPress', 'category' => 'essential', 'purpose' => 'Cookie test', 'duration' => 'session', 'source' => 'wp_core', 'plugin_slug' => '' ],
		];
	}

	private function scan_known_plugins(): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$active  = get_option( 'active_plugins', [] );
		$results = [];

		foreach ( $active as $plugin_file ) {
			$slug = explode( '/', $plugin_file )[0];

			if ( isset( $this->known_plugins[ $slug ] ) ) {
				$info = $this->known_plugins[ $slug ];
				foreach ( $info['cookies'] as $cookie ) {
					$results[] = [
						'cookie_name' => $cookie['name'],
						'provider'    => $info['provider'],
						'category'    => $info['category'],
						'purpose'     => $cookie['purpose'] ?? '',
						'duration'    => $cookie['duration'] ?? '',
						'source'      => 'known_plugin',
						'plugin_slug' => $slug,
					];
				}
			}
		}

		return $results;
	}

	private function scan_theme(): array {
		$results    = [];
		$theme_dir  = get_template_directory();
		$scan_files = [
			$theme_dir . '/functions.php',
			$theme_dir . '/header.php',
		];

		$cdn_map = [
			'googletagmanager\.com' => [ 'provider' => 'Google', 'category' => 'statistics' ],
			'google-analytics\.com' => [ 'provider' => 'Google Analytics', 'category' => 'statistics' ],
			'connect\.facebook\.net'=> [ 'provider' => 'Meta', 'category' => 'marketing' ],
			'snap\.licdn\.com'      => [ 'provider' => 'LinkedIn', 'category' => 'marketing' ],
			'static\.hotjar\.com'   => [ 'provider' => 'Hotjar', 'category' => 'statistics' ],
		];

		foreach ( $scan_files as $file ) {
			if ( ! file_exists( $file ) ) continue;

			$content = file_get_contents( $file );

			foreach ( $cdn_map as $pattern => $meta ) {
				if ( preg_match( '/' . $pattern . '/i', $content ) ) {
					$results[] = [
						'cookie_name' => '(detected from theme: ' . $meta['provider'] . ')',
						'provider'    => $meta['provider'],
						'category'    => $meta['category'],
						'purpose'     => 'Detected via CDN reference in theme',
						'duration'    => 'varies',
						'source'      => 'theme_scan',
						'plugin_slug' => '',
					];
				}
			}
		}

		return $results;
	}

	private function save_results( array $results ): void {
		global $wpdb;

		foreach ( $results as $row ) {
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$wpdb->prefix}wpcs_cookies
					 (cookie_name, provider, purpose, category, duration, source)
					 VALUES (%s, %s, %s, %s, %s, %s)
					 ON DUPLICATE KEY UPDATE
					 provider = VALUES(provider),
					 purpose  = VALUES(purpose),
					 category = VALUES(category),
					 duration = VALUES(duration)",
					$row['cookie_name'],
					$row['provider'],
					$row['purpose'],
					$row['category'],
					$row['duration'],
					$row['source']
				)
			);
		}
	}
}
