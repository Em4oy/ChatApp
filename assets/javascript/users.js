(() => {
  const searchBar = document.querySelector('.search input');
  const searchIcon = document.querySelector('.search button');
  const usersList = document.querySelector('.users-list');
  if (!searchBar || !searchIcon || !usersList) return;

  searchIcon.addEventListener('click', () => {
    searchBar.classList.toggle('show');
    searchIcon.classList.toggle('active');
    searchBar.focus();
    if (searchBar.classList.contains('active')) {
      searchBar.value = '';
      searchBar.classList.remove('active');
    }
  });

  // Wait for window.E2E to be initialised (it's an async IIFE in e2e.js)
  function waitForE2E(timeout) {
    return new Promise((resolve) => {
      if (window.E2E) { resolve(); return; }
      const start = Date.now();
      const t = setInterval(() => {
        if (window.E2E || Date.now() - start > timeout) { clearInterval(t); resolve(); }
      }, 50);
    });
  }

  // Decrypt any ENC:DH preview snippets rendered in the sidebar
  async function decryptSidebarPreviews(container) {
    const nodes = container.querySelectorAll('p[data-enc="dh"]');
    if (!nodes.length) return;
    await waitForE2E(3000);
    if (!window.E2E || !window.E2E._fetchPeerPublicKey || !window.E2E._deriveSharedKey) return;
    try {
      const b64priv = localStorage.getItem('e2e_priv');
      if (!b64priv) return;
      const s = atob(b64priv);
      const ab = new Uint8Array(s.length);
      for (let i = 0; i < s.length; i++) ab[i] = s.charCodeAt(i);
      let privKey;
      try {
        privKey = await window.crypto.subtle.importKey('pkcs8', ab.buffer, { name: 'ECDH', namedCurve: 'P-256' }, true, ['deriveKey', 'deriveBits']);
      } catch (e) { return; }

      const s2ab = b => { const ss = atob(b); const a = new Uint8Array(ss.length); for (let i = 0; i < ss.length; i++) a[i] = ss.charCodeAt(i); return a.buffer; };

      for (const el of Array.from(nodes)) {
        try {
          const peer = el.getAttribute('data-peer');
          const iv   = el.getAttribute('data-iv');
          const ct   = el.getAttribute('data-ct');
          if (!(peer && iv && ct)) continue;
          const theirPub  = await window.E2E._fetchPeerPublicKey(peer);
          const sharedKey = await window.E2E._deriveSharedKey(privKey, theirPub, 'decrypt');
          const plain     = await window.crypto.subtle.decrypt({ name: 'AES-GCM', iv: new Uint8Array(s2ab(iv)) }, sharedKey, s2ab(ct));
          const text      = new TextDecoder().decode(plain);
          el.removeAttribute('data-enc');
          el.textContent  = text.length > 28 ? text.slice(0, 28) + '\u2026' : text;
        } catch (e) { /* leave as-is */ }
      }
    } catch (e) { /* ignore */ }
  }

  // Apply flashing class to sidebar rows that have unread messages (data-unseen="1")
  function applyUnseenFlash(container) {
    container.querySelectorAll('a').forEach(a => {
      if (a.dataset.unseen === '1') {
        a.classList.add('unseen-flash');
      } else {
        a.classList.remove('unseen-flash');
      }
    });
  }

  const doSearch = async (term) => {
    try {
      const body = new URLSearchParams({ searchTerm: term });
      const res = await fetch('api/search', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
      });
      if (res.ok) {
        const data = await res.text();
        usersList.innerHTML = data;
        await decryptSidebarPreviews(usersList);
        applyUnseenFlash(usersList);
      }
    } catch (err) {
      // console.error(err);
    }
  };

  searchBar.addEventListener('input', (e) => {
    const searchTerm = e.target.value;
    if (searchTerm.trim() !== '') searchBar.classList.add('active');
    else searchBar.classList.remove('active');
    doSearch(searchTerm);
  });

  const refreshUsers = async () => {
    try {
      const res = await fetch('api/users');
      if (res.ok) {
        const data = await res.text();
        if (!searchBar.classList.contains('active')) {
          usersList.innerHTML = data;
          await decryptSidebarPreviews(usersList);
          applyUnseenFlash(usersList);
        }
      }
    } catch (err) {
      // console.error(err);
    }
  };

  // initial load and poll
  refreshUsers();
  setInterval(refreshUsers, 4000);
})();

