document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("registerForm");
  const passwordInput = document.getElementById("password");
  const passwordConfirmInput = document.getElementById("password_confirm");
  const emailInput = document.getElementById("email");

  [passwordInput, passwordConfirmInput, emailInput].forEach(input => {
    input.addEventListener("input", () => input.setCustomValidity(""));
  });

  form.addEventListener("submit", (e) => {
    e.preventDefault(); 

    passwordInput.setCustomValidity("");
    passwordConfirmInput.setCustomValidity("");
    emailInput.setCustomValidity("");

    const password = passwordInput.value;
    const passwordConfirm = passwordConfirmInput.value;

    if (password.length < 8) {
      passwordInput.setCustomValidity("Password must be at least 8 characters long.");
    } else if (!/[A-Z]/.test(password)) {
      passwordInput.setCustomValidity("Password must contain at least one uppercase letter.");
    } else if (!/[a-z]/.test(password)) {
      passwordInput.setCustomValidity("Password must contain at least one lowercase letter.");
    } else if (!/[0-9]/.test(password)) {
      passwordInput.setCustomValidity("Password must contain at least one number.");
    } else if (!/[!@#$%^&*(),.?\":{}|<>]/.test(password)) {
      passwordInput.setCustomValidity("Password must contain at least one special character.");
    }

    if (password !== passwordConfirm) {
      passwordConfirmInput.setCustomValidity("Passwords do not match.");
    }

    if (form.checkValidity()) {
      form.submit(); 
    } else {
      form.reportValidity(); 
    }
  });
});
