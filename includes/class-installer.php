<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class WPCS_Installer {

	private const DB_VERSION = '1.0';

	public static function activate(): void {
		self::create_tables();
		self::seed_defaults();
		self::schedule_events();
		update_option( 'wpcs_db_version', self::DB_VERSION );
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'wpcs_scheduled_scan' );
		wp_clear_scheduled_hook( 'wpcs_purge_logs' );
	}

	private static function create_tables(): void {
		global $wpdb;

		$charset = $wpdb->get_charset_collate();

		$consent_log = "CREATE TABLE {$wpdb->prefix}wpcs_consent_log (
			id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			consent_uuid    VARCHAR(36)         NOT NULL,
			user_id         BIGINT(20) UNSIGNED NULL DEFAULT NULL,
			ip_hash         VARCHAR(64)         NOT NULL,
			user_agent_hash VARCHAR(64)         NOT NULL,
			consent_json    LONGTEXT            NOT NULL,
			method          VARCHAR(20)         NOT NULL,
			version         VARCHAR(10)         NOT NULL,
			jurisdiction    VARCHAR(10)         NOT NULL DEFAULT 'UNKNOWN',
			created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			expires_at      DATETIME            NOT NULL,
			PRIMARY KEY (id),
			KEY consent_uuid (consent_uuid),
			KEY created_at (created_at)
		) ENGINE=InnoDB $charset;";

		$cookies = "CREATE TABLE {$wpdb->prefix}wpcs_cookies (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			cookie_name  VARCHAR(255)        NOT NULL,
			provider     VARCHAR(255)        NOT NULL DEFAULT '',
			purpose      TEXT                NOT NULL DEFAULT '',
			category     VARCHAR(50)         NOT NULL DEFAULT 'statistics',
			duration     VARCHAR(100)        NOT NULL DEFAULT '',
			cookie_type  VARCHAR(20)         NOT NULL DEFAULT 'http',
			domain       VARCHAR(255)        NOT NULL DEFAULT '',
			source       VARCHAR(20)         NOT NULL DEFAULT 'manual',
			is_active    TINYINT(1)          NOT NULL DEFAULT 1,
			created_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY category (category),
			UNIQUE KEY cookie_name_domain (cookie_name, domain)
		) ENGINE=InnoDB $charset;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $consent_log );
		dbDelta( $cookies );
	}

	private static function seed_defaults(): void {
		if ( get_option( 'wpcs_settings' ) ) {
			return;
		}
		add_option( 'wpcs_settings', WPCS_Settings::get_defaults() );
	}

	private static function schedule_events(): void {
		if ( ! wp_next_scheduled( 'wpcs_scheduled_scan' ) ) {
			wp_schedule_event( time(), 'weekly', 'wpcs_scheduled_scan' );
		}
		if ( ! wp_next_scheduled( 'wpcs_purge_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'wpcs_purge_logs' );
		}
	}
}
