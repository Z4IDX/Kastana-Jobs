/* Kastana Admin — confirm destructive actions before submit. */
(function () {
  'use strict';
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.dataset.confirm)) {
        e.preventDefault();
      }
    });
  });

  /* ---------- Live mini-preview on the Customize page ---------- */
  var bp = document.getElementById('brand-preview');
  if (bp) {
    var q = function (n) { return document.querySelector('[name="' + n + '"]'); };
    var nameI = q('brand_name'), heroI = q('hero_title'),
        pcI = q('primary_color'), hlI = q('highlight_color'),
        heroTheme = q('hero_theme'), fontTheme = q('font_theme');
    var nameEl = bp.querySelector('.bp-name'),
        heroEl = bp.querySelector('.bp-hero-title'),
        logoEl = bp.querySelector('.bp-logo');
    var hexOk = function (v) { return /^#[0-9a-fA-F]{6}$/.test(v); };
    var fonts = {
      default: "'Fraunces', Georgia, serif",
      modern: "'Space Grotesk', system-ui, sans-serif",
      editorial: "'Playfair Display', Georgia, serif",
      minimal: "'Inter', system-ui, sans-serif"
    };

    var swatchFor = function (input) {
      return input && input.parentElement
        ? input.parentElement.querySelector('span[aria-hidden]') : null;
    };

    function update() {
      var name = (nameI && (nameI.value.trim() || nameI.getAttribute('placeholder'))) || 'Your brand';
      if (nameEl) nameEl.textContent = name;
      if (logoEl) logoEl.textContent = name.charAt(0).toUpperCase();
      if (heroEl) heroEl.textContent = (heroI && heroI.value.trim()) || 'Work worth chasing.';

      if (pcI && hexOk(pcI.value)) { bp.style.setProperty('--pc', pcI.value); var s1 = swatchFor(pcI); if (s1) s1.style.background = pcI.value; }
      if (hlI && hexOk(hlI.value)) { bp.style.setProperty('--hl', hlI.value); var s2 = swatchFor(hlI); if (s2) s2.style.background = hlI.value; }

      if (heroTheme) bp.classList.toggle('bp--light', heroTheme.value === 'light');
      if (fontTheme) bp.style.setProperty('--bp-font', fonts[fontTheme.value] || fonts.default);
    }

    [nameI, heroI, pcI, hlI].forEach(function (el) { if (el) el.addEventListener('input', update); });
    [heroTheme, fontTheme].forEach(function (el) { if (el) el.addEventListener('change', update); });
    update();
  }
})();
