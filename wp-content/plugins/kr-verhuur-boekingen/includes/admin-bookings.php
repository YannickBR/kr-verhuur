<?php
/**
 * Beheer: boekingenlijst, filters, snelle acties, CSV-export en het bewerkscherm.
 */

defined( 'ABSPATH' ) || exit;

/* ===========================================================================
 * Lijstweergave
 * ========================================================================= */

add_filter(
	'manage_kr_booking_posts_columns',
	function () {
		return array(
			'cb'           => '<input type="checkbox">',
			'krv_ref'      => 'Boeking',
			'krv_customer' => 'Klant',
			'krv_product'  => 'Artikel',
			'krv_period'   => 'Periode',
			'krv_total'    => 'Totaal',
			'krv_status'   => 'Status',
			'krv_created'  => 'Aangevraagd',
		);
	}
);

add_filter(
	'manage_edit-kr_booking_sortable_columns',
	function () {
		return array(
			'krv_ref'     => 'ID',
			'krv_period'  => 'krv_start',
			'krv_total'   => 'krv_total',
			'krv_created' => 'date',
		);
	}
);

function krv_status_badge( $status ) {
	$colors = array(
		'pending'   => array( '#fff4d6', '#8a5a00' ),
		'confirmed' => array( '#e6f3ed', '#2f6e55' ),
		'completed' => array( '#e8eef2', '#3b4e5a' ),
		'cancelled' => array( '#fbeceb', '#a3302b' ),
	);
	$c     = isset( $colors[ $status ] ) ? $colors[ $status ] : $colors['completed'];
	$label = isset( krv_statuses()[ $status ] ) ? krv_statuses()[ $status ] : $status;
	return '<span style="display:inline-block;padding:2px 10px;border-radius:999px;font-weight:600;font-size:12px;background:' . $c[0] . ';color:' . $c[1] . '">' . esc_html( $label ) . '</span>';
}

add_action(
	'manage_kr_booking_posts_custom_column',
	function ( $col, $id ) {
		$b = krv_get_booking( $id );
		switch ( $col ) {
			case 'krv_ref':
				echo '<strong><a class="row-title" href="' . esc_url( get_edit_post_link( $id ) ) . '">' . esc_html( krv_booking_ref( $id ) ) . '</a></strong>';
				if ( 'draft' === get_post_status( $id ) ) {
					echo ' — <span class="post-state">Concept</span>';
				}
				if ( $b['request_id'] ) {
					$n = count( krv_request_bookings( $b['request_id'] ) );
					if ( $n > 1 ) {
						echo '<br><small><a href="' . esc_url( admin_url( 'edit.php?post_type=kr_booking&s=' . rawurlencode( $b['request_id'] ) ) ) . '">Bestelling ' . esc_html( $b['request_id'] ) . ' (' . (int) $n . ' artikelen)</a></small>';
					}
				}
				break;
			case 'krv_customer':
				echo esc_html( $b['name'] );
				if ( $b['email'] ) {
					echo '<br><a href="mailto:' . esc_attr( $b['email'] ) . '">' . esc_html( $b['email'] ) . '</a>';
				}
				if ( $b['phone'] ) {
					echo '<br><a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $b['phone'] ) ) . '">' . esc_html( $b['phone'] ) . '</a>';
				}
				break;
			case 'krv_product':
				echo esc_html( ( $b['quantity'] > 1 ? $b['quantity'] . '× ' : '' ) . $b['product_name'] );
				if ( $b['extras'] ) {
					$all = krv_extras();
					$lbl = array();
					foreach ( (array) $b['extras'] as $x ) {
						$lbl[] = isset( $all[ $x ] ) ? $all[ $x ]['label'] : $x;
					}
					echo '<br><small>+ ' . esc_html( implode( ', ', $lbl ) ) . '</small>';
				}
				break;
			case 'krv_period':
				if ( $b['start'] ) {
					echo esc_html( krv_pretty_period( $b['start'], $b['end'] ) );
				}
				break;
			case 'krv_total':
				echo esc_html( krv_euro( $b['total'] ) );
				if ( $b['paid'] ) {
					echo '<br><small style="color:#2f6e55">✔ Betaald</small>';
				}
				break;
			case 'krv_status':
				echo krv_status_badge( $b['status'] ); // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'krv_created':
				echo esc_html( get_the_date( 'j M Y H:i', $id ) );
				echo '<br><small>' . esc_html( 'admin' === $b['source'] ? 'Via beheer' : 'Via website' ) . '</small>';
				break;
		}
	},
	10,
	2
);

/* Statusweergaven boven de lijst: Alle | Aanvraag (3) | Bevestigd (5) ... */
add_filter(
	'views_edit-kr_booking',
	function ( $views ) {
		global $wpdb;
		$counts = $wpdb->get_results(
			"SELECT pm.meta_value AS status, COUNT(*) AS n FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = '_krv_status' AND p.post_type = 'kr_booking' AND p.post_status = 'publish'
			 GROUP BY pm.meta_value",
			OBJECT_K
		);
		$current = isset( $_GET['krv_status'] ) ? sanitize_key( $_GET['krv_status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$base    = admin_url( 'edit.php?post_type=kr_booking' );
		$out     = array();
		$total   = 0;
		foreach ( $counts as $c ) {
			$total += (int) $c->n;
		}
		$out['all'] = '<a href="' . esc_url( $base ) . '"' . ( '' === $current && empty( $_GET['post_status'] ) ? ' class="current"' : '' ) . '>Alle <span class="count">(' . $total . ')</span></a>'; // phpcs:ignore WordPress.Security.NonceVerification
		foreach ( krv_statuses() as $key => $label ) {
			$n           = isset( $counts[ $key ] ) ? (int) $counts[ $key ]->n : 0;
			$out[ $key ] = '<a href="' . esc_url( add_query_arg( 'krv_status', $key, $base ) ) . '"' . ( $current === $key ? ' class="current"' : '' ) . '>' . esc_html( $label ) . ' <span class="count">(' . $n . ')</span></a>';
		}
		foreach ( array( 'draft', 'trash' ) as $keep ) {
			if ( isset( $views[ $keep ] ) ) {
				$out[ $keep ] = $views[ $keep ];
			}
		}
		return $out;
	}
);

/* Extra filters: artikel en periode. */
add_action(
	'restrict_manage_posts',
	function ( $post_type ) {
		if ( 'kr_booking' !== $post_type ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification
		$product = isset( $_GET['krv_product'] ) ? (int) $_GET['krv_product'] : 0;
		$when    = isset( $_GET['krv_when'] ) ? sanitize_key( $_GET['krv_when'] ) : '';
		$from    = isset( $_GET['krv_from'] ) ? sanitize_text_field( wp_unslash( $_GET['krv_from'] ) ) : '';
		$to      = isset( $_GET['krv_to'] ) ? sanitize_text_field( wp_unslash( $_GET['krv_to'] ) ) : '';
		if ( isset( $_GET['krv_status'] ) ) {
			echo '<input type="hidden" name="krv_status" value="' . esc_attr( sanitize_key( $_GET['krv_status'] ) ) . '">';
		}
		// phpcs:enable
		echo '<select name="krv_product"><option value="">Alle artikelen</option>';
		foreach ( get_posts( array( 'post_type' => 'kr_product', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC', 'post_status' => 'any' ) ) as $p ) {
			echo '<option value="' . (int) $p->ID . '"' . selected( $product, $p->ID, false ) . '>' . esc_html( $p->post_title ) . '</option>';
		}
		echo '</select>';
		echo '<select name="krv_when"><option value="">Alle periodes</option>';
		foreach ( array( 'upcoming' => 'Komend / lopend', 'today' => 'Vandaag in verhuur', 'past' => 'Afgelopen' ) as $k => $l ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $when, $k, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select>';
		echo '<input type="date" name="krv_from" value="' . esc_attr( $from ) . '" title="Huur vanaf" style="vertical-align:middle"> ';
		echo '<input type="date" name="krv_to" value="' . esc_attr( $to ) . '" title="Huur tot en met" style="vertical-align:middle">';
	}
);

/** Bouw de meta_query op basis van de filters in de URL. */
function krv_booking_filters_meta_query( $args ) {
	$mq    = array();
	$today = krv_today();
	if ( ! empty( $args['krv_status'] ) && array_key_exists( $args['krv_status'], krv_statuses() ) ) {
		$mq[] = array( 'key' => '_krv_status', 'value' => $args['krv_status'] );
	}
	if ( ! empty( $args['krv_product'] ) ) {
		$mq[] = array( 'key' => '_krv_product_id', 'value' => (int) $args['krv_product'] );
	}
	switch ( isset( $args['krv_when'] ) ? $args['krv_when'] : '' ) {
		case 'upcoming':
			$mq[] = array( 'key' => '_krv_end', 'value' => $today, 'compare' => '>=' );
			break;
		case 'today':
			$mq[] = array( 'key' => '_krv_start', 'value' => $today, 'compare' => '<=' );
			$mq[] = array( 'key' => '_krv_end', 'value' => $today, 'compare' => '>=' );
			break;
		case 'past':
			$mq[] = array( 'key' => '_krv_end', 'value' => $today, 'compare' => '<' );
			break;
	}
	if ( ! empty( $args['krv_from'] ) && krv_valid_date( $args['krv_from'] ) ) {
		$mq[] = array( 'key' => '_krv_end', 'value' => $args['krv_from'], 'compare' => '>=' );
	}
	if ( ! empty( $args['krv_to'] ) && krv_valid_date( $args['krv_to'] ) ) {
		$mq[] = array( 'key' => '_krv_start', 'value' => $args['krv_to'], 'compare' => '<=' );
	}
	return $mq;
}

add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || 'kr_booking' !== $q->get( 'post_type' ) ) {
			return;
		}
		$mq = krv_booking_filters_meta_query( wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( $mq ) {
			$q->set( 'meta_query', array_merge( array( 'relation' => 'AND' ), $mq ) );
		}
		$orderby = $q->get( 'orderby' );
		if ( 'krv_start' === $orderby ) {
			$q->set( 'meta_key', '_krv_start' );
			$q->set( 'orderby', 'meta_value' );
		} elseif ( 'krv_total' === $orderby ) {
			$q->set( 'meta_key', '_krv_total' );
			$q->set( 'orderby', 'meta_value_num' );
		} elseif ( ! $orderby ) {
			$q->set( 'orderby', 'date' );
			$q->set( 'order', 'DESC' );
		}
	}
);

/* Snelle acties per rij. */
add_filter(
	'post_row_actions',
	function ( $actions, $post ) {
		if ( 'kr_booking' !== $post->post_type ) {
			return $actions;
		}
		$status = get_post_meta( $post->ID, '_krv_status', true );
		$new    = array( 'edit' => '<a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">Bekijken / bewerken</a>' );
		foreach ( array( 'confirmed' => 'Bevestigen', 'completed' => 'Afronden', 'cancelled' => 'Annuleren' ) as $s => $label ) {
			if ( $status !== $s ) {
				$url       = wp_nonce_url( admin_url( 'admin-post.php?action=krv_set_status&status=' . $s . '&id=' . $post->ID ), 'krv_status_' . $post->ID );
				$new[ $s ] = '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
			}
		}
		if ( isset( $actions['trash'] ) ) {
			$new['trash'] = $actions['trash'];
		}
		return $new;
	},
	10,
	2
);

add_action(
	'admin_post_krv_set_status',
	function () {
		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		check_admin_referer( 'krv_status_' . $id );
		if ( ! current_user_can( 'edit_post', $id ) ) {
			wp_die( 'Geen toegang.' );
		}
		$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
		krv_set_status( $id, $status );
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=kr_booking' ) );
		exit;
	}
);

/* Bulkacties. */
add_filter(
	'bulk_actions-edit-kr_booking',
	function ( $actions ) {
		$new = array();
		foreach ( krv_statuses() as $k => $l ) {
			$new[ 'krv_' . $k ] = 'Status: ' . $l;
		}
		$new['krv_export'] = 'Exporteer selectie (CSV)';
		return array_merge( $new, $actions );
	}
);

add_filter(
	'handle_bulk_actions-edit-kr_booking',
	function ( $redirect, $action, $ids ) {
		if ( 'krv_export' === $action ) {
			krv_export_csv( $ids );
		}
		if ( 0 === strpos( $action, 'krv_' ) ) {
			$status = substr( $action, 4 );
			foreach ( $ids as $id ) {
				if ( current_user_can( 'edit_post', $id ) ) {
					krv_set_status( $id, $status );
				}
			}
			$redirect = add_query_arg( 'krv_updated', count( $ids ), $redirect );
		}
		return $redirect;
	},
	10,
	3
);

/* Exportknop boven de lijst (neemt de huidige filters mee). */
add_action(
	'manage_posts_extra_tablenav',
	function ( $which ) {
		global $typenow;
		if ( 'kr_booking' !== $typenow || 'top' !== $which ) {
			return;
		}
		$args = array_intersect_key( wp_unslash( $_GET ), array_flip( array( 'krv_status', 'krv_product', 'krv_when', 'krv_from', 'krv_to', 's' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$url  = wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'krv_export' ), $args ), admin_url( 'admin-post.php' ) ), 'krv_export' );
		echo '<div class="alignleft actions"><a class="button" href="' . esc_url( $url ) . '">Exporteer naar CSV</a></div>';
	}
);

add_action(
	'admin_post_krv_export',
	function () {
		check_admin_referer( 'krv_export' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( 'Geen toegang.' );
		}
		$in   = wp_unslash( $_GET );
		$args = array(
			'post_type'   => 'kr_booking',
			'post_status' => 'publish',
			'numberposts' => -1,
			'fields'      => 'ids',
			'orderby'     => 'date',
			'order'       => 'DESC',
		);
		$mq = krv_booking_filters_meta_query( $in );
		if ( $mq ) {
			$args['meta_query'] = array_merge( array( 'relation' => 'AND' ), $mq );
		}
		if ( ! empty( $in['s'] ) ) {
			$args['s'] = sanitize_text_field( $in['s'] );
		}
		krv_export_csv( get_posts( $args ) );
	}
);

function krv_export_csv( $ids ) {
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=boekingen-' . wp_date( 'Y-m-d' ) . '.csv' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" ); // BOM zodat Excel de tekens goed toont.
	$extras = krv_extras();
	fputcsv( $out, array( 'Boeking', 'Bestelling', 'Status', 'Aangevraagd', 'Artikel', 'Aantal', 'Van', 'Tot en met', 'Dagen', 'Extra opties', 'Totaal', 'Borg', 'Betaald', 'Naam', 'E-mail', 'Telefoon', 'Adres', 'Opmerkingen', 'Interne notities', 'Bron' ), ';' );
	foreach ( $ids as $id ) {
		$b = krv_get_booking( $id );
		if ( ! $b ) {
			continue;
		}
		$x = array();
		foreach ( (array) $b['extras'] as $k ) {
			$x[] = isset( $extras[ $k ] ) ? $extras[ $k ]['label'] : $k;
		}
		fputcsv(
			$out,
			array(
				krv_booking_ref( $id ), $b['request_id'], krv_statuses()[ $b['status'] ] ?? $b['status'], get_the_date( 'Y-m-d H:i', $id ),
				$b['product_name'], $b['quantity'], $b['start'], $b['end'], $b['days'], implode( ', ', $x ),
				number_format( (float) $b['total'], 2, ',', '' ), number_format( (float) $b['deposit'], 2, ',', '' ), $b['paid'] ? 'ja' : 'nee',
				$b['name'], $b['email'], $b['phone'], $b['address'], $b['notes'], $b['admin_notes'], $b['source'],
			),
			';'
		);
	}
	fclose( $out );
	exit;
}

/* Teller met openstaande aanvragen in het menu. */
add_action(
	'admin_menu',
	function () {
		global $menu;
		$n = count(
			get_posts(
				array(
					'post_type'   => 'kr_booking',
					'numberposts' => -1,
					'fields'      => 'ids',
					'meta_key'    => '_krv_status',
					'meta_value'  => 'pending',
				)
			)
		);
		if ( ! $n ) {
			return;
		}
		foreach ( $menu as $i => $item ) {
			if ( 'edit.php?post_type=kr_booking' === $item[2] ) {
				$menu[ $i ][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . $n . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			}
		}
	},
	99
);

/* Meldingen. */
add_action(
	'admin_notices',
	function () {
		$screen = get_current_screen();
		if ( ! $screen || 'kr_booking' !== $screen->post_type ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! empty( $_GET['krv_updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . (int) $_GET['krv_updated'] . ' boeking(en) bijgewerkt.</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification
		}
		$err = get_transient( 'krv_admin_error_' . get_current_user_id() );
		if ( $err ) {
			delete_transient( 'krv_admin_error_' . get_current_user_id() );
			echo '<div class="notice notice-error"><p><strong>Boeking niet opgeslagen:</strong> ' . esc_html( $err ) . '</p></div>';
		}
	}
);

/* ===========================================================================
 * Bewerkscherm
 * ========================================================================= */

add_action(
	'add_meta_boxes_kr_booking',
	function () {
		add_meta_box( 'krv_booking_details', 'Boekingsgegevens', 'krv_booking_details_box', 'kr_booking', 'normal', 'high' );
		add_meta_box( 'krv_booking_log', 'Historie', 'krv_booking_log_box', 'kr_booking', 'normal', 'low' );
		$request = get_post_meta( get_the_ID(), '_krv_request_id', true );
		if ( $request && count( krv_request_bookings( $request ) ) > 1 ) {
			add_meta_box( 'krv_booking_order', 'Bestelling ' . $request, 'krv_booking_order_box', 'kr_booking', 'side', 'default' );
		}
		add_meta_box( 'krv_booking_status', 'Status & prijs', 'krv_booking_status_box', 'kr_booking', 'side', 'high' );
	}
);

/* Titel bovenaan het bewerkscherm. */
add_action(
	'edit_form_after_title',
	function ( $post ) {
		if ( 'kr_booking' !== $post->post_type ) {
			return;
		}
		$is_new = 'auto-draft' === $post->post_status;
		echo '<h1 style="margin:10px 0 0;padding:0">' . esc_html( $is_new ? 'Nieuwe boeking' : krv_booking_ref( $post->ID ) ) . '</h1>';
	}
);

function krv_booking_details_box( $post ) {
	$b = krv_get_booking( $post->ID );
	wp_nonce_field( 'krv_booking', 'krv_booking_nonce' );
	$products = get_posts( array( 'post_type' => 'kr_product', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC', 'post_status' => 'any' ) );
	?>
	<style>
		.krv-form{display:grid;grid-template-columns:1fr 1fr;gap:12px 20px}
		.krv-form .full{grid-column:1/-1}
		.krv-form label{display:block;font-weight:600;margin-bottom:4px}
		.krv-form input[type=text],.krv-form input[type=email],.krv-form input[type=date],.krv-form input[type=number],.krv-form select,.krv-form textarea{width:100%}
		.krv-form h3{grid-column:1/-1;margin:14px 0 0;padding-top:12px;border-top:1px solid #dcdcde}
		.krv-extras-list{columns:2}
		.krv-extras-list label{font-weight:400;display:block}
		@media(max-width:782px){.krv-form{grid-template-columns:1fr}.krv-extras-list{columns:1}}
	</style>
	<div class="krv-form">
		<h3 style="border:0;margin-top:0;padding-top:0">Huur</h3>
		<div class="full"><label for="krv_product_id">Artikel</label>
			<select id="krv_product_id" name="krvb[product_id]" required>
				<option value="">— Kies een artikel —</option>
				<?php foreach ( $products as $p ) : ?>
					<option value="<?php echo (int) $p->ID; ?>" <?php selected( (int) $b['product_id'], $p->ID ); ?>><?php echo esc_html( $p->post_title ); ?></option>
				<?php endforeach; ?>
			</select></div>
		<div><label for="krv_start">Eerste huurdag</label><input type="date" id="krv_start" name="krvb[start]" value="<?php echo esc_attr( $b['start'] ); ?>" required></div>
		<div><label for="krv_end">Laatste huurdag</label><input type="date" id="krv_end" name="krvb[end]" value="<?php echo esc_attr( $b['end'] ); ?>"></div>
		<div><label for="krv_quantity">Aantal</label><input type="number" min="1" id="krv_quantity" name="krvb[quantity]" value="<?php echo esc_attr( $b['quantity'] ); ?>"></div>
		<div><label for="krv_discount">Korting (€ incl. btw)</label><input type="number" step="0.01" min="0" id="krv_discount" name="krvb[discount]" value="<?php echo esc_attr( get_post_meta( $post->ID, '_krv_discount', true ) ); ?>"></div>
		<div class="full"><label>Extra opties</label>
			<div class="krv-extras-list">
				<?php foreach ( krv_extras() as $k => $x ) : ?>
					<label><input type="checkbox" name="krvb[extras][]" value="<?php echo esc_attr( $k ); ?>" <?php checked( in_array( $k, (array) $b['extras'], true ) ); ?>> <?php echo esc_html( $x['label'] . ' (' . krv_euro( $x['price'] ) . ( 'perDay' === $x['type'] ? ' p/d' : '' ) . ')' ); ?></label>
				<?php endforeach; ?>
			</div></div>

		<h3>Klant</h3>
		<div><label for="krv_name">Naam</label><input type="text" id="krv_name" name="krvb[name]" value="<?php echo esc_attr( $b['name'] ); ?>" required></div>
		<div><label for="krv_email">E-mail</label><input type="email" id="krv_email" name="krvb[email]" value="<?php echo esc_attr( $b['email'] ); ?>"></div>
		<div><label for="krv_phone">Telefoon</label><input type="text" id="krv_phone" name="krvb[phone]" value="<?php echo esc_attr( $b['phone'] ); ?>"></div>
		<div><label for="krv_address">Afleveradres</label><input type="text" id="krv_address" name="krvb[address]" value="<?php echo esc_attr( $b['address'] ); ?>"></div>
		<div class="full"><label for="krv_notes">Opmerkingen van de klant</label><textarea id="krv_notes" name="krvb[notes]" rows="3"><?php echo esc_textarea( $b['notes'] ); ?></textarea></div>
		<div class="full"><label for="krv_admin_notes">Interne notities <span style="font-weight:400;color:#646970">(niet zichtbaar voor de klant)</span></label><textarea id="krv_admin_notes" name="krvb[admin_notes]" rows="3"><?php echo esc_textarea( $b['admin_notes'] ); ?></textarea></div>
	</div>
	<?php
}

function krv_booking_status_box( $post ) {
	$b = krv_get_booking( $post->ID );
	?>
	<p><label for="krv_status"><strong>Status</strong></label><br>
		<select id="krv_status" name="krvb[status]" style="width:100%">
			<?php foreach ( krv_statuses() as $k => $l ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $b['status'], $k ); ?>><?php echo esc_html( $l ); ?></option>
			<?php endforeach; ?>
		</select></p>
	<p><label><input type="checkbox" name="krvb[notify]" value="1"> E-mail klant bij bevestigen/annuleren</label></p>
	<p><label><input type="checkbox" name="krvb[paid]" value="1" <?php checked( $b['paid'] ); ?>> Betaald</label></p>
	<p><label><input type="checkbox" name="krvb[force]" value="1"> Beschikbaarheid negeren <span style="color:#646970">(dubbel boeken toestaan)</span></label></p>
	<?php if ( $b['lines'] ) : ?>
		<table style="width:100%;border-top:1px solid #dcdcde;margin-top:10px;padding-top:6px">
			<tr><td colspan="2" style="color:#646970;font-size:12px">Bedragen incl. btw</td></tr>
			<?php foreach ( (array) $b['lines'] as $l ) : ?>
				<tr><td><?php echo esc_html( $l['label'] ); ?></td><td style="text-align:right"><?php echo esc_html( krv_euro( $l['amount'] ) ); ?></td></tr>
			<?php endforeach; ?>
			<?php $vat = krv_vat_breakdown( $b['total'] ); ?>
			<tr><td><strong>Totaal incl. btw</strong></td><td style="text-align:right"><strong><?php echo esc_html( krv_euro( $b['total'] ) ); ?></strong></td></tr>
			<tr><td style="color:#646970">Waarvan btw (<?php echo esc_html( $vat['rate'] ); ?>%)</td><td style="text-align:right;color:#646970"><?php echo esc_html( krv_euro( $vat['vat'] ) ); ?></td></tr>
			<?php if ( $b['deposit'] > 0 ) : ?>
				<tr><td>Borg</td><td style="text-align:right"><?php echo esc_html( krv_euro( $b['deposit'] ) ); ?></td></tr>
			<?php endif; ?>
		</table>
		<p class="description">De prijs wordt bij opslaan opnieuw berekend.</p>
	<?php endif; ?>
	<?php
}

/** Andere artikelen uit dezelfde bestelling. */
function krv_booking_order_box( $post ) {
	$request = get_post_meta( $post->ID, '_krv_request_id', true );
	$total   = 0;
	echo '<ul style="margin:0">';
	foreach ( krv_request_bookings( $request ) as $id ) {
		$b      = krv_get_booking( $id );
		$total += (float) $b['total'];
		$label  = ( $b['quantity'] > 1 ? $b['quantity'] . '× ' : '' ) . $b['product_name'];
		echo '<li style="padding:6px 0;border-bottom:1px solid #f0f0f1">';
		echo (int) $id === (int) $post->ID ? '<strong>' . esc_html( $label ) . '</strong> (deze)' : '<a href="' . esc_url( get_edit_post_link( $id ) ) . '">' . esc_html( $label ) . '</a>';
		echo '<br><small>' . esc_html( krv_pretty_period( $b['start'], $b['end'] ) ) . ' · ' . esc_html( krv_euro( $b['total'] ) ) . '</small> ' . krv_status_badge( $b['status'] ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</li>';
	}
	echo '</ul><p><strong>Totaal bestelling: ' . esc_html( krv_euro( $total ) ) . '</strong></p>';
	echo '<p><a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=kr_booking&s=' . rawurlencode( $request ) ) ) . '">Toon hele bestelling</a></p>';
}

function krv_booking_log_box( $post ) {
	$log = get_post_meta( $post->ID, '_krv_log', true );
	if ( ! is_array( $log ) || ! $log ) {
		echo '<p>Nog geen historie.</p>';
		return;
	}
	echo '<ul style="margin:0">';
	foreach ( array_reverse( $log ) as $row ) {
		echo '<li style="padding:6px 0;border-bottom:1px solid #f0f0f1"><strong>' . esc_html( mysql2date( 'j M Y H:i', $row['time'] ) ) . '</strong> – ' . esc_html( $row['user'] ) . ': ' . esc_html( $row['msg'] ) . '</li>';
	}
	echo '</ul>';
}

/** Opslaan vanuit het bewerkscherm. */
function krv_admin_save_booking( $post_id, $post ) {
	if ( ! isset( $_POST['krv_booking_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['krv_booking_nonce'] ), 'krv_booking' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$in       = isset( $_POST['krvb'] ) ? wp_unslash( (array) $_POST['krvb'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$is_new   = ! get_post_meta( $post_id, '_krv_product_id', true );
	$status   = isset( $in['status'] ) && array_key_exists( $in['status'], krv_statuses() ) ? $in['status'] : 'pending';
	$discount = isset( $in['discount'] ) ? max( 0, (float) $in['discount'] ) : 0;

	// Geannuleerde/afgeronde boekingen hoeven niet op beschikbaarheid gecontroleerd te worden.
	$force = ! empty( $in['force'] ) || ! in_array( $status, krv_blocking_statuses(), true );
	$data  = krv_validate_booking(
		$in,
		array(
			'context'    => 'admin',
			'exclude_id' => $post_id,
			'force'      => $force,
		)
	);

	if ( is_wp_error( $data ) ) {
		set_transient( 'krv_admin_error_' . get_current_user_id(), $data->get_error_message(), 60 );
		if ( $is_new ) {
			remove_action( 'save_post_kr_booking', 'krv_admin_save_booking', 10 );
			wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft', 'post_title' => 'Concept boeking' ) );
			add_action( 'save_post_kr_booking', 'krv_admin_save_booking', 10, 2 );
		}
		return;
	}

	if ( $discount > 0 ) {
		$data['lines'][] = array( 'key' => 'discount', 'label' => 'Korting', 'amount' => -$discount );
		$data['total']   = round( max( 0, $data['total'] - $discount ), 2 );
	}
	update_post_meta( $post_id, '_krv_discount', $discount ? $discount : '' );

	$data['admin_notes'] = sanitize_textarea_field( isset( $in['admin_notes'] ) ? $in['admin_notes'] : '' );
	$data['paid']        = empty( $in['paid'] ) ? 0 : 1;

	if ( $is_new ) {
		$data['source']     = 'admin';
		$data['status']     = $status;
		$data['request_id'] = krv_booking_ref( $post_id );
		krv_save_booking_data( $post_id, $data );
		krv_log( $post_id, 'Boeking aangemaakt via beheer (status: ' . krv_statuses()[ $status ] . ').' );
	} else {
		$before = krv_get_booking( $post_id );
		krv_save_booking_data( $post_id, $data );
		$changed = array();
		foreach ( array( 'product_id' => 'artikel', 'start' => 'begindatum', 'end' => 'einddatum', 'quantity' => 'aantal', 'extras' => 'extra opties', 'total' => 'prijs', 'paid' => 'betaald' ) as $k => $label ) {
			if ( (string) wp_json_encode( $before[ $k ] ) !== (string) wp_json_encode( $data[ $k ] ) && ! ( is_numeric( $before[ $k ] ) && (float) $before[ $k ] === (float) $data[ $k ] ) ) {
				$changed[] = $label;
			}
		}
		if ( $changed ) {
			krv_log( $post_id, 'Gewijzigd: ' . implode( ', ', $changed ) . '.' );
		}
		krv_set_status( $post_id, $status );
	}

	if ( ! empty( $in['notify'] ) ) {
		krv_send_status_mail( $post_id, $status );
	}
}
add_action( 'save_post_kr_booking', 'krv_admin_save_booking', 10, 2 );

/* Overbodige elementen op het bewerkscherm verbergen. */
add_action(
	'admin_head',
	function () {
		$screen = get_current_screen();
		if ( $screen && 'kr_booking' === $screen->post_type && 'post' === $screen->base ) {
			echo '<style>#misc-publishing-actions,#minor-publishing-actions{display:none}</style>';
		}
	}
);

/* ===========================================================================
 * Dashboard-widget
 * ========================================================================= */

add_action(
	'wp_dashboard_setup',
	function () {
		wp_add_dashboard_widget( 'krv_dashboard', 'KR Verhuur – boekingen', 'krv_dashboard_widget' );
	}
);

function krv_dashboard_widget() {
	$today   = krv_today();
	$pending = get_posts( array( 'post_type' => 'kr_booking', 'numberposts' => 10, 'meta_key' => '_krv_status', 'meta_value' => 'pending' ) );
	$soon    = get_posts(
		array(
			'post_type'   => 'kr_booking',
			'numberposts' => 10,
			'meta_key'    => '_krv_start',
			'orderby'     => 'meta_value',
			'order'       => 'ASC',
			'meta_query'  => array(
				array( 'key' => '_krv_status', 'value' => 'confirmed' ),
				array( 'key' => '_krv_start', 'value' => array( $today, wp_date( 'Y-m-d', strtotime( '+7 days' ) ) ), 'compare' => 'BETWEEN' ),
			),
		)
	);
	$row = function ( $p ) {
		$b = krv_get_booking( $p->ID );
		return '<li><a href="' . esc_url( get_edit_post_link( $p->ID ) ) . '">' . esc_html( krv_booking_ref( $p->ID ) ) . '</a> – ' .
			esc_html( $b['name'] . ', ' . $b['product_name'] . ' – ' . krv_pretty_period( $b['start'], $b['end'] ) ) . '</li>';
	};
	echo '<h3>Openstaande aanvragen (' . count( $pending ) . ')</h3>';
	echo $pending ? '<ul>' . implode( '', array_map( $row, $pending ) ) . '</ul>' : '<p>Geen openstaande aanvragen. 🎉</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<h3>Komende 7 dagen</h3>';
	echo $soon ? '<ul>' . implode( '', array_map( $row, $soon ) ) . '</ul>' : '<p>Geen bevestigde verhuur de komende week.</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'edit.php?post_type=kr_booking' ) ) . '">Alle boekingen</a> <a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=kr_booking&page=krv-planning' ) ) . '">Planning</a></p>';
}

/* Eigen meldingen na opslaan. */
add_filter(
	'post_updated_messages',
	function ( $messages ) {
		$m                        = array_fill( 1, 10, 'Boeking opgeslagen.' );
		$m[0]                     = '';
		$messages['kr_booking']   = $m;
		$p                        = array_fill( 1, 10, 'Huurartikel opgeslagen.' );
		$p[0]                     = '';
		$p[6]                     = 'Huurartikel gepubliceerd. <a href="' . esc_url( get_permalink() ) . '">Bekijk artikel</a>';
		$messages['kr_product']   = $p;
		return $messages;
	}
);

/* De standaard maandfilter (aanmaakdatum) is overbodig naast de periodefilters. */
add_filter(
	'disable_months_dropdown',
	function ( $disable, $post_type ) {
		return 'kr_booking' === $post_type ? true : $disable;
	},
	10,
	2
);
