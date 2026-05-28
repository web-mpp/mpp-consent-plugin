<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

final class WPCS_Plugin {

	private static ?self $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->init();
	}

	private function init(): void {
		( new WPCS_I18n() )->load();

		if ( is_admin() ) {
			new WPCS_Admin();
			new WPCS_SettingsAdmin();
		}

		new WPCS_ConsentManager();
		new WPCS_GCMHandler();
		new WPCS_ScriptBlocker();
		new WPCS_Frontend();
		new WPCS_RestAPI();
		new WPCS_WPConsentAPI();
	}
}
