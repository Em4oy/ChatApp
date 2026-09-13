(() => {
  const form      = document.getElementById('verify-code-form');
  const errorText = document.getElementById('verify-error');
  const verifyBtn = document.getElementById('verify-btn');
  const codeInput = document.getElementById('login-code');

  if (!form) return;

  // Only allow numeric input in the code field
  codeInput?.addEventListener('input', () => {
    codeInput.value = codeInput.value.replace(/\D/g, '').slice(0, 6);
  });

  form.addEventListener('submit', e => e.preventDefault());

  verifyBtn?.addEventListener('click', async (e) => {
    e.preventDefault();
    if (errorText) { errorText.style.display = 'none'; errorText.textContent = ''; }

    const code = codeInput?.value.trim() ?? '';
    if (code.length !== 6) {
      showError('Please enter the 6-digit code from your email.');
      return;
    }

    verifyBtn.disabled = true;
    verifyBtn.value = 'Verifying…';

    try {
      const body = new URLSearchParams({ code });
      const res  = await fetch('api/auth/login-verify', {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:    body.toString()
      });
      const data = (await res.text()).trim();

      if (data === 'success') {
        const baseHref = document.querySelector('base')?.getAttribute('href') ?? '/';
        location.href = (baseHref.endsWith('/') ? baseHref : baseHref + '/') + 'chat';
        return;
      } else if (data === 'expired') {
        // pending session gone — send back to login
        showError('Code has expired. Please log in again.');
        setTimeout(() => {
          const baseHref = document.querySelector('base')?.getAttribute('href') ?? '/';
          location.href = (baseHref.endsWith('/') ? baseHref : baseHref + '/') + 'login';
        }, 2000);
        return;
      } else {
        showError(data || 'Code is not correct, try again.');
      }
    } catch (err) {
      showError('Network error. Please try again.');
    }

    verifyBtn.disabled = false;
    verifyBtn.value = 'Verify';
  });

  function showError(msg) {
    if (!errorText) return;
    errorText.textContent = msg;
    errorText.style.display = 'block';
  }
})();
