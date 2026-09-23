<?php
/**
 * Beheer: planningsoverzicht per maand (artikelen × dagen).
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'edit.php?post_type=kr_booking', 'Planning', 'Planning', 'edit_posts', 'krv-planning', 'krv_planning_page', 1 );
	}
);

function krv_planning_page() {
	// phpcs:ignore WordPress.Security.NonceVerification
	$month = isset( $_GET['maand'] ) && preg_match( '/^\d{4}-\d{2}$/', sanitize_text_field( wp_unslash( $_GET['maand'] ) ) ) ? sanitize_text_field( wp_unslash( $_GET['maand'] ) ) : wp_date( 'Y-m' );
	$first = new DateTimeImmutable( $month . '-01' );
	$from  = $first->format( 'Y-m-d' );
	$to    = $first->modify( 'last day of this month' )->format( 'Y-m-d' );
	$dates = krv_date_range( $from, $to );
	$today = krv_today();
	$base  = admin_url( 'edit.php?post_type=kr_booking&page=krv-planning' );

	$bookings = get_posts(
		array(
			'post_type'   => 'kr_booking',
			'numberposts' => -1,
			'meta_query'  => array(
				'relation' => 'AND',
				array( 'key' => '_krv_status', 'value' => 'cancelled', 'compare' => '!=' ),
				array( 'key' => '_krv_start', 'value' => $to, 'compare' => '<=' ),
				array( 'key' => '_krv_end', 'value' => $from, 'compare' => '>=' ),
			),
		)
	);
	// [product_id][date] => [booking, ...]
	$grid = array();
	foreach ( $bookings as $p ) {
		$b = krv_get_booking( $p->ID );
		foreach ( krv_date_range( max( $from, $b['start'] ), min( $to, $b['end'] ) ) as $d ) {
			$grid[ (int) $b['product_id'] ][ $d ][] = $b;
		}
	}
	$products = get_posts( array( 'post_type' => 'kr_product', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
	$colors   = array( 'pending' => '#f0b429', 'confirmed' => '#519f81', 'completed' => '#8a9aa5' );
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">Planning</h1>
		<a class="page-title-action" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=kr_booking' ) ); ?>">Nieuwe boeking</a>
		<hr class="wp-header-end">
		<style>
			.krv-plan-nav{display:flex;align-items:center;gap:10px;margin:14px 0}
			.krv-plan-nav h2{margin:0;min-width:180px;text-align:center;text-transform:capitalize}
			.krv-plan-wrap{overflow-x:auto;background:#fff;border:1px solid #dcdcde}
			.krv-plan{border-collapse:collapse;font-size:12px;min-width:100%}
			.krv-plan th,.krv-plan td{border:1px solid #f0f0f1;padding:0;text-align:center;height:34px;min-width:30px}
			.krv-plan th.prod{text-align:left;padding:4px 10px;min-width:180px;position:sticky;left:0;background:#fff;z-index:1;font-weight:600}
			.krv-plan thead th{background:#f6f7f7;padding:4px 2px}
			.krv-plan .we{background:#fafafa}
			.krv-plan .today{outline:2px solid #002533;outline-offset:-2px}
			.krv-plan a.cell{display:flex;align-items:center;justify-content:center;height:34px;color:#fff;text-decoration:none;font-weight:700}
			.krv-legend span{display:inline-flex;align-items:center;gap:6px;margin-right:16px}
			.krv-legend i{width:12px;height:12px;border-radius:3px;display:inline-block}
		</style>
		<div class="krv-plan-nav">
			<a class="button" href="<?php echo esc_url( add_query_arg( 'maand', $first->modify( '-1 month' )->format( 'Y-m' ), $base ) ); ?>">&larr; Vorige</a>
			<h2><?php echo esc_html( wp_date( 'F Y', $first->getTimestamp() + 43200 ) ); ?></h2>
			<a class="button" href="<?php echo esc_url( add_query_arg( 'maand', $first->modify( '+1 month' )->format( 'Y-m' ), $base ) ); ?>">Volgende &rarr;</a>
			<a class="button-link" href="<?php echo esc_url( $base ); ?>">Vandaag</a>
		</div>
		<p class="krv-legend">
			<span><i style="background:<?php echo esc_attr( $colors['pending'] ); ?>"></i>Aanvraag</span>
			<span><i style="background:<?php echo esc_attr( $colors['confirmed'] ); ?>"></i>Bevestigd</span>
			<span><i style="background:<?php echo esc_attr( $colors['completed'] ); ?>"></i>Afgerond</span>
			<span>Getal = aantal verhuurde stuks / voorraad. Klik op een vak om de boeking te openen.</span>
		</p>
		<div class="krv-plan-wrap">
			<table class="krv-plan">
				<thead><tr><th class="prod">Artikel</th>
					<?php foreach ( $dates as $d ) : ?>
						<?php $ts = strtotime( $d . ' 12:00' ); $we = in_array( (int) gmdate( 'N', $ts ), array( 6, 7 ), true ); ?>
						<th class="<?php echo $we ? 'we' : ''; ?> <?php echo $d === $today ? 'today' : ''; ?>"><?php echo esc_html( wp_date( 'D', $ts ) ); ?><br><?php echo (int) substr( $d, 8 ); ?></th>
					<?php endforeach; ?>
				</tr></thead>
				<tbody>
				<?php foreach ( $products as $prod ) : ?>
					<?php $stock = max( 1, (int) get_post_meta( $prod->ID, '_krv_stock', true ) ); ?>
					<tr><th class="prod"><a href="<?php echo esc_url( add_query_arg( array( 'post_type' => 'kr_booking', 'krv_product' => $prod->ID ), admin_url( 'edit.php' ) ) ); ?>"><?php echo esc_html( $prod->post_title ); ?></a></th>
						<?php foreach ( $dates as $d ) : ?>
							<?php
							$cell = isset( $grid[ $prod->ID ][ $d ] ) ? $grid[ $prod->ID ][ $d ] : array();
							$ts   = strtotime( $d . ' 12:00' );
							$we   = in_array( (int) gmdate( 'N', $ts ), array( 6, 7 ), true );
							?>
							<td class="<?php echo $we ? 'we' : ''; ?> <?php echo $d === $today ? 'today' : ''; ?>">
							<?php
							if ( $cell ) {
								$qty    = array_sum( array_map( function ( $b ) { return max( 1, (int) $b['quantity'] ); }, $cell ) );
								$status = in_array( 'pending', wp_list_pluck( $cell, 'status' ), true ) ? 'pending' : $cell[0]['status'];
								$title  = implode( "\n", array_map( function ( $b ) { return krv_booking_ref( $b['id'] ) . ' – ' . $b['name'] . ( $b['quantity'] > 1 ? ' (' . $b['quantity'] . '×)' : '' ) . ' – ' . krv_statuses()[ $b['status'] ]; }, $cell ) );
								$link   = 1 === count( $cell ) ? get_edit_post_link( $cell[0]['id'] ) : add_query_arg( array( 'post_type' => 'kr_booking', 'krv_product' => $prod->ID, 'krv_from' => $d, 'krv_to' => $d ), admin_url( 'edit.php' ) );
								$bg     = isset( $colors[ $status ] ) ? $colors[ $status ] : '#8a9aa5';
								echo '<a class="cell" style="background:' . esc_attr( $bg ) . '" href="' . esc_url( $link ) . '" title="' . esc_attr( $title ) . '">' . ( $stock > 1 ? (int) $qty . '/' . (int) $stock : '' ) . '</a>';
							}
							?>
							</td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
