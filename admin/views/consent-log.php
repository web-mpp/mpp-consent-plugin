<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

$per_page   = 25;
$paged      = max( 1, absint( $_GET['paged'] ?? 1 ) );
$offset     = ( $paged - 1 ) * $per_page;
$total      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_consent_log" );
$logs       = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}wpcs_consent_log ORDER BY created_at DESC LIMIT %d OFFSET %d",
		$per_page,
		$offset
	),
	ARRAY_A
);
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Consent Log', 'wp-cookie-shield' ); ?></h1>

	<p><?php printf( esc_html__( 'Total records: %d', 'wp-cookie-shield' ), (int) $total ); ?></p>

	<table class="widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Date', 'wp-cookie-shield' ); ?></th>
				<th><?php esc_html_e( 'UUID', 'wp-cookie-shield' ); ?></th>
				<th><?php esc_html_e( 'Method', 'wp-cookie-shield' ); ?></th>
				<th><?php esc_html_e( 'Jurisdiction', 'wp-cookie-shield' ); ?></th>
				<th><?php esc_html_e( 'Version', 'wp-cookie-shield' ); ?></th>
				<th><?php esc_html_e( 'Categories', 'wp-cookie-shield' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $logs as $log ) :
				$cats = json_decode( $log['consent_json'], true ) ?: [];
			?>
			<tr>
				<td><?php echo esc_html( $log['created_at'] ); ?></td>
				<td><code><?php echo esc_html( substr( $log['consent_uuid'], 0, 8 ) ); ?>…</code></td>
				<td><?php echo esc_html( $log['method'] ); ?></td>
				<td><?php echo esc_html( $log['jurisdiction'] ); ?></td>
				<td><?php echo esc_html( $log['version'] ); ?></td>
				<td>
					<?php foreach ( $cats as $cat => $granted ) : ?>
						<span class="wpcs-badge <?php echo $granted ? 'wpcs-badge-granted' : 'wpcs-badge-denied'; ?>">
							<?php echo esc_html( $cat ); ?>
						</span>
					<?php endforeach; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php
	$pages = ceil( $total / $per_page );
	if ( $pages > 1 ) {
		echo paginate_links( [
			'base'    => add_query_arg( 'paged', '%#%' ),
			'format'  => '',
			'current' => $paged,
			'total'   => $pages,
		] );
	}
	?>
</div>
