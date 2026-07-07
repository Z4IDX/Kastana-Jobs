/* Kastana Jobs — public interactions
   Progressive enhancement only: the site works fully without JS. */
(function () {
  'use strict';

  /* ---------- Scroll reveal ---------- */
  const reveals = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && reveals.length) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    reveals.forEach((el, i) => {
      el.style.transitionDelay = Math.min(i * 60, 300) + 'ms';
      io.observe(el);
    });
  } else {
    reveals.forEach((el) => el.classList.add('is-visible'));
  }

  /* ---------- Sort auto-submit (works fine without JS too) ---------- */
  const sortSelect = document.getElementById('sort-select');
  if (sortSelect && sortSelect.form) {
    sortSelect.form.classList.add('js-enhanced'); // hides the now-redundant Apply button
    sortSelect.addEventListener('change', () => sortSelect.form.submit());
  }

  /* ---------- Copy apply link ---------- */
  const copyBtn = document.getElementById('copy-apply-link');
  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      const url = copyBtn.dataset.copyUrl;
      const done = () => {
        copyBtn.textContent = copyBtn.dataset.labelDone;
        setTimeout(() => { copyBtn.textContent = copyBtn.dataset.label; }, 1600);
      };
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(url).then(done).catch(() => fallbackCopy(url, done));
      } else {
        fallbackCopy(url, done);
      }
    });
  }

  function fallbackCopy(text, done) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.focus();
    ta.select();
    try { document.execCommand('copy'); done(); } catch (e) { /* no-op */ }
    document.body.removeChild(ta);
  }
})();
