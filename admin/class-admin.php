<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class WPCS_Admin {

	public function __construct() {
		add_action( 'admin_menu',            [ $this, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wpcs_scheduled_scan',   [ $this, 'run_scheduled_scan' ] );
		add_action( 'wpcs_purge_logs',       [ $this, 'run_log_purge' ] );
	}

	public function register_menus(): void {
		add_options_page(
			__( 'Cookie Shield', 'wp-cookie-shield' ),
			__( 'Cookie Shield', 'wp-cookie-shield' ),
			'manage_options',
			'wpcs-settings',
			[ $this, 'render_settings_page' ]
		);

		add_management_page(
			__( 'Consent Log', 'wp-cookie-shield' ),
			__( 'Consent Log', 'wp-cookie-shield' ),
			'manage_options',
			'wpcs-consent-log',
			[ $this, 'render_consent_log_page' ]
		);
	}

	public function enqueue_assets( string $hook ): void {
		$allowed = [ 'settings_page_wpcs-settings', 'tools_page_wpcs-consent-log' ];
		if ( ! in_array( $hook, $allowed, true ) ) return;

		wp_enqueue_style(
			'wpcs-admin',
			WPCS_PLUGIN_URL . 'admin/assets/admin.css',
			[],
			WPCS_VERSION
		);

		wp_enqueue_script(
			'wpcs-admin',
			WPCS_PLUGIN_URL . 'admin/assets/admin.js',
			[ 'jquery' ],
			WPCS_VERSION,
			true
		);

		wp_localize_script( 'wpcs-admin', 'wpcsAdmin', [
			'nonce'      => wp_create_nonce( 'wpcs_admin_action' ),
			'restNonce'  => wp_create_nonce( 'wp_rest' ),
			'restUrl'    => rest_url( 'wp-cookie-shield/v1' ),
		] );
	}

	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) return;

		$active_tab = sanitize_key( $_GET['tab'] ?? 'general' );
		$tabs = [
			'general'    => __( 'General', 'wp-cookie-shield' ),
			'categories' => __( 'Categories', 'wp-cookie-shield' ),
			'scanner'    => __( 'Scanner', 'wp-cookie-shield' ),
			'gcm'        => __( 'Google Consent Mode', 'wp-cookie-shield' ),
			'compliance' => __( 'Compliance', 'wp-cookie-shield' ),
			'languages'  => __( 'Languages', 'wp-cookie-shield' ),
			'advanced'   => __( 'Advanced', 'wp-cookie-shield' ),
		];
		?>
		<div class="wrap wpcs-admin-wrap">
			<h1><?php esc_html_e( 'Cookie Shield', 'wp-cookie-shield' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'tab', $slug ) ); ?>"
					   class="nav-tab <?php echo $active_tab === $slug ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="wpcs-tab-content">
				<?php
				$view = WPCS_PLUGIN_DIR . 'admin/views/settings-' . $active_tab . '.php';
				if ( file_exists( $view ) ) {
					include $view;
				}
				?>
			</div>
		</div>
		<?php
	}

	public function render_consent_log_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) return;
		include WPCS_PLUGIN_DIR . 'admin/views/consent-log.php';
	}

	public function run_scheduled_scan(): void {
		$scanner = new WPCS_CookieScanner();
		$scanner->scan();
	}

	public function run_log_purge(): void {
		$logger = new WPCS_ConsentLogger();
		$logger->purge_expired();
	}
}
