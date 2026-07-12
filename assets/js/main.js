/* Kastana Jobs — public interactions
   Progressive enhancement only: the site works fully without JS. */
(function () {
  'use strict';

  /* ---------- Dark-mode toggle (persisted in a cookie, read server-side) ---------- */
  var themeBtn = document.querySelector('[data-theme-toggle]');
  if (themeBtn) {
    themeBtn.addEventListener('click', function () {
      var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      var next = isDark ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      try { document.cookie = 'theme=' + next + ';path=/;max-age=31536000;samesite=strict'; } catch (e) { /* no-op */ }
    });
  }

  /* ---------- Confirm destructive actions before submit ---------- */
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.dataset.confirm)) e.preventDefault();
    });
  });

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
    filterForm.querySelectorAll('select, input[type="checkbox"]').forEach(function (ctrl) {
      ctrl.addEventListener('change', function () { filterForm.submit(); });
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

  /* ---------- Rotating hero ticker (tips + facts + stat) ---------- */
  var ticker = document.querySelector('[data-ticker]');
  if (ticker) {
    var tItems = Array.prototype.slice.call(ticker.querySelectorAll('[data-ticker-item]'));
    if (tItems.length > 1) {
      var tIdx = 0;
      var tPaused = false;
      var tReduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      var tFade = tReduce ? 0 : 450;

      var tAdvance = function () {
        if (tPaused) return;
        var cur = tItems[tIdx];
        var nIdx = tIdx + 1 >= tItems.length ? 0 : tIdx + 1;
        var nxt = tItems[nIdx];
        cur.classList.add('is-fading');
        setTimeout(function () {
          cur.classList.remove('is-active', 'is-fading');
          cur.hidden = true;
          cur.setAttribute('aria-hidden', 'true');
          nxt.hidden = false;
          nxt.setAttribute('aria-hidden', 'false');
          nxt.classList.add('is-fading');
          requestAnimationFrame(function () {
            requestAnimationFrame(function () {
              nxt.classList.remove('is-fading');
              nxt.classList.add('is-active');
            });
          });
          tIdx = nIdx;
        }, tFade);
      };

      setInterval(tAdvance, 6000);
      ticker.addEventListener('mouseenter', function () { tPaused = true; });
      ticker.addEventListener('mouseleave', function () { tPaused = false; });
      ticker.addEventListener('focusin', function () { tPaused = true; });
      ticker.addEventListener('focusout', function () { tPaused = false; });
    }
  }

  /* ---------- Save/bookmark without a page reload (progressive enhancement) ---------- */
  document.querySelectorAll('.save-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.fetch) return; // old browsers: normal POST + redirect
      e.preventDefault();
      var btn = form.querySelector('.save-btn');
      var actionInput = form.querySelector('input[name="save_action"]');
      fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form)
      }).then(function (r) { return r.json(); }).then(function (data) {
        if (!data || !data.ok) { form.submit(); return; }
        btn.classList.toggle('is-saved', data.saved);
        btn.setAttribute('aria-pressed', data.saved ? 'true' : 'false');
        btn.setAttribute('aria-label', data.saved ? btn.dataset.labelUnsave : btn.dataset.labelSave);
        var stext = btn.querySelector('[data-save-text]');
        if (stext && btn.dataset.textSave) stext.textContent = data.saved ? btn.dataset.textUnsave : btn.dataset.textSave;
        if (actionInput) actionInput.value = data.saved ? 'unsave' : 'save';
        document.querySelectorAll('[data-saved-count]').forEach(function (el) {
          el.hidden = data.count <= 0;
          el.textContent = el.classList.contains('nav-count') ? '(' + data.count + ')' : String(data.count);
        });
        showToast(data.saved ? (document.body.dataset.tSaved || 'Saved') : (document.body.dataset.tRemoved || 'Removed'), data.saved ? 'success' : 'info');
      }).catch(function () { form.submit(); });
    });
  });

  /* ---------- Password show/hide toggle ---------- */
  var eyeIcons = '<svg class="pw-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>'
    + '<svg class="pw-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18M10.6 10.6a3 3 0 0 0 4.2 4.2M9.9 5.1A9.5 9.5 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3 3.8M6.1 6.1A17 17 0 0 0 2 12s3.5 7 10 7a9.5 9.5 0 0 0 3-.5"/></svg>';
  document.querySelectorAll('.field input[type="password"]').forEach(function (input) {
    var wrap = document.createElement('span');
    wrap.className = 'pw-field';
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(input);
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'pw-toggle';
    btn.setAttribute('aria-label', document.body.dataset.tShowpw || 'Show password');
    btn.innerHTML = eyeIcons;
    wrap.appendChild(btn);
    btn.addEventListener('click', function () {
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.classList.toggle('is-visible', show);
      btn.setAttribute('aria-label', show ? (document.body.dataset.tHidepw || 'Hide password') : (document.body.dataset.tShowpw || 'Show password'));
    });
  });

  /* ---------- Back-to-top button ---------- */
  var toTop = document.createElement('button');
  toTop.type = 'button';
  toTop.className = 'to-top';
  toTop.setAttribute('aria-label', document.body.dataset.tBack || 'Back to top');
  toTop.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M6 11l6-6 6 6"/></svg>';
  document.body.appendChild(toTop);
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var toggleToTop = function () { toTop.classList.toggle('is-visible', window.scrollY > 600); };
  window.addEventListener('scroll', toggleToTop, { passive: true });
  toggleToTop();
  toTop.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
  });

  /* ---------- Press "/" to focus search ---------- */
  document.addEventListener('keydown', function (e) {
    if (e.key !== '/' || e.metaKey || e.ctrlKey || e.altKey) return;
    var el = document.activeElement, tag = el && el.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || (el && el.isContentEditable)) return;
    var search = document.querySelector('input[type="search"]');
    if (search) { e.preventDefault(); search.focus(); }
  });

  /* ---------- Lightweight toast helper (reuses the toast styles) ---------- */
  function showToast(msg, kind) {
    var stack = document.getElementById('toast-stack');
    if (!stack) {
      stack = document.createElement('div');
      stack.id = 'toast-stack';
      stack.className = 'toast-stack';
      document.body.appendChild(stack);
    }
    var el = document.createElement('div');
    el.className = 'toast toast--' + (kind || 'success');
    el.setAttribute('role', 'status');
    var p = document.createElement('p');
    p.textContent = msg;
    el.appendChild(p);
    stack.appendChild(el);
    setTimeout(function () {
      el.classList.add('is-leaving');
      setTimeout(function () { el.remove(); }, 250);
    }, 2200);
  }

  /* ---------- Native share (mobile OS share sheet); hidden where unsupported ---------- */
  document.querySelectorAll('[data-web-share]').forEach(function (btn) {
    if (!navigator.share) return;
    btn.style.display = '';
    btn.addEventListener('click', function () {
      navigator.share({ title: btn.dataset.shareTitle, url: btn.dataset.shareUrl }).catch(function () {});
    });
  });

  /* ---------- Warn before leaving a form with unsaved edits ---------- */
  document.querySelectorAll('form[data-dirty-guard]').forEach(function (form) {
    var dirty = false;
    form.addEventListener('input', function () { dirty = true; });
    form.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) {
      if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });
  });

  /* ---------- Live character counters on textareas ---------- */
  document.querySelectorAll('.field textarea').forEach(function (ta) {
    var min = parseInt(ta.getAttribute('data-min') || '0', 10);
    var counter = document.createElement('span');
    counter.className = 'char-counter';
    ta.parentNode.appendChild(counter);
    var update = function () {
      var n = ta.value.length;
      counter.textContent = min ? (n + ' / ' + min) : String(n);
      counter.classList.toggle('is-short', min > 0 && n < min);
    };
    ta.addEventListener('input', update);
    update();
  });

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
