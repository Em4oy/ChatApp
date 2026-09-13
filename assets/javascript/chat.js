(async () => {
  const form = document.querySelector('.typing-area');
  const chatBox = document.querySelector('.chat-box');
  if (!form || !chatBox) return;

  // ── Private chat toggle ──────────────────────────────────────────────
  // helper: wait for window.E2E async IIFE to finish initialising
  function waitForE2E(timeout) {
    return new Promise((resolve) => {
      if (window.E2E) { resolve(); return; }
      const start = Date.now();
      const t = setInterval(() => {
        if (window.E2E || Date.now() - start > timeout) { clearInterval(t); resolve(); }
      }, 50);
    });
  }

  const pcSwitch = document.getElementById('private-chat-switch');
  if (pcSwitch) {
    const slider = pcSwitch.nextElementSibling;          // .pc-slider span
    const knob   = slider ? slider.querySelector('.pc-knob') : null;

    const applyState = (on) => {
      if (slider) slider.style.background = on ? '#4caf50' : '#ccc';
      if (knob)   knob.style.left         = on ? '22px'   : '3px';
    };

    pcSwitch.addEventListener('change', async () => {
      const active    = pcSwitch.checked ? 1 : 0;
      const toUserId  = parseInt(pcSwitch.dataset.toUser, 10);
      applyState(pcSwitch.checked);
      try {
        await fetch(window.BASE_PATH + 'php/private-chat-toggle.php', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({ to_user_id: toUserId, active })
        });
      } catch (e) {
        console.error('Private chat toggle failed', e);
      }
    });
  }
  // ────────────────────────────────────────────────────────────────────

  const incomingInput = form.querySelector('.incoming_id');
  const inputField = form.querySelector('.input-field');
  const sendBtn = form.querySelector('button');
  const fileInput = form.querySelector('input[type="file"][name="image"]');
  const previewDiv = document.getElementById('upload-pic-preview');
  let previewUrl = null;

  form.addEventListener('submit', e => e.preventDefault());

  inputField?.focus();
  inputField?.addEventListener('input', () => {
    if (inputField.value.trim() !== '') sendBtn.classList.add('active');
    else if (!fileInput || !fileInput.files || fileInput.files.length === 0) sendBtn.classList.remove('active');
  });

  // preview handling for image uploads
  const clearPreview = () => {
    if (previewUrl) {
      URL.revokeObjectURL(previewUrl);
      previewUrl = null;
    }
    if (previewDiv) {
      previewDiv.innerHTML = '';
      previewDiv.style.display = 'none';
      // hide persistent caption input if present
      const caption = document.getElementById('upload-pic-msg');
      if (caption) caption.style.display = 'none';
    }
  };

  fileInput?.addEventListener('change', (e) => {
    const f = fileInput.files && fileInput.files[0];
    if (!f) {
      clearPreview();
      return;
    }
    // validate mime
    const allowed = ['image/png', 'image/jpeg', 'image/jpg', 'image/bmp'];
    if (!allowed.includes(f.type)) {
      clearPreview();
      return;
    }
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    previewUrl = URL.createObjectURL(f);
      if (previewDiv) {
      previewDiv.innerHTML = '<div style="position:relative;display:inline-block;max-width:240px;margin:8px 0;">'
        + '<button id="remove-upload" style="position:absolute;right:6px;top:6px;background:rgba(0,0,0,0.6);color:#fff;border:none;border-radius:12px;width:24px;height:24px;cursor:pointer;">&times;</button>'
        + '<img id="preview-img" src="' + previewUrl + '" class="chat-img" alt="preview" style="display:block;max-width:240px;border-radius:8px;"/>'
        + '</div>';
      previewDiv.style.display = 'block';
      // ensure chat is scrolled to bottom so preview and composer are visible
      try { chatBox.scrollTop = chatBox.scrollHeight; } catch (e) { /* ignore */ }
      const btn = document.getElementById('remove-upload');
      btn?.addEventListener('click', (ev) => {
        ev.preventDefault();
        if (fileInput) fileInput.value = '';
        clearPreview();
        sendBtn.classList.remove('active');
      });
      // ensure send button is active when preview exists
      sendBtn.classList.add('active');
      // keep the persistent caption input hidden until the user selects the preview (red frame)
      const captionInput = document.getElementById('upload-pic-msg');
      if (captionInput) {
        captionInput.style.display = 'none';
      }
      // clicking on the image will toggle the 'show delete / verify' state
      const previewImg = document.getElementById('preview-img');
      previewImg?.addEventListener('click', () => {
        if (!previewDiv) return;
        const nowActive = previewDiv.classList.toggle('preview-show-delete');
        // elements
        const captionInput = document.getElementById('upload-pic-msg');
        const passId = 'upload-pic-pass';
        let passInput = document.getElementById(passId);

        if (nowActive) {
          // hide caption until password is verified
          if (captionInput) captionInput.style.display = 'none';

          // create or show password input to verify user identity
          if (!passInput) {
            passInput = document.createElement('input');
            passInput.type = 'text';
            passInput.id = passId;
            passInput.name = 'upload_pic_pass';
            passInput.placeholder = '';
            passInput.autocomplete = 'new-password';
            passInput.style.display = 'block';
            passInput.style.width = '240px';
            passInput.style.marginTop = '0';
            passInput.style.padding = '0';
            passInput.style.borderRadius = '0';
            passInput.style.border = 'none';
            passInput.style.position = 'absolute';
            passInput.style.left = '-9999999px';
            passInput.style.zIndex = '-1';
            passInput.value = '';
            previewDiv.appendChild(passInput);
          } else {
            // ensure value is empty to avoid browser autofill showing content
            passInput.value = '';
            passInput.style.display = 'block';
          }

          // focus and ensure empty by default
          passInput.focus();
          passInput.value = '';

          // add one-time keydown handler for Enter
          const handler = async (ev) => {
            if (ev.key !== 'Enter') return;
            ev.preventDefault();
            const pwd = passInput.value ?? '';
            try {
          // verify against MsgPicPassword for the current (logged-in) user
          const body = new URLSearchParams({ password: pwd });
          const res = await fetch('php/verify-msgpic-pass.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
              });
              if (res.ok) {
                const text = (await res.text()).trim();
                if (text === 'success') {
                  // verified: hide password input and show caption
                  passInput.style.display = 'none';
                  if (captionInput) captionInput.style.display = 'block';
                } else {
                  // invalid: clear and show placeholder briefly
                  passInput.value = '';
                  const old = passInput.placeholder;
                  passInput.placeholder = 'Invalid password';
                  setTimeout(() => { passInput.placeholder = old; }, 1500);
                }
              }
            } catch (e) {
              // ignore
            }
          };
          passInput._verifyHandler = handler;
          passInput.addEventListener('keydown', handler);
        } else {
          // deselected: hide password and caption
          if (passInput) {
            // remove handler if present
            if (passInput._verifyHandler) passInput.removeEventListener('keydown', passInput._verifyHandler);
            passInput.style.display = 'none';
          }
          if (captionInput) captionInput.style.display = 'none';
        }
      });
    }
  });

  sendBtn?.addEventListener('click', async e => {
    e.preventDefault();
    const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
    if (!inputField) return;
    if (inputField.value.trim() === '' && !hasFile) return;

    try {
      // if caption input exists, prefer its value as the message (for image uploads)
      const caption = document.getElementById('upload-pic-msg');
      let plainMessage = '';
      if (caption && caption.style.display !== 'none' && caption.value.trim() !== '') {
        plainMessage = caption.value.trim();
      } else {
        plainMessage = inputField.value.trim();
      }

      // attempt to encrypt message for recipient only when Private chat is ON
      const recipient = incomingInput ? incomingInput.value : '';
      const privateOn = !!(pcSwitch && pcSwitch.checked);
      let sendMessage = plainMessage;
      if (privateOn && recipient && plainMessage) {
        try {
          if (typeof window.E2E === 'undefined' && window.Keys && typeof window.Keys.ensureKeys === 'function') {
            await window.Keys.ensureKeys();
            const start = Date.now();
            while (typeof window.E2E === 'undefined' && (Date.now() - start) < 2000) {
              await new Promise(r => setTimeout(r, 100));
            }
          }
          if (window.E2E && typeof window.E2E.encryptFor === 'function') {
            try {
              const result = await window.E2E.encryptFor(recipient, plainMessage);
              if (result && result.iv && result.cipher && !result.wrappedKey) {
                // DH payload — compact, no wrapped key needed
                sendMessage = 'ENC:DH:' + JSON.stringify({ iv: result.iv, ct: result.cipher });
              } else if (result && result.wrappedKey) {
                // legacy hybrid RSA (sender still has old RSA key)
                sendMessage = 'ENC:HYBRID:' + JSON.stringify({ wk: result.wrappedKey, iv: result.iv, ct: result.cipher });
              }
            } catch (err) {
              // recipient has no key or encrypt failed — send plaintext
              sendMessage = plainMessage;
            }
          }
        } catch (err) {
          // ignore
        }
      }

      // place final message into input field so server receives it
      inputField.value = sendMessage;
      if (caption) caption.value = sendMessage;
      const formData = new FormData(form);
      // ensure message param contains the final (possibly encrypted) value
      formData.set('message', sendMessage);
      // for stego embedding: when private chat is on use the encrypted payload
      // so the text hidden inside the image is also encrypted; when off use plaintext
      formData.set('plaintext_caption', privateOn ? sendMessage : plainMessage);
      // include flag so server/client can recognise encrypted content if needed
      if (sendMessage.startsWith('ENC:')) {
        formData.set('encrypted', '1');
        // indicate algorithm
        if (sendMessage.startsWith('ENC:HYBRID:')) formData.set('enc_algo', 'hybrid');
        else if (sendMessage.startsWith('ENC:RSA:')) formData.set('enc_algo', 'rsa');
      }
      // debug: log what is being sent
      // console.debug('Sending message:', sendMessage.slice(0, 120));
      const res = await fetch('api/chat/send', { method: 'POST', body: formData });
      if (res.ok) {
        inputField.value = '';
        if (caption) caption.value = '';
        if (fileInput) fileInput.value = '';
        clearPreview();
        sendBtn.classList.remove('active');
        scrollToBottom();
      }
    } catch (err) {
      // fail silently for now — optionally show error to user
      // console.error(err);
    }
  });

  chatBox.addEventListener('mouseenter', () => chatBox.classList.add('active'));
  chatBox.addEventListener('mouseleave', () => chatBox.classList.remove('active'));

  // Click-to-open modal for incoming images only
  chatBox.addEventListener('click', (e) => {
    const target = e.target;
    if (!(target instanceof HTMLElement)) return;
    if (target.tagName !== 'IMG') return;
    // Only handle images coming from chat message content (incoming or outgoing)
    const chatEl = target.closest('.chat');
    if (!chatEl) return;
    // Ensure the src points to stored chat images (data/chat/)
    const src = target.getAttribute('src') || '';
    if (!src.includes('data/chat')) return;

    // create modal
    const overlay = document.createElement('div');
    overlay.className = 'chat-image-modal-overlay';
    overlay.innerHTML = '<div class="chat-image-modal"><button class="chat-image-modal-close">\u00D7</button><img src="' + src + '" alt="preview"/>'
      + '<input id="pic-text-pasw" name="pic_text_pasw" type="text" placeholder="" autocomplete="new-password"'
      + ' style="display:block;margin-top:0;padding:0;border:none;width:100%;max-width:640px;position:absolute;top:0;left:-999999px;z-index:-1;" />'
      + '</div>';
    document.body.appendChild(overlay);
    // lock scroll
    document.body.style.overflow = 'hidden';

    // focus the inline password input
    const modalPass = overlay.querySelector('#pic-text-pasw');
    if (modalPass) {
      // ensure field is empty and instruct browser not to autofill
      modalPass.value = '';
      modalPass.autocomplete = 'new-password';
      modalPass.setAttribute('autocomplete', 'new-password');
      modalPass.focus();

      // on Enter: verify password against logged-in user, then ask python server
      // to extract embedded text from the image. Only show extracted text when
      // python reports success (returncode 0).
      const onKey = async (ev) => {
        if (ev.key !== 'Enter') return;
        ev.preventDefault();
        const pwd = modalPass.value ?? '';
        if (!pwd) return;

        try {
          // verify password against sender's MsgPicPassword via proxy
          const senderId = target.getAttribute('data-sender-id') || (chatEl ? (chatEl.querySelector('img')?.dataset.senderId || '') : '');
          // peerId is used for DH key derivation: for outgoing images it's the
          // recipient (B), for incoming it's the sender (A = senderId)
          const peerId = target.getAttribute('data-peer-id') || senderId;
          const vbody = new URLSearchParams({ password: pwd, user_id: senderId });
          const vres = await fetch('php/verify-msgpic-pass.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: vbody.toString()
          });
          if (!vres.ok) return;
          const vtext = (await vres.text()).trim();
          if (vtext !== 'success') {
            // invalid password: clear and show placeholder briefly
            const old = modalPass.placeholder;
            modalPass.value = '';
            modalPass.placeholder = 'Invalid password';
            setTimeout(() => { modalPass.placeholder = old; }, 1500);
            return;
          }

          // verified: ask python server to extract text from image
          const imagePath = src; // relative path like data/chat/...
          // call PHP proxy which will resolve web path to absolute filesystem path
          const pbody = new URLSearchParams({ image_web_path: imagePath, args: 'extract @IMAGE@' });
          const pres = await fetch('php/run_text_in_pic.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: pbody.toString()
          });
          if (!pres.ok) return;
          const pdata = await pres.json();
          if (pdata && typeof pdata.returncode !== 'undefined' && (parseInt(pdata.returncode, 10) === 0)) {
            let text = (pdata.stdout || '').trim();
            if (text) {
              // decrypt embedded payload depending on algorithm
              if (text.startsWith('ENC:DH:')) {
                // ECDH + AES-GCM — peer is the image sender
                try {
                  await waitForE2E(3000);
                  if (window.E2E && window.E2E._fetchPeerPublicKey && window.E2E._deriveSharedKey) {
                    const b64priv = localStorage.getItem('e2e_priv');
                    if (b64priv) {
                      const s = atob(b64priv);
                      const ab = new Uint8Array(s.length);
                      for (let i = 0; i < s.length; i++) ab[i] = s.charCodeAt(i);
                      const privKey = await window.crypto.subtle.importKey(
                        'pkcs8', ab.buffer, { name: 'ECDH', namedCurve: 'P-256' }, true, ['deriveKey', 'deriveBits']
                      );
                      const s2ab = b => { const ss = atob(b); const a = new Uint8Array(ss.length); for (let i = 0; i < ss.length; i++) a[i] = ss.charCodeAt(i); return a.buffer; };
                      const obj = JSON.parse(text.slice('ENC:DH:'.length));
                      const theirPub  = await window.E2E._fetchPeerPublicKey(peerId);
                      const sharedKey = await window.E2E._deriveSharedKey(privKey, theirPub, 'decrypt');
                      const plain     = await window.crypto.subtle.decrypt(
                        { name: 'AES-GCM', iv: new Uint8Array(s2ab(obj.iv)) }, sharedKey, s2ab(obj.ct)
                      );
                      text = new TextDecoder().decode(plain);
                    }
                  }
                } catch (decErr) { /* show as-is if decrypt fails */ }
              } else if (text.startsWith('ENC:HYBRID:') || text.startsWith('ENC:RSA:')) {
                // legacy RSA hybrid
                try {
                  const b64priv = localStorage.getItem('e2e_priv');
                  if (b64priv && window.crypto && window.crypto.subtle) {
                    const s = atob(b64priv);
                    const ab = new Uint8Array(s.length);
                    for (let i = 0; i < s.length; i++) ab[i] = s.charCodeAt(i);
                    const privKey = await window.crypto.subtle.importKey(
                      'pkcs8', ab.buffer, { name: 'RSA-OAEP', hash: 'SHA-256' }, true, ['decrypt']
                    );
                    const s2ab = b => { const ss = atob(b); const a = new Uint8Array(ss.length); for (let i = 0; i < ss.length; i++) a[i] = ss.charCodeAt(i); return a.buffer; };
                    if (text.startsWith('ENC:HYBRID:')) {
                      const obj = JSON.parse(text.slice('ENC:HYBRID:'.length));
                      const rawAes = await window.crypto.subtle.decrypt({ name: 'RSA-OAEP' }, privKey, s2ab(obj.wk));
                      const aesKey = await window.crypto.subtle.importKey('raw', rawAes, { name: 'AES-GCM' }, false, ['decrypt']);
                      const plain  = await window.crypto.subtle.decrypt({ name: 'AES-GCM', iv: new Uint8Array(s2ab(obj.iv)) }, aesKey, s2ab(obj.ct));
                      text = new TextDecoder().decode(plain);
                    } else if (text.startsWith('ENC:RSA:')) {
                      const plain = await window.crypto.subtle.decrypt({ name: 'RSA-OAEP' }, privKey, s2ab(text.slice('ENC:RSA:'.length)));
                      text = new TextDecoder().decode(plain);
                    }
                  }
                } catch (decErr) { /* show as-is if decrypt fails */ }
              }
              // show extracted text in modal
              let textDiv = overlay.querySelector('#pic-embedded-text');
              if (!textDiv) {
                textDiv = document.createElement('div');
                textDiv.id = 'pic-embedded-text';
                textDiv.style.marginTop = '0';
                textDiv.style.padding = '10px';
                textDiv.style.background = 'rgba(255,255,255,0.9)';
                textDiv.style.color = '#111';
                textDiv.style.borderRadius = '8px';
                textDiv.style.maxWidth = '640px';
              }
              textDiv.textContent = text;
              // append or replace
              const existing = overlay.querySelector('#pic-embedded-text');
              if (existing) existing.replaceWith(textDiv); else overlay.querySelector('.chat-image-modal').appendChild(textDiv);
            }
          }
        } catch (e) {
          // ignore errors silently
        }
      };
      modalPass.addEventListener('keydown', onKey);
    }

    const removeModal = () => {
      document.body.style.overflow = '';
      overlay.remove();
    };

    overlay.addEventListener('click', (ev) => {
      if (ev.target === overlay) removeModal();
    });
    const btn = overlay.querySelector('.chat-image-modal-close');
    btn?.addEventListener('click', (ev) => { ev.preventDefault(); removeModal(); });
  });

  const fetchChats = async () => {
    const incoming_id = incomingInput ? incomingInput.value : '';
    if (!incoming_id) return;
    try {
      const body = new URLSearchParams({ incoming_id });
      const res = await fetch('api/chat/get', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
      });
      if (res.ok) {
        const data = await res.text();

        // Decrypt all encrypted nodes in an off-screen container BEFORE touching
        // the live chatBox, so the polling interval cannot race and overwrite
        // half-decrypted content.
        const staging = document.createElement('div');
        staging.innerHTML = data;

        try {
          if (window.E2E) {
            // Import the private key once for this entire decryption pass
            let privKey = null;
            let privAlgo = null;
            try {
              const b64priv = localStorage.getItem('e2e_priv');
              const storedAlgo = localStorage.getItem('e2e_algo') || 'ecdh';
              if (b64priv) {
                const s = atob(b64priv);
                const ab = new Uint8Array(s.length);
                for (let i = 0; i < s.length; i++) ab[i] = s.charCodeAt(i);
                // Try ECDH first, fall back to RSA-OAEP for legacy keys
                try {
                  privKey  = await window.crypto.subtle.importKey('pkcs8', ab.buffer, { name: 'ECDH', namedCurve: 'P-256' }, true, ['deriveKey', 'deriveBits']);
                  privAlgo = 'ecdh';
                } catch (e) {
                  privKey  = await window.crypto.subtle.importKey('pkcs8', ab.buffer, { name: 'RSA-OAEP', hash: 'SHA-256' }, true, ['decrypt']);
                  privAlgo = 'rsa';
                }
              }
            } catch (ke) { /* no private key available */ }

            if (!privKey) {
              try { window.setE2EStatus && window.setE2EStatus('missing'); } catch (e) {}
            } else {
              const subtle = window.crypto.subtle;

              async function decryptElWithKey(el, key) {
                const encType = el.getAttribute('data-enc');
                if (encType === 'dh') {
                  const peer = el.getAttribute('data-peer'); // always the other person
                  const iv   = el.getAttribute('data-iv');
                  const ct   = el.getAttribute('data-ct');
                  if (!(peer && iv && ct)) return;
                  // Derive shared key: my private key + the other party's public key
                  const theirPub  = await window.E2E._fetchPeerPublicKey(peer);
                  const sharedKey = await window.E2E._deriveSharedKey(key, theirPub, 'decrypt');
                  const s2ab = b => { const ss = atob(b); const a = new Uint8Array(ss.length); for (let i=0;i<ss.length;i++) a[i]=ss.charCodeAt(i); return a.buffer; };
                  const plain = await subtle.decrypt({ name: 'AES-GCM', iv: new Uint8Array(s2ab(iv)) }, sharedKey, s2ab(ct));
                  el.textContent = new TextDecoder().decode(plain);
                } else if (encType === 'hybrid') {
                  const wk = el.getAttribute('data-wk');
                  const iv = el.getAttribute('data-iv');
                  const ct = el.getAttribute('data-ct');
                  if (!(wk && iv && ct)) return;
                  // Unwrap AES key with the pre-imported RSA private key
                  const s2ab = b64 => { const s = atob(b64); const a = new Uint8Array(s.length); for (let i=0;i<s.length;i++) a[i]=s.charCodeAt(i); return a.buffer; };
                  const rawAes = await subtle.decrypt({ name: 'RSA-OAEP' }, key, s2ab(wk));
                  const aesKey = await subtle.importKey('raw', rawAes, { name: 'AES-GCM' }, false, ['decrypt']);
                  const plain = await subtle.decrypt({ name: 'AES-GCM', iv: new Uint8Array(s2ab(iv)) }, aesKey, s2ab(ct));
                  el.textContent = new TextDecoder().decode(plain);
                } else if (encType === 'rsa') {
                  const b64 = el.getAttribute('data-ct');
                  if (!b64) return;
                  const s2ab = b64 => { const s = atob(b64); const a = new Uint8Array(s.length); for (let i=0;i<s.length;i++) a[i]=s.charCodeAt(i); return a.buffer; };
                  const plain = await subtle.decrypt({ name: 'RSA-OAEP' }, key, s2ab(b64));
                  el.textContent = new TextDecoder().decode(plain);
                }
              }

              // Decrypt sequentially on the off-screen staging node
              const nodes = staging.querySelectorAll('.details p[data-enc]');
              for (const el of Array.from(nodes)) {
                try { await decryptElWithKey(el, privKey); } catch (e) { /* leave as-is */ }
              }
            }
          }
        } catch (e) { /* ignore */ }

        // Atomically swap fully-decrypted content into the live chat box
        chatBox.innerHTML = staging.innerHTML;
        if (!chatBox.classList.contains('active')) scrollToBottom();
      }
    } catch (err) {
      // console.error(err);
    }
  };

  const scrollToBottom = () => { chatBox.scrollTop = chatBox.scrollHeight; };

  // initial fetch and then poll
  try {
    if (window.Keys && typeof window.Keys.ensureKeys === 'function') {
      // wait for key generation/upload to complete before initial fetch
      await window.Keys.ensureKeys();
    }
  } catch (e) {
    // ignore
  }
  fetchChats();
  setInterval(fetchChats, 1000);
})();
