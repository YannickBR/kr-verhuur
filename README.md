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

**Btw** (ook onder Boekingen → Instellingen):
- *Prijzen standaard tonen*: **inclusief** of **exclusief btw**, voor nieuwe bezoekers.
- *Wisselknop voor bezoekers*: bezoekers wisselen zelf met de knop **Incl. btw / Excl. btw** (in de header, in het mobiele menu en bij de totalen in de winkelwagen). Alle prijzen op de pagina wisselen direct mee; de keuze wordt onthouden (cookie `krv_vat`) en geldt ook voor de e-mail bij hun bestelling. Zet je de knop uit, dan ziet iedereen de standaard.
- *Ingevoerde prijzen zijn*: incl. of excl. btw (hoe je prijzen bij artikelen en extra opties invult).
- *Btw-percentage* (standaard 21%).
- Bij excl. btw toont de winkelwagen subtotaal excl. btw, het btw-bedrag en het totaal incl. btw.
- Boekingen slaan het bedrag altijd **incl. btw** op (wat de klant betaalt); in het beheer staat het btw-bedrag erbij. De borg valt buiten de btw.

## E-mails

Alle e-mails zijn HTML-mails in de huisstijl (logo, kleuren, overzicht van de bestelling met btw-opbouw) met een tekstversie voor e-mailprogramma's zonder HTML.

- **Bestelling ontvangen** – naar de klant, direct na het bestellen. Tegelijk krijg jij een melding met een knop naar de bestelling in het beheer.
- **Reservering bevestigd / geannuleerd** – naar de klant als je in de boeking "E-mail klant" aanvinkt. Je kunt dan een **persoonlijk bericht** voor die klant toevoegen; bij een bestelling met meerdere artikelen geldt de status (en de mail) standaard voor de hele bestelling.
- Teksten (onderwerp, kop, bericht, groet en voettekst) pas je aan bij **Boekingen → Instellingen → E-mails aan klanten**, met codes als `{voornaam}` en `{bestelnummer}`. Per e-mail is er een **voorbeeld** en je kunt een **testmail** naar jezelf sturen.
- E-mails worden verstuurd vanaf het e-mailadres bij *Bedrijf & contact*. Gebruik een SMTP-plugin (bijv. WP Mail SMTP) zodat ze niet in de spam belanden.
- Het logo in de e-mail is het logo uit *Weergave → Customizer → Site-identiteit* als je dat hebt ingesteld, anders het meegeleverde KR Verhuur-logo.

## Contactgegevens aanpassen

E-mailadres, telefoonnummer, bedrijfsnaam en werkgebied staan bij **Boekingen → Instellingen → Bedrijf & contact**. Ze worden gebruikt in de header/footer van de website, het contactblok op de homepage en in alle e-mails. Het adres waar meldingen van nieuwe bestellingen heen gaan stel je daar apart in.

## Hoe reserveren werkt (winkelwagen)

1. De klant kiest op de productpagina één dag (één klik) of een periode (twee klikken). Volgeboekte dagen zijn doorgestreept; bij artikelen met voorraad telt het gekozen aantal mee, inclusief wat al in de winkelwagen zit.
2. De klant kiest extra opties, ziet de prijs en klikt **In winkelwagen**. Zo kunnen meerdere artikelen (elk met een eigen periode) verzameld worden. Het winkelwagen-icoon in de header toont het aantal.
3. Op **/winkelwagen/** worden prijzen en beschikbaarheid live gecontroleerd. De klant vult één keer zijn gegevens in (afleveradres alleen als er halen en brengen is gekozen) en klikt **Bestelling plaatsen**.
4. Alle artikelen worden samen gecontroleerd en opgeslagen als boekingen met één bestelnummer en status **Aanvraag**. De beheerder krijgt één e-mail met de hele bestelling, de klant één overzicht. Aanvragen blokkeren de agenda direct.
5. De beheerder bevestigt de boekingen in wp-admin. In de lijst en op het bewerkscherm zie je welke boekingen bij dezelfde bestelling horen.

De server controleert altijd opnieuw de prijs, beschikbaarheid, datums en verplichte velden. De website toont de prijs alleen vooraf.

## Later uitbreiden

- Online betalen (bijv. Mollie of WooCommerce) kan aan een bestelling gekoppeld worden via de hooks `krv_request_created` (nieuwe bestelling, met bestelnummer en boekings-ID's) en `krv_booking_status_changed`.
- De winkelwagen staat in de browser van de bezoeker (localStorage); de server controleert bij het bestellen altijd opnieuw prijs en beschikbaarheid.

## Logo

Het logo is als SVG nagebouwd in het theme. Wil je het originele bestand gebruiken? Upload het via *Weergave → Customizer → Site-identiteit → Logo*; het theme gebruikt het dan automatisch.
