(() => {
  const genBtn = document.getElementById('generate-keys');
  const exportBtn = document.getElementById('export-private-key');
  const importBtn = document.getElementById('import-private-key-btn');
  if (genBtn) {
    genBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      if (!window.E2E || typeof window.E2E.genKeyPair !== 'function') return alert('E2E module not loaded');
      genBtn.disabled = true;
      try {
        await window.E2E.genKeyPair();
        alert('Keys generated and public key uploaded');
      } catch (err) {
        alert('Key generation failed');
      }
      genBtn.disabled = false;
    });
  }
  if (exportBtn) {
    exportBtn.addEventListener('click', function (e) {
      e.preventDefault();
      const b64 = localStorage.getItem('e2e_priv');
      if (!b64) { alert('No private key stored in this browser'); return; }
      try {
        const blob = new Blob([b64], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'private_e2e_key.b64.txt';
        document.body.appendChild(a);
        a.click();
        setTimeout(function () { a.remove(); URL.revokeObjectURL(url); }, 1000);
      } catch (err) {
        alert('Export failed: ' + err.message);
      }
    });
  }
  const fileInput = document.getElementById('import-private-key-file');
  if (importBtn && fileInput) {
    importBtn.addEventListener('click', function (e) {
      e.preventDefault();
      fileInput.value = '';
      fileInput.click();
    });
    fileInput.addEventListener('change', async function () {
      const file = fileInput.files && fileInput.files[0];
      if (!file) return;
      try {
        const text = await file.text();
        const val = text.trim();
        if (!val) { alert('File is empty'); return; }
        if (!window.E2E || typeof window.E2E.importPrivateKeyFromBase64 !== 'function') { alert('E2E module not loaded'); return; }
        const ok = await window.E2E.importPrivateKeyFromBase64(val);
        if (ok) alert('Private key imported successfully'); else alert('Failed to import private key — make sure the file is correct');
      } catch (err) {
        alert('Could not read file: ' + err.message);
      }
    });
  }
})();
