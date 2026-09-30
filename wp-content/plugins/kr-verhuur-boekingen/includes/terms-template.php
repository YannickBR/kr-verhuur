<?php
/**
 * Concepttekst voor de algemene (verhuur)voorwaarden.
 *
 * Wordt eenmalig als CONCEPT-pagina aangemaakt. Het is een startpunt, geen juridisch advies:
 * laat de tekst controleren en vul de gegevens tussen [haken] aan voordat je hem publiceert.
 */

defined( 'ABSPATH' ) || exit;

function krv_terms_template() {
	$company = krv_setting( 'company_name' ) ?: 'KRverhuur';
	$address = trim( krv_setting( 'address_street' ) . ', ' . krv_setting( 'address_city' ), ', ' );
	$email   = krv_setting( 'email' );
	$phone   = krv_setting( 'phone' );

	$h = function ( $t ) {
		return "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html( $t ) . "</h2>\n<!-- /wp:heading -->\n\n";
	};
	$p = function ( $t ) {
		return "<!-- wp:paragraph -->\n<p>" . $t . "</p>\n<!-- /wp:paragraph -->\n\n";
	};
	$ol = function ( $items ) {
		$li = '';
		foreach ( $items as $i ) {
			$li .= "<!-- wp:list-item -->\n<li>" . $i . "</li>\n<!-- /wp:list-item -->\n";
		}
		return "<!-- wp:list {\"ordered\":true} -->\n<ol class=\"wp-block-list\">" . $li . "</ol>\n<!-- /wp:list -->\n\n";
	};

	$c = esc_html( $company );

	return
		$p( '<strong>Concept – controleer en vul deze tekst aan (met name de gegevens tussen [haken]) voordat je de pagina publiceert. Deze tekst is een startpunt en geen juridisch advies.</strong>' ) .

		$h( 'Artikel 1 – Gegevens verhuurder' ) .
		$p( $c . ', ' . esc_html( $address ) . '. Telefoon: ' . esc_html( $phone ) . ', e-mail: ' . esc_html( $email ) . '. KvK-nummer: [KvK-nummer]. Btw-nummer: [btw-nummer].' ) .

		$h( 'Artikel 2 – Toepasselijkheid' ) .
		$ol(
			array(
				'Deze voorwaarden gelden voor alle reserveringen, huurovereenkomsten en verkopen van ' . $c . ' (hierna: verhuurder) met de klant (hierna: huurder).',
				'Afwijkingen gelden alleen als verhuurder die schriftelijk (ook per e-mail) heeft bevestigd.',
				'Voor het huren van de camper via Goboony gelden de voorwaarden van Goboony.',
			)
		) .

		$h( 'Artikel 3 – Reservering en bevestiging' ) .
		$ol(
			array(
				'Een reservering via de website is een aanvraag. De huurovereenkomst komt tot stand zodra verhuurder de reservering per e-mail heeft bevestigd.',
				'Verhuurder mag een reservering zonder opgave van redenen weigeren, bijvoorbeeld als het artikel niet beschikbaar is.',
				'De huurder controleert de bevestiging en meldt eventuele fouten zo snel mogelijk.',
			)
		) .

		$h( 'Artikel 4 – Prijzen en betaling' ) .
		$ol(
			array(
				'Alle prijzen op de website zijn in euro\'s. Bij elke prijs staat of deze inclusief of exclusief btw is.',
				'De huurprijs geldt per dag, tenzij anders vermeld. Extra opties zoals halen en brengen, opbouwen of schoonmaak worden apart berekend.',
				'Betaling vindt plaats [bij het ophalen / vooraf via factuur binnen [x] dagen / anders]. ',
				'Bij het niet tijdig betalen is verhuurder gerechtigd het gehuurde niet mee te geven.',
			)
		) .

		$h( 'Artikel 5 – Borg' ) .
		$ol(
			array(
				'Voor sommige artikelen vraagt verhuurder een borg. Het bedrag staat bij het artikel vermeld.',
				'De borg wordt voldaan [bij het ophalen / bij aflevering] en terugbetaald na inlevering, mits het gehuurde compleet, onbeschadigd en schoon is teruggebracht.',
				'Verhuurder mag schade, ontbrekende onderdelen of schoonmaakkosten met de borg verrekenen.',
			)
		) .

		$h( 'Artikel 6 – Annuleren' ) .
		$ol(
			array(
				'Annuleren kan kosteloos tot [x] dagen voor de eerste huurdag.',
				'Bij annulering binnen [x] dagen voor de eerste huurdag is [x]% van de huurprijs verschuldigd.',
				'Bij annulering op de huurdag zelf of het niet ophalen van het gehuurde is de volledige huurprijs verschuldigd.',
				'Verhuurder mag een reservering annuleren bij overmacht (zie artikel 11); eventueel betaalde bedragen worden dan terugbetaald.',
			)
		) .

		$h( 'Artikel 7 – Ophalen, bezorgen en terugbrengen' ) .
		$ol(
			array(
				'Het gehuurde wordt opgehaald en teruggebracht op het adres van verhuurder, op de afgesproken tijden, tenzij halen en brengen is afgesproken.',
				'Bij halen en brengen zorgt de huurder dat de plek van plaatsing goed bereikbaar, vlak en geschikt is.',
				'Wordt het gehuurde te laat teruggebracht, dan mag verhuurder per extra dag de dagprijs in rekening brengen.',
				'De huurder controleert het gehuurde bij ontvangst en meldt direct eventuele gebreken.',
			)
		) .

		$h( 'Artikel 8 – Gebruik van het gehuurde' ) .
		$ol(
			array(
				'De huurder gebruikt het gehuurde zorgvuldig en volgens de instructies en de bestemming ervan.',
				'Het is niet toegestaan het gehuurde aan derden door te verhuren of uit te lenen zonder toestemming van verhuurder.',
				'Voor springkussens, bumperbaan en vergelijkbare attracties geldt: altijd onder toezicht van een volwassene, niet gebruiken bij harde wind of regen, en de gebruiksinstructies volgen.',
				'Verwarming en gereedschap alleen gebruiken volgens de handleiding en de geldende veiligheidsvoorschriften.',
			)
		) .

		$h( 'Artikel 9 – Schade, verlies en schoonmaak' ) .
		$ol(
			array(
				'De huurder is vanaf het moment van ophalen of afleveren tot aan het terugbrengen verantwoordelijk voor het gehuurde.',
				'Schade, verlies of diefstal meldt de huurder direct. De kosten van herstel of vervanging komen voor rekening van de huurder, tenzij de schade het gevolg is van normaal gebruik of slijtage.',
				'Het gehuurde wordt schoon teruggebracht. Is dat niet het geval, dan mag verhuurder schoonmaakkosten in rekening brengen, tenzij de optie schoonmaak is afgenomen.',
			)
		) .

		$h( 'Artikel 10 – Aansprakelijkheid' ) .
		$ol(
			array(
				'Verhuurder is niet aansprakelijk voor schade die ontstaat door onjuist of onzorgvuldig gebruik van het gehuurde.',
				'De aansprakelijkheid van verhuurder is in alle gevallen beperkt tot het bedrag dat de verzekering van verhuurder uitkeert, of, als die niet uitkeert, tot de huurprijs van de betreffende reservering.',
			)
		) .

		$h( 'Artikel 11 – Overmacht' ) .
		$p( 'Bij overmacht, zoals extreme weersomstandigheden, ziekte, storingen of andere omstandigheden buiten de invloed van verhuurder, mag verhuurder de overeenkomst opschorten of kosteloos annuleren.' ) .

		$h( 'Artikel 12 – Verkoop van artikelen' ) .
		$p( 'Voor artikelen die te koop worden aangeboden (bijvoorbeeld pellets) gelden de prijzen per eenheid zoals vermeld. [Vul aan: afhalen of bezorgen, retourneren, herroepingsrecht bij bestellingen op afstand.]' ) .

		$h( 'Artikel 13 – Klachten' ) .
		$p( 'Klachten meldt de huurder zo snel mogelijk, bij voorkeur binnen [x] dagen na afloop van de huurperiode, per e-mail aan ' . esc_html( $email ) . '. Verhuurder reageert binnen 14 dagen.' ) .

		$h( 'Artikel 14 – Privacy' ) .
		$p( 'Verhuurder gebruikt de gegevens van de huurder alleen voor het uitvoeren van de reservering en de administratie, en deelt deze niet met derden, behalve als dat nodig is voor de uitvoering of wettelijk verplicht is.' ) .

		$h( 'Artikel 15 – Toepasselijk recht' ) .
		$p( 'Op alle overeenkomsten is Nederlands recht van toepassing.' );
}
