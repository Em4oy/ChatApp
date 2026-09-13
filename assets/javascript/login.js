(() => {
  const form = document.querySelector('.login form');
  if (!form) return;

  const continueBtn     = form.querySelector('.button input');
  const errorText       = form.querySelector('.error-text');
  const resendWrap      = document.getElementById('resend-verify-wrap');
  const resendBtn       = document.getElementById('resend-verify-btn');
  const resendMsg       = document.getElementById('resend-verify-msg');

  const UNVERIFIED_MSG  = 'Please verify your email address before logging in.';

  form.addEventListener('submit', e => e.preventDefault());

  continueBtn?.addEventListener('click', async (e) => {
    e.preventDefault();
    // hide resend button on a fresh login attempt
    if (resendWrap) resendWrap.style.display = 'none';
    if (resendMsg)  { resendMsg.style.display = 'none'; resendMsg.textContent = ''; }
    try {
      const formData = new FormData(form);
      const res = await fetch('api/auth/login', { method: 'POST', body: formData });
      if (res.ok) {
        const data = (await res.text()).trim();
        if (data === 'success') {
          const baseHref = document.querySelector('base')?.getAttribute('href') ?? '/';
          location.href = (baseHref.endsWith('/') ? baseHref : baseHref + '/') + 'chat';
        } else if (data === '2fa') {
          const baseHref = document.querySelector('base')?.getAttribute('href') ?? '/';
          location.href = (baseHref.endsWith('/') ? baseHref : baseHref + '/') + 'login-verify';
        } else {
          if (errorText) {
            errorText.style.display = 'block';
            errorText.textContent = data;
          }
          // show resend button only for the unverified-account error
          if (data === UNVERIFIED_MSG && resendWrap) {
            resendWrap.style.display = 'block';
          }
        }
      }
    } catch (err) {
      // optionally log
    }
  });

  resendBtn?.addEventListener('click', async () => {
    const emailInput = form.querySelector('input[name="email"]');
    const email = emailInput ? emailInput.value.trim() : '';
    if (!email) {
      if (resendMsg) { resendMsg.style.display = 'inline'; resendMsg.style.color = '#d1242f'; resendMsg.textContent = 'Enter your email first.'; }
      return;
    }

    resendBtn.disabled = true;
    resendBtn.textContent = 'Sending…';
    if (resendMsg) { resendMsg.style.display = 'none'; resendMsg.textContent = ''; }

    try {
      const body = new URLSearchParams({ email });
      const res  = await fetch('php/resend-verify.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:    body.toString()
      });
      const data = await res.json();

      if (data.already_verified) {
        if (resendMsg) { resendMsg.style.display = 'inline'; resendMsg.style.color = '#0969da'; resendMsg.textContent = 'Account already verified — you can log in.'; }
        if (resendWrap) resendWrap.style.display = 'none';
      } else {
        if (resendMsg) { resendMsg.style.display = 'inline'; resendMsg.style.color = '#1a7f37'; resendMsg.textContent = 'Verification email sent! Check your inbox.'; }
        resendBtn.textContent = 'Resend verification email';
        resendBtn.disabled = false;
      }
    } catch (err) {
      if (resendMsg) { resendMsg.style.display = 'inline'; resendMsg.style.color = '#d1242f'; resendMsg.textContent = 'Failed to send. Please try again.'; }
      resendBtn.textContent = 'Resend verification email';
      resendBtn.disabled = false;
    }
  });
})();
