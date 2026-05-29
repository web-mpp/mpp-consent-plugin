<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Checks the configured GitHub repository for new releases and wires into
 * WordPress's built-in plugin update UI.
 *
 * Setup:
 *   1. Define WPCS_GITHUB_REPO as 'owner/repo-name' before loading this class.
 *   2. Tag releases as 'v1.2.3' on GitHub.
 *   3. Attach the installable ZIP (named wp-cookie-shield.zip) as a release asset.
 */
class WPCS_Updater {

	private string $plugin_slug = 'wp-cookie-shield';
	private string $plugin_file;
	private string $github_repo;
	private ?array $release_cache = null;

	public function __construct() {
		if ( ! defined( 'WPCS_GITHUB_REPO' ) || empty( WPCS_GITHUB_REPO ) ) {
			return;
		}

		$this->github_repo = WPCS_GITHUB_REPO;
		$this->plugin_file = plugin_basename( WPCS_PLUGIN_FILE );

		// pre_set fires when WP stores the transient after its own API check.
		// site_transient fires on every READ — catches hosts with aggressive object
		// caching (e.g. WP Engine) where the transient set may be bypassed.
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );
		add_filter( 'site_transient_update_plugins',         [ $this, 'check_for_update' ] );
		add_filter( 'plugins_api',                           [ $this, 'plugin_info' ], 20, 3 );
		add_filter( 'upgrader_post_install',                 [ $this, 'fix_folder_name' ], 10, 3 );

		// Admin-only hooks
		if ( is_admin() ) {
			add_action( 'admin_notices',                         [ $this, 'maybe_show_update_notice' ] );
			add_action( 'admin_post_wpcs_force_update_check',    [ $this, 'handle_force_check' ] );
		}
	}

	public function check_for_update( mixed $transient ): mixed {
		// WordPress passes false when the transient doesn't exist yet
		if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $transient;
		}

		$latest = ltrim( $release['tag_name'], 'v' );

		if ( version_compare( $latest, WPCS_VERSION, '>' ) ) {
			$transient->response[ $this->plugin_file ] = (object) [
				'slug'        => $this->plugin_slug,
				'plugin'      => $this->plugin_file,
				'new_version' => $latest,
				'url'         => $release['html_url'],
				'package'     => $this->get_download_url( $release ),
			];
			unset( $transient->no_update[ $this->plugin_file ] );
		} else {
			$transient->no_update[ $this->plugin_file ] = (object) [
				'slug'        => $this->plugin_slug,
				'plugin'      => $this->plugin_file,
				'new_version' => WPCS_VERSION,
				'url'         => $release['html_url'],
				'package'     => '',
			];
		}

		return $transient;
	}

	/**
	 * Show an admin notice if an update is available — visible on every admin page,
	 * not just the Updates screen. Bypasses transient caching issues entirely.
	 */
	public function maybe_show_update_notice(): void {
		if ( ! current_user_can( 'update_plugins' ) ) return;

		$release = $this->get_latest_release();
		if ( ! $release ) return;

		$latest = ltrim( $release['tag_name'], 'v' );
		if ( ! version_compare( $latest, WPCS_VERSION, '>' ) ) return;

		$update_url   = wp_nonce_url( admin_url( 'update.php?action=upgrade-plugin&plugin=' . urlencode( $this->plugin_file ) ), 'upgrade-plugin_' . $this->plugin_file );
		$force_url    = wp_nonce_url( admin_url( 'admin-post.php?action=wpcs_force_update_check' ), 'wpcs_force_check' );
		$release_url  = esc_url( $release['html_url'] );

		printf(
			'<div class="notice notice-warning"><p><strong>WP Cookie Shield %s</strong> is available ' .
			'<a href="%s" target="_blank">— view release notes</a>. ' .
			'<a href="%s">Update now</a> | <a href="%s">Refresh check</a></p></div>',
			esc_html( $latest ),
			$release_url,
			esc_url( $update_url ),
			esc_url( $force_url )
		);
	}

	/** Clear cached release data and redirect back. */
	public function handle_force_check(): void {
		if ( ! current_user_can( 'update_plugins' ) ) wp_die( 'Insufficient permissions.' );
		check_admin_referer( 'wpcs_force_check' );

		delete_transient( 'wpcs_github_release' );
		delete_site_transient( 'update_plugins' );
		$this->release_cache = null;

		wp_redirect( admin_url( 'plugins.php' ) );
		exit;
	}

	public function plugin_info( mixed $result, string $action, object $args ): mixed {
		if ( $action !== 'plugin_information' || $args->slug !== $this->plugin_slug ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) return $result;

		return (object) [
			'name'          => 'WP Cookie Shield',
			'slug'          => $this->plugin_slug,
			'version'       => ltrim( $release['tag_name'], 'v' ),
			'author'        => 'MPP',
			'homepage'      => "https://github.com/{$this->github_repo}",
			'requires'      => '6.3',
			'tested'        => '6.7',
			'requires_php'  => '8.1',
			'download_link' => $this->get_download_url( $release ),
			'sections'      => [
				'description' => 'GDPR/ePrivacy/CCPA-compliant cookie consent manager with Google Consent Mode v2, multilingual support, and cookie scanner.',
				'changelog'   => nl2br( esc_html( $release['body'] ?? '' ) ),
			],
		];
	}

	public function fix_folder_name( mixed $response, array $hook_extra, array $result ): mixed {
		// Only act on our own plugin; pass through errors and other plugins untouched
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_file ) {
			return $response;
		}

		global $wp_filesystem;
		$plugin_folder = WP_PLUGIN_DIR . '/' . $this->plugin_slug;

		if ( ! empty( $result['destination'] ) && $result['destination'] !== $plugin_folder ) {
			$wp_filesystem->move( $result['destination'], $plugin_folder );
		}

		return $response;
	}

	private function get_latest_release(): ?array {
		if ( $this->release_cache !== null ) {
			return $this->release_cache;
		}

		$cached = get_transient( 'wpcs_github_release' );
		if ( $cached !== false ) {
			$this->release_cache = $cached;
			return $this->release_cache;
		}

		$response = wp_remote_get(
			"https://api.github.com/repos/{$this->github_repo}/releases/latest",
			[
				'headers' => [ 'User-Agent' => 'WP Cookie Shield/' . WPCS_VERSION ],
				'timeout' => 10,
				'sslverify' => true,
			]
		);

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
			return null;
		}

		set_transient( 'wpcs_github_release', $data, 12 * HOUR_IN_SECONDS );
		$this->release_cache = $data;

		return $data;
	}

	private function get_download_url( array $release ): string {
		foreach ( $release['assets'] ?? [] as $asset ) {
			if ( str_ends_with( $asset['name'], '.zip' ) ) {
				return $asset['browser_download_url'];
			}
		}
		return $release['zipball_url'] ?? '';
	}
}
