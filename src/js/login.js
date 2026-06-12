/* ==========================================
   INEZA AFRICAN MINING — Login Page Logic
   ========================================== */

/**
 * Toggle password visibility between text and password
 */
function togglePassword() {
  const pw = document.getElementById('password');
  const icon = document.getElementById('eyeIcon');
  if (!pw || !icon) return;

  if (pw.type === 'password') {
    pw.type = 'text';
    icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
  } else {
    pw.type = 'password';
    icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
  }
}

/**
 * Handle form submission and mock validation
 */
function handleLogin() {
  const emailInput = document.getElementById('email');
  const pwInput = document.getElementById('password');
  const errEl = document.getElementById('alertError');
  const sucEl = document.getElementById('alertSuccess');
  const btn = document.getElementById('loginBtn');
  const txt = document.getElementById('btnText');
  const spin = document.getElementById('spinner');
  const arr = document.getElementById('btnArrow');
  const errTxt = document.getElementById('errorText');

  if (!emailInput || !pwInput || !errEl || !sucEl || !btn || !txt || !spin || !arr || !errTxt) return;

  const email = emailInput.value.trim();
  const pw = pwInput.value;

  errEl.style.display = 'none';
  sucEl.style.display = 'none';

  if (!email) {
    errTxt.textContent = 'Please enter your email address.';
    errEl.style.display = 'flex';
    emailInput.focus();
    return;
  }
  if (!pw) {
    errTxt.textContent = 'Please enter your password.';
    errEl.style.display = 'flex';
    pwInput.focus();
    return;
  }

  // Loading state
  btn.disabled = true;
  txt.textContent = 'Signing in…';
  spin.style.display = 'block';
  arr.style.display = 'none';
  btn.style.opacity = '.85';

  setTimeout(() => {
    spin.style.display = 'none';
    arr.style.display = 'block';
    btn.disabled = false;
    btn.style.opacity = '1';
    txt.textContent = 'Sign In';

    // Mock verification: accept any valid format email + min 6 char password
    if (email.includes('@') && pw.length >= 6) {
      sucEl.style.display = 'flex';
      setTimeout(() => {
        window.location.href = 'pages/dashboard.php';
      }, 1000);
    } else {
      errTxt.textContent = 'Invalid email or password. Please try again (min 6 char password).';
      errEl.style.display = 'flex';
    }
  }, 1200);
}

// Add enter key submission listener
document.addEventListener('keydown', function (e) {
  if (e.key === 'Enter') {
    handleLogin();
  }
});
