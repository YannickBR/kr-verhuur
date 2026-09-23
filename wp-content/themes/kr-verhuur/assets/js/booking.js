/*
 * Reserveren van één huurartikel (productpagina).
 *
 * Gegevens komen uit WordPress via window.KRV_BOOKING (zie functions.php).
 * De prijs wordt hier alleen getoond; de server rekent bij het opslaan opnieuw.
 *
 * Opbouw (los van elkaar, zodat later een winkelwagen gekoppeld kan worden):
 *   KR.pricing.calculate(product, selection) → prijsregels + totaal
 *   KR.booking.createLineItem(product, sel)  → één regel zoals die straks in de winkelwagen komt
 *   KR.booking.submit({ items, customer })   → POST /wp-json/kr/v1/bookings (accepteert al meerdere regels)
 */
window.KR = window.KR || {};

(function () {
  const CFG = window.KRV_BOOKING;
  if (!CFG) return;

  const euro = (n) => new Intl.NumberFormat("nl-NL", { style: "currency", currency: "EUR" }).format(n);
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

  KR.pricing = {
    calculate(p, sel) {
      const days = Math.max(1, sel.days || 1);
      const qty = Math.max(1, sel.quantity || 1);
      const lines = [
        { key: "rent", label: (qty > 1 ? qty + "× " : "") + "Huur " + days + (days === 1 ? " dag" : " dagen"), amount: (p.priceDay + (days - 1) * p.priceExtraDay) * qty },
      ];
      (sel.extras || []).forEach((key) => {
        const x = p.extras[key];
        if (!x) return;
        const perDay = x.type === "perDay";
        lines.push({ key, label: x.label + (perDay && days > 1 ? " (" + days + " dagen)" : ""), amount: Number(x.price) * (perDay ? days : 1) });
      });
      const total = Math.round(lines.reduce((s, l) => s + l.amount, 0) * 100) / 100;
      return { lines, total, deposit: p.deposit || 0 };
    },
  };

  KR.booking = {
    createLineItem(p, sel) {
      return { product_id: p.id, start: sel.start, end: sel.end, quantity: sel.quantity || 1, extras: (sel.extras || []).slice() };
    },
    async submit(request) {
      const res = await fetch(CFG.restUrl + "bookings", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-WP-Nonce": CFG.nonce },
        body: JSON.stringify(request),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(data.message || "Versturen mislukt (" + res.status + ")");
      return data;
    },
  };

  function mount(el) {
    // wp_localize_script levert getallen soms als tekst; zet ze expliciet om.
    const raw = CFG.product;
    const p = Object.assign({}, raw, {
      id: Number(raw.id),
      priceDay: Number(raw.priceDay),
      priceExtraDay: Number(raw.priceExtraDay),
      deposit: Number(raw.deposit),
      maxDays: Number(raw.maxDays),
      stock: Number(raw.stock),
      allowQuantity: !!Number(raw.allowQuantity),
    });
    const minLead = Number(CFG.minLead) || 0;
    const maxAhead = Number(CFG.maxAhead) || 365;
    const icons = CFG.icons || {};
    const D = KR.dates;
    const state = { start: null, end: null, days: 0, quantity: 1, extras: [] };
    let availability = {}; // { "YYYY-MM-DD": resterend aantal }
    let cal;

    const extrasHtml = Object.keys(p.extras)
      .map((k) => {
        const x = p.extras[k];
        return (
          '<label class="option"><input type="checkbox" name="extra" value="' + esc(k) + '">' +
          '<span class="option-body"><span class="option-title"><span>' + esc(x.label) + "</span><span>+ " + euro(x.price) +
          (x.type === "perDay" ? " p/d" : "") + '</span></span><span class="option-desc">' + esc(x.description || "") + "</span></span></label>"
        );
      })
      .join("");

    const needsAddress = () => state.extras.some((k) => p.extras[k] && Number(p.extras[k].needs_address));
    let step = 1;
    const stepNo = () => step++;

    el.innerHTML =
      "<h2>" + (icons.calendar || "") + "Reserveren</h2>" +
      '<form id="booking-form" novalidate>' +
      '<div class="booking-step"><h3>' + stepNo() + '. Kies je huurdag(en)</h3><div id="calendar"><p class="cal-hint">Beschikbaarheid laden…</p></div></div>' +
      (p.allowQuantity
        ? '<div class="booking-step"><h3>Aantal <small>(max. ' + p.stock + ")</small></h3>" +
          '<div class="qty"><button type="button" data-q="-1" aria-label="Minder">−</button>' +
          '<input id="qty" type="number" min="1" max="' + p.stock + '" value="1" aria-label="Aantal"><button type="button" data-q="1" aria-label="Meer">+</button></div></div>'
        : "") +
      (extrasHtml ? '<div class="booking-step"><h3>' + stepNo() + ". Extra opties</h3>" + extrasHtml + "</div>" : "") +
      '<div class="booking-step"><h3>' + stepNo() + ". Je gegevens</h3>" +
      '<div class="form-grid">' +
      '<div class="field full"><label for="f-name">Naam *</label><input id="f-name" name="name" autocomplete="name" required></div>' +
      '<div class="field"><label for="f-email">E-mail *</label><input id="f-email" name="email" type="email" autocomplete="email" required></div>' +
      '<div class="field"><label for="f-phone">Telefoon *</label><input id="f-phone" name="phone" type="tel" autocomplete="tel" required></div>' +
      '<div class="field full hidden" id="address-field"><label for="f-address">Afleveradres *</label><input id="f-address" name="address" autocomplete="street-address" placeholder="Straat, huisnummer, postcode en plaats"></div>' +
      '<div class="field full"><label for="f-notes">Opmerkingen</label><textarea id="f-notes" name="notes" placeholder="Bijv. gewenste tijden of bijzonderheden"></textarea></div>' +
      '<div class="hp" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>' +
      "</div></div>" +
      '<div class="summary" id="summary"></div>' +
      '<button class="btn btn-primary btn-block" type="submit" id="submit-btn" disabled>Reservering aanvragen</button>' +
      '<div id="booking-msg" aria-live="polite"></div>' +
      "</form>";

    const form = el.querySelector("#booking-form");
    const summary = el.querySelector("#summary");
    const submitBtn = el.querySelector("#submit-btn");
    const msg = el.querySelector("#booking-msg");
    const addressField = el.querySelector("#address-field");

    function update() {
      const addr = needsAddress();
      addressField.classList.toggle("hidden", !addr);
      addressField.querySelector("input").required = addr;
      submitBtn.disabled = !state.start;

      if (!state.start) {
        summary.innerHTML =
          '<div class="summary-row"><span>Kies eerst een datum om de prijs te zien.</span></div>' +
          '<div class="summary-row"><span>Vanaf</span><span>' + euro(p.priceDay) + " / dag</span></div>";
        return;
      }
      const price = KR.pricing.calculate(p, state);
      summary.innerHTML =
        price.lines.map((l) => '<div class="summary-row"><span>' + esc(l.label) + "</span><span>" + euro(l.amount) + "</span></div>").join("") +
        '<div class="summary-row summary-total"><span>Totaal</span><span>' + euro(price.total) + "</span></div>" +
        '<div class="summary-note">' + (price.deposit ? "Excl. borg van " + euro(price.deposit) + ". " : "") + "Prijzen incl. btw. Je ontvangt een bevestiging per e-mail.</div>";
    }

    function buildCalendar() {
      cal = KR.Calendar(el.querySelector("#calendar"), {
        isBooked: (iso) => iso in availability && availability[iso] < state.quantity,
        today: CFG.today,
        minDate: D.iso(D.addDays(D.parse(CFG.today), minLead)),
        maxDate: D.iso(D.addDays(D.parse(CFG.today), maxAhead)),
        maxDays: p.maxDays,
        icons,
        onChange(sel) {
          state.start = sel.start;
          state.end = sel.end;
          state.days = sel.days;
          update();
        },
      });
    }

    function loadAvailability() {
      return fetch(CFG.restUrl + "availability/" + p.id, { headers: { Accept: "application/json" } })
        .then((r) => (r.ok ? r.json() : {}))
        .catch(() => ({}))
        .then((data) => {
          availability = data || {};
        });
    }

    loadAvailability().then(buildCalendar);

    el.querySelectorAll('input[name="extra"]').forEach((cb) =>
      cb.addEventListener("change", () => {
        cb.closest(".option").classList.toggle("checked", cb.checked);
        state.extras = [...el.querySelectorAll('input[name="extra"]:checked')].map((x) => x.value);
        update();
      })
    );

    const qty = el.querySelector("#qty");
    if (qty) {
      const setQty = (v) => {
        state.quantity = Math.min(p.stock, Math.max(1, parseInt(v, 10) || 1));
        qty.value = state.quantity;
        if (cal) cal.refresh();
        update();
      };
      qty.addEventListener("change", () => setQty(qty.value));
      el.querySelectorAll("[data-q]").forEach((b) => b.addEventListener("click", () => setQty(state.quantity + Number(b.dataset.q))));
    }

    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      msg.innerHTML = "";
      if (!state.start) return;
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }
      const fd = new FormData(form);
      const request = {
        items: [KR.booking.createLineItem(p, state)],
        customer: {
          name: String(fd.get("name") || "").trim(),
          email: String(fd.get("email") || "").trim(),
          phone: String(fd.get("phone") || "").trim(),
          address: String(fd.get("address") || "").trim(),
          notes: String(fd.get("notes") || "").trim(),
        },
        website: fd.get("website") || "",
      };
      submitBtn.disabled = true;
      submitBtn.textContent = "Bezig met versturen…";
      try {
        const r = await KR.booking.submit(request);
        form.innerHTML =
          '<div class="alert alert-success booking-done"><strong>Bedankt voor je aanvraag!</strong><br>' +
          "Je referentie is <strong>" + esc(r.reference) + "</strong>. We hebben een bevestiging naar " + esc(request.customer.email) +
          " gestuurd en nemen zo snel mogelijk contact met je op.</div>";
        el.scrollIntoView({ behavior: "smooth", block: "start" });
      } catch (err) {
        msg.innerHTML = '<div class="alert alert-error">' + esc(err.message) + "</div>";
        submitBtn.disabled = false;
        submitBtn.textContent = "Reservering aanvragen";
        // Beschikbaarheid kan intussen veranderd zijn.
        loadAvailability().then(() => cal && cal.refresh());
      }
    });

    update();
  }

  document.addEventListener("DOMContentLoaded", () => {
    const el = document.getElementById("booking");
    if (el) mount(el);
  });
})();
