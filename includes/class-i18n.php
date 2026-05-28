<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class WPCS_I18n {

	public function load(): void {
		add_action( 'init', function (): void {
			load_plugin_textdomain(
				WPCS_TEXT_DOMAIN,
				false,
				dirname( plugin_basename( WPCS_PLUGIN_FILE ) ) . '/languages'
			);
		} );
	}
}
