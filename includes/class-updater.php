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
 *
 * WordPress will then show "Update available" in the Plugins list and handle
 * the download + installation automatically.
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

		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );
		add_filter( 'plugins_api',                           [ $this, 'plugin_info' ], 20, 3 );
		add_filter( 'upgrader_post_install',                 [ $this, 'fix_folder_name' ], 10, 3 );
	}

	public function check_for_update( object $transient ): object {
		if ( empty( $transient->checked ) ) {
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
		} else {
			// Tell WP this plugin is up to date so it doesn't re-check immediately
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

	public function plugin_info( mixed $result, string $action, object $args ): mixed {
		if ( $action !== 'plugin_information' || $args->slug !== $this->plugin_slug ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $result;
		}

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

	/**
	 * WordPress unzips the plugin into a temp folder named after the ZIP file,
	 * not the plugin slug. This renames it to the correct slug so the plugin
	 * continues to work after the update.
	 */
	public function fix_folder_name( bool $response, array $hook_extra, array $result ): bool {
		if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_file ) {
			return $response;
		}

		global $wp_filesystem;
		$plugin_folder = WP_PLUGIN_DIR . '/' . $this->plugin_slug;

		if ( $result['destination'] !== $plugin_folder ) {
			$wp_filesystem->move( $result['destination'], $plugin_folder );
			$result['destination'] = $plugin_folder;
		}

		return $response;
	}

	private function get_latest_release(): ?array {
		if ( $this->release_cache !== null ) {
			return $this->release_cache;
		}

		$transient_key = 'wpcs_github_release';
		$cached        = get_transient( $transient_key );

		if ( $cached !== false ) {
			$this->release_cache = $cached;
			return $this->release_cache;
		}

		$response = wp_remote_get(
			"https://api.github.com/repos/{$this->github_repo}/releases/latest",
			[
				'headers' => [ 'User-Agent' => 'WP Cookie Shield/' . WPCS_VERSION ],
				'timeout' => 10,
			]
		);

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
			return null;
		}

		// Cache for 12 hours to avoid hammering the GitHub API
		set_transient( $transient_key, $data, 12 * HOUR_IN_SECONDS );
		$this->release_cache = $data;

		return $data;
	}

	private function get_download_url( array $release ): string {
		// Prefer a release asset named wp-cookie-shield.zip
		foreach ( $release['assets'] ?? [] as $asset ) {
			if ( str_ends_with( $asset['name'], '.zip' ) ) {
				return $asset['browser_download_url'];
			}
		}

		// Fall back to the auto-generated source ZIP
		return $release['zipball_url'] ?? '';
	}
}
