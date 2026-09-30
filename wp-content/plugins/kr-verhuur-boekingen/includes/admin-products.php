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
	<p><label for="krv_mode"><strong>Soort artikel</strong></label><br>
		<select id="krv_mode" name="krv[mode]">
			<option value="rent" <?php selected( $p['mode'], 'rent' ); ?>>Verhuur – prijs per dag, met kalender</option>
			<option value="sale" <?php selected( $p['mode'], 'sale' ); ?>>Verkoop – prijs per stuk, zonder kalender (bijv. pellets)</option>
		</select></p>

	<div class="krv-grid">
		<div><label for="krv_price_day"><span class="krv-rent">Prijs eerste dag</span><span class="krv-sale">Prijs per eenheid</span> (€ <?php echo esc_html( $vat_label ); ?>)</label>
			<input type="number" step="0.01" min="0" id="krv_price_day" name="krv[price_day]" value="<?php echo esc_attr( $p['price_day'] ); ?>"></div>
		<div class="krv-sale"><label for="krv_unit">Eenheid</label>
			<input type="text" id="krv_unit" name="krv[unit]" value="<?php echo esc_attr( $p['unit'] ); ?>" placeholder="stuk" style="width:100%">
			<div class="krv-help">Bijv. stuk, zak, pallet. Getoond als "€ 7,50 / zak".</div></div>
		<div class="krv-rent"><label for="krv_price_extra_day">Prijs per extra dag (€ <?php echo esc_html( $vat_label ); ?>)</label>
			<input type="number" step="0.01" min="0" id="krv_price_extra_day" name="krv[price_extra_day]" value="<?php echo esc_attr( $raw_extra ); ?>" placeholder="gelijk aan eerste dag"></div>
		<div class="krv-rent"><label for="krv_deposit">Borg (€)</label>
			<input type="number" step="0.01" min="0" id="krv_deposit" name="krv[deposit]" value="<?php echo esc_attr( $p['deposit'] ); ?>"></div>
		<div class="krv-rent"><label for="krv_max_days">Max. aantal huurdagen</label>
			<input type="number" min="1" id="krv_max_days" name="krv[max_days]" value="<?php echo esc_attr( $p['max_days'] ); ?>"></div>
		<div><label for="krv_stock"><span class="krv-rent">Voorraad (aantal stuks)</span><span class="krv-sale">Maximaal per bestelling</span></label>
			<input type="number" min="1" id="krv_stock" name="krv[stock]" value="<?php echo esc_attr( $p['stock'] ); ?>">
			<div class="krv-help krv-rent">Een dag is vol als alle stuks verhuurd zijn.</div></div>
		<div class="krv-rent"><label>&nbsp;</label>
			<label style="font-weight:400"><input type="checkbox" name="krv[allow_quantity]" value="1" <?php checked( $p['allow_quantity'] ); ?>> Klant kan aantal kiezen</label></div>
	</div>

	<p><strong>Boeken via een andere website</strong> <span class="krv-help">(optioneel, bijv. de camper via Goboony)</span></p>
	<div class="krv-grid">
		<div style="grid-column:span 2"><label for="krv_external_url">Link</label>
			<input type="url" id="krv_external_url" name="krv[external_url]" value="<?php echo esc_attr( $p['external_url'] ); ?>" placeholder="https://www.goboony.nl/..." style="width:100%">
			<div class="krv-help">Ingevuld? Dan toont de pagina een knop naar deze link in plaats van de kalender.</div></div>
		<div><label for="krv_external_label">Knoptekst</label>
			<input type="text" id="krv_external_label" name="krv[external_label]" value="<?php echo esc_attr( get_post_meta( $post->ID, '_krv_external_label', true ) ); ?>" placeholder="<?php echo esc_attr( krv_external_label_default( $p['external_url'] ?: 'https://www.goboony.nl' ) ); ?>" style="width:100%"></div>
	</div>

	<p><strong>Foto's</strong> <span class="krv-help">De uitgelichte afbeelding (rechts) is de hoofdfoto. Voeg hier extra foto's toe voor de fotoslider, bijv. binnenkant en maatvoering. Sleep om de volgorde te wijzigen.</span></p>
	<ul class="krv-gallery" id="krv-gallery">
		<?php foreach ( $p['gallery'] as $img_id ) : ?>
			<?php $src = wp_get_attachment_image_url( $img_id, 'thumbnail' ); ?>
			<?php if ( $src ) : ?>
				<li data-id="<?php echo (int) $img_id; ?>"><img src="<?php echo esc_url( $src ); ?>" alt=""><button type="button" class="krv-gallery-remove" aria-label="Verwijderen">×</button></li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>
	<input type="hidden" id="krv-gallery-ids" name="krv[gallery]" value="<?php echo esc_attr( implode( ',', $p['gallery'] ) ); ?>">
	<p><button type="button" class="button" id="krv-gallery-add">Foto's toevoegen</button></p>
	<p><label for="krv_image_fit"><strong>Weergave van foto's</strong></label><br>
		<select id="krv_image_fit" name="krv[image_fit]">
			<option value="contain" <?php selected( $p['image_fit'], 'contain' ); ?>>Hele foto tonen (aanbevolen, niets wordt afgesneden)</option>
			<option value="cover" <?php selected( $p['image_fit'], 'cover' ); ?>>Kader vullen (foto wordt bijgesneden)</option>
		</select></p>

	<style>
		.krv-sale{display:none}
		.krv-is-sale .krv-sale{display:block}.krv-is-sale span.krv-sale{display:inline}
		.krv-is-sale .krv-rent{display:none}
		.krv-gallery{display:flex;flex-wrap:wrap;gap:8px;margin:8px 0}
		.krv-gallery li{position:relative;width:90px;height:90px;margin:0;cursor:move;border:1px solid #dcdcde;border-radius:4px;overflow:hidden;background:#f6f7f7}
		.krv-gallery img{width:100%;height:100%;object-fit:cover;display:block}
		.krv-gallery-remove{position:absolute;top:2px;right:2px;width:22px;height:22px;border:0;border-radius:50%;background:#d63638;color:#fff;cursor:pointer;line-height:20px;padding:0}
	</style>
	<script>
	jQuery(function ($) {
		var box = $('#krv_product'), list = $('#krv-gallery'), input = $('#krv-gallery-ids');
		function syncMode() { box.toggleClass('krv-is-sale', $('#krv_mode').val() === 'sale'); }
		$('#krv_mode').on('change', syncMode); syncMode();
		function sync() { input.val(list.children().map(function () { return $(this).data('id'); }).get().join(',')); }
		list.sortable({ update: sync });
		list.on('click', '.krv-gallery-remove', function () { $(this).closest('li').remove(); sync(); });
		var frame;
		$('#krv-gallery-add').on('click', function (e) {
			e.preventDefault();
			if (!frame) {
				frame = wp.media({ title: "Foto's toevoegen", button: { text: 'Toevoegen' }, library: { type: 'image' }, multiple: 'add' });
				frame.on('select', function () {
					frame.state().get('selection').each(function (a) {
						var id = a.id, s = a.attributes.sizes || {}, url = (s.thumbnail || s.medium || a.attributes).url;
						if (list.find('[data-id="' + id + '"]').length) return;
						list.append('<li data-id="' + id + '"><img src="' + url + '" alt=""><button type="button" class="krv-gallery-remove" aria-label="Verwijderen">×</button></li>');
					});
					sync();
				});
			}
			frame.open();
		});
	});
	</script>

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
		update_post_meta( $post_id, '_krv_mode', 'sale' === ( $in['mode'] ?? '' ) ? 'sale' : 'rent' );
		update_post_meta( $post_id, '_krv_unit', sanitize_text_field( $in['unit'] ?? '' ) );
		update_post_meta( $post_id, '_krv_external_url', esc_url_raw( $in['external_url'] ?? '' ) );
		update_post_meta( $post_id, '_krv_external_label', sanitize_text_field( $in['external_label'] ?? '' ) );
		update_post_meta( $post_id, '_krv_image_fit', 'cover' === ( $in['image_fit'] ?? '' ) ? 'cover' : 'contain' );
		$gallery = array_values( array_filter( array_map( 'intval', explode( ',', (string) ( $in['gallery'] ?? '' ) ) ) ) );
		update_post_meta( $post_id, '_krv_gallery', $gallery );
	}
);

/*
 * Huurartikelen in de klassieke editor bewerken: dan staan tekst, huurgegevens, foto's en
 * de uitgelichte afbeelding overzichtelijk op één scherm (in de blokeditor zitten de
 * huurgegevens weggestopt in een ingeklapte lade onderaan).
 */
add_filter(
	'use_block_editor_for_post_type',
	function ( $use, $post_type ) {
		return 'kr_product' === $post_type ? false : $use;
	},
	10,
	2
);

/* Mediabibliotheek en sorteren beschikbaar maken op het bewerkscherm van huurartikelen. */
add_action(
	'admin_enqueue_scripts',
	function () {
		$screen = get_current_screen();
		if ( $screen && 'kr_product' === $screen->post_type && 'post' === $screen->base ) {
			wp_enqueue_media();
			wp_enqueue_script( 'jquery-ui-sortable' );
		}
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
				$new['krv_price'] = 'Prijs';
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
			if ( $p['external_url'] ) {
				echo '<em>' . esc_html( $p['external_label'] ) . '</em>';
			} else {
				echo esc_html( krv_euro( $p['price_day'] ) . ' / ' . krv_price_unit( $p ) );
			}
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
