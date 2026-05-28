<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class WPCS_Geolocation {

	const JURISDICTIONS = [
		'EU'   => [ 'AT','BE','BG','CY','CZ','DE','DK','EE','ES','FI','FR','GR',
		            'HR','HU','IE','IT','LT','LU','LV','MT','NL','PL','PT','RO',
		            'SE','SI','SK','IS','LI','NO','GB' ],
		'CA'   => [ 'CA' ],
		'US-CA'=> [ 'US' ],
	];

	public function get_country(): string {
		// Cloudflare
		if ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
			return strtoupper( sanitize_text_field( $_SERVER['HTTP_CF_IPCOUNTRY'] ) );
		}

		// Fastly
		if ( ! empty( $_SERVER['HTTP_X_COUNTRY_CODE'] ) ) {
			return strtoupper( sanitize_text_field( $_SERVER['HTTP_X_COUNTRY_CODE'] ) );
		}

		// CloudFront
		if ( ! empty( $_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY'] ) ) {
			return strtoupper( sanitize_text_field( $_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY'] ) );
		}

		return 'UNKNOWN';
	}

	public function get_jurisdiction( string $country = '' ): string {
		if ( '' === $country ) {
			$country = $this->get_country();
		}

		foreach ( self::JURISDICTIONS as $jurisdiction => $countries ) {
			if ( in_array( $country, $countries, true ) ) {
				return $jurisdiction;
			}
		}

		return 'UNKNOWN';
	}

	public function is_regulated( string $country = '' ): bool {
		return 'UNKNOWN' !== $this->get_jurisdiction( $country );
	}

	public function should_show_banner(): bool {
		if ( ! WPCS_Settings::get( 'geo_enabled' ) ) {
			return true;
		}

		$country      = $this->get_country();
		$jurisdiction = $this->get_jurisdiction( $country );

		if ( 'UNKNOWN' === $country ) {
			return true; // Fallback: show banner
		}

		$configured = (array) WPCS_Settings::get( 'geo_jurisdictions' );

		return in_array( $jurisdiction, $configured, true );
	}
}
