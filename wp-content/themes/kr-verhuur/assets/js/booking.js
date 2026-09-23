/*
 * Productpagina: huurdagen, aantal en extra opties kiezen → in de winkelwagen.
 *
 * Gegevens komen uit WordPress via window.KRV_BOOKING (zie functions.php).
 * De prijs wordt hier alleen getoond; de server rekent bij het bestellen opnieuw.
 */
window.KR = window.KR || {};

(function () {
  const CFG = window.KRV_BOOKING;
  if (!CFG) return;

  const euro = (n) => new Intl.NumberFormat("nl-NL", { style: "currency", currency: "EUR" }).format(n);
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  KR.util = Object.assign(KR.util || {}, { euro, esc });

  // Bedragen zijn intern incl. btw; weergave volgens de instelling (incl. of excl. btw).
  const vatRate = Number(CFG.vat && CFG.vat.rate) || 0;
  const showExcl = !!Number(CFG.vat && CFG.vat.showExcl);
  const vatLabel = showExcl ? "excl. btw" : "incl. btw";
  const show = (incl) => euro(showExcl ? incl / (1 + vatRate / 100) : incl);

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
    let availability = {}; // { "YYYY-MM-DD": resterend aantal volgens de server }
    let inCart = {}; // { "YYYY-MM-DD": aantal van dit artikel al in de winkelwagen }
    let cal;

    const left = (iso) => (iso in availability ? availability[iso] : p.stock) - (inCart[iso] || 0);

    const extrasHtml = Object.keys(p.extras)
      .map((k) => {
        const x = p.extras[k];
        return (
          '<label class="option"><input type="checkbox" name="extra" value="' + esc(k) + '">' +
          '<span class="option-body"><span class="option-title"><span>' + esc(x.label) + "</span><span>+ " + show(Number(x.price)) +
          (x.type === "perDay" ? " p/d" : "") + '</span></span><span class="option-desc">' + esc(x.description || "") + "</span></span></label>"
        );
      })
      .join("");

    el.innerHTML =
      "<h2>" + (icons.calendar || "") + "Reserveren</h2>" +
      '<form id="booking-form" novalidate>' +
      '<div class="booking-step"><h3>1. Kies je huurdag(en)</h3><div id="calendar"><p class="cal-hint">Beschikbaarheid laden…</p></div></div>' +
      (p.allowQuantity
        ? '<div class="booking-step"><h3>Aantal <small>(max. ' + p.stock + ")</small></h3>" +
          '<div class="qty"><button type="button" data-q="-1" aria-label="Minder">−</button>' +
          '<input id="qty" type="number" min="1" max="' + p.stock + '" value="1" aria-label="Aantal"><button type="button" data-q="1" aria-label="Meer">+</button></div></div>'
        : "") +
      (extrasHtml ? '<div class="booking-step"><h3>2. Extra opties</h3>' + extrasHtml + "</div>" : "") +
      '<div class="summary" id="summary"></div>' +
      '<button class="btn btn-primary btn-block" type="submit" id="submit-btn" disabled>' + (icons.cart || "") + "In winkelwagen</button>" +
      '<div id="booking-msg" aria-live="polite"></div>' +
      "</form>";

    const form = el.querySelector("#booking-form");
    const summary = el.querySelector("#summary");
    const submitBtn = el.querySelector("#submit-btn");
    const msg = el.querySelector("#booking-msg");

    function update() {
      submitBtn.disabled = !state.start;
      if (!state.start) {
        summary.innerHTML =
          '<div class="summary-row"><span>Kies eerst een datum om de prijs te zien.</span></div>' +
          '<div class="summary-row"><span>Vanaf</span><span>' + show(p.priceDay) + " / dag</span></div>";
        return;
      }
      const price = KR.pricing.calculate(p, state);
      summary.innerHTML =
        price.lines.map((l) => '<div class="summary-row"><span>' + esc(l.label) + "</span><span>" + show(l.amount) + "</span></div>").join("") +
        '<div class="summary-row summary-total"><span>Subtotaal <small>' + vatLabel + "</small></span><span>" + show(price.total) + "</span></div>" +
        '<div class="summary-note">' + (price.deposit ? "Excl. borg van " + euro(price.deposit) + ". " : "") + "Prijzen " + vatLabel + ".</div>";
    }

    function buildCalendar() {
      cal = KR.Calendar(el.querySelector("#calendar"), {
        isBooked: (iso) => left(iso) < state.quantity,
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
          inCart = KR.cart ? KR.cart.usage(p.id) : {};
        });
    }

    loadAvailability().then(buildCalendar);

    // Winkelwagen gewijzigd (bijv. in een ander tabblad): beschikbaarheid bijwerken.
    document.addEventListener("krv:cart", () => {
      inCart = KR.cart.usage(p.id);
      if (cal) cal.refresh();
    });

    el.querySelectorAll('input[name="extra"]').forEach((cb) =>
      cb.addEventListener("change", () => {
        cb.closest(".option").classList.toggle("checked", cb.checked);
        state.extras = [...el.querySelectorAll('input[name="extra"]:checked')].map((x) => x.value);
        update();
      })
    );

    const qty = el.querySelector("#qty");
    function setQty(v) {
      state.quantity = Math.min(p.stock, Math.max(1, parseInt(v, 10) || 1));
      if (qty) qty.value = state.quantity;
      if (cal) cal.refresh();
      update();
    }
    if (qty) {
      qty.addEventListener("change", () => setQty(qty.value));
      el.querySelectorAll("[data-q]").forEach((b) => b.addEventListener("click", () => setQty(state.quantity + Number(b.dataset.q))));
    }

    form.addEventListener("submit", (e) => {
      e.preventDefault();
      if (!state.start) return;
      const price = KR.pricing.calculate(p, state);
      KR.cart.add({
        product_id: p.id,
        name: p.name,
        url: CFG.productUrl,
        icon: CFG.productIcon || "",
        start: state.start,
        end: state.end,
        days: state.days,
        quantity: state.quantity,
        extras: state.extras.slice(),
        extraLabels: state.extras.map((k) => p.extras[k].label),
        needsAddress: state.extras.some((k) => Number(p.extras[k].needs_address)),
        total: price.total,
      });

      msg.innerHTML =
        '<div class="alert alert-success added">' +
        "<p><strong>" + esc(p.name) + "</strong> staat in je winkelwagen.</p>" +
        '<div class="added-actions"><a class="btn btn-primary" href="' + esc(CFG.cartUrl) + '">Naar winkelwagen (' + KR.cart.count() + ")</a>" +
        '<a class="btn btn-outline" href="' + esc(CFG.catalogUrl) + '">Verder winkelen</a></div></div>';

      // Formulier leegmaken voor een eventuele volgende periode.
      el.querySelectorAll('input[name="extra"]:checked').forEach((cb) => {
        cb.checked = false;
        cb.closest(".option").classList.remove("checked");
      });
      state.extras = [];
      setQty(1);
      if (cal) cal.reset();
      msg.scrollIntoView({ behavior: "smooth", block: "nearest" });
    });

    update();
  }

  document.addEventListener("DOMContentLoaded", () => {
    const el = document.getElementById("booking");
    if (el) mount(el);
  });
})();
