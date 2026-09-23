# KR Verhuur – WordPress website

Nieuwe website voor KR Verhuur, gebouwd als **WordPress-theme + plugin**:

| Onderdeel | Map | Wat doet het |
|---|---|---|
| Theme **KR Verhuur** | `wp-content/themes/kr-verhuur` | Uiterlijk: huisstijl (#002533 / #519f81), logo, homepage, assortiment, productpagina met reserveringskalender |
| Plugin **KR Verhuur – Boekingen** | `wp-content/plugins/kr-verhuur-boekingen` | Huurartikelen, huurgroepen, extra opties, beschikbaarheid, boekingen en het beheer ervan |

De boekingen zitten bewust in de plugin: bij een ander theme blijft de hele boekingshistorie bewaard.

## Installeren

1. Kopieer `wp-content/themes/kr-verhuur` en `wp-content/plugins/kr-verhuur-boekingen` naar de `wp-content` map van je WordPress-site (of upload ze als zip via *Weergave → Thema's* en *Plugins → Nieuwe plugin*).
2. Activeer eerst de plugin **KR Verhuur – Boekingen**. Er worden automatisch 8 huurgroepen (camper, photobooth, toiletwagen, springkussen, bumperbaan, meubilair, verwarming, gereedschap), voorbeeldartikelen en extra opties aangemaakt.
3. Activeer het theme **KR Verhuur**.
4. Zet *Instellingen → Permalinks* op "Berichtnaam" (dan werken `/huren/…` en `/huurgroep/…`).
5. Vul bij **Boekingen → Instellingen** je contactgegevens, e-mailadres voor meldingen en de Goboony-link in.
6. Pas de voorbeeldartikelen en **voorbeeldprijzen** aan onder **Huurartikelen**, en voeg foto's toe als uitgelichte afbeelding.

Tip: installeer een SMTP-plugin (bijv. WP Mail SMTP) zodat bevestigingsmails betrouwbaar aankomen.

## Beheer (wp-admin)

**Boekingen**
- **Alle boekingen** – de volledige historie met zoeken (naam, e-mail, telefoon), statusfilters (Aanvraag / Bevestigd / Afgerond / Geannuleerd), filters op artikel en periode, sorteren op huurdatum of bedrag.
- Snelle acties per boeking: bevestigen, afronden, annuleren. Bulkacties voor meerdere boekingen tegelijk.
- **Exporteer naar CSV** (opent in Excel), met de actieve filters.
- **Boeking bewerken** – artikel, datums, aantal, extra opties, korting, klantgegevens, interne notities, betaald-vinkje. De prijs wordt opnieuw berekend en de beschikbaarheid gecontroleerd. Optioneel een bevestigings- of annuleringsmail naar de klant.
- **Historie** per boeking: wie wat wanneer heeft gewijzigd.
- **Nieuwe boeking** – telefonische boekingen of blokkades zelf invoeren.
- **Planning** – maandoverzicht van alle artikelen × dagen, met kleur per status.
- Dashboard-widget met openstaande aanvragen en de verhuur van de komende 7 dagen; een teller in het menu toont nieuwe aanvragen.

**Huurartikelen** – per artikel: prijs eerste dag, prijs per extra dag, borg, max. aantal dagen, voorraad, of de klant een aantal mag kiezen, kenmerken en welke extra opties beschikbaar zijn.

**Huurartikelen → Huurgroepen** – icoon, korte omschrijving, volgorde en optioneel een externe link (de camper linkt zo direct naar Goboony).

**Boekingen → Instellingen** – contactgegevens, hoeveel dagen vooruit geboekt kan worden, welke statussen de agenda blokkeren, en de extra opties (halen en brengen, schoonmaakkosten, opbouwen, …) met prijs per boeking of per dag.

## Hoe reserveren werkt

1. De klant kiest op de productpagina één dag (één klik) of een periode (twee klikken). Volgeboekte dagen zijn doorgestreept; bij artikelen met voorraad telt het gekozen aantal mee.
2. De klant kiest extra opties en ziet direct de totaalprijs. Bij "Halen en brengen" wordt een afleveradres gevraagd.
3. De aanvraag komt binnen als boeking met status **Aanvraag**. De beheerder krijgt een e-mail en de klant een ontvangstbevestiging. Aanvragen blokkeren de agenda direct, zodat er niet dubbel geboekt wordt.
4. De beheerder bevestigt de boeking in wp-admin (eventueel met een e-mail naar de klant).

De server controleert altijd opnieuw de prijs, beschikbaarheid, datums en verplichte velden. De website toont de prijs alleen vooraf.

## Later: winkelwagen

De opbouw is al voorbereid op een winkelwagen:
- `POST /wp-json/kr/v1/bookings` accepteert een lijst `items` (meerdere artikelen). Alles wordt eerst gevalideerd en daarna samen opgeslagen, met één aanvraagnummer.
- In de theme-JS zijn `KR.pricing.calculate()`, `KR.booking.createLineItem()` en `KR.booking.submit()` losse functies. Een winkelwagen verzamelt alleen regels en geeft ze bij het afrekenen door aan `submit()`.
- Wil je later betalen via WooCommerce of Mollie, dan kan een boeking aan een bestelling gekoppeld worden via de hooks `krv_booking_created` en `krv_booking_status_changed`.

## Logo

Het logo is als SVG nagebouwd in het theme. Wil je het originele bestand gebruiken? Upload het via *Weergave → Customizer → Site-identiteit → Logo*; het theme gebruikt het dan automatisch.
