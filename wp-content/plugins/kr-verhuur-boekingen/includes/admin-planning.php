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
	// phpcs:ignore WordPress.Security.NonceVerification
	$group_filter = isset( $_GET['groep'] ) ? (int) $_GET['groep'] : 0;
	$base         = admin_url( 'edit.php?post_type=kr_booking&page=krv-planning' );
	if ( $group_filter ) {
		$base = add_query_arg( 'groep', $group_filter, $base );
	}

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
	$colors = array( 'pending' => '#f0b429', 'confirmed' => '#519f81', 'completed' => '#8a9aa5' );

	// Artikelen per huurgroep. Verkoopartikelen en artikelen die via een externe site
	// geboekt worden (bijv. de camper via Goboony) hebben geen agenda en staan er niet in.
	$in_planning = function ( $prod ) {
		$p = krv_get_product( $prod );
		return $p && ! $p['is_sale'] && ! $p['external_url'];
	};
	$products = array_values( array_filter( get_posts( array( 'post_type' => 'kr_product', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC' ) ), $in_planning ) );
	$sections = array();
	$grouped  = array();
	foreach ( krv_get_groups() as $term ) {
		$items = array_values(
			array_filter(
				$products,
				function ( $prod ) use ( $term ) {
					return has_term( $term->term_id, 'kr_group', $prod );
				}
			)
		);
		if ( $items ) {
			$sections[] = array( 'term' => $term, 'items' => $items );
			foreach ( $items as $it ) {
				$grouped[ $it->ID ] = true;
			}
		}
	}
	$rest = array_values(
		array_filter(
			$products,
			function ( $prod ) use ( $grouped ) {
				return empty( $grouped[ $prod->ID ] );
			}
		)
	);
	if ( $rest ) {
		$sections[] = array( 'term' => null, 'items' => $rest );
	}
	$all_sections = $sections;
	if ( $group_filter ) {
		$sections = array_values(
			array_filter(
				$sections,
				function ( $s ) use ( $group_filter ) {
					return $s['term'] && (int) $s['term']->term_id === $group_filter;
				}
			)
		);
	}
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
			.krv-plan tr.grp th{background:#002533;color:#fff;text-align:left;padding:6px 10px;font-size:13px;height:auto;position:sticky;left:0}
			.krv-plan tr.grp th a{color:#fff}
			.krv-plan tr.grp th small{opacity:.75;font-weight:400;margin-left:6px}
			.krv-plan th.prod{padding-left:18px}
			.krv-plan-groups{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 12px}
			.krv-plan-groups a{display:inline-block;padding:4px 12px;border-radius:999px;border:1px solid #c3c4c7;background:#fff;text-decoration:none;color:#1d2327}
			.krv-plan-groups a.current{background:#002533;border-color:#002533;color:#fff}
		</style>
		<div class="krv-plan-nav">
			<a class="button" href="<?php echo esc_url( add_query_arg( 'maand', $first->modify( '-1 month' )->format( 'Y-m' ), $base ) ); ?>">&larr; Vorige</a>
			<h2><?php echo esc_html( wp_date( 'F Y', $first->getTimestamp() + 43200 ) ); ?></h2>
			<a class="button" href="<?php echo esc_url( add_query_arg( 'maand', $first->modify( '+1 month' )->format( 'Y-m' ), $base ) ); ?>">Volgende &rarr;</a>
			<a class="button-link" href="<?php echo esc_url( $base ); ?>">Vandaag</a>
		</div>
		<?php $month_base = add_query_arg( 'maand', $first->format( 'Y-m' ), admin_url( 'edit.php?post_type=kr_booking&page=krv-planning' ) ); ?>
		<nav class="krv-plan-groups" aria-label="Huurgroep">
			<a href="<?php echo esc_url( $month_base ); ?>" class="<?php echo $group_filter ? '' : 'current'; ?>">Alle huurgroepen</a>
			<?php foreach ( $all_sections as $s ) : ?>
				<?php if ( $s['term'] ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'groep', $s['term']->term_id, $month_base ) ); ?>" class="<?php echo (int) $s['term']->term_id === $group_filter ? 'current' : ''; ?>"><?php echo esc_html( $s['term']->name ); ?></a>
				<?php endif; ?>
			<?php endforeach; ?>
		</nav>
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
				<?php foreach ( $sections as $section ) : ?>
					<tr class="grp"><th colspan="<?php echo (int) count( $dates ) + 1; ?>">
						<?php if ( $section['term'] ) : ?>
							<a href="<?php echo esc_url( add_query_arg( 'groep', $section['term']->term_id, $month_base ) ); ?>"><?php echo esc_html( $section['term']->name ); ?></a>
						<?php else : ?>
							Zonder huurgroep
						<?php endif; ?>
						<small><?php echo (int) count( $section['items'] ); ?> <?php echo 1 === count( $section['items'] ) ? 'artikel' : 'artikelen'; ?></small>
					</th></tr>
				<?php foreach ( $section['items'] as $prod ) : ?>
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
				<?php endforeach; ?>
				<?php if ( ! $sections ) : ?>
					<tr><td colspan="<?php echo (int) count( $dates ) + 1; ?>" style="padding:16px;text-align:left">Geen artikelen met een agenda in deze huurgroep.</td></tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
