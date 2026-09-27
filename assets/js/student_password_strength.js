console.log("Student validation JS loaded");

document.addEventListener("DOMContentLoaded", function () {

    const passwordInput = document.getElementById("password");
    const strengthBar   = document.getElementById("strengthBar");
    const strengthText  = document.getElementById("strengthText");
    const toggleBtn     = document.getElementById("togglePassword");

    // SAFETY CHECK
    if (!passwordInput || !strengthBar || !strengthText || !toggleBtn) {
        console.error("Password elements not found in DOM");
        return;
    }

    // 🔐 PASSWORD STRENGTH
    passwordInput.addEventListener("input", function () {
        const password = passwordInput.value;
        let strength = 0;

        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        switch (strength) {
            case 0:
            case 1:
                strengthBar.style.width = "25%";
                strengthBar.style.background = "red";
                strengthText.textContent = "Weak password";
                break;

            case 2:
                strengthBar.style.width = "50%";
                strengthBar.style.background = "orange";
                strengthText.textContent = "Medium password";
                break;

            case 3:
                strengthBar.style.width = "75%";
                strengthBar.style.background = "#4CAF50";
                strengthText.textContent = "Strong password";
                break;

            case 4:
                strengthBar.style.width = "100%";
                strengthBar.style.background = "green";
                strengthText.textContent = "Very strong password";
                break;
        }

        if (password.length === 0) {
            strengthBar.style.width = "0%";
            strengthText.textContent = "";
        }
    });

    // 👁 TOGGLE PASSWORD VISIBILITY
    toggleBtn.addEventListener("click", function () {
        passwordInput.type =
            passwordInput.type === "password" ? "text" : "password";
    });

});