<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class WPCS_ConsentManager {

	private static ?self $instance = null;

	private array $current_consent = [];

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		self::$instance = $this;
		$this->current_consent = $this->read_cookie();
	}

	// -------------------------------------------------------------------------
	// Cookie read / write
	// -------------------------------------------------------------------------

	public function read_cookie(): array {
		$raw = $_COOKIE['wpcs_consent'] ?? '';
		if ( '' === $raw ) {
			return [];
		}

		$decoded = base64_decode( $raw, true );
		if ( false === $decoded ) {
			return [];
		}

		$data = json_decode( $decoded, true );
		if ( ! is_array( $data ) ) {
			return [];
		}

		return $this->is_valid_consent( $data ) ? $data : [];
	}

	public function is_valid_consent( array $data ): bool {
		if ( empty( $data['version'] ) || empty( $data['expires'] ) || empty( $data['categories'] ) ) {
			return false;
		}

		$current_version = WPCS_Settings::get( 'policy_version' );
		if ( $data['version'] !== $current_version ) {
			return false;
		}

		if ( time() > (int) $data['expires'] ) {
			return false;
		}

		return true;
	}

	public function has_consent(): bool {
		return ! empty( $this->current_consent );
	}

	public function get_categories(): array {
		if ( empty( $this->current_consent['categories'] ) ) {
			return $this->get_default_categories();
		}
		return $this->current_consent['categories'];
	}

	public function is_category_granted( string $category ): bool {
		$categories = $this->get_categories();
		return ! empty( $categories[ $category ] );
	}

	private function get_default_categories(): array {
		$cats = WPCS_Settings::get( 'categories' );
		$defaults = [];
		foreach ( $cats as $key => $cat ) {
			$defaults[ $key ] = ! empty( $cat['locked'] ) ? true : false;
		}
		return $defaults;
	}

	// -------------------------------------------------------------------------
	// DNT / GPC server-side pre-check
	// -------------------------------------------------------------------------

	public function should_auto_deny_marketing(): bool {
		// Global Privacy Control
		if ( isset( $_SERVER['HTTP_SEC_GPC'] ) && '1' === $_SERVER['HTTP_SEC_GPC'] ) {
			return true;
		}

		// Do Not Track (only when setting enabled)
		if ( WPCS_Settings::get( 'dnt_respect' ) && isset( $_SERVER['HTTP_DNT'] ) && '1' === $_SERVER['HTTP_DNT'] ) {
			return true;
		}

		return false;
	}
}
