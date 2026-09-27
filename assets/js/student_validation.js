

/* =========================================
   CREATE STUDENT FORM VALIDATION
========================================= */
function validateCreateForm() {
    const username = document.getElementById("username").value.trim();
    const email    = document.getElementById("email").value.trim();
    const id       = document.getElementById("id_number").value.trim();
    const pwd      = document.getElementById("password").value;

    if (username.length < 3) {
        alert("Username must be at least 3 characters");
        return false;
    }

    if (!email.includes("@")) {
        alert("Please enter a valid email address");
        return false;
    }

    if (id.length < 4) {
        alert("ID Number must be at least 4 characters");
        return false;
    }

    if (pwd.length < 8) {
        alert("Password must be at least 8 characters");
        return false;
    }

    if (!(/[A-Z]/.test(pwd) && /[0-9]/.test(pwd))) {
        alert("Password must contain at least one uppercase letter and one number");
        return false;
    }

    return true;
}
/* ===============================
  

/* ===============================
   FORM VALIDATION
================================ */
function validateCreateForm() {
    const username = document.getElementById("username").value.trim();
    const email = document.getElementById("email").value.trim();
    const id = document.getElementById("id_number").value.trim();
    const pwd = document.getElementById("password").value;

    if (username.length < 3) {
        alert("Username must be at least 3 characters");
        return false;
    }

    if (!email.includes("@")) {
        alert("Enter a valid email address");
        return false;
    }

    if (id.length < 4) {
        alert("ID Number must be at least 4 characters");
        return false;
    }

    if (pwd.length < 8) {
        alert("Password must be at least 8 characters");
        return false;
    }

    return true;
}