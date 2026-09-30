/*
 * Fotoslider op de artikelpagina: pijltjes, miniaturen, vegen op mobiel,
 * pijltjestoetsen en een vergroting op volledig scherm.
 */
(function () {
  function initGallery(root) {
    const slides = [...root.querySelectorAll(".gallery-slide")];
    const thumbs = [...root.querySelectorAll(".gallery-thumb")];
    const current = root.querySelector("[data-gallery-current]");
    let index = 0;

    function go(i) {
      if (!slides.length) return;
      index = (i + slides.length) % slides.length;
      slides.forEach((s, n) => s.classList.toggle("is-active", n === index));
      thumbs.forEach((t, n) => {
        t.classList.toggle("is-active", n === index);
        t.setAttribute("aria-current", n === index ? "true" : "false");
      });
      if (current) current.textContent = index + 1;
    }

    root.addEventListener("click", (e) => {
      if (e.target.closest(".gallery-nav.prev")) return go(index - 1);
      if (e.target.closest(".gallery-nav.next")) return go(index + 1);
      const t = e.target.closest("[data-go]");
      if (t) return go(Number(t.dataset.go));
      if (e.target.closest(".gallery-zoom") || e.target.closest(".gallery-slide")) openLightbox(slides, index, go);
    });

    // Vegen op touchscreens.
    const main = root.querySelector(".gallery-main");
    let x0 = null;
    main.addEventListener("touchstart", (e) => (x0 = e.touches[0].clientX), { passive: true });
    main.addEventListener("touchend", (e) => {
      if (x0 === null) return;
      const dx = e.changedTouches[0].clientX - x0;
      if (Math.abs(dx) > 40) go(index + (dx < 0 ? 1 : -1));
      x0 = null;
    });

    // Pijltjestoetsen als de slider focus heeft.
    root.tabIndex = -1;
    root.addEventListener("keydown", (e) => {
      if (e.key === "ArrowLeft") go(index - 1);
      if (e.key === "ArrowRight") go(index + 1);
    });
  }

  /* Vergroting op volledig scherm. */
  function openLightbox(slides, start, syncGallery) {
    let i = start;
    const box = document.createElement("div");
    box.className = "lightbox";
    box.setAttribute("role", "dialog");
    box.setAttribute("aria-modal", "true");
    box.setAttribute("aria-label", "Foto vergroot");
    const multi = slides.length > 1;
    box.innerHTML =
      '<button type="button" class="lightbox-close" aria-label="Sluiten">×</button>' +
      (multi ? '<button type="button" class="lightbox-nav prev" aria-label="Vorige foto">‹</button><button type="button" class="lightbox-nav next" aria-label="Volgende foto">›</button>' : "") +
      '<img alt="">' +
      (multi ? '<span class="lightbox-count"></span>' : "");
    const img = box.querySelector("img");
    const count = box.querySelector(".lightbox-count");

    function show(n) {
      i = (n + slides.length) % slides.length;
      const s = slides[i];
      const small = s.querySelector("img");
      img.src = s.dataset.full || (small && small.currentSrc) || "";
      img.alt = (small && small.alt) || "";
      if (count) count.textContent = i + 1 + " / " + slides.length;
      syncGallery(i);
    }
    function close() {
      document.removeEventListener("keydown", onKey);
      document.body.classList.remove("lightbox-open");
      box.remove();
    }
    function onKey(e) {
      if (e.key === "Escape") close();
      if (e.key === "ArrowLeft") show(i - 1);
      if (e.key === "ArrowRight") show(i + 1);
    }

    box.addEventListener("click", (e) => {
      if (e.target.closest(".lightbox-nav.prev")) return show(i - 1);
      if (e.target.closest(".lightbox-nav.next")) return show(i + 1);
      if (e.target === box || e.target.closest(".lightbox-close")) close();
    });
    let x0 = null;
    box.addEventListener("touchstart", (e) => (x0 = e.touches[0].clientX), { passive: true });
    box.addEventListener("touchend", (e) => {
      if (x0 === null) return;
      const dx = e.changedTouches[0].clientX - x0;
      if (Math.abs(dx) > 40) show(i + (dx < 0 ? 1 : -1));
      x0 = null;
    });

    document.addEventListener("keydown", onKey);
    document.body.appendChild(box);
    document.body.classList.add("lightbox-open");
    show(i);
    box.querySelector(".lightbox-close").focus();
  }

  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[data-gallery]").forEach(initGallery);
  });
})();
