/*
 * Reserveren van één product.
 *
 * Opbouw (bewust los van elkaar, zodat later een winkelwagen kan worden gekoppeld):
 *   KR.pricing.calculate(product, selection) → prijsregels + totaal
 *   KR.booking.createLineItem(...)           → één "regel" zoals die straks in de winkelwagen komt
 *   KR.booking.submit(request)               → verstuurt de aanvraag (endpoint of e-mail)
 *
 * Winkelwagen later: vervang in `mount()` de knop "Reservering aanvragen" door
 * "In winkelwagen" en roep `KR.cart.add(lineItem)` aan; de lineItem-structuur blijft gelijk.
 */
window.KR = window.KR || {};

KR.pricing = {
  // selection = { days, quantity, extras: ["delivery", ...] }
  calculate(p, sel) {
    const lines = [];
    const days = Math.max(1, sel.days || 1);
    const qty = Math.max(1, sel.quantity || 1);
    const extraDay = p.pricePerExtraDay != null ? p.pricePerExtraDay : p.pricePerDay;
    const rent = (p.pricePerDay + (days - 1) * extraDay) * qty;
    lines.push({
      key: "rent",
      label: (qty > 1 ? qty + "× " : "") + "Huur " + days + (days === 1 ? " dag" : " dagen"),
      amount: rent,
    });
    (sel.extras || []).forEach((key) => {
      const x = KR.extras[key];
      if (!x) return;
      const amount = x.type === "perDay" ? x.price * days : x.price;
      lines.push({ key, label: x.label + (x.type === "perDay" && days > 1 ? " (" + days + " dagen)" : ""), amount });
    });
    const total = lines.reduce((s, l) => s + l.amount, 0);
    return { lines, total: Math.round(total * 100) / 100, deposit: p.deposit || 0 };
  },
};

KR.booking = {
  createLineItem(p, sel) {
    const price = KR.pricing.calculate(p, sel);
    return {
      productId: p.id,
      productName: p.name,
      group: p.group,
      startDate: sel.start,
      endDate: sel.end,
      days: sel.days,
      quantity: sel.quantity || 1,
      extras: (sel.extras || []).slice(),
      lines: price.lines,
      total: price.total,
      deposit: price.deposit,
    };
  },

  // request = { items: [lineItem, ...], customer: {...} }
  // Werkt al met een lijst regels, zodat dit ook de checkout van de winkelwagen kan worden.
  async submit(request) {
    const S = KR.settings;
    if (S.bookingEndpoint) {
      const res = await fetch(S.bookingEndpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify(request),
      });
      if (!res.ok) throw new Error("Versturen mislukt (" + res.status + ")");
      return { method: "endpoint" };
    }
    const subject = "Reserveringsaanvraag – " + request.items.map((i) => i.productName).join(", ");
    location.href = "mailto:" + S.email + "?subject=" + encodeURIComponent(subject) + "&body=" + encodeURIComponent(KR.booking.toText(request));
    return { method: "mailto" };
  },

  toText(request) {
    const U = KR.util;
    const D = KR.dates;
    const c = request.customer;
    const out = [];
    request.items.forEach((i) => {
      out.push("Product: " + i.productName + (i.quantity > 1 ? " (" + i.quantity + "×)" : ""));
      out.push("Periode: " + D.pretty(i.startDate) + (i.days > 1 ? " t/m " + D.pretty(i.endDate) : "") + " (" + i.days + (i.days === 1 ? " dag" : " dagen") + ")");
      i.lines.forEach((l) => out.push("  - " + l.label + ": " + U.euro(l.amount)));
      out.push("Totaal: " + U.euro(i.total) + (i.deposit ? " (excl. borg " + U.euro(i.deposit) + ")" : ""));
      out.push("");
    });
    out.push("Naam: " + c.name);
    out.push("E-mail: " + c.email);
    out.push("Telefoon: " + c.phone);
    if (c.address) out.push("Adres: " + c.address);
    if (c.notes) out.push("Opmerkingen: " + c.notes);
    return out.join("\n");
  },

  mount(el, p) {
    const U = KR.util;
    const S = KR.settings;
    const icon = KR.icons;
    const state = { start: null, end: null, days: 0, quantity: 1, extras: [] };

    const extrasHtml = (p.extras || [])
      .filter((k) => KR.extras[k])
      .map((k) => {
        const x = KR.extras[k];
        return (
          '<label class="option"><input type="checkbox" name="extra" value="' + k + '">' +
          '<span class="option-body"><span class="option-title"><span>' + U.esc(x.label) + "</span><span>+ " + U.euro(x.price) +
          (x.type === "perDay" ? " p/d" : "") + '</span></span><span class="option-desc">' + U.esc(x.description) + "</span></span></label>"
        );
      })
      .join("");

    el.innerHTML =
      "<h2>" + icon("calendar") + "Reserveren</h2>" +
      '<form id="booking-form" novalidate>' +
      '<div class="booking-step"><h3>1. Kies je huurdag(en)</h3><div id="calendar"></div></div>' +
      (p.quantity
        ? '<div class="booking-step"><h3>Aantal</h3><div class="qty"><button type="button" data-q="-1" aria-label="Minder">−</button>' +
          '<input id="qty" type="number" min="1" max="999" value="1" aria-label="Aantal"><button type="button" data-q="1" aria-label="Meer">+</button></div></div>'
        : "") +
      (extrasHtml ? '<div class="booking-step"><h3>2. Extra opties</h3>' + extrasHtml + "</div>" : "") +
      '<div class="booking-step"><h3>' + (extrasHtml ? "3" : "2") + ". Je gegevens</h3>" +
      '<div class="form-grid">' +
      '<div class="field full"><label for="f-name">Naam *</label><input id="f-name" name="name" autocomplete="name" required></div>' +
      '<div class="field"><label for="f-email">E-mail *</label><input id="f-email" name="email" type="email" autocomplete="email" required></div>' +
      '<div class="field"><label for="f-phone">Telefoon *</label><input id="f-phone" name="phone" type="tel" autocomplete="tel" required></div>' +
      '<div class="field full hidden" id="address-field"><label for="f-address">Afleveradres *</label><input id="f-address" name="address" autocomplete="street-address" placeholder="Straat, huisnummer, postcode en plaats"></div>' +
      '<div class="field full"><label for="f-notes">Opmerkingen</label><textarea id="f-notes" name="notes" placeholder="Bijv. gewenste tijden of bijzonderheden"></textarea></div>' +
      "</div></div>" +
      '<div class="summary" id="summary"></div>' +
      '<button class="btn btn-primary btn-block" type="submit" id="submit-btn" style="margin-top:16px" disabled>Reservering aanvragen</button>' +
      '<div id="booking-msg" aria-live="polite"></div>' +
      "</form>";

    const form = el.querySelector("#booking-form");
    const summary = el.querySelector("#summary");
    const submitBtn = el.querySelector("#submit-btn");
    const msg = el.querySelector("#booking-msg");
    const addressField = el.querySelector("#address-field");

    function update() {
      const needsAddress = state.extras.includes("delivery");
      addressField.classList.toggle("hidden", !needsAddress);
      addressField.querySelector("input").required = needsAddress;
      submitBtn.disabled = !state.start;

      if (!state.start) {
        summary.innerHTML = '<div class="summary-row"><span>Kies eerst een datum om de prijs te zien.</span></div>' +
          '<div class="summary-row"><span>Vanaf</span><span>' + U.euro(p.pricePerDay) + " / dag</span></div>";
        return;
      }
      const price = KR.pricing.calculate(p, state);
      summary.innerHTML =
        price.lines.map((l) => '<div class="summary-row"><span>' + U.esc(l.label) + "</span><span>" + U.euro(l.amount) + "</span></div>").join("") +
        '<div class="summary-row summary-total"><span>Totaal</span><span>' + U.euro(price.total) + "</span></div>" +
        (price.deposit ? '<div class="summary-note">Excl. borg van ' + U.euro(price.deposit) + ". Prijzen incl. btw.</div>" : '<div class="summary-note">Prijzen incl. btw.</div>');
    }

    const D = KR.dates;
    KR.Calendar(el.querySelector("#calendar"), {
      booked: p.bookedDates,
      minDate: D.addDays(new Date(), S.minLeadDays || 0),
      maxDate: D.addDays(new Date(), S.maxDaysAhead || 365),
      maxDays: p.maxDays,
      onChange(sel) {
        state.start = sel.start;
        state.end = sel.end;
        state.days = sel.days;
        update();
      },
    });

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
        state.quantity = Math.min(999, Math.max(1, parseInt(v, 10) || 1));
        qty.value = state.quantity;
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
        createdAt: new Date().toISOString(),
        items: [KR.booking.createLineItem(p, state)],
        customer: {
          name: fd.get("name").trim(),
          email: fd.get("email").trim(),
          phone: fd.get("phone").trim(),
          address: (fd.get("address") || "").trim(),
          notes: (fd.get("notes") || "").trim(),
        },
      };
      submitBtn.disabled = true;
      try {
        const r = await KR.booking.submit(request);
        msg.innerHTML =
          '<div class="alert alert-success">' +
          (r.method === "endpoint"
            ? "Bedankt! Je aanvraag is verstuurd. We bevestigen je reservering zo snel mogelijk."
            : "Je e-mailprogramma is geopend met je aanvraag. Verstuur de e-mail om de reservering af te ronden.") +
          "</div>";
      } catch (err) {
        msg.innerHTML = '<div class="alert alert-error">' + U.esc(err.message) + ". Probeer het opnieuw of bel ons.</div>";
      } finally {
        submitBtn.disabled = false;
      }
    });

    update();
  },
};
