document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("registerForm");
  const passwordInput = document.getElementById("password");
  const passwordConfirmInput = document.getElementById("password_confirm");
  const emailInput = document.getElementById("email");

  // Check if client-side validation is disabled
  const scriptTag = document.currentScript || document.querySelector('script[src*="register.js"]');
  const disableValidation = scriptTag?.dataset.disableValidation === 'true';

  const commonPasswords = [
    "password","123456","12345678","qwerty","abc123","Password123!","letmein","admin","welcome"
  ];

  [passwordInput, passwordConfirmInput, emailInput].forEach(input => {
    input.addEventListener("input", () => input.setCustomValidity(""));
  });

  form.addEventListener("submit", (e) => {
    // If validation is disabled, skip all checks
    if (disableValidation) {
      form.submit();
      return;
    }

    e.preventDefault();

    passwordInput.setCustomValidity("");
    passwordConfirmInput.setCustomValidity("");
    emailInput.setCustomValidity("");

    const password = passwordInput.value;
    const passwordConfirm = passwordConfirmInput.value;

    const errors = [];

    if (password.length < 8) {
      errors.push("Password must be at least 8 characters long.");
    }
    if (!/[A-Z]/.test(password)) {
      errors.push("Password must contain at least one uppercase letter.");
    }
    if (!/[a-z]/.test(password)) {
      errors.push("Password must contain at least one lowercase letter.");
    }
    if (!/[0-9]/.test(password)) {
      errors.push("Password must contain at least one number.");
    }
    if (!/[!@#$%^&*(),.?\":{}|<>]/.test(password)) {
      errors.push("Password must contain at least one special character.");
    }
    if (commonPasswords.includes(password)) {
      errors.push("Password is too common. Please choose a stronger password.");
    }
    if (password !== passwordConfirm) {
      errors.push("Passwords do not match.");
    }

    if (errors.length > 0) {
      const msg = errors.join(" ");
      passwordInput.setCustomValidity(msg); 
      passwordInput.reportValidity();
    } else {
      form.submit();
    }
  });
});
