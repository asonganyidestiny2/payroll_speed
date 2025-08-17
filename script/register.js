function validateForm() {
    let username = document.forms["regForm"]["username"].value.trim();
    let password = document.forms["regForm"]["password"].value;
    let confirm = document.forms["regForm"]["confirm_password"].value;
    let role = document.forms["regForm"]["role"].value;
    let errors = [];

    if (username.length < 4) errors.push("Username must be at least 4 characters.");
    if (password.length < 6) errors.push("Password must be at least 6 characters.");
    if (password !== confirm) errors.push("Passwords do not match.");
    if (!["secretary","hrm","admin"].includes(role)) errors.push("Invalid role selected.");

    if (errors.length > 0) {
        alert(errors.join("\n"));
        return false; // Prevent form submission
    }
    return true; // Allow form submission
}
