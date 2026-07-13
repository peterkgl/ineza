<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Forgot Password</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/forgot_password.css">
</head>
<body>

<div class="forgot-container">
  <div class="forgot-card">
    
    <!-- BRAND header -->
    <div class="brand">
      <img src="../../src/logo/ineza_logo.png" alt="INEZA African Mining Logo" class="brand-logo-image">
      <div class="brand-sub">Financial Suite</div>
    </div>
    
    <!-- STEPPER PROGRESS BAR -->
    <div class="stepper" id="stepperContainer">
      <div class="step active" id="indicator-step1" title="Request Code">1</div>
      <div class="step-line" id="indicator-line1"></div>
      <div class="step" id="indicator-step2" title="Verify Identity">2</div>
      <div class="step-line" id="indicator-line2"></div>
      <div class="step" id="indicator-step3" title="New Password">3</div>
    </div>

    <!-- NOTIFICATION SYSTEM -->
    <div class="notification-toast" id="notifToast" style="display:none;">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
      <span id="notifText"></span>
    </div>

    <!-- ERROR ALERT -->
    <div class="alert-msg error" id="alertError" style="display:none;">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <span id="errorText"></span>
    </div>

    <!-- ==========================================
         STEP 1: EMAIL REQUEST
         ========================================== -->
    <div class="step-section active" id="section-step1">
      <div class="form-title">Forgot password?</div>
      <div class="form-desc">Enter your email address and we'll send a verification code to reset your password.</div>

      <div class="field">
        <label for="email">Email address</label>
        <div class="field-wrap">
          <svg class="field-icon" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          <input type="email" id="email" placeholder="admin@inezamining.rw" required autocomplete="email">
        </div>
      </div>

      <button class="btn-action" onclick="handleSendCode()" id="btn-step1">
        <span class="btn-label">Send Verification Code</span>
        <div class="spinner" id="spinner-step1"></div>
      </button>
    </div>

    <!-- ==========================================
         STEP 2: VERIFICATION CODE
         ========================================== -->
    <div class="step-section" id="section-step2">
      <div class="form-title">Enter verification code</div>
      <div class="form-desc">We have sent a 6-digit verification code to <strong id="displayEmail"></strong>.</div>

      <div class="field">
        <label>Verification Code</label>
        <div class="code-inputs">
          <input type="text" maxlength="1" class="code-box" id="code-0" oninput="moveToNext(this, 0)" onkeydown="moveBack(event, 0)" autocomplete="off">
          <input type="text" maxlength="1" class="code-box" id="code-1" oninput="moveToNext(this, 1)" onkeydown="moveBack(event, 1)" autocomplete="off">
          <input type="text" maxlength="1" class="code-box" id="code-2" oninput="moveToNext(this, 2)" onkeydown="moveBack(event, 2)" autocomplete="off">
          <input type="text" maxlength="1" class="code-box" id="code-3" oninput="moveToNext(this, 3)" onkeydown="moveBack(event, 3)" autocomplete="off">
          <input type="text" maxlength="1" class="code-box" id="code-4" oninput="moveToNext(this, 4)" onkeydown="moveBack(event, 4)" autocomplete="off">
          <input type="text" maxlength="1" class="code-box" id="code-5" oninput="moveToNext(this, 5)" onkeydown="moveBack(event, 5)" autocomplete="off">
        </div>
      </div>

      <!-- TIMER / RESEND CONTAINER -->
      <div class="timer-container" id="timerContainer">
        Resend code in <span id="timerCountdown">60</span>s
      </div>
      <div class="resend-container" id="resendContainer" style="display:none;">
        Didn't receive the code? <a href="javascript:void(0)" onclick="handleResendCode()" class="resend-link">Resend Code</a>
      </div>

      <button class="btn-action" onclick="handleVerifyCode()" id="btn-step2" style="margin-top: 24px;">
        <span class="btn-label">Verify Code</span>
        <div class="spinner" id="spinner-step2"></div>
      </button>
    </div>

    <!-- ==========================================
         STEP 3: CREATE NEW PASSWORD
         ========================================== -->
    <div class="step-section" id="section-step3">
      <div class="form-title">Create new password</div>
      <div class="form-desc">Choose a secure, strong password for your account.</div>

      <div class="field">
        <label for="newPassword">New Password</label>
        <div class="field-wrap">
          <svg class="field-icon" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <input type="password" id="newPassword" placeholder="••••••••" required oninput="validatePasswordStrength()">
          <button class="toggle-password" onclick="togglePassVisibility('newPassword')" type="button">
            <svg class="eye-open" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <!-- PASSWORD STRENGTH METER -->
      <div class="strength-meter">
        <div class="strength-bar" id="strengthBar"></div>
        <div class="strength-text" id="strengthText">Password Strength: Empty</div>
      </div>

      <div class="field" style="margin-top: 16px;">
        <label for="confirmPassword">Confirm Password</label>
        <div class="field-wrap">
          <svg class="field-icon" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <input type="password" id="confirmPassword" placeholder="••••••••" required>
          <button class="toggle-password" onclick="togglePassVisibility('confirmPassword')" type="button">
            <svg class="eye-open" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <button class="btn-action" onclick="handleResetPassword()" id="btn-step3">
        <span class="btn-label">Reset Password</span>
        <div class="spinner" id="spinner-step3"></div>
      </button>
    </div>

    <!-- ==========================================
         STEP 4: SUCCESS VIEW
         ========================================== -->
    <div class="step-section" id="section-step4">
      <div class="success-icon-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <div class="form-title" style="text-align: center;">Password Updated!</div>
      <div class="form-desc" style="text-align: center; margin-bottom: 24px;">Your password has been successfully updated. Please sign in again using your new password.</div>

      <a href="../../index" class="btn-action" style="text-decoration: none;">
        <span>Sign In</span>
      </a>
    </div>

    <!-- BACK TO SIGN IN FOOTER (hidden in success view) -->
    <div class="form-footer" id="footerContainer">
      <a href="../../index" class="back-link">
        <svg viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back to Sign In
      </a>
    </div>

  </div>
</div>

<script>
// FRONTEND DATA AND APP STATE
var appState = {
    email: '',
    verificationCode: '',
    timerInterval: null,
    timeLeft: 60
};

// MOCK ACTION: SEND CODE
function handleSendCode() {
    var emailInput = document.getElementById('email');
    var email = emailInput.value.trim();
    var alertError = document.getElementById('alertError');
    var errorText = document.getElementById('errorText');
    var btn = document.getElementById('btn-step1');
    var spinner = document.getElementById('spinner-step1');
    
    // Email Validation
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email || !emailRegex.test(email)) {
        showAlert("Please enter a valid email address.");
        return;
    }
    
    hideAlert();
    setLoading(btn, spinner, true);
    
    setTimeout(function() {
        setLoading(btn, spinner, false);
        appState.email = email;
        document.getElementById('displayEmail').innerText = email;
        
        // Generate a mock code for the user to use easily
        appState.verificationCode = Math.floor(100000 + Math.random() * 900000).toString();
        
        // Transition to Step 2
        transitionToStep(2);
        
        // Start countdown timer
        startResendTimer();
        
        // Show code notification
        showToast("Code sent! For testing, use verification code: " + appState.verificationCode);
        
        // Focus first code box
        setTimeout(function() {
            document.getElementById('code-0').focus();
        }, 100);
    }, 1200);
}

// MOCK ACTION: RESEND CODE
function handleResendCode() {
    appState.verificationCode = Math.floor(100000 + Math.random() * 900000).toString();
    showToast("New code sent! For testing, use: " + appState.verificationCode);
    startResendTimer();
    
    // Clear code boxes
    for(var i = 0; i < 6; i++) {
        document.getElementById('code-' + i).value = '';
    }
    document.getElementById('code-0').focus();
}

// MOCK ACTION: VERIFY CODE
function handleVerifyCode() {
    var code = '';
    for (var i = 0; i < 6; i++) {
        code += document.getElementById('code-' + i).value.trim();
    }
    
    var btn = document.getElementById('btn-step2');
    var spinner = document.getElementById('spinner-step2');
    
    if (code.length < 6) {
        showAlert("Please enter the full 6-digit verification code.");
        return;
    }
    
    if (code !== appState.verificationCode) {
        showAlert("Incorrect verification code. Please try again.");
        return;
    }
    
    hideAlert();
    setLoading(btn, spinner, true);
    
    setTimeout(function() {
        setLoading(btn, spinner, false);
        clearInterval(appState.timerInterval);
        
        // Transition to Step 3
        transitionToStep(3);
    }, 1000);
}

// MOCK ACTION: RESET PASSWORD
function handleResetPassword() {
    var newPasswordInput = document.getElementById('newPassword');
    var confirmPasswordInput = document.getElementById('confirmPassword');
    var newPassword = newPasswordInput.value;
    var confirmPassword = confirmPasswordInput.value;
    var btn = document.getElementById('btn-step3');
    var spinner = document.getElementById('spinner-step3');
    
    // Password validation
    if (newPassword.length < 8) {
        showAlert("Password must be at least 8 characters long.");
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showAlert("Passwords do not match. Please verify.");
        return;
    }
    
    hideAlert();
    setLoading(btn, spinner, true);
    
    setTimeout(function() {
        setLoading(btn, spinner, false);
        // Transition to Success (Step 4)
        transitionToStep(4);
    }, 1500);
}

// TIMING & COUNTDOWN
function startResendTimer() {
    clearInterval(appState.timerInterval);
    appState.timeLeft = 60;
    
    document.getElementById('timerContainer').style.display = 'block';
    document.getElementById('resendContainer').style.display = 'none';
    document.getElementById('timerCountdown').innerText = appState.timeLeft;
    
    appState.timerInterval = setInterval(function() {
        appState.timeLeft--;
        document.getElementById('timerCountdown').innerText = appState.timeLeft;
        
        if (appState.timeLeft <= 0) {
            clearInterval(appState.timerInterval);
            document.getElementById('timerContainer').style.display = 'none';
            document.getElementById('resendContainer').style.display = 'block';
        }
    }, 1000);
}

// STEP TRANSITIONS
function transitionToStep(step) {
    // Hide all step sections
    var sections = document.getElementsByClassName('step-section');
    for (var i = 0; i < sections.length; i++) {
        sections[i].classList.remove('active');
    }
    
    // Show active step section
    document.getElementById('section-step' + step).classList.add('active');
    
    // Update stepper indicators
    if (step <= 3) {
        for (var i = 1; i <= 3; i++) {
            var ind = document.getElementById('indicator-step' + i);
            var line = document.getElementById('indicator-line' + (i - 1));
            
            if (i < step) {
                ind.classList.add('completed');
                ind.classList.remove('active');
                ind.innerHTML = '&#10003;'; // Checkmark
            } else if (i === step) {
                ind.classList.add('active');
                ind.classList.remove('completed');
                ind.innerText = i;
            } else {
                ind.classList.remove('active', 'completed');
                ind.innerText = i;
            }
            
            if (line) {
                if (i < step) {
                    line.classList.add('active');
                } else {
                    line.classList.remove('active');
                }
            }
        }
    } else {
        // Step 4 (Success): Hide stepper and footer back link
        document.getElementById('stepperContainer').style.display = 'none';
        document.getElementById('footerContainer').style.display = 'none';
    }
}

// 6-DIGIT CODE INPUT UTILITIES
function moveToNext(input, index) {
    // Strip non-numbers
    input.value = input.value.replace(/[^0-9]/g, '');
    
    if (input.value.length === 1 && index < 5) {
        document.getElementById('code-' + (index + 1)).focus();
    }
}

function moveBack(event, index) {
    if (event.key === "Backspace" && event.target.value.length === 0 && index > 0) {
        var prevBox = document.getElementById('code-' + (index - 1));
        prevBox.focus();
        prevBox.value = '';
    }
}

// PASSWORD STRENGTH VALIDATOR
function validatePasswordStrength() {
    var pass = document.getElementById('newPassword').value;
    var bar = document.getElementById('strengthBar');
    var text = document.getElementById('strengthText');
    
    if (!pass) {
        bar.className = "strength-bar";
        bar.style.width = "0%";
        text.innerText = "Password Strength: Empty";
        return;
    }
    
    var score = 0;
    if (pass.length >= 8) score++;
    if (/[A-Z]/.test(pass)) score++;
    if (/[0-9]/.test(pass)) score++;
    if (/[^A-Za-z0-9]/.test(pass)) score++;
    
    bar.className = "strength-bar";
    if (score <= 1) {
        bar.classList.add('weak');
        bar.style.width = "25%";
        text.innerText = "Password Strength: Weak";
    } else if (score === 2) {
        bar.classList.add('medium');
        bar.style.width = "50%";
        text.innerText = "Password Strength: Medium";
    } else if (score === 3) {
        bar.classList.add('strong');
        bar.style.width = "75%";
        text.innerText = "Password Strength: Strong";
    } else if (score === 4) {
        bar.classList.add('very-strong');
        bar.style.width = "100%";
        text.innerText = "Password Strength: Very Strong";
    }
}

// HELPER UTILITIES
function togglePassVisibility(id) {
    var input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
    } else {
        input.type = 'password';
    }
}

function showAlert(msg) {
    var alertError = document.getElementById('alertError');
    var errorText = document.getElementById('errorText');
    alertError.style.display = 'flex';
    errorText.innerText = msg;
}

function hideAlert() {
    document.getElementById('alertError').style.display = 'none';
}

function showToast(msg) {
    var toast = document.getElementById('notifToast');
    var notifText = document.getElementById('notifText');
    toast.style.display = 'flex';
    notifText.innerText = msg;
    
    // Auto-hide toast after 8 seconds
    setTimeout(function() {
        toast.style.display = 'none';
    }, 8000);
}

function setLoading(btn, spinner, isLoading) {
    btn.disabled = isLoading;
    var label = btn.querySelector('.btn-label');
    if (label) {
        label.style.display = isLoading ? 'none' : 'block';
    }
    if (spinner) {
        spinner.style.display = isLoading ? 'block' : 'none';
    }
}
</script>
</body>
</html>
