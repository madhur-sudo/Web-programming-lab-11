// script.js — ACCESS
// Vanilla JS only. No frameworks.

(function () {
  'use strict';

  // -------------------------------------------------------------------------
  // 1. Show / Hide Password Toggle
  // -------------------------------------------------------------------------

  const toggleBtn = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');

  if (toggleBtn && passwordInput) {
    toggleBtn.addEventListener('click', function () {
      const isHidden = passwordInput.type === 'password';
      passwordInput.type = isHidden ? 'text' : 'password';

      // Swap eye icons
      const showIcon = toggleBtn.querySelector('.eye-icon--show');
      const hideIcon = toggleBtn.querySelector('.eye-icon--hide');
      if (showIcon) showIcon.style.display = isHidden ? 'none' : '';
      if (hideIcon) hideIcon.style.display = isHidden ? '' : 'none';

      // Update accessible label
      toggleBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
      toggleBtn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
    });
  }

  // -------------------------------------------------------------------------
  // 2. Login Form — Submit Spinner
  // -------------------------------------------------------------------------

  const loginForm = document.getElementById('loginForm') || document.getElementById('registerForm');
  const submitBtn = document.getElementById('submitBtn');

  if (loginForm && submitBtn) {
    loginForm.addEventListener('submit', function () {
      // Only show spinner if basic HTML5 validity passes
      if (loginForm.checkValidity() !== false) {
        submitBtn.classList.add('btn--loading');
        submitBtn.disabled = true;
      }
    });
  }

  // -------------------------------------------------------------------------
  // 3. Copy-to-Clipboard Buttons (when present)
  // -------------------------------------------------------------------------

  document.querySelectorAll('.copy-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const text = btn.getAttribute('data-copy');
      if (!text) return;

      navigator.clipboard.writeText(text).then(function () {
        btn.classList.add('copy-btn--copied');
        const originalTitle = btn.getAttribute('aria-label') || 'Copy';
        btn.setAttribute('aria-label', 'Copied!');

        setTimeout(function () {
          btn.classList.remove('copy-btn--copied');
          btn.setAttribute('aria-label', originalTitle);
        }, 1800);
      }).catch(function () {
        // Fallback: select text manually
        const temp = document.createElement('textarea');
        temp.value = text;
        temp.style.position = 'absolute';
        temp.style.opacity = '0';
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
      });
    });
  });

  // -------------------------------------------------------------------------
  // 4. Dashboard: Confirm before logout
  // -------------------------------------------------------------------------

  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', function (e) {
      if (!window.confirm('Are you sure you want to sign out?')) {
        e.preventDefault();
      }
    });
  }

  // -------------------------------------------------------------------------
  // 5. Dashboard: Prevent back-button re-access after page load
  //    (Supplementary to server-side auth check)
  // -------------------------------------------------------------------------

  if (document.body.classList.contains('page-dashboard')) {
    // Replace history state so back button cannot re-enter without server check
    history.replaceState(null, '', window.location.href);
    window.addEventListener('popstate', function () {
      window.location.replace('login.php');
    });
  }

  // -------------------------------------------------------------------------
  // 6. Auto-dismiss alerts after a delay (optional UX)
  //    Only for non-critical info alerts; leave error alerts visible.
  // -------------------------------------------------------------------------

  // (No auto-dismiss for error alerts — user must see them.)

})();
