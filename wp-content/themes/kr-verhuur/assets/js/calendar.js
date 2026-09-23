/*
 * Kalender met periodeselectie (1 of meerdere dagen).
 *
 *   const cal = KR.Calendar(el, {
 *     isBooked(iso) → true/false,  // dag (voor het gekozen aantal) niet beschikbaar
 *     start, end,                  // optioneel: vooraf geselecteerde periode ("YYYY-MM-DD")
 *     minDate, maxDate,            // "YYYY-MM-DD"
 *     today,                       // "YYYY-MM-DD" (tijdzone van de site)
 *     maxDays: 7,                  // maximale lengte van een periode
 *     onChange({ start, end, days })  // start/end als "YYYY-MM-DD" (of null)
 *   });
 *   cal.refresh();                 // opnieuw tekenen na wijziging beschikbaarheid/aantal
 */
window.KR = window.KR || {};

KR.dates = {
  iso(d) {
    return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0");
  },
  parse(s) {
    const [y, m, d] = s.split("-").map(Number);
    return new Date(y, m - 1, d);
  },
  addDays(d, n) {
    const r = new Date(d.getFullYear(), d.getMonth(), d.getDate());
    r.setDate(r.getDate() + n);
    return r;
  },
  diffDays(a, b) {
    return Math.round((KR.dates.parse(b) - KR.dates.parse(a)) / 86400000);
  },
  pretty(s) {
    return KR.dates.parse(s).toLocaleDateString("nl-NL", { weekday: "short", day: "numeric", month: "long", year: "numeric" });
  },
};

KR.Calendar = function (el, opts) {
  const D = KR.dates;
  const icons = opts.icons || {};
  const isBooked = opts.isBooked || (() => false);
  const todayIso = opts.today || D.iso(new Date());
  const minIso = opts.minDate || todayIso;
  const maxIso = opts.maxDate || D.iso(D.addDays(D.parse(todayIso), 365));
  const maxDays = opts.maxDays || 365;

  let start = opts.start || null;
  let end = opts.start ? opts.end || opts.start : null;
  let view = D.parse(start && start >= minIso ? start : minIso);
  view = new Date(view.getFullYear(), view.getMonth(), 1);
  let message = "";

  const available = (iso) => iso >= minIso && iso <= maxIso && !isBooked(iso);

  // Is de hele periode vrij?
  function rangeFree(a, b) {
    for (let d = D.parse(a); D.iso(d) <= b; d = D.addDays(d, 1)) {
      if (!available(D.iso(d))) return false;
    }
    return true;
  }

  function select(iso) {
    message = "";
    if (!start || end) {
      // Nieuwe periode beginnen
      start = iso;
      end = null;
    } else if (iso === start) {
      end = iso; // één dag
    } else if (iso < start) {
      start = iso;
    } else {
      const len = D.diffDays(start, iso) + 1;
      if (len > maxDays) {
        message = "Maximaal " + maxDays + " dagen per reservering.";
      } else if (!rangeFree(start, iso)) {
        message = "Er zit een bezette dag in deze periode. Kies een andere periode.";
        start = iso;
      } else {
        end = iso;
      }
    }
    render();
    emit();
  }

  function emit() {
    // Zonder einddatum geldt de selectie als 1 dag, zodat 1-daagse huur met één klik kan.
    const e = end || start;
    opts.onChange &&
      opts.onChange({ start, end: e, days: start ? D.diffDays(start, e) + 1 : 0, complete: !!start });
  }

  function render() {
    const y = view.getFullYear();
    const m = view.getMonth();
    const first = new Date(y, m, 1);
    const offset = (first.getDay() + 6) % 7; // maandag = 0
    const daysInMonth = new Date(y, m + 1, 0).getDate();
    const title = first.toLocaleDateString("nl-NL", { month: "long", year: "numeric" });
    const canPrev = D.iso(new Date(y, m, 0)) >= minIso;
    const canNext = D.iso(new Date(y, m + 1, 1)) <= maxIso;
    const rangeEnd = end || start;

    let cells = "";
    ["ma", "di", "wo", "do", "vr", "za", "zo"].forEach((d) => (cells += '<div class="cal-dow">' + d + "</div>"));
    for (let i = 0; i < offset; i++) cells += '<span class="cal-day empty"></span>';
    for (let day = 1; day <= daysInMonth; day++) {
      const iso = D.iso(new Date(y, m, day));
      const cls = ["cal-day"];
      const booked = iso >= minIso && iso <= maxIso && isBooked(iso);
      if (booked) cls.push("booked");
      if (iso === todayIso) cls.push("today");
      if (start && iso === start) cls.push("start");
      if (rangeEnd && iso === rangeEnd) cls.push("end");
      if (start && rangeEnd && iso > start && iso < rangeEnd) cls.push("in-range");
      const dis = !available(iso);
      cells +=
        '<button type="button" class="' + cls.join(" ") + '" data-date="' + iso + '"' + (dis ? " disabled" : "") +
        ' aria-label="' + D.pretty(iso) + (booked ? " (bezet)" : "") + '"' +
        (start && iso >= start && iso <= rangeEnd ? ' aria-pressed="true"' : "") + ">" + day + "</button>";
    }

    let hint;
    if (message) hint = '<span style="color:var(--danger)">' + message + "</span>";
    else if (!start) hint = "Kies je <strong>eerste huurdag</strong>.";
    else if (!end) hint = "<strong>" + D.pretty(start) + "</strong> – klik op een einddatum voor meerdere dagen.";
    else {
      const n = D.diffDays(start, end) + 1;
      hint = "<strong>" + D.pretty(start) + (n > 1 ? " t/m " + D.pretty(end) : "") + "</strong> (" + n + (n === 1 ? " dag" : " dagen") + ")";
    }

    el.innerHTML =
      '<div class="cal"><div class="cal-head"><span class="cal-title">' + title + "</span>" +
      '<div class="cal-nav"><button type="button" data-nav="-1" aria-label="Vorige maand"' + (canPrev ? "" : " disabled") + ">" + (icons.chevronLeft || "‹") + "</button>" +
      '<button type="button" data-nav="1" aria-label="Volgende maand"' + (canNext ? "" : " disabled") + ">" + (icons.chevronRight || "›") + "</button></div></div>" +
      '<div class="cal-grid">' + cells + "</div>" +
      '<div class="cal-legend"><span><i style="background:var(--green)"></i>Geselecteerd</span>' +
      '<span><i style="background:#fbf1f0;border:1px solid #e8c9c6"></i>Bezet</span>' +
      '<span><i style="background:var(--bg);border:1px solid var(--line)"></i>Beschikbaar</span></div>' +
      '<div class="cal-hint" aria-live="polite">' + hint + "</div></div>";
  }

  el.addEventListener("click", (e) => {
    const nav = e.target.closest("[data-nav]");
    if (nav && !nav.disabled) {
      view = new Date(view.getFullYear(), view.getMonth() + Number(nav.dataset.nav), 1);
      render();
      return;
    }
    const day = e.target.closest("[data-date]");
    if (day && !day.disabled) select(day.dataset.date);
  });

  render();

  return {
    // Na een wijziging in beschikbaarheid: selectie wissen als die niet meer vrij is.
    refresh() {
      if (start && !rangeFree(start, end || start)) {
        start = end = null;
        message = "Je gekozen periode is niet meer (voor dit aantal) beschikbaar.";
        render();
        emit();
        return;
      }
      render();
    },
    reset() {
      start = end = null;
      message = "";
      render();
      emit();
    },
  };
};
