(() => {
  async function ensureKeys() {
    try {
      const uid = window.CURRENT_USER_ID || 0;
      if (!uid) return false;
      const base = window.BASE_PATH || '/';

      const privB64 = localStorage.getItem('e2e_priv');
      const algo    = localStorage.getItem('e2e_algo');

      // If user has an old RSA key, migrate to ECDH automatically
      if (privB64 && algo !== 'ecdh') {
        if (window.E2E && typeof window.E2E.genKeyPair === 'function') {
          await window.E2E.genKeyPair(); // generates ECDH, uploads new public key
          setE2EStatus('on');
          return true;
        }
      }

      const pubRes = await fetch(base + 'php/key-get-public.php?user_id=' + encodeURIComponent(uid));

      if (pubRes.ok) {
        if (privB64 && algo === 'ecdh') {
          setE2EStatus('on');
        } else if (privB64) {
          setE2EStatus('on');
        } else {
          // Server has the public key but localStorage is empty (browser storage was cleared).
          // Try to restore the private key from server backup.
          if (window.E2E && typeof window.E2E.restorePrivateKey === 'function') {
            const restored = await window.E2E.restorePrivateKey();
            if (restored) {
              setE2EStatus('on');
              return true;
            }
          }
          setE2EStatus('missing');
        }
        return true;
      }

      // Server has no public key
      if (privB64 && algo === 'ecdh') {
        // Re-upload existing ECDH public key
        if (window.E2E && typeof window.E2E.reUploadPublicKey === 'function') {
          const ok = await window.E2E.reUploadPublicKey();
          if (ok) { setE2EStatus('on'); return true; }
        }
      }

      // localStorage is empty — try restoring the private key from server backup
      if (!privB64 && window.E2E && typeof window.E2E.restorePrivateKey === 'function') {
        const restored = await window.E2E.restorePrivateKey();
        if (restored) {
          // Re-upload public key so the server record is fresh too
          if (typeof window.E2E.reUploadPublicKey === 'function') {
            await window.E2E.reUploadPublicKey();
          }
          setE2EStatus('on');
          return true;
        }
      }

      // No key anywhere — generate fresh ECDH keypair
      if (window.E2E && typeof window.E2E.genKeyPair === 'function') {
        await window.E2E.genKeyPair();
        setE2EStatus('on');
        return true;
      }
    } catch (e) {
      // ignore
    }
    return false;
  }

  (async () => {
    try {
      const ok = await ensureKeys();
      if (!ok) {
        try { window.setE2EStatus && window.setE2EStatus('off'); } catch (e) {}
      }
    } catch (e) {}
  })();

  window.Keys = { ensureKeys };
})();

function setE2EStatus(state) {
  try {
    const el = document.getElementById('e2e-status');
    if (!el) return;
    el.classList.remove('e2e-on', 'e2e-off', 'e2e-missing');
    if (state === 'on') {
      el.classList.add('e2e-on');
      el.textContent = 'E2E: on';
    } else if (state === 'missing') {
      el.classList.add('e2e-missing');
      el.textContent = 'E2E: missing key';
    } else {
      el.classList.add('e2e-off');
      el.textContent = 'E2E: off';
    }
  } catch (e) {}
}

// expose setter for other scripts
window.setE2EStatus = setE2EStatus;
