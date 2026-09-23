# KR Verhuur – website

Eenvoudige, statische website voor KR Verhuur (geen build-stap nodig). Open `index.html` of zet de map op een willekeurige webhost.

## Pagina's
- `index.html` – home met huurgroepen, werkwijze en contact
- `huren.html` – assortiment, filterbaar per groep (`huren.html?groep=photobooth`)
- `product.html?id=…` – productpagina met reserveringskalender, extra opties en prijsberekening

## Beheer
Alles staat in **`assets/js/data.js`**:
- `KR.settings` – contactgegevens, Goboony-link, waar aanvragen heen gaan (`bookingEndpoint`)
- `KR.groups` – huurgroepen (Camper linkt direct naar Goboony via `externalUrl`)
- `KR.products` – producten, prijs eerste dag / extra dag, borg, max. dagen, beschikbare extra's, bezette dagen (`bookedDates`), optioneel `image`
- `KR.extras` – extra opties zoals halen & brengen, schoonmaakkosten, opbouwen (vast bedrag of per dag)

Huisstijl: kleuren staan als CSS-variabelen bovenaan `assets/css/style.css` (#002533 en #519f81). Het logo is nagebouwd als SVG (`assets/img/logo-*.svg`, inline in `assets/js/app.js`); vervang het gerust door het originele bestand.

## Reserveren
1. Kies één dag (één klik) of een periode (twee klikken) in de kalender; bezette dagen en de maximale huurduur worden bewaakt.
2. Kies extra opties; bij "Halen en brengen" wordt een afleveradres gevraagd.
3. De aanvraag gaat naar `bookingEndpoint` (JSON POST) of, als dat leeg is, als e-mail naar `KR.settings.email`.

## Later: winkelwagen
De code is al opgesplitst zodat een winkelwagen er eenvoudig bij kan:
- `KR.pricing.calculate()` – prijsberekening
- `KR.booking.createLineItem()` – één winkelwagenregel (product, periode, aantal, extra's, totaal)
- `KR.booking.submit({ items: [...], customer })` – accepteert al een lijst regels

Een `KR.cart` hoeft alleen regels te verzamelen (bijv. in `localStorage`) en ze bij het afrekenen door te geven aan `submit()`.
