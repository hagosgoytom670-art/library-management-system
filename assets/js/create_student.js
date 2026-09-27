// ==============================
// VALIDATION RULES
// ==============================
const validationRules = {
  username: {
    regex: /^(?=.*[A-Za-z])[A-Za-z\s]{4,20}$/,
    message: "Username must be 4–20 characters and include letters."
  },

  email: {
    regex: /^[\w\.-]+@[\w\.-]+\.\w{2,}$/,
    message: "Invalid email format."
  },

  id_number: {
    regex: /^[0-9\/]{6,20}$/,
    message: "ID must be 6–20 characters (digits and / only)."
  },

  department: {
    regex: /^(CS|IT|Accounting|Business|ECE|Law)$/,
    message: "Select a valid department."
  },

  year: {
    regex: /^[1-5]$/,
    message: "Year must be 1–5."
  },

  registered_calendar: {
    custom: value => value !== "",
    message: "Select a registration date."
  },

  password: {
    regex: /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/,
    message: "Weak password (8+ chars, upper, lower, number, symbol)."
  },

  profile_picture: {
    custom: input => {
      if (input.files.length === 0) return true;
      const file = input.files[0];
      const types = ["image/jpeg", "image/png", "image/gif", "image/webp"];
      return types.includes(file.type) && file.size <= 2 * 1024 * 1024;
    },
    message: "Only images (max 2MB)."
  }
};

// ==============================
// ERROR HANDLING
// ==============================
function showError(input, message) {
  let error = input.parentNode.querySelector(".error-banner");

  if (!error) {
    error = document.createElement("div");
    error.className = "error-banner";
    error.style.color = "red";
    input.parentNode.appendChild(error);
  }

  error.textContent = message;
  input.style.border = "2px solid red";
}

function clearError(input) {
  const error = input.parentNode.querySelector(".error-banner");
  if (error) error.remove();
  input.style.border = "2px solid green";
}

// ==============================
// FIELD VALIDATION
// ==============================
function validateField(input) {
  const rule = validationRules[input.name];
  if (!rule) return true;

  let value = input.type === "file" ? input : input.value.trim();
  let valid = rule.regex ? rule.regex.test(value) : rule.custom(value);

  if (!valid) {
    showError(input, rule.message);
    return false;
  } else {
    clearError(input);
    return true;
  }
}

// ==============================
// FORM VALIDATION
// ==============================
function validateForm(form) {
  let valid = true;

  form.querySelectorAll("[name]").forEach(input => {
    if (!validateField(input)) valid = false;
  });

  return valid;
}

// ==============================
// AJAX DUPLICATE CHECK
// ==============================
async function checkDuplicate(input) {
  const field = input.name;
  const value = input.value.trim();

  if (!value) return;

  try {
    const res = await fetch(`check_user.php?${field}=${encodeURIComponent(value)}`);
    const data = await res.json();

    if (field === "email" && data.emailExists) {
      showError(input, "Email already exists.");
    }

    if (field === "id_number" && data.idExists) {
      showError(input, "ID already exists.");
    }
  } catch (err) {
    console.error("Duplicate check failed", err);
  }
}

// ==============================
// PASSWORD STRENGTH
// ==============================
function checkPasswordStrength(input) {
  const password = input.value;

  const bar = document.getElementById("strength-bar");
  const text = document.getElementById("strength-text");

  if (!bar || !text) return;

  let strength = 0;

  if (password.length >= 8) strength++;
  if (/[A-Z]/.test(password)) strength++;
  if (/[a-z]/.test(password)) strength++;
  if (/\d/.test(password)) strength++;
  if (/[@$!%*?&]/.test(password)) strength++;

  const percent = (strength / 5) * 100;
  bar.style.width = percent + "%";

  if (strength <= 2) {
    bar.style.background = "red";
    text.textContent = "Weak";
  } else if (strength <= 4) {
    bar.style.background = "orange";
    text.textContent = "Medium";
  } else {
    bar.style.background = "green";
    text.textContent = "Strong";
  }
}

// ==============================
// INIT
// ==============================
document.addEventListener("DOMContentLoaded", () => {

  console.log("JS Loaded ✅");

  // Real-time validation
  document.querySelectorAll("form [name]").forEach(input => {

    input.addEventListener("input", () => validateField(input));
    input.addEventListener("change", () => validateField(input));

    // Duplicate check
    if (input.name === "email" || input.name === "id_number") {
      input.addEventListener("blur", () => checkDuplicate(input));
    }

    // Password strength
    if (input.name === "password") {
      input.addEventListener("input", () => checkPasswordStrength(input));
    }

  });

  // Form submit
  document.querySelectorAll("form").forEach(form => {
    form.addEventListener("submit", function(e) {
      if (!validateForm(this)) {
        e.preventDefault();
      }
    });
  });

});