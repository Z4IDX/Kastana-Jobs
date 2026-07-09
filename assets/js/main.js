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

  /* ---------- Filter/sort auto-submit (works fine without JS too) ---------- */
  const filterForm = document.getElementById('filter-form');
  if (filterForm) {
    filterForm.classList.add('js-enhanced'); // hides the now-redundant Apply button
    filterForm.querySelectorAll('select').forEach(function (sel) {
      sel.addEventListener('change', function () { filterForm.submit(); });
    });
  }

  /* ---------- Notification toasts (dismiss + auto-hide) ---------- */
  var toasts = document.querySelectorAll('#toast-stack .toast');
  toasts.forEach(function (toast) {
    var dismiss = function () {
      toast.classList.add('is-leaving');
      setTimeout(function () { toast.remove(); }, 300);
    };
    var closeBtn = toast.querySelector('.toast__close');
    if (closeBtn) closeBtn.addEventListener('click', dismiss);
    setTimeout(dismiss, 8000); // auto-hide after 8s
  });

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
