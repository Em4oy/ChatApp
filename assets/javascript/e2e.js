// E2E client-side ECDH key exchange (Diffie-Hellman style, P-256) + AES-GCM encryption
// Both parties derive the same shared secret from their own private key + the other party's
// public key — identical to how Telegram Secret Chats work.
(async () => {
  const subtle = window.crypto.subtle;

  // ── helpers ───────────────────────────────────────────────────────────────
  function ab2b64(buf) { return btoa(String.fromCharCode(...new Uint8Array(buf))); }
  function b642ab(b64) { const s = atob(b64); const a = new Uint8Array(s.length); for (let i=0;i<s.length;i++) a[i]=s.charCodeAt(i); return a.buffer; }
  function spkiToPem(spki) {
    const b64 = ab2b64(spki);
    return '-----BEGIN PUBLIC KEY-----\n' + b64.match(/.{1,64}/g).join('\n') + '\n-----END PUBLIC KEY-----\n';
  }
  function pemToSpki(pem) { return b642ab(pem.replace(/-----[^-]+-----/g,'').replace(/\s+/g,'')); }

  // ── server private-key backup/restore ──────────────────────────────────────
  async function backupPrivateKey(b64) {
    try {
      const base = window.BASE_PATH || '/';
      await fetch(base + 'php/key-backup-private.php', {
        method: 'POST',
        body: new URLSearchParams({ private_key: b64 })
      });
    } catch (e) { /* non-fatal */ }
  }

  async function restorePrivateKey() {
    try {
      const base = window.BASE_PATH || '/';
      const res = await fetch(base + 'php/key-restore-private.php');
      if (!res.ok) return false;
      const b64 = (await res.text()).trim();
      if (!b64) return false;
      // Detect algorithm
      const ab = b642ab(b64);
      let algo = 'ecdh';
      try {
        await subtle.importKey('pkcs8', ab, { name: 'ECDH', namedCurve: 'P-256' }, true, ['deriveKey', 'deriveBits']);
      } catch (e) {
        try {
          await subtle.importKey('pkcs8', ab, { name: 'RSA-OAEP', hash: 'SHA-256' }, true, ['decrypt']);
          algo = 'rsa';
        } catch (e2) {
          return false; // unrecognised key material
        }
      }
      localStorage.setItem('e2e_priv', b64);
      localStorage.setItem('e2e_algo', algo);
      return true;
    } catch (e) {
      return false;
    }
  }

  // ── server key storage (same PHP endpoints as before) ─────────────────────
  async function uploadPublicKey(pubPem) {
    const base = window.BASE_PATH || '/';
    const res = await fetch(base + 'php/key-upload-public.php', { method: 'POST', body: new URLSearchParams({ public_key: pubPem }) });
    if (res.ok) {
      localStorage.setItem('e2e_pub', pubPem);
      try { window.setE2EStatus && window.setE2EStatus('on'); } catch (e) {}
    }
    return res.ok;
  }

  // ── key generation (ECDH P-256) ───────────────────────────────────────────
  async function genKeyPair() {
    const kp = await subtle.generateKey({ name: 'ECDH', namedCurve: 'P-256' }, true, ['deriveKey', 'deriveBits']);
    const pubBuf  = await subtle.exportKey('spki',  kp.publicKey);
    const privBuf = await subtle.exportKey('pkcs8', kp.privateKey);
    const privB64 = ab2b64(privBuf);
    localStorage.setItem('e2e_priv', privB64);
    localStorage.setItem('e2e_algo', 'ecdh');
    const pubPem = spkiToPem(pubBuf);
    await uploadPublicKey(pubPem);
    await backupPrivateKey(privB64);
    return true;
  }

  async function reUploadPublicKey() {
    const pubPem = localStorage.getItem('e2e_pub');
    if (!pubPem) return false;
    return uploadPublicKey(pubPem);
  }

  async function exportPrivateKeyBase64() { return localStorage.getItem('e2e_priv') || null; }
  async function exportPublicKeyPem()     { return localStorage.getItem('e2e_pub')  || null; }

  // ── import own private key from localStorage ──────────────────────────────
  async function importPrivateKey() {
    const b64 = localStorage.getItem('e2e_priv');
    if (!b64) return null;
    const ab = b642ab(b64);
    // Try ECDH first (new keys), fall back to RSA-OAEP (legacy)
    try { return await subtle.importKey('pkcs8', ab, { name: 'ECDH', namedCurve: 'P-256' }, true, ['deriveKey', 'deriveBits']); } catch (e) {}
    try { return await subtle.importKey('pkcs8', ab, { name: 'RSA-OAEP', hash: 'SHA-256' }, true, ['decrypt']); } catch (e) {}
    return null;
  }

  // Used by Import button on user page
  async function importPrivateKeyFromBase64(b64) {
    try {
      const ab = b642ab(b64);
      let algo = 'ecdh';
      try {
        await subtle.importKey('pkcs8', ab, { name: 'ECDH', namedCurve: 'P-256' }, true, ['deriveKey', 'deriveBits']);
      } catch (e) {
        await subtle.importKey('pkcs8', ab, { name: 'RSA-OAEP', hash: 'SHA-256' }, true, ['decrypt']);
        algo = 'rsa';
      }
      localStorage.setItem('e2e_priv', b64);
      localStorage.setItem('e2e_algo', algo);
      await backupPrivateKey(b64);
      try { window.setE2EStatus && window.setE2EStatus('on'); } catch (e) {}
      return true;
    } catch (e) {
      console.error('E2E: importPrivateKeyFromBase64 failed', e);
      return false;
    }
  }

  // ── ECDH helpers ──────────────────────────────────────────────────────────
  async function fetchPeerPublicKey(userId) {
    const base = window.BASE_PATH || '/';
    const res = await fetch(base + 'php/key-get-public.php?user_id=' + encodeURIComponent(userId));
    if (!res.ok) throw new Error('no public key for user ' + userId);
    const pem = await res.text();
    return subtle.importKey('spki', pemToSpki(pem), { name: 'ECDH', namedCurve: 'P-256' }, true, []);
  }

  async function deriveSharedAesKey(myPriv, theirPub, usage) {
    return subtle.deriveKey(
      { name: 'ECDH', public: theirPub },
      myPriv,
      { name: 'AES-GCM', length: 256 },
      false,
      [usage]
    );
  }

  // ── encrypt (DH) ──────────────────────────────────────────────────────────
  async function encryptFor(recipientId, plaintext) {
    const myPriv = await importPrivateKey();
    if (!myPriv) throw new Error('no private key');

    // Fall back to legacy hybrid RSA for users who haven't regenerated keys yet
    if (myPriv.algorithm.name !== 'ECDH') {
      return legacyEncryptFor(recipientId, plaintext);
    }

    const theirPub  = await fetchPeerPublicKey(recipientId);
    const sharedKey = await deriveSharedAesKey(myPriv, theirPub, 'encrypt');
    const iv        = window.crypto.getRandomValues(new Uint8Array(12));
    const cipher    = await subtle.encrypt({ name: 'AES-GCM', iv }, sharedKey, new TextEncoder().encode(plaintext));
    return { iv: ab2b64(iv.buffer), cipher: ab2b64(cipher) };
  }

  // ── decrypt (DH) ─────────────────────────────────────────────────────────
  // senderId = user_id of the message author (we fetch their public key to derive shared secret)
  async function decryptDH(senderId, ivB64, ctB64) {
    const myPriv = await importPrivateKey();
    if (!myPriv) throw new Error('no private key');
    if (myPriv.algorithm.name !== 'ECDH') throw new Error('key is not ECDH');
    const theirPub  = await fetchPeerPublicKey(senderId);
    const sharedKey = await deriveSharedAesKey(myPriv, theirPub, 'decrypt');
    const plain     = await subtle.decrypt({ name: 'AES-GCM', iv: new Uint8Array(b642ab(ivB64)) }, sharedKey, b642ab(ctB64));
    return new TextDecoder().decode(plain);
  }

  // ── legacy RSA (backward compat for old ENC:HYBRID / ENC:RSA messages) ────
  async function legacyEncryptFor(recipientId, plaintext) {
    const base = window.BASE_PATH || '/';
    const res = await fetch(base + 'php/key-get-public.php?user_id=' + encodeURIComponent(recipientId));
    if (!res.ok) throw new Error('no public key');
    const pem = await res.text();
    const pub    = await subtle.importKey('spki', b642ab(pem.replace(/-----[^-]+-----/g,'').replace(/\s+/g,'')), { name: 'RSA-OAEP', hash: 'SHA-256' }, true, ['encrypt']);
    const aesKey = await subtle.generateKey({ name: 'AES-GCM', length: 256 }, true, ['encrypt','decrypt']);
    const iv     = window.crypto.getRandomValues(new Uint8Array(12));
    const cipher = await subtle.encrypt({ name: 'AES-GCM', iv }, aesKey, new TextEncoder().encode(plaintext));
    const rawKey = await subtle.exportKey('raw', aesKey);
    const wrapped = await subtle.encrypt({ name: 'RSA-OAEP' }, pub, rawKey);
    return { wrappedKey: ab2b64(wrapped), iv: ab2b64(iv.buffer), cipher: ab2b64(cipher) };
  }

  async function decryptLarge(wrappedB64, ivB64, cipherB64) {
    const priv   = await subtle.importKey('pkcs8', b642ab(localStorage.getItem('e2e_priv')||''), { name: 'RSA-OAEP', hash: 'SHA-256' }, true, ['decrypt']);
    const rawAes = await subtle.decrypt({ name: 'RSA-OAEP' }, priv, b642ab(wrappedB64));
    const aesKey = await subtle.importKey('raw', rawAes, { name: 'AES-GCM' }, false, ['decrypt']);
    const plain  = await subtle.decrypt({ name: 'AES-GCM', iv: new Uint8Array(b642ab(ivB64)) }, aesKey, b642ab(cipherB64));
    return new TextDecoder().decode(plain);
  }

  async function decryptFrom(cipherB64) {
    const priv  = await subtle.importKey('pkcs8', b642ab(localStorage.getItem('e2e_priv')||''), { name: 'RSA-OAEP', hash: 'SHA-256' }, true, ['decrypt']);
    const plain = await subtle.decrypt({ name: 'RSA-OAEP' }, priv, b642ab(cipherB64));
    return new TextDecoder().decode(plain);
  }

  window.E2E = {
    genKeyPair, reUploadPublicKey,
    encryptFor, decryptDH,
    decryptLarge, decryptFrom,
    exportPrivateKeyBase64, exportPublicKeyPem, importPrivateKeyFromBase64,
    restorePrivateKey,
    _importPrivateKey: importPrivateKey,
    _fetchPeerPublicKey: fetchPeerPublicKey,
    _deriveSharedKey: deriveSharedAesKey,
    _b642ab: b642ab
  };
})();
