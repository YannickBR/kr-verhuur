<?php
/**
 * Beheer: velden voor huurartikelen en huurgroepen.
 */

defined( 'ABSPATH' ) || exit;

function krv_icon_choices() {
	return array(
		'box'    => 'Doos (algemeen)',
		'camper' => 'Camper',
		'camera' => 'Camera / photobooth',
		'toilet' => 'Toilet',
		'castle' => 'Springkussen',
		'bumper' => 'Bumperauto',
		'chair'  => 'Stoel / meubilair',
		'flame'  => 'Vlam / verwarming',
		'tool'   => 'Gereedschap',
		'tent'   => 'Tent',
		'music'  => 'Muziek / geluid',
	);
}

/* ---------------------------------------------------------------------------
 * Huurartikel meta box
 * ------------------------------------------------------------------------- */

add_action(
	'add_meta_boxes_kr_product',
	function () {
		add_meta_box( 'krv_product', 'Huurgegevens', 'krv_product_metabox', 'kr_product', 'normal', 'high' );
	}
);

function krv_product_metabox( $post ) {
	$p = krv_get_product( $post );
	$raw_extra = get_post_meta( $post->ID, '_krv_price_extra_day', true );
	$vat_label = krv_setting( 'prices_incl_vat' ) ? 'incl. btw' : 'excl. btw';
	wp_nonce_field( 'krv_product', 'krv_product_nonce' );
	?>
	<style>
		.krv-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px 20px;margin:8px 0 16px}
		.krv-grid label{display:block;font-weight:600;margin-bottom:4px}
		.krv-grid input[type=number]{width:100%}
		.krv-extras label{display:block;margin:4px 0}
		.krv-help{color:#646970;font-size:12px;margin-top:2px}
	</style>
	<div class="krv-grid">
		<div><label for="krv_price_day">Prijs eerste dag (€ <?php echo esc_html( $vat_label ); ?>)</label>
			<input type="number" step="0.01" min="0" id="krv_price_day" name="krv[price_day]" value="<?php echo esc_attr( $p['price_day'] ); ?>"></div>
		<div><label for="krv_price_extra_day">Prijs per extra dag (€ <?php echo esc_html( $vat_label ); ?>)</label>
			<input type="number" step="0.01" min="0" id="krv_price_extra_day" name="krv[price_extra_day]" value="<?php echo esc_attr( $raw_extra ); ?>" placeholder="gelijk aan eerste dag"></div>
		<div><label for="krv_deposit">Borg (€)</label>
			<input type="number" step="0.01" min="0" id="krv_deposit" name="krv[deposit]" value="<?php echo esc_attr( $p['deposit'] ); ?>"></div>
		<div><label for="krv_max_days">Max. aantal huurdagen</label>
			<input type="number" min="1" id="krv_max_days" name="krv[max_days]" value="<?php echo esc_attr( $p['max_days'] ); ?>"></div>
		<div><label for="krv_stock">Voorraad (aantal stuks)</label>
			<input type="number" min="1" id="krv_stock" name="krv[stock]" value="<?php echo esc_attr( $p['stock'] ); ?>">
			<div class="krv-help">Een dag is vol als alle stuks verhuurd zijn.</div></div>
		<div><label>&nbsp;</label>
			<label style="font-weight:400"><input type="checkbox" name="krv[allow_quantity]" value="1" <?php checked( $p['allow_quantity'] ); ?>> Klant kan aantal kiezen</label></div>
	</div>

	<p><label for="krv_features"><strong>Kenmerken</strong> (één per regel)</label><br>
		<textarea id="krv_features" name="krv[features]" rows="4" style="width:100%"><?php echo esc_textarea( implode( "\n", $p['features'] ) ); ?></textarea></p>

	<p><strong>Beschikbare extra opties</strong> <span class="krv-help">(beheer de opties en prijzen via Boekingen → Instellingen)</span></p>
	<div class="krv-extras">
		<?php foreach ( krv_extras() as $key => $x ) : ?>
			<label><input type="checkbox" name="krv[extras][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $p['extras'], true ) ); ?>>
				<?php echo esc_html( $x['label'] ); ?> – <?php echo esc_html( krv_euro( $x['price'] ) . ( 'perDay' === $x['type'] ? ' per dag' : '' ) ); ?></label>
		<?php endforeach; ?>
	</div>
	<?php
}

add_action(
	'save_post_kr_product',
	function ( $post_id ) {
		if ( ! isset( $_POST['krv_product_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['krv_product_nonce'] ), 'krv_product' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$in = isset( $_POST['krv'] ) ? wp_unslash( (array) $_POST['krv'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		update_post_meta( $post_id, '_krv_price_day', (float) ( $in['price_day'] ?? 0 ) );
		update_post_meta( $post_id, '_krv_price_extra_day', '' === ( $in['price_extra_day'] ?? '' ) ? '' : (float) $in['price_extra_day'] );
		update_post_meta( $post_id, '_krv_deposit', (float) ( $in['deposit'] ?? 0 ) );
		update_post_meta( $post_id, '_krv_max_days', max( 1, (int) ( $in['max_days'] ?? 1 ) ) );
		update_post_meta( $post_id, '_krv_stock', max( 1, (int) ( $in['stock'] ?? 1 ) ) );
		update_post_meta( $post_id, '_krv_allow_quantity', empty( $in['allow_quantity'] ) ? 0 : 1 );
		update_post_meta( $post_id, '_krv_features', sanitize_textarea_field( $in['features'] ?? '' ) );
		update_post_meta( $post_id, '_krv_extras', array_values( array_intersect( array_map( 'sanitize_key', (array) ( $in['extras'] ?? array() ) ), array_keys( krv_extras() ) ) ) );
	}
);

/* Prijs-kolom in de lijst met huurartikelen. */
add_filter(
	'manage_kr_product_posts_columns',
	function ( $cols ) {
		$new = array();
		foreach ( $cols as $k => $v ) {
			$new[ $k ] = $v;
			if ( 'title' === $k ) {
				$new['krv_price'] = 'Prijs / dag';
				$new['krv_stock'] = 'Voorraad';
			}
		}
		return $new;
	}
);
add_action(
	'manage_kr_product_posts_custom_column',
	function ( $col, $id ) {
		$p = krv_get_product( $id );
		if ( 'krv_price' === $col ) {
			echo esc_html( krv_euro( $p['price_day'] ) );
		} elseif ( 'krv_stock' === $col ) {
			echo (int) $p['stock'];
		}
	},
	10,
	2
);

/* ---------------------------------------------------------------------------
 * Huurgroep velden
 * ------------------------------------------------------------------------- */

function krv_group_fields_html( $term = null ) {
	$m     = $term ? krv_group_meta( $term ) : array( 'icon' => 'box', 'tagline' => '', 'external_url' => '', 'order' => 0 );
	$table = (bool) $term;
	$rows  = array(
		'icon'         => array( 'Icoon', function () use ( $m ) {
			echo '<select name="krv_group[icon]" id="krv_group_icon">';
			foreach ( krv_icon_choices() as $k => $label ) {
				echo '<option value="' . esc_attr( $k ) . '"' . selected( $m['icon'], $k, false ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select>';
		} ),
		'tagline'      => array( 'Korte omschrijving', function () use ( $m ) {
			echo '<input type="text" class="regular-text" name="krv_group[tagline]" id="krv_group_tagline" value="' . esc_attr( $m['tagline'] ) . '">';
		} ),
		'external_url' => array( 'Externe link', function () use ( $m ) {
			echo '<input type="url" class="regular-text" name="krv_group[external_url]" id="krv_group_external_url" value="' . esc_attr( $m['external_url'] ) . '" placeholder="https://www.goboony.nl/...">';
			echo '<p class="description">Vul in om deze groep direct naar een andere site te laten linken (bijv. Goboony voor de camper).</p>';
		} ),
		'order'        => array( 'Volgorde', function () use ( $m ) {
			echo '<input type="number" name="krv_group[order]" id="krv_group_order" value="' . esc_attr( $m['order'] ) . '" style="width:80px">';
		} ),
	);
	wp_nonce_field( 'krv_group', 'krv_group_nonce' );
	foreach ( $rows as $key => $row ) {
		if ( $table ) {
			echo '<tr class="form-field"><th scope="row"><label for="krv_group_' . esc_attr( $key ) . '">' . esc_html( $row[0] ) . '</label></th><td>';
			$row[1]();
			echo '</td></tr>';
		} else {
			echo '<div class="form-field"><label for="krv_group_' . esc_attr( $key ) . '">' . esc_html( $row[0] ) . '</label>';
			$row[1]();
			echo '</div>';
		}
	}
}

add_action( 'kr_group_add_form_fields', function () { krv_group_fields_html(); } );
add_action( 'kr_group_edit_form_fields', function ( $term ) { krv_group_fields_html( $term ); } );

function krv_save_group( $term_id ) {
	if ( ! isset( $_POST['krv_group_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['krv_group_nonce'] ), 'krv_group' ) || ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	$in = isset( $_POST['krv_group'] ) ? wp_unslash( (array) $_POST['krv_group'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	update_term_meta( $term_id, '_krv_icon', array_key_exists( $in['icon'] ?? '', krv_icon_choices() ) ? $in['icon'] : 'box' );
	update_term_meta( $term_id, '_krv_tagline', sanitize_text_field( $in['tagline'] ?? '' ) );
	update_term_meta( $term_id, '_krv_external_url', esc_url_raw( $in['external_url'] ?? '' ) );
	update_term_meta( $term_id, '_krv_order', (int) ( $in['order'] ?? 0 ) );
}
add_action( 'created_kr_group', 'krv_save_group' );
add_action( 'edited_kr_group', 'krv_save_group' );
