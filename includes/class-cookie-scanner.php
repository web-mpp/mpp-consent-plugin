<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class WPCS_CookieScanner {

	private array $known_plugins = [];

	/**
	 * CDN URL patterns → provider info + cookies they set.
	 * Used by scan_page_source() to identify third-party services from rendered HTML.
	 */
	private const CDN_COOKIE_MAP = [
		'googletagmanager\.com' => [
			'provider' => 'Google Tag Manager',
			'category' => 'statistics',
			'cookies'  => [
				[ 'name' => '_ga',   'duration' => '2 years',  'purpose' => 'Google Analytics — distinguishes unique users' ],
				[ 'name' => '_ga_*', 'duration' => '2 years',  'purpose' => 'Google Analytics — persists session state' ],
				[ 'name' => '_gid',  'duration' => '24 hours', 'purpose' => 'Google Analytics — distinguishes users' ],
				[ 'name' => '_gat',  'duration' => '1 minute', 'purpose' => 'Google Analytics — throttle request rate' ],
			],
		],
		'google-analytics\.com' => [
			'provider' => 'Google Analytics',
			'category' => 'statistics',
			'cookies'  => [
				[ 'name' => '_ga',   'duration' => '2 years',  'purpose' => 'Distinguishes unique users' ],
				[ 'name' => '_ga_*', 'duration' => '2 years',  'purpose' => 'Persists session state' ],
				[ 'name' => '_gid',  'duration' => '24 hours', 'purpose' => 'Distinguishes users' ],
			],
		],
		'googlesyndication\.com|doubleclick\.net' => [
			'provider' => 'Google Ads',
			'category' => 'marketing',
			'cookies'  => [
				[ 'name' => '_gcl_au', 'duration' => '3 months', 'purpose' => 'Google Ads conversion tracking' ],
				[ 'name' => 'IDE',     'duration' => '2 years',  'purpose' => 'Google DoubleClick ad targeting' ],
				[ 'name' => 'test_cookie', 'duration' => 'session', 'purpose' => 'Google ad cookie test' ],
			],
		],
		'connect\.facebook\.net' => [
			'provider' => 'Meta (Facebook)',
			'category' => 'marketing',
			'cookies'  => [
				[ 'name' => '_fbp', 'duration' => '3 months', 'purpose' => 'Facebook Pixel browser identifier' ],
				[ 'name' => '_fbc', 'duration' => '3 months', 'purpose' => 'Facebook click identifier' ],
				[ 'name' => 'fr',   'duration' => '3 months', 'purpose' => 'Facebook ad delivery and measurement' ],
			],
		],
		'js\.hs-scripts\.com|js\.hsforms\.net|js\.hubspot\.com|hs-analytics\.net' => [
			'provider' => 'HubSpot',
			'category' => 'marketing',
			'cookies'  => [
				[ 'name' => '__hssc',     'duration' => 'session',   'purpose' => 'HubSpot session counter' ],
				[ 'name' => '__hssrc',    'duration' => 'session',   'purpose' => 'HubSpot new-session marker' ],
				[ 'name' => '__hstc',     'duration' => '13 months', 'purpose' => 'HubSpot main visitor tracking' ],
				[ 'name' => 'hubspotutk', 'duration' => '13 months', 'purpose' => 'HubSpot visitor token for contact de-duplication' ],
			],
		],
		'snap\.licdn\.com|linkedin\.com\/insight' => [
			'provider' => 'LinkedIn',
			'category' => 'marketing',
			'cookies'  => [
				[ 'name' => 'li_at',                'duration' => '1 year',  'purpose' => 'LinkedIn authentication' ],
				[ 'name' => 'AnalyticsSyncHistory', 'duration' => '1 month', 'purpose' => 'LinkedIn analytics sync' ],
				[ 'name' => 'bcookie',              'duration' => '2 years', 'purpose' => 'LinkedIn browser identifier' ],
				[ 'name' => 'bscookie',             'duration' => '2 years', 'purpose' => 'LinkedIn secure browser ID' ],
				[ 'name' => 'li_gc',                'duration' => '6 months','purpose' => 'LinkedIn consent' ],
			],
		],
		'static\.hotjar\.com' => [
			'provider' => 'Hotjar',
			'category' => 'statistics',
			'cookies'  => [
				[ 'name' => '_hjid',             'duration' => '1 year',  'purpose' => 'Hotjar user identifier' ],
				[ 'name' => '_hjFirstSeen',      'duration' => 'session', 'purpose' => 'First-session marker' ],
				[ 'name' => '_hjSession_*',      'duration' => '30 mins', 'purpose' => 'Current session data' ],
				[ 'name' => '_hjSessionUser_*',  'duration' => '1 year',  'purpose' => 'Session user continuity' ],
			],
		],
		'widget\.intercom\.io|api-iam\.intercom\.io' => [
			'provider' => 'Intercom',
			'category' => 'statistics',
			'cookies'  => [
				[ 'name' => 'intercom-id-*',      'duration' => '9 months', 'purpose' => 'Intercom visitor identifier' ],
				[ 'name' => 'intercom-session-*', 'duration' => '1 week',   'purpose' => 'Intercom session' ],
			],
		],
		'js\.driftt\.com|drift\.com' => [
			'provider' => 'Drift',
			'category' => 'statistics',
			'cookies'  => [
				[ 'name' => 'driftt_aid', 'duration' => '2 years', 'purpose' => 'Drift anonymous visitor identifier' ],
			],
		],
		'client\.crisp\.chat' => [
			'provider' => 'Crisp',
			'category' => 'statistics',
			'cookies'  => [
				[ 'name' => 'crisp-client/*', 'duration' => '6 months', 'purpose' => 'Crisp chat session' ],
			],
		],
		'clarity\.ms' => [
			'provider' => 'Microsoft Clarity',
			'category' => 'statistics',
			'cookies'  => [
				[ 'name' => '_clck', 'duration' => '1 year',  'purpose' => 'Microsoft Clarity user identifier' ],
				[ 'name' => '_clsk', 'duration' => '1 day',   'purpose' => 'Microsoft Clarity session identifier' ],
				[ 'name' => 'CLID',  'duration' => '1 year',  'purpose' => 'Microsoft Clarity visitor ID' ],
			],
		],
		'analytics\.tiktok\.com|static\.ads-twitter\.com' => [
			'provider' => 'TikTok Pixel',
			'category' => 'marketing',
			'cookies'  => [
				[ 'name' => '_ttp', 'duration' => '13 months', 'purpose' => 'TikTok advertising identifier' ],
			],
		],
		'cdn\.segment\.com' => [
			'provider' => 'Segment',
			'category' => 'statistics',
			'cookies'  => [
				[ 'name' => 'ajs_user_id',    'duration' => '1 year', 'purpose' => 'Segment user identifier' ],
				[ 'name' => 'ajs_anonymous_id','duration' => '1 year', 'purpose' => 'Segment anonymous visitor ID' ],
			],
		],
		'cdn\.amplitude\.com' => [
			'provider' => 'Amplitude',
			'category' => 'statistics',
			'cookies'  => [
				[ 'name' => 'amplitude_id_*', 'duration' => '10 years', 'purpose' => 'Amplitude analytics identifier' ],
			],
		],
	];

	/**
	 * Inline script patterns → provider info (when no external src URL is present).
	 */
	private const INLINE_PATTERNS = [
		'gtag\s*\('                     => 'googletagmanager\.com',
		'dataLayer\.push'               => 'googletagmanager\.com',
		'fbq\s*\('                      => 'connect\.facebook\.net',
		'_hsq\s*\.'                     => 'js\.hs-scripts\.com',
		'hbspt\.'                       => 'js\.hs-scripts\.com',
		'_hjid'                         => 'static\.hotjar\.com',
		'window\.intercomSettings'      => 'widget\.intercom\.io',
		'drift\.'                       => 'js\.driftt\.com',
		'\$crisp'                       => 'client\.crisp\.chat',
		'window\.clarity\s*='          => 'clarity\.ms',
		'ttq\.'                         => 'analytics\.tiktok\.com',
		'analytics\.identify|analytics\.track' => 'cdn\.segment\.com',
	];

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
		$results = array_merge( $results, $this->scan_page_source() );

		$results = apply_filters( 'wpcs_scan_results', $results );

		$this->save_results( $results );
		WPCS_Settings::update( [ 'last_scan_time' => time() ] );
		do_action( 'wpcs_scan_complete', $results, time() );

		return $results;
	}

	private function scan_wp_core(): array {
		return [
			[ 'cookie_name' => 'wordpress_*',           'provider' => 'WordPress', 'category' => 'essential', 'purpose' => 'Authentication and session management', 'duration' => 'session',  'source' => 'wp_core', 'plugin_slug' => '' ],
			[ 'cookie_name' => 'wordpress_logged_in_*', 'provider' => 'WordPress', 'category' => 'essential', 'purpose' => 'Keeps users logged in',                'duration' => '14 days', 'source' => 'wp_core', 'plugin_slug' => '' ],
			[ 'cookie_name' => 'wp-settings-*',         'provider' => 'WordPress', 'category' => 'essential', 'purpose' => 'Admin interface preferences',           'duration' => '1 year',  'source' => 'wp_core', 'plugin_slug' => '' ],
			[ 'cookie_name' => 'wordpress_test_cookie', 'provider' => 'WordPress', 'category' => 'essential', 'purpose' => 'Cookie support test',                   'duration' => 'session', 'source' => 'wp_core', 'plugin_slug' => '' ],
			[ 'cookie_name' => 'comment_author_*',      'provider' => 'WordPress', 'category' => 'preferences','purpose' => 'Remembers comment author name/email', 'duration' => '347 days','source' => 'wp_core', 'plugin_slug' => '' ],
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

	/**
	 * Fetch the rendered homepage and detect third-party scripts from actual output.
	 * This catches anything injected by plugins, themes, or page builders regardless
	 * of how it is enqueued — far more reliable than scanning PHP source files.
	 */
	private function scan_page_source(): array {
		$response = wp_remote_get( home_url( '/' ), [
			'timeout'    => 20,
			'user-agent' => 'WP Cookie Shield Scanner/1.0 (cookie audit)',
			'sslverify'  => false,
		] );

		if ( is_wp_error( $response ) ) {
			return [];
		}

		$body    = wp_remote_retrieve_body( $response );
		$results = [];
		$seen    = [];

		// Collect all external script src values
		preg_match_all( '/<script[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $body, $src_matches );
		$script_srcs = $src_matches[1] ?? [];

		// Collect all inline script content
		preg_match_all( '/<script(?![^>]+src)[^>]*>(.*?)<\/script>/si', $body, $inline_matches );
		$inline_scripts = implode( "\n", $inline_matches[1] ?? [] );

		// Match external src URLs against CDN map
		foreach ( self::CDN_COOKIE_MAP as $pattern => $info ) {
			$already_found = false;

			foreach ( $script_srcs as $src ) {
				if ( preg_match( '/' . $pattern . '/i', $src ) ) {
					$already_found = true;
					break;
				}
			}

			// Fall back to inline script pattern detection
			if ( ! $already_found && isset( self::INLINE_PATTERNS ) ) {
				foreach ( self::INLINE_PATTERNS as $inline_pattern => $cdn_pattern ) {
					if ( $cdn_pattern === $pattern || preg_match( '/' . $cdn_pattern . '/i', $pattern ) ) {
						if ( preg_match( '/' . $inline_pattern . '/i', $inline_scripts ) ) {
							$already_found = true;
							break;
						}
					}
				}
			}

			if ( ! $already_found ) continue;
			if ( isset( $seen[ $info['provider'] ] ) ) continue;
			$seen[ $info['provider'] ] = true;

			foreach ( $info['cookies'] as $cookie ) {
				$results[] = [
					'cookie_name' => $cookie['name'],
					'provider'    => $info['provider'],
					'category'    => $info['category'],
					'purpose'     => $cookie['purpose'],
					'duration'    => $cookie['duration'],
					'source'      => 'page_scan',
					'plugin_slug' => '',
				];
			}
		}

		// Also check inline scripts for patterns not covered by CDN URLs
		foreach ( self::INLINE_PATTERNS as $inline_pattern => $cdn_key ) {
			if ( ! preg_match( '/' . $inline_pattern . '/i', $inline_scripts ) ) continue;

			// Find matching CDN entry
			foreach ( self::CDN_COOKIE_MAP as $cdn_pattern => $info ) {
				if ( $cdn_key === $cdn_pattern || preg_match( '/' . preg_quote( $cdn_key, '/' ) . '/i', $cdn_pattern ) ) {
					if ( isset( $seen[ $info['provider'] ] ) ) break;
					$seen[ $info['provider'] ] = true;
					foreach ( $info['cookies'] as $cookie ) {
						$results[] = [
							'cookie_name' => $cookie['name'],
							'provider'    => $info['provider'],
							'category'    => $info['category'],
							'purpose'     => $cookie['purpose'],
							'duration'    => $cookie['duration'],
							'source'      => 'page_scan',
							'plugin_slug' => '',
						];
					}
					break;
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
