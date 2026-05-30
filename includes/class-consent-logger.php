<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class WPCS_ConsentLogger {

	public function log(
		string $uuid,
		array  $categories,
		string $method,
		string $jurisdiction = 'UNKNOWN'
	): bool {
		global $wpdb;

		$settings     = WPCS_Settings::get();
		$expiry_days  = absint( $settings['consent_expiry_days'] ?? 365 );
		$expires_at   = gmdate( 'Y-m-d H:i:s', time() + $expiry_days * DAY_IN_SECONDS );

		$ip   = $_SERVER['REMOTE_ADDR'] ?? '';
		$ua   = $_SERVER['HTTP_USER_AGENT'] ?? '';
		$salt = wp_salt( 'auth' );

		$result = $wpdb->insert(
			$wpdb->prefix . 'wpcs_consent_log',
			[
				'consent_uuid'    => sanitize_text_field( $uuid ),
				'user_id'         => get_current_user_id() ?: null,
				'ip_hash'         => hash_hmac( 'sha256', $ip, $salt ),
				'user_agent_hash' => hash_hmac( 'sha256', $ua, $salt ),
				'consent_json'    => wp_json_encode( $categories ),
				'method'          => sanitize_key( $method ),
				'version'         => sanitize_text_field( $settings['policy_version'] ?? '1.0' ),
				'jurisdiction'    => sanitize_key( $jurisdiction ),
				'created_at'      => current_time( 'mysql', true ),
				'expires_at'      => $expires_at,
			],
			[ '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		if ( $result ) {
			do_action( 'wpcs_consent_saved', $uuid, $categories, $method );
		}

		return (bool) $result;
	}

	public function purge_expired(): int {
		global $wpdb;

		// Keep logs for at least 3 years for legal compliance
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - 3 * YEAR_IN_SECONDS );

		return (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}wpcs_consent_log WHERE expires_at < %s AND created_at < %s",
				current_time( 'mysql', true ),
				$cutoff
			)
		);
	}
}
