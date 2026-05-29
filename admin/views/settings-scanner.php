<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

$s             = WPCS_Settings::get();
$last_scan     = $s['last_scan_time'];
$freq_days     = $s['scan_frequency_days'];
$overdue       = $last_scan > 0 && ( time() - $last_scan ) > $freq_days * DAY_IN_SECONDS;
$last_scan_str = $last_scan > 0 ? human_time_diff( $last_scan ) . ' ' . __( 'ago', 'wp-cookie-shield' ) : __( 'Never', 'wp-cookie-shield' );

global $wpdb;
$cookies = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpcs_cookies ORDER BY category, cookie_name", ARRAY_A );
?>

<div class="wpcs-scanner-header">
	<p id="wpcs-last-scan-text">
		<?php
		printf(
			/* translators: %s: human-readable time */
			esc_html__( 'Last scanned: %s', 'wp-cookie-shield' ),
			esc_html( $last_scan_str )
		);
		?>
		<?php if ( $overdue ) : ?>
			<span class="wpcs-badge wpcs-badge-warning"><?php esc_html_e( 'Scan overdue', 'wp-cookie-shield' ); ?></span>
		<?php endif; ?>
	</p>
	<button type="button" id="wpcs-run-scan" class="button button-primary">
		<?php esc_html_e( 'Run Scan Now', 'wp-cookie-shield' ); ?>
	</button>
	<span id="wpcs-scan-status" class="wpcs-scan-status"></span>
</div>

<div id="wpcs-cookies-wrap">
<?php if ( $cookies ) : ?>
<table class="widefat fixed striped" id="wpcs-cookies-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Cookie Name', 'wp-cookie-shield' ); ?></th>
			<th><?php esc_html_e( 'Provider', 'wp-cookie-shield' ); ?></th>
			<th><?php esc_html_e( 'Category', 'wp-cookie-shield' ); ?></th>
			<th><?php esc_html_e( 'Duration', 'wp-cookie-shield' ); ?></th>
			<th><?php esc_html_e( 'Source', 'wp-cookie-shield' ); ?></th>
		</tr>
	</thead>
	<tbody id="wpcs-cookies-tbody">
		<?php foreach ( $cookies as $cookie ) : ?>
		<tr>
			<td><code><?php echo esc_html( $cookie['cookie_name'] ); ?></code></td>
			<td><?php echo esc_html( $cookie['provider'] ); ?></td>
			<td><span class="wpcs-badge wpcs-badge-<?php echo esc_attr( $cookie['category'] ); ?>"><?php echo esc_html( $cookie['category'] ); ?></span></td>
			<td><?php echo esc_html( $cookie['duration'] ); ?></td>
			<td><?php echo esc_html( $cookie['source'] ); ?></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php else : ?>
	<p id="wpcs-cookies-empty"><?php esc_html_e( 'No cookies found yet. Run a scan to detect cookies.', 'wp-cookie-shield' ); ?></p>
<?php endif; ?>
</div>
