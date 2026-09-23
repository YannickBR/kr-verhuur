/*
 * Winkelwagen (in de browser van de bezoeker).
 *
 * Een regel = één artikel voor één periode:
 *   { key, product_id, name, url, start, end, days, quantity, extras: [keys], extraLabels: [..], needsAddress, total }
 *
 * De prijs in de winkelwagen is een indicatie; op de winkelwagenpagina en bij het
 * plaatsen van de bestelling rekent de server alles opnieuw uit.
 */
window.KR = window.KR || {};

(function () {
  const KEY = "krv_cart_v1";
  let memory = [];

  function read() {
    try {
      const raw = window.localStorage.getItem(KEY);
      const items = raw ? JSON.parse(raw) : [];
      return Array.isArray(items) ? items : [];
    } catch (e) {
      return memory.slice();
    }
  }

  function write(items) {
    memory = items.slice();
    try {
      window.localStorage.setItem(KEY, JSON.stringify(items));
    } catch (e) {
      /* opslag niet beschikbaar (privévenster): alleen in geheugen */
    }
    updateBadges();
    document.dispatchEvent(new CustomEvent("krv:cart", { detail: { items } }));
  }

  function updateBadges() {
    const n = read().length;
    document.querySelectorAll("[data-cart-count]").forEach((el) => {
      el.textContent = n;
      el.hidden = n === 0;
    });
  }

  KR.cart = {
    items: read,
    count: () => read().length,
    add(item) {
      const items = read();
      item.key = item.key || Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
      items.push(item);
      write(items);
      return item;
    },
    remove(key) {
      write(read().filter((i) => i.key !== key));
    },
    clear() {
      write([]);
    },
    // Aantal stuks van een artikel dat per dag al in de winkelwagen zit: { "YYYY-MM-DD": n }
    usage(productId) {
      const out = {};
      read()
        .filter((i) => Number(i.product_id) === Number(productId))
        .forEach((i) => {
          const d = new Date(i.start + "T12:00:00");
          const end = new Date(i.end + "T12:00:00");
          while (d <= end) {
            const iso = d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0");
            out[iso] = (out[iso] || 0) + Number(i.quantity || 1);
            d.setDate(d.getDate() + 1);
          }
        });
      return out;
    },
  };

  // Andere tabbladen bijwerken.
  window.addEventListener("storage", (e) => {
    if (e.key === KEY) {
      updateBadges();
      document.dispatchEvent(new CustomEvent("krv:cart", { detail: { items: read() } }));
    }
  });

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", updateBadges);
  else updateBadges();
})();

/*
 * Btw-weergave: incl. of excl. btw, te wisselen door de bezoeker.
 *
 * Bedragen staan in de pagina als <span class="js-price" data-price-incl="…">.
 * De keuze wordt in een cookie bewaard, zodat ook de server (en de e-mails)
 * hem kennen. Andere scripts luisteren naar het event "krv:vat".
 */
(function () {
  const CFG = window.KRV_VAT || { rate: 21, mode: "incl", toggle: 0, cookie: "krv_vat" };
  const rate = Number(CFG.rate) || 0;
  const euro = (n) => new Intl.NumberFormat("nl-NL", { style: "currency", currency: "EUR" }).format(n);
  let mode = CFG.mode === "excl" ? "excl" : "incl";

  // Een eerder gemaakte keuze (bijv. bij een pagina uit de cache) gaat voor.
  if (Number(CFG.toggle)) {
    const m = document.cookie.match(new RegExp("(?:^|; )" + CFG.cookie + "=(incl|excl)"));
    if (m) mode = m[1];
  }

  const toExcl = (incl) => Math.round((Number(incl) / (1 + rate / 100)) * 100) / 100;

  KR.vat = {
    rate,
    mode: () => mode,
    isExcl: () => mode === "excl",
    label: () => (mode === "excl" ? "excl. btw" : "incl. btw"),
    toExcl,
    // Bedrag incl. btw → tekst zoals de bezoeker het wil zien.
    show: (incl) => euro(mode === "excl" ? toExcl(incl) : Number(incl)),
    set(next) {
      if (next !== "incl" && next !== "excl") return;
      mode = next;
      document.cookie = CFG.cookie + "=" + next + "; path=/; max-age=31536000; SameSite=Lax";
      KR.vat.apply();
      document.dispatchEvent(new CustomEvent("krv:vat", { detail: { mode } }));
    },
    // Alle bedragen en labels op de pagina bijwerken.
    apply(root) {
      const scope = root || document;
      scope.querySelectorAll("[data-price-incl]").forEach((el) => {
        el.textContent = KR.vat.show(el.getAttribute("data-price-incl"));
      });
      scope.querySelectorAll(".js-vat-label").forEach((el) => (el.textContent = KR.vat.label()));
      document.querySelectorAll("[data-vat-set]").forEach((b) => b.setAttribute("aria-pressed", String(b.dataset.vatSet === mode)));
      document.body.classList.toggle("vat-excl", mode === "excl");
      document.body.classList.toggle("vat-incl", mode !== "excl");
    },
  };

  document.addEventListener("click", (e) => {
    const b = e.target.closest("[data-vat-set]");
    if (b) KR.vat.set(b.dataset.vatSet);
  });

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", () => KR.vat.apply());
  else KR.vat.apply();
})();
