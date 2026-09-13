(() => {
  // Support multiple forms/fields: find all toggle icons and bind to closest password input
  const toggles = document.querySelectorAll('.form .field i');
  if (!toggles || toggles.length === 0) return;

  toggles.forEach(toggle => {
    toggle.addEventListener('click', () => {
      // try to find a password input within the same form
      const form = toggle.closest('form');
      const pwd = form ? form.querySelector("input[type='password']") : document.querySelector(".form input[type='password']");
      if (!pwd) return;
      if (pwd.type === 'password') {
        pwd.type = 'text';
        toggle.classList.add('active');
      } else {
        pwd.type = 'password';
        toggle.classList.remove('active');
      }
    });
  });
})();
