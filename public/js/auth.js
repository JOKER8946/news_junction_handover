/**
 * News Junction - Authentication Logic
 */

document.addEventListener('DOMContentLoaded', () => {
  initTabs();
  initPasswordToggles();
  initForms();
  handleUrlParams();
});

function showAlert(message, isError = false) {
  const alertEl = document.getElementById('authAlert');
  alertEl.className = `alert-message ${isError ? 'alert-error' : 'alert-success'}`;
  alertEl.textContent = message;
  alertEl.style.display = 'block';
  alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function hideAlert() {
  const alertEl = document.getElementById('authAlert');
  alertEl.style.display = 'none';
}

function showTab(targetTab) {
  hideAlert();
  document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.auth-form-panel').forEach(f => f.style.display = 'none');

  const tabBtn = document.querySelector(`[data-target="${targetTab}"]`);
  if (tabBtn) tabBtn.classList.add('active');

  if (targetTab === 'loginTab') {
    document.getElementById('loginForm').style.display = 'block';
  } else if (targetTab === 'signupTab') {
    document.getElementById('signupForm').style.display = 'block';
  } else if (targetTab === 'forgotTab') {
    document.getElementById('forgotForm').style.display = 'block';
  } else if (targetTab === 'resetConfirmTab') {
    document.getElementById('resetConfirmForm').style.display = 'block';
  }
}

function initTabs() {
  document.querySelectorAll('.auth-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      showTab(tab.getAttribute('data-target'));
    });
  });

  document.getElementById('linkForgotPassword').addEventListener('click', (e) => {
    e.preventDefault();
    showTab('forgotTab');
  });

  document.getElementById('linkGoToSignup').addEventListener('click', (e) => {
    e.preventDefault();
    showTab('signupTab');
  });

  document.getElementById('linkGoToLogin').addEventListener('click', (e) => {
    e.preventDefault();
    showTab('loginTab');
  });

  document.getElementById('linkBackToLogin').addEventListener('click', (e) => {
    e.preventDefault();
    showTab('loginTab');
  });
}

function initPasswordToggles() {
  document.querySelectorAll('.password-toggle-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const inputId = btn.getAttribute('data-input');
      const input = document.getElementById(inputId);
      if (input) {
        if (input.type === 'password') {
          input.type = 'text';
          btn.textContent = '🔒';
        } else {
          input.type = 'password';
          btn.textContent = '👁️';
        }
      }
    });
  });
}

function handleUrlParams() {
  const params = new URLSearchParams(window.location.search);
  const type = params.get('type');
  const action = params.get('action');
  const token = params.get('token');

  if (action === 'reset' && token) {
    document.getElementById('resetToken').value = token;
    showTab('resetConfirmTab');
    document.getElementById('authTabs').style.display = 'none';
  } else if (type === 'signup') {
    showTab('signupTab');
  } else {
    showTab('loginTab');
  }
}

function initForms() {
  // Login Form Submission
  const loginForm = document.getElementById('loginForm');
  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideAlert();
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.textContent = 'Signing in...';

    const email = document.getElementById('loginEmail').value.trim();
    const pwd = document.getElementById('loginPassword').value;

    try {
      const res = await API.auth.login(email, pwd);
      if (res.status === 'OK' || (typeof res === 'string' && res.startsWith('OK'))) {
        showAlert('Login successful! Redirecting to news feed...', false);
        setTimeout(() => {
          window.location.assign('/reader.html');
        }, 800);
      } else {
        showAlert(res.message || 'Login failed. Please check your credentials.', true);
      }
    } catch (err) {
      showAlert(err.message || 'Invalid credentials. Please verify your email and password.', true);
    } finally {
      btn.disabled = false;
      btn.textContent = 'Sign In';
    }
  });

  // Sign Up Form Submission
  const signupForm = document.getElementById('signupForm');
  signupForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideAlert();

    const pwd1 = document.getElementById('signPwd1').value;
    const pwd2 = document.getElementById('signPwd2').value;

    if (pwd1 !== pwd2) {
      showAlert('Passwords do not match. Please re-enter.', true);
      return;
    }

    if (pwd1.length < 6) {
      showAlert('Password must be at least 6 characters long.', true);
      return;
    }

    const btn = document.getElementById('signupBtn');
    btn.disabled = true;
    btn.textContent = 'Creating Account...';

    const userData = {
      fullName: document.getElementById('signFullName').value.trim(),
      email: document.getElementById('signEmail').value.trim(),
      countryCode: document.getElementById('countryCode').value,
      phone: document.getElementById('userPhone').value.trim(),
      pincode: document.getElementById('signPincode').value.trim(),
      company: document.getElementById('signCompany').value.trim(),
      password: pwd1
    };

    try {
      const res = await API.auth.register(userData);
      if (res.status === 'OK') {
        showAlert('Account created successfully! Welcome to News Junction.', false);
        setTimeout(() => {
          window.location.assign('/reader.html');
        }, 1000);
      } else {
        showAlert(res.message || 'Could not create account.', true);
      }
    } catch (err) {
      showAlert(err.message || 'Error creating account. Please try again.', true);
    } finally {
      btn.disabled = false;
      btn.textContent = 'Create Account';
    }
  });

  // Forgot Password Form Submission
  const forgotForm = document.getElementById('forgotForm');
  forgotForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideAlert();
    const btn = document.getElementById('forgotBtn');
    btn.disabled = true;
    btn.textContent = 'Sending...';

    const email = document.getElementById('forgotEmail').value.trim();
    try {
      const res = await API.auth.forgotPassword(email);
      showAlert(res.message || 'Password reset instructions have been generated.', false);
    } catch (err) {
      showAlert(err.message || 'Error dispatching reset request.', true);
    } finally {
      btn.disabled = false;
      btn.textContent = 'Send Reset Link';
    }
  });

  // Reset Password Confirm Form
  const resetConfirmForm = document.getElementById('resetConfirmForm');
  resetConfirmForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideAlert();

    const p1 = document.getElementById('newPassword').value;
    const p2 = document.getElementById('confirmNewPassword').value;
    const token = document.getElementById('resetToken').value;

    if (p1 !== p2) {
      showAlert('Passwords do not match.', true);
      return;
    }

    const btn = document.getElementById('resetConfirmBtn');
    btn.disabled = true;
    btn.textContent = 'Updating...';

    try {
      const res = await API.auth.resetPassword(token, p1);
      if (res.status === 'OK') {
        showAlert('Password reset successfully! You can now sign in.', false);
        setTimeout(() => {
          document.getElementById('authTabs').style.display = 'flex';
          showTab('loginTab');
        }, 1500);
      } else {
        showAlert(res.message || 'Failed to reset password.', true);
      }
    } catch (err) {
      showAlert(err.message || 'Failed to update password.', true);
    } finally {
      btn.disabled = false;
      btn.textContent = 'Update Password';
    }
  });
}
