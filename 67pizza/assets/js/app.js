/* 67pizza – frontend behaviour (progressive enhancement; all actions also work without JS) */
(function () {
  'use strict';
  const BASE = document.querySelector('meta[name="base-url"]')?.content || '';
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

  /* ---------- Toasts ---------- */
  window.showToast = function (message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container || typeof bootstrap === 'undefined') { alert(message); return; }
    const icon = type === 'success' ? 'bi-check-circle-fill text-success' : type === 'danger' ? 'bi-exclamation-triangle-fill text-danger' : 'bi-info-circle-fill text-brand';
    const el = document.createElement('div');
    el.className = `toast toast-${type} align-items-center`;
    el.setAttribute('role', 'alert');
    el.innerHTML = `<div class="d-flex"><div class="toast-body d-flex align-items-center gap-2"><i class="bi ${icon}"></i><span>${escapeHtml(message)}</span></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
    container.appendChild(el);
    const t = new bootstrap.Toast(el, { delay: 3200 });
    t.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
  };
  function escapeHtml(s) { return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }

  /* ---------- AJAX helper ---------- */
  async function post(action, data) {
    const body = new URLSearchParams({ csrf_token: CSRF, ...data });
    const res = await fetch(`${BASE}/actions/${action}`, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': CSRF, 'Accept': 'application/json' },
      body,
    });
    let json;
    try { json = await res.json(); } catch (e) { json = { success: false, message: 'Unexpected server response.' }; }
    return json;
  }

  function updateCartCount(n) {
    document.querySelectorAll('.js-cart-count').forEach(el => {
      el.textContent = n;
      el.classList.toggle('d-none', !n);
    });
  }

  /* ---------- Add to cart ---------- */
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.js-add-cart');
    if (!btn) return;
    e.preventDefault();
    const id = btn.dataset.id;
    const qty = btn.dataset.qty || document.querySelector('#qtyInput')?.value || 1;
    btn.classList.add('loading');
    const original = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    const r = await post('cart.php', { action: 'add', product_id: id, quantity: qty });
    btn.classList.remove('loading');
    btn.innerHTML = original;
    if (r.success) {
      updateCartCount(r.cart_count);
      showToast(r.message, 'success');
    } else if (r.login) {
      window.location.href = `${BASE}/login.php`;
    } else {
      showToast(r.message || 'Could not add to cart.', 'danger');
    }
  });

  /* ---------- Favorite toggle ---------- */
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.js-fav');
    if (!btn) return;
    e.preventDefault();
    const r = await post('favorite.php', { product_id: btn.dataset.id });
    if (r.login) { window.location.href = `${BASE}/login.php`; return; }
    if (!r.success) { showToast(r.message || 'Error', 'danger'); return; }
    btn.classList.toggle('active', r.favorited);
    btn.querySelector('i').className = r.favorited ? 'bi bi-heart-fill' : 'bi bi-heart';
    showToast(r.message, r.favorited ? 'success' : 'info');
    if (!r.favorited && btn.closest('.js-fav-page')) {
      const card = btn.closest('.product-card');
      card.style.transition = 'opacity .3s'; card.style.opacity = '0';
      setTimeout(() => { card.remove(); if (!document.querySelector('.product-card')) window.location.reload(); }, 300);
    }
  });

  /* ---------- Cart page quantity controls ---------- */
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.js-qty');
    if (!btn) return;
    e.preventDefault();
    const wrap = btn.closest('.qty-control');
    const input = wrap.querySelector('input');
    let v = parseInt(input.value, 10) || 1;
    v += btn.dataset.dir === 'up' ? 1 : -1;
    if (v < 1) v = 1; if (v > 50) v = 50;
    input.value = v;
    if (wrap.dataset.live === '1') {
      const r = await post('cart.php', { action: 'update', product_id: wrap.dataset.id, quantity: v });
      if (r.success) { window.location.reload(); } else { showToast(r.message, 'danger'); }
    }
  });
  document.querySelectorAll('.qty-control[data-live="1"] input').forEach(inp => {
    inp.addEventListener('change', async () => {
      const r = await post('cart.php', { action: 'update', product_id: inp.closest('.qty-control').dataset.id, quantity: inp.value });
      if (r.success) window.location.reload(); else showToast(r.message, 'danger');
    });
  });

  /* ---------- Confirm dialogs ---------- */
  document.addEventListener('submit', (e) => {
    const form = e.target.closest('form[data-confirm]');
    if (form && !confirm(form.dataset.confirm)) e.preventDefault();
  });

  /* ---------- Bootstrap client validation ---------- */
  document.querySelectorAll('form.needs-validation').forEach(form => {
    form.addEventListener('submit', (e) => {
      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    });
  });

  /* ---------- Payment option highlight ---------- */
  document.querySelectorAll('.payment-option input').forEach(r => {
    const sync = () => document.querySelectorAll('.payment-option').forEach(l => l.classList.toggle('selected', l.querySelector('input').checked));
    r.addEventListener('change', () => { sync(); document.querySelectorAll('[data-payment-info]').forEach(d => d.classList.toggle('d-none', d.dataset.paymentInfo !== r.value)); });
    sync();
  });

  /* ---------- Page loader ---------- */
  const loader = document.getElementById('pageLoader');
  if (loader) window.addEventListener('load', () => loader.classList.add('hide'));
})();
