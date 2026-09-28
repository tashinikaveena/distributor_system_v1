// Inventory Management System

console.log("Inventory Management System Loaded");

// Confirm Delete

function confirmDelete() {
    return confirm("Are you sure you want to delete?");
}

// Auto Hide Alerts

setTimeout(function () {

    let alerts = document.querySelectorAll('.alert');

    alerts.forEach(function (alert) {
        alert.style.display = 'none';
    });

}, 3000);


// Registration Validation

function validateRegister(){

    let name =
        document.forms["registerForm"]["name"].value;

    let email =
        document.forms["registerForm"]["email"].value;

    let password =
        document.forms["registerForm"]["password"].value;

    if(name == "")
    {
        alert("Name is required");
        return false;
    }

    if(email == "")
    {
        alert("Email is required");
        return false;
    }

    if(password.length < 6)
    {
        alert("Password must be at least 6 characters");
        return false;
    }

    return true;
}

// Show / Hide Password

function togglePassword(inputId, button) {

    const input = document.getElementById(inputId);
    const icon = button.querySelector("i");

    if (input.type === "password") {

        input.type = "text";

        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");

    } else {

        input.type = "password";

        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

// Card Number Formatting

const cardNumberInput =
    document.getElementById("card_number");

if (cardNumberInput) {

    cardNumberInput.addEventListener(
        "input",
        function () {

            let value =
                this.value.replace(/\D/g, "");

            value =
                value.substring(0, 19);

            this.value =
                value.replace(/(.{4})/g, "$1 ").trim();
        }
    );
}

// Expiry Date Formatting

const expiryInput =
    document.getElementById("expiry_date");

if (expiryInput) {

    expiryInput.addEventListener(
        "input",
        function () {

            let value =
                this.value.replace(/\D/g, "");

            value =
                value.substring(0, 4);

            if (value.length >= 3) {

                value =
                    value.substring(0, 2)
                    + "/"
                    + value.substring(2);
            }

            this.value = value;
        }
    );
}

// CVV Digits Only

const cvvInput =
    document.getElementById("cvv");

if (cvvInput) {

    cvvInput.addEventListener(
        "input",
        function () {

            this.value =
                this.value
                    .replace(/\D/g, "")
                    .substring(0, 4);
        }
    );
}