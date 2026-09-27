document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById('loginForm') || document.querySelector('form');
  const username = document.getElementById('username');
  const password = document.getElementById('password');
  const role = document.getElementById('role');

  // Error elements (explicit IDs used in your HTML)
  const usernameError = document.getElementById('usernameError');
  const passwordError = document.getElementById('passwordError');
  const roleError = document.getElementById('roleError');

  // Helper to show/hide error text
  function showError(el, message) {
    if (!el) return;
    el.textContent = message || '';
    if (message) el.classList.add('visible');
    else el.classList.remove('visible');
  }

  // Validation functions
  function validateUsername() {
    const val = username.value.trim();
    if (!val) {
      showError(usernameError, 'Username is required.');
      return false;
    }
    if (!/^[A-Za-z\s]+$/.test(val)) {
      showError(usernameError, 'Username must contain only letters and spaces.');
      return false;
    }
    showError(usernameError, '');
    return true;
  }

  function validatePassword() {
    const val = password.value;
    if (!val) {
      showError(passwordError, 'Password is required.');
      return false;
    }
    if (val.length < 6) {
      showError(passwordError, 'Password must be at least 6 characters long.');
      return false;
    }
    showError(passwordError, '');
    return true;
  }

  function validateRole() {
    const val = role.value;
    if (!val) {
      showError(roleError, 'Please select a role.');
      return false;
    }
    showError(roleError, '');
    return true;
  }

  // Real-time validation
  if (username) username.addEventListener('input', validateUsername);
  if (password) password.addEventListener('input', validatePassword);
  if (role) role.addEventListener('change', validateRole);

  // Form submit
  if (form) {
    form.addEventListener('submit', function (e) {
      // Clear server-side banner if present (optional)
      const serverError = document.getElementById('serverError');
      if (serverError) serverError.classList.remove('visible');

      const okUser = validateUsername();
      const okPass = validatePassword();
      const okRole = validateRole();

      if (!(okUser && okPass && okRole)) {
        e.preventDefault();
        // focus first invalid field
        if (!okUser) username.focus();
        else if (!okPass) password.focus();
        else role.focus();
      }
      // otherwise allow normal submit to login.php
    });
  }

  // Respect reduced motion preference (stop any animations/rotations elsewhere)
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (prefersReduced.matches) {
    // nothing specific here for validation, but you can disable animations if needed
  }
});



