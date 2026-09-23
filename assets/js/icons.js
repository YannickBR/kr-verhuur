/* Eenvoudige lijn-iconen (24x24, stroke = currentColor). */
window.KR = window.KR || {};

KR.icons = (function () {
  const paths = {
    camper: '<path d="M2 17V7a2 2 0 0 1 2-2h11l5 5v7"/><path d="M2 17h20"/><circle cx="7" cy="17.5" r="2"/><circle cx="17" cy="17.5" r="2"/><path d="M5 9h4v3H5zM12 9h3"/>',
    camera: '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7l1.5-3h5L16 7"/><circle cx="12" cy="13.5" r="3.5"/>',
    toilet: '<path d="M6 3h7v7H6z"/><path d="M4 10h14a0 0 0 0 1 0 0 7 7 0 0 1-7 7H10a6 6 0 0 1-6-6z"/><path d="M9 17l-1 4h7l-1-4"/>',
    castle: '<path d="M3 21V9l3 2V6l3 2 3-4 3 4 3-2v5l3-2v12z"/><path d="M10 21v-5a2 2 0 0 1 4 0v5"/>',
    bumper: '<path d="M4 15l1.5-5A2 2 0 0 1 7.4 8.5h9.2a2 2 0 0 1 1.9 1.5L20 15"/><rect x="2" y="15" width="20" height="4" rx="2"/><path d="M12 8.5V4M10 4h4"/>',
    chair: '<path d="M7 3h10v8H7z"/><path d="M5 11h14v3H5z"/><path d="M7 14v7M17 14v7"/>',
    flame: '<path d="M12 22a7 7 0 0 0 7-7c0-4-3-6-4-10-2 2-3 4-3 6-1-1-2-2-2-4-3 3-5 5-5 8a7 7 0 0 0 7 7z"/>',
    tool: '<path d="M14.7 6.3a4 4 0 0 0 5 5L21 13l-8 8-3-3 8-8-1.3-1.3a4 4 0 0 1-5-5L13 3z"/><path d="M3 21l6-6"/>',
    calendar: '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
    truck: '<path d="M2 6h12v10H2zM14 10h4l3 3v3h-7z"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
    check: '<path d="M4 12l5 5L20 6"/>',
    sparkle: '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5L18 18M6 18l2.5-2.5M15.5 8.5L18 6"/>',
    phone: '<path d="M5 3h4l2 5-3 2a11 11 0 0 0 6 6l2-3 5 2v4a2 2 0 0 1-2 2A18 18 0 0 1 3 5a2 2 0 0 1 2-2z"/>',
    mail: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
    pin: '<path d="M12 22s7-7 7-12a7 7 0 0 0-14 0c0 5 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/>',
    arrow: '<path d="M5 12h14M13 6l6 6-6 6"/>',
    external: '<path d="M14 4h6v6M20 4l-9 9"/><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
    chevronLeft: '<path d="M15 5l-7 7 7 7"/>',
    chevronRight: '<path d="M9 5l7 7-7 7"/>',
    menu: '<path d="M4 7h16M4 12h16M4 17h16"/>',
    close: '<path d="M6 6l12 12M18 6L6 18"/>',
  };
  return function icon(name, cls) {
    return '<svg class="icon ' + (cls || "") + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (paths[name] || "") + "</svg>";
  };
})();
