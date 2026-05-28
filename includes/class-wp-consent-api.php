<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class WPCS_WPConsentAPI {

	public function __construct() {
		add_filter( 'wp_consent_api_registered_wp-cookie-shield', '__return_true' );

		add_filter( 'wp_consent_category_map', [ $this, 'map_categories' ] );

		add_filter( 'wp_has_consent', [ $this, 'check_consent' ], 10, 2 );
	}

	public function map_categories( array $map ): array {
		return array_merge( $map, [
			'statistics'  => [ 'statistics', 'statistics-anonymous' ],
			'marketing'   => [ 'marketing' ],
			'preferences' => [ 'preferences', 'functional' ],
			'essential'   => [ 'functional' ],
		] );
	}

	public function check_consent( bool $has_consent, string $category ): bool {
		return WPCS_ConsentManager::get_instance()->is_category_granted( $category );
	}
}
