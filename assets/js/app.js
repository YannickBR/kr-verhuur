/* KR Verhuur – gedeelde layout (header/footer) en pagina's. */
window.KR = window.KR || {};

(function () {
  const S = KR.settings;
  const icon = KR.icons;

  /* ---------- helpers ---------- */
  KR.util = {
    euro(n) {
      return new Intl.NumberFormat("nl-NL", { style: "currency", currency: "EUR" }).format(n);
    },
    esc(s) {
      return String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
    },
    param(name) {
      return new URLSearchParams(location.search).get(name);
    },
    group(id) {
      return KR.groups.find((g) => g.id === id);
    },
    product(id) {
      return KR.products.find((p) => p.id === id);
    },
    groupUrl(g) {
      if (g.externalUrl) return g.externalUrl === "goboony" ? S.goboonyUrl : g.externalUrl;
      return "huren.html?groep=" + encodeURIComponent(g.id);
    },
    productUrl(p) {
      return "product.html?id=" + encodeURIComponent(p.id);
    },
    fromPrice(g) {
      const prices = KR.products.filter((p) => p.group === g.id).map((p) => p.pricePerDay);
      return prices.length ? Math.min(...prices) : null;
    },
  };
  const U = KR.util;

  /* Logo inline, zodat het het webfont van de site gebruikt. */
  const MARK =
    '<g fill="#519f81"><path d="M18 0h46v172a24 24 0 0 1-24 24H18A18 18 0 0 1 0 178V18A18 18 0 0 1 18 0z"/>' +
    '<circle cx="118" cy="62" r="35"/><path d="M82 108l68 25a20 20 0 0 1 13 19v44l-68-27a20 20 0 0 1-13-19z"/></g>';
  KR.logo = function (variant) {
    const c = variant === "dark" ? "#002533" : "#dff1fb";
    return (
      '<svg viewBox="0 0 900 200" role="img" aria-label="KR Verhuur – Voor als je het zelf niet hebt!">' + MARK +
      '<text x="222" y="118" font-family="Nunito, Arial, sans-serif" font-size="118" font-weight="900" fill="' + c + '" letter-spacing="-2">KRverhuur</text>' +
      '<text x="226" y="178" font-family="Nunito, Arial, sans-serif" font-size="44" font-weight="600" fill="' + c + '">Voor als je het zelf niet hebt!</text></svg>'
    );
  };
  KR.mark = function () {
    return '<svg viewBox="0 0 170 200" aria-hidden="true">' + MARK + "</svg>";
  };

  /* ---------- header & footer ---------- */
  function renderHeader() {
    const el = document.getElementById("site-header");
    if (!el) return;
    const page = document.body.dataset.page;
    const cur = (p) => (page === p ? ' aria-current="page"' : "");
    el.className = "site-header";
    el.innerHTML =
      '<div class="container">' +
      '<a class="brand" href="index.html">' + KR.logo("light") + "</a>" +
      '<button class="nav-toggle" aria-label="Menu" aria-expanded="false">' + icon("menu") + "</button>" +
      '<nav class="nav" id="nav">' +
      '<a href="index.html"' + cur("home") + ">Home</a>" +
      '<a href="huren.html"' + cur("huren") + ">Assortiment</a>" +
      '<a href="index.html#hoe-werkt-het">Hoe werkt het</a>' +
      '<a href="index.html#contact">Contact</a>' +
      '<a class="btn btn-primary" href="huren.html">' + icon("calendar") + "Direct reserveren</a>" +
      "</nav></div>";
    const btn = el.querySelector(".nav-toggle");
    const nav = el.querySelector(".nav");
    btn.addEventListener("click", () => {
      const open = nav.classList.toggle("open");
      btn.setAttribute("aria-expanded", open);
      btn.innerHTML = icon(open ? "close" : "menu");
    });
  }

  function renderFooter() {
    const el = document.getElementById("site-footer");
    if (!el) return;
    el.className = "site-footer";
    const groups = KR.groups
      .map((g) => {
        const ext = g.externalUrl ? ' target="_blank" rel="noopener"' : "";
        return '<li><a href="' + U.groupUrl(g) + '"' + ext + ">" + U.esc(g.name) + "</a></li>";
      })
      .join("");
    el.innerHTML =
      '<div class="container"><div class="footer-grid">' +
      '<div class="footer-brand">' + KR.logo("light") +
      "<p>Huur alles voor je feest, evenement of klus. Eenvoudig online reserveren, wij regelen de rest.</p></div>" +
      "<div><h4>Huurgroepen</h4><ul>" + groups + "</ul></div>" +
      "<div><h4>Contact</h4><ul>" +
      '<li><a href="tel:' + S.phoneHref + '">' + U.esc(S.phone) + "</a></li>" +
      '<li><a href="mailto:' + S.email + '">' + U.esc(S.email) + "</a></li>" +
      "<li>" + U.esc(S.region) + "</li></ul></div>" +
      '</div><div class="footer-bottom"><span>© ' + new Date().getFullYear() + " " + U.esc(S.companyName) +
      "</span><span>Alle prijzen incl. btw</span></div></div>";
  }

  /* ---------- building blocks ---------- */
  function groupCard(g) {
    const ext = !!g.externalUrl;
    const from = U.fromPrice(g);
    return (
      '<a class="group-card' + (ext ? " is-external" : "") + '" href="' + U.groupUrl(g) + '"' +
      (ext ? ' target="_blank" rel="noopener"' : "") + ">" +
      (ext ? '<span class="badge">' + icon("external") + " Goboony</span>" : "") +
      '<div class="group-icon">' + icon(g.icon) + "</div>" +
      "<h3>" + U.esc(g.name) + "</h3>" +
      "<p>" + U.esc(g.tagline) + "</p>" +
      '<span class="more">' +
      (ext ? "Bekijk op Goboony " + icon("external") : (from != null ? "Vanaf " + U.euro(from) + " p/d " : "Bekijken ") + icon("arrow")) +
      "</span></a>"
    );
  }

  function productMedia(p, g) {
    return (
      '<div class="product-media">' +
      (p.image ? '<img src="' + U.esc(p.image) + '" alt="' + U.esc(p.name) + '" loading="lazy">' : icon(g.icon)) +
      '<span class="badge">' + U.esc(g.name) + "</span></div>"
    );
  }

  function productCard(p) {
    const g = U.group(p.group);
    return (
      '<a class="product-card" href="' + U.productUrl(p) + '">' + productMedia(p, g) +
      '<div class="product-body"><h3>' + U.esc(p.name) + "</h3><p>" + U.esc(p.description) + "</p>" +
      '<div class="product-foot"><span class="price">' + U.euro(p.pricePerDay) + " <small>/ dag</small></span>" +
      '<span class="btn btn-outline">Reserveren</span></div></div></a>'
    );
  }

  /* ---------- pages ---------- */
  function pageHome() {
    const grid = document.getElementById("group-grid");
    if (grid) grid.innerHTML = KR.groups.map(groupCard).join("");
    const mark = document.getElementById("hero-mark");
    if (mark) mark.innerHTML = KR.mark();
    const c = document.getElementById("contact-items");
    if (c)
      c.innerHTML =
        '<div class="contact-item">' + icon("phone") + '<div>Bel of app ons<br><a href="tel:' + S.phoneHref + '">' + U.esc(S.phone) + "</a></div></div>" +
        '<div class="contact-item">' + icon("mail") + '<div>Mail ons<br><a href="mailto:' + S.email + '">' + U.esc(S.email) + "</a></div></div>" +
        '<div class="contact-item">' + icon("pin") + "<div>Werkgebied<br><strong>" + U.esc(S.region) + "</strong></div></div>";
    const usps = document.getElementById("hero-usps");
    if (usps)
      usps.innerHTML = ["Online reserveren via de kalender", "1 of meerdere dagen huren", "Halen & brengen mogelijk"]
        .map((t) => "<li>" + icon("check") + t + "</li>")
        .join("");
  }

  function pageCatalog() {
    const gid = U.param("groep");
    const g = gid ? U.group(gid) : null;
    const title = document.getElementById("catalog-title");
    const intro = document.getElementById("catalog-intro");
    const crumbs = document.getElementById("catalog-crumbs");
    if (g) {
      document.title = g.name + " huren | KR Verhuur";
      title.textContent = g.name + " huren";
      intro.textContent = g.tagline + " Kies een product, selecteer je huurdagen en reserveer direct.";
      crumbs.innerHTML = '<a href="index.html">Home</a> / <a href="huren.html">Assortiment</a> / ' + U.esc(g.name);
    }

    const chips = document.getElementById("filter-bar");
    chips.innerHTML =
      '<a class="chip' + (!g ? " active" : "") + '" href="huren.html">Alles</a>' +
      KR.groups
        .map((x) => {
          const ext = !!x.externalUrl;
          return (
            '<a class="chip' + (g && g.id === x.id ? " active" : "") + '" href="' + U.groupUrl(x) + '"' +
            (ext ? ' target="_blank" rel="noopener"' : "") + ">" + icon(x.icon) + U.esc(x.name) + (ext ? " " + icon("external") : "") + "</a>"
          );
        })
        .join("");

    const list = document.getElementById("product-grid");
    if (g && g.externalUrl) {
      list.outerHTML =
        '<div class="external-panel"><div><h2>' + U.esc(g.name) + "</h2><p>" + U.esc(g.tagline) +
        '</p></div><a class="btn btn-primary" href="' + U.groupUrl(g) + '" target="_blank" rel="noopener">Naar Goboony ' + icon("external") + "</a></div>";
      return;
    }
    const items = KR.products.filter((p) => !g || p.group === g.id);
    list.innerHTML = items.length
      ? items.map(productCard).join("")
      : '<div class="empty-state">Er zijn nog geen producten in deze groep. Neem gerust contact op!</div>';
  }

  function pageProduct() {
    const p = U.product(U.param("id"));
    const root = document.getElementById("product-root");
    if (!p) {
      root.innerHTML = '<div class="card empty-state"><h2>Product niet gevonden</h2><p><a href="huren.html">Terug naar het assortiment</a></p></div>';
      return;
    }
    const g = U.group(p.group);
    document.title = p.name + " huren | KR Verhuur";
    document.getElementById("product-title").textContent = p.name;
    document.getElementById("product-intro").textContent = g.tagline;
    document.getElementById("product-crumbs").innerHTML =
      '<a href="index.html">Home</a> / <a href="huren.html">Assortiment</a> / <a href="' + U.groupUrl(g) + '">' + U.esc(g.name) + "</a> / " + U.esc(p.name);

    const extraDay = p.pricePerExtraDay != null ? p.pricePerExtraDay : p.pricePerDay;
    root.innerHTML =
      '<div class="detail"><div>' +
      '<div class="card"><div class="detail-media">' + productMedia(p, g) + "</div>" +
      "<h2>" + U.esc(p.name) + "</h2><p>" + U.esc(p.description) + "</p>" +
      '<ul class="feature-list">' + (p.features || []).map((f) => "<li>" + icon("check") + U.esc(f) + "</li>").join("") + "</ul></div>" +
      '<div class="card" style="margin-top:22px"><h3>Tarieven</h3><table class="price-table">' +
      "<tr><td>Eerste dag</td><td>" + U.euro(p.pricePerDay) + "</td></tr>" +
      "<tr><td>Elke extra dag</td><td>" + U.euro(extraDay) + "</td></tr>" +
      (p.deposit ? "<tr><td>Borg</td><td>" + U.euro(p.deposit) + "</td></tr>" : "") +
      "<tr><td>Maximale huurperiode</td><td>" + p.maxDays + " dagen</td></tr>" +
      "</table></div></div>" +
      '<aside class="card booking" id="booking"></aside></div>';

    KR.booking.mount(document.getElementById("booking"), p);
  }

  document.addEventListener("DOMContentLoaded", () => {
    renderHeader();
    renderFooter();
    const page = document.body.dataset.page;
    if (page === "home") pageHome();
    if (page === "huren") pageCatalog();
    if (page === "product") pageProduct();
  });
})();
