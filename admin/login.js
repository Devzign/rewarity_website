document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('loginForm');
  const emailInput = document.getElementById('email');
  const passwordInput = document.getElementById('password');
  const emailError = document.getElementById('emailError');
  const passwordError = document.getElementById('passwordError');
  const passwordToggle = document.getElementById('passwordToggle');
  const loginBtn = document.querySelector('.login-btn');

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  const showError = (input, errorElement, message) => {
    if (!input || !errorElement) {
      return;
    }
    const group = input.closest('.form-group');
    if (group) {
      group.classList.add('error');
    }
    errorElement.textContent = message;
    errorElement.classList.add('show');
  };

  const clearError = (input, errorElement) => {
    if (!input || !errorElement) {
      return;
    }
    const group = input.closest('.form-group');
    if (group) {
      group.classList.remove('error');
    }
    errorElement.classList.remove('show');
    errorElement.textContent = '';
  };

  if (passwordToggle && passwordInput) {
    passwordToggle.addEventListener('click', () => {
      const type = passwordInput.type === 'password' ? 'text' : 'password';
      passwordInput.type = type;
      passwordToggle.style.transform = type === 'text' ? 'rotate(180deg)' : 'rotate(0deg)';
    });
  }

  if (emailInput && emailError) {
    emailInput.addEventListener('input', () => clearError(emailInput, emailError));
    emailInput.addEventListener('blur', () => {
      const value = emailInput.value.trim();
      if (value === '') {
        showError(emailInput, emailError, 'Email address is required');
      } else if (!emailRegex.test(value)) {
        showError(emailInput, emailError, 'Please enter a valid email address');
      } else {
        clearError(emailInput, emailError);
      }
    });
  }

  if (passwordInput && passwordError) {
    passwordInput.addEventListener('input', () => clearError(passwordInput, passwordError));
    passwordInput.addEventListener('blur', () => {
      const value = passwordInput.value;
      if (value === '') {
        showError(passwordInput, passwordError, 'Password is required');
      } else if (value.length < 6) {
        showError(passwordInput, passwordError, 'Password must be at least 6 characters long');
      } else {
        clearError(passwordInput, passwordError);
      }
    });
  }

  if (form) {
    form.addEventListener('submit', (event) => {
      let hasError = false;

      if (emailInput && emailError) {
        const value = emailInput.value.trim();
        if (value === '') {
          showError(emailInput, emailError, 'Email address is required');
          hasError = true;
        } else if (!emailRegex.test(value)) {
          showError(emailInput, emailError, 'Please enter a valid email address');
          hasError = true;
        }
      }

      if (passwordInput && passwordError) {
        const value = passwordInput.value;
        if (value === '') {
          showError(passwordInput, passwordError, 'Password is required');
          hasError = true;
        } else if (value.length < 6) {
          showError(passwordInput, passwordError, 'Password must be at least 6 characters long');
          hasError = true;
        }
      }

      if (hasError) {
        event.preventDefault();
        return;
      }

      if (loginBtn) {
        loginBtn.classList.add('loading');
      }
    });
  }
});
