/*
 * Winkelwagenpagina: overzicht, actuele prijzen/beschikbaarheid en bestelling plaatsen.
 * Alle regels gaan in één keer naar POST /wp-json/kr/v1/bookings.
 */
(function () {
  const CFG = window.KRV_CART;
  const root = document.getElementById("cart-root");
  if (!CFG || !root || !window.KR || !KR.cart) return;

  const euro = (n) => new Intl.NumberFormat("nl-NL", { style: "currency", currency: "EUR" }).format(n);
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const D = KR.dates;

  // Bedragen van de server zijn incl. btw; weergave volgens de instelling.
  const vatRate = Number(CFG.vat && CFG.vat.rate) || 0;
  const showExcl = !!Number(CFG.vat && CFG.vat.showExcl);
  const toExcl = (incl) => Math.round((incl / (1 + vatRate / 100)) * 100) / 100;
  const show = (incl) => euro(showExcl ? toExcl(incl) : incl);
  const icons = CFG.icons || {};

  let checks = []; // resultaat van /cart/validate, zelfde volgorde als de winkelwagen
  let checking = false;
  let busy = false;
  const saved = {}; // ingevulde klantgegevens bewaren bij opnieuw tekenen

  const period = (i) => D.pretty(i.start) + (i.days > 1 ? " t/m " + D.pretty(i.end) : "") + " · " + i.days + (i.days === 1 ? " dag" : " dagen");
  const payload = (i) => ({ product_id: i.product_id, start: i.start, end: i.end, quantity: i.quantity, extras: i.extras });

  async function validate() {
    const items = KR.cart.items();
    if (!items.length) {
      checks = [];
      return render();
    }
    checking = true;
    render();
    try {
      const res = await fetch(CFG.restUrl + "cart/validate", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ items: items.map(payload) }),
      });
      const data = await res.json();
      checks = Array.isArray(data.items) ? data.items : [];
    } catch (e) {
      checks = [];
    }
    checking = false;
    render();
  }

  // Totaalblok: bij excl. btw subtotaal + btw + totaal incl.; anders totaal incl. met btw-bedrag.
  function totalsHtml(incl) {
    const excl = toExcl(incl);
    const vat = Math.round((incl - excl) * 100) / 100;
    const row = (a, b, cls) => '<div class="summary-row' + (cls ? " " + cls : "") + '"><span>' + a + "</span><span>" + b + "</span></div>";
    if (showExcl) {
      return row("Subtotaal excl. btw", euro(excl)) + row("Btw " + vatRate + "%", euro(vat)) + row("Totaal incl. btw", euro(incl), "summary-total");
    }
    return row("Totaal incl. btw", euro(incl), "summary-total") + row("Waarvan btw " + vatRate + "%", euro(vat), "summary-vat");
  }

  function captureForm() {
    root.querySelectorAll("#checkout-form [name]").forEach((f) => (saved[f.name] = f.value));
  }

  function render() {
    captureForm();
    const items = KR.cart.items();

    if (!items.length) {
      root.innerHTML =
        '<div class="card empty-state"><h2>Je winkelwagen is leeg</h2>' +
        "<p>Kies een artikel, selecteer je huurdagen en voeg het toe aan je winkelwagen.</p>" +
        '<p><a class="btn btn-primary" href="' + esc(CFG.catalogUrl) + '">Bekijk het assortiment</a></p></div>';
      return;
    }

    let total = 0;
    let deposit = 0;
    let problems = 0;
    const rows = items
      .map((item, n) => {
        const c = checks[n];
        const ok = !c || c.ok;
        if (!ok) problems++;
        const lineTotal = c && c.ok ? c.total : item.total;
        if (ok) {
          total += Number(lineTotal);
          deposit += c && c.ok ? Number(c.deposit) : 0;
        }
        const lines = c && c.ok ? c.lines : null;
        return (
          '<article class="cart-item' + (ok ? "" : " has-error") + '">' +
          '<div class="cart-item-main">' +
          '<h3><a href="' + esc(item.url) + '">' + (item.quantity > 1 ? esc(item.quantity) + "× " : "") + esc(item.name) + "</a></h3>" +
          '<p class="cart-period">' + (icons.calendar || "") + esc(period(item)) + "</p>" +
          (lines
            ? '<ul class="cart-lines">' + lines.map((l) => "<li><span>" + esc(l.label) + "</span><span>" + show(l.amount) + "</span></li>").join("") + "</ul>"
            : item.extraLabels && item.extraLabels.length
            ? '<p class="cart-extras">+ ' + item.extraLabels.map(esc).join(", ") + "</p>"
            : "") +
          (ok ? "" : '<p class="cart-error">' + esc(c.error) + " Verwijder dit artikel of kies een andere periode.</p>") +
          "</div>" +
          '<div class="cart-item-side"><span class="price">' + (ok ? show(lineTotal) : "—") + "</span>" +
          '<button type="button" class="link-btn" data-remove="' + esc(item.key) + '">Verwijderen</button></div>' +
          "</article>"
        );
      })
      .join("");

    const needsAddress = items.some((i) => i.needsAddress);
    const v = (k) => esc(saved[k] || "");

    root.innerHTML =
      '<div class="cart-layout"><div>' +
      '<div class="card"><div class="cart-head"><h2>Je winkelwagen <small>(' + items.length + (items.length === 1 ? " artikel" : " artikelen") + ")</small></h2>" +
      '<a class="link-btn" href="' + esc(CFG.catalogUrl) + '">+ Nog iets toevoegen</a></div>' +
      (checking ? '<p class="cart-checking">Beschikbaarheid controleren…</p>' : "") +
      rows + "</div></div>" +
      '<aside class="card checkout">' +
      "<h2>Bestelling plaatsen</h2>" +
      '<form id="checkout-form" novalidate><div class="form-grid">' +
      '<div class="field full"><label for="c-name">Naam *</label><input id="c-name" name="name" autocomplete="name" required value="' + v("name") + '"></div>' +
      '<div class="field"><label for="c-email">E-mail *</label><input id="c-email" name="email" type="email" autocomplete="email" required value="' + v("email") + '"></div>' +
      '<div class="field"><label for="c-phone">Telefoon *</label><input id="c-phone" name="phone" type="tel" autocomplete="tel" required value="' + v("phone") + '"></div>' +
      (needsAddress
        ? '<div class="field full"><label for="c-address">Afleveradres *</label><input id="c-address" name="address" autocomplete="street-address" required placeholder="Straat, huisnummer, postcode en plaats" value="' + v("address") + '"></div>'
        : "") +
      '<div class="field full"><label for="c-notes">Opmerkingen</label><textarea id="c-notes" name="notes" placeholder="Bijv. gewenste tijden of bijzonderheden">' + v("notes") + "</textarea></div>" +
      '<div class="hp" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>' +
      "</div>" +
      '<div class="summary">' + totalsHtml(total) +
      '<div class="summary-note">' + (deposit ? "Excl. borg van " + euro(deposit) + ". " : "") + "Je ontvangt een bevestiging per e-mail; wij bevestigen de beschikbaarheid zo snel mogelijk.</div></div>" +
      '<button class="btn btn-primary btn-block" id="checkout-btn" type="submit"' + (problems || checking || busy ? " disabled" : "") + ">" +
      (busy ? "Bezig met versturen…" : "Bestelling plaatsen") + "</button>" +
      (problems ? '<p class="cart-error">Los eerst de melding' + (problems > 1 ? "en" : "") + " bij je artikelen op.</p>" : "") +
      '<div id="checkout-msg" aria-live="polite"></div>' +
      "</form></aside></div>";
  }

  root.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-remove]");
    if (!btn) return;
    KR.cart.remove(btn.dataset.remove);
  });

  root.addEventListener("submit", async (e) => {
    if (e.target.id !== "checkout-form") return;
    e.preventDefault();
    const form = e.target;
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }
    const fd = new FormData(form);
    const items = KR.cart.items();
    const request = {
      items: items.map(payload),
      customer: {
        name: String(fd.get("name") || "").trim(),
        email: String(fd.get("email") || "").trim(),
        phone: String(fd.get("phone") || "").trim(),
        address: String(fd.get("address") || "").trim(),
        notes: String(fd.get("notes") || "").trim(),
      },
      website: fd.get("website") || "",
    };
    busy = true;
    render();
    try {
      const res = await fetch(CFG.restUrl + "bookings", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-WP-Nonce": CFG.nonce },
        body: JSON.stringify(request),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(data.message || "Versturen mislukt (" + res.status + ")");
      busy = false;
      KR.cart.clear();
      root.innerHTML =
        '<div class="card order-done">' + (icons.check || "") +
        "<h2>Bedankt voor je bestelling!</h2>" +
        "<p>Je bestelnummer is <strong>" + esc(data.reference) + "</strong>. We hebben een overzicht naar <strong>" + esc(request.customer.email) +
        "</strong> gestuurd en bevestigen je reservering zo snel mogelijk.</p>" +
        '<p><a class="btn btn-primary" href="' + esc(CFG.homeUrl) + '">Terug naar home</a></p></div>';
      window.scrollTo({ top: 0, behavior: "smooth" });
    } catch (err) {
      busy = false;
      await validate(); // beschikbaarheid kan intussen veranderd zijn
      const msg = root.querySelector("#checkout-msg");
      if (msg) msg.innerHTML = '<div class="alert alert-error">' + esc(err.message) + "</div>";
    }
  });

  document.addEventListener("krv:cart", () => {
    if (!busy) validate();
  });

  validate();
})();
