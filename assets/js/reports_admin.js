document.addEventListener('DOMContentLoaded', () => {
  const forms = document.querySelectorAll('.report-status-form');
  forms.forEach((form) => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const data = new FormData(form);
      const resp = await fetch(form.action || window.location.href, {
        method: 'POST',
        body: data,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
      });
      const json = await resp.json().catch(() => null);
      if (!json) return;
      const badge = form.closest('.p-4')?.querySelector('[data-status-badge]');
      if (json.ok) {
        if (badge) {
          badge.textContent = json.status_label;
        }
        form.querySelectorAll('button[type="submit"]').forEach((btn) => {
          btn.textContent = 'Mis à jour';
          setTimeout(() => (btn.textContent = 'Mettre à jour'), 1200);
        });
      }
    });
  });
});
