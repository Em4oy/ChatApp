(() => {
  const form = document.querySelector('.form.user form');
  const btn = document.getElementById('save-user-btn');
  const err = document.querySelector('.form.user .error-text');
  if (!form || !btn) return;

  form.addEventListener('submit', e => e.preventDefault());

  btn.addEventListener('click', async (e) => {
    e.preventDefault();
    const fd = new FormData(form);
    try {
      const res = await fetch('php/user-update.php', { method: 'POST', body: fd });
      const text = await res.text();
      if (res.ok && text.trim() === 'success') {
        location.reload();
      } else {
        if (err) { err.style.display = 'block'; err.textContent = text || 'Failed to save'; }
      }
    } catch (ex) {
      if (err) { err.style.display = 'block'; err.textContent = 'Network error'; }
    }
  });
})();
