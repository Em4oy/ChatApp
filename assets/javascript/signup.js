(() => {
  const form = document.querySelector('.signup form');
  if (!form) return;

  const continueBtn = form.querySelector('.button input');
  const errorText = form.querySelector('.error-text');

  form.addEventListener('submit', e => e.preventDefault());

  continueBtn?.addEventListener('click', async (e) => {
    e.preventDefault();
    try {
      const formData = new FormData(form);
      const res = await fetch('api/auth/signup', { method: 'POST', body: formData });
      if (res.ok) {
        const data = await res.text();
        if (data === 'success') {
          // legacy path — redirect straight to chat
          const baseHref = document.querySelector('base')?.getAttribute('href') ?? '/';
          location.href = (baseHref.endsWith('/') ? baseHref : baseHref + '/') + 'chat';
        } else if (data === 'verify') {
          // verification email sent — show message in-place, hide form
          form.style.display = 'none';
          const msg = document.createElement('div');
          msg.style.cssText = 'text-align:center;padding:32px 16px;';
          msg.innerHTML = '<div style="font-size:48px;margin-bottom:12px;">✉️</div>'
            + '<p style="font-size:15px;line-height:1.6;color:#1a7f37;">'
            + 'Registration successful!<br>Please check your email and click the verification link to activate your account.'
            + '</p>';
          form.parentNode.insertBefore(msg, form);
        } else {
          if (errorText) {
            errorText.style.display = 'block';
            errorText.textContent = data;
          }
        }
      }
    } catch (err) {
      // optionally log
    }
  });
})();
