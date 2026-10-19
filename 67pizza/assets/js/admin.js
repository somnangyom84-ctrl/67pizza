/* 67pizza admin behaviour */
(function () {
  'use strict';
  const body = document.body;
  const toggle = document.getElementById('sidebarToggle');
  const overlay = document.getElementById('adminOverlay');
  toggle?.addEventListener('click', () => body.classList.toggle('sidebar-open'));
  overlay?.addEventListener('click', () => body.classList.remove('sidebar-open'));

  // Image preview for file inputs
  document.querySelectorAll('input[type="file"][data-preview]').forEach(inp => {
    inp.addEventListener('change', () => {
      const img = document.querySelector(inp.dataset.preview);
      if (!img || !inp.files[0]) return;
      img.src = URL.createObjectURL(inp.files[0]);
      img.style.display = 'block';
    });
  });

  // Auto-slug
  const nameInp = document.querySelector('[data-slug-source]');
  const slugInp = document.querySelector('[data-slug-target]');
  if (nameInp && slugInp) {
    nameInp.addEventListener('input', () => {
      if (slugInp.dataset.touched === '1') return;
      slugInp.value = nameInp.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    });
    slugInp.addEventListener('input', () => slugInp.dataset.touched = '1');
  }

  // Charts (Chart.js via CDN, graceful fallback to table)
  window.renderChart = function (canvasId, type, labels, datasets, fallbackId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    if (typeof Chart === 'undefined') {
      canvas.closest('.chart-wrap')?.classList.add('d-none');
      document.getElementById(fallbackId)?.classList.remove('d-none');
      return;
    }
    Chart.defaults.color = '#9a9aa3';
    Chart.defaults.borderColor = '#2a2a31';
    new Chart(canvas, {
      type,
      data: { labels, datasets },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { labels: { color: '#fff' } } },
        scales: type === 'doughnut' ? {} : Object.assign({ y: { beginAtZero: true, ticks: { callback: v => Number(v).toLocaleString() } } }, datasets.some(d => d.yAxisID === 'y1') ? { y1: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } } } : {}),
      },
    });
  };
})();
