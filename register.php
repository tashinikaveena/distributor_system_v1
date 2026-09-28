<?php

include 'config/database.php';

$message = "";
$message_type = "";

if(isset($_POST['register']))
{
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $shop_name = trim($_POST['shop_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = "shopkeeper";

    if($password !== $confirm_password)
    {
        $message = "Passwords do not match.";
        $message_type = "error";
    }
    elseif(strlen($password) < 6)
    {
        $message = "Password must contain at least 6 characters.";
        $message_type = "error";
    }
    else
    {
        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if($result->num_rows > 0)
        {
            $message = "An account already exists with this email address.";
            $message_type = "error";
        }
        else
        {
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO users
                (name, email, phone, shop_name, password, role)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssssss",
                $name,
                $email,
                $phone,
                $shop_name,
                $hashed_password,
                $role
            );

            if($stmt->execute())
            {
                $message = "Registration successful. You can now log in.";
                $message_type = "success";
            }
            else
            {
                $message = "Registration failed. Please try again.";
                $message_type = "error";
            }
        }
    }
}

?>

<?php include('includes/header.php'); ?>

<header class="public-header">

    <div class="logo">
        <i class="fa-solid fa-box-open"></i>
        Inventory Management System
    </div>

    <nav>
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
        <a href="register.php" class="active">Register</a>
    </nav>

</header>

<main class="auth-page">

    <div class="auth-background-icon auth-left-icon">
        <i class="fa-solid fa-warehouse"></i>
    </div>

    <div class="auth-background-icon auth-right-icon">
        <i class="fa-solid fa-clipboard-check"></i>
    </div>

    <section class="register-card">

        <div class="register-heading">

            <div class="register-icon">
                <i class="fa-solid fa-user-plus"></i>
            </div>

            <h1>Create Your Account</h1>

            <p>
                Fill in the details below to create your account.
            </p>

        </div>

        <?php if($message != "") { ?>

            <div class="form-message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php } ?>

        <form
            method="POST"
            name="registerForm"
            onsubmit="return validateRegister();"
            class="register-form">

            <div class="form-group">

                <label for="name">
                    Full Name
                </label>

                <div class="input-wrapper">

                    <i class="fa-solid fa-user"></i>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Enter your full name"
                        value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>"
                        required>

                </div>

            </div>

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <div class="input-wrapper">

                    <i class="fa-solid fa-envelope"></i>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email address"
                        value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                        required>

                </div>

            </div>

            <div class="form-group">

                <label for="phone">
                    Phone Number
                </label>

                <div class="input-wrapper">

                    <i class="fa-solid fa-phone"></i>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        placeholder="Enter your phone number"
                        value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>"
                        required>

                </div>

            </div>

            <div class="form-group">

                <label for="shop_name">
                    Shop Name
                </label>

                <div class="input-wrapper">

                    <i class="fa-solid fa-store"></i>

                    <input
                        type="text"
                        id="shop_name"
                        name="shop_name"
                        placeholder="Enter your shop name"
                        value="<?php echo isset($shop_name) ? htmlspecialchars($shop_name) : ''; ?>"
                        required>

                </div>

            </div>

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <div class="input-wrapper password-wrapper">

                    <i class="fa-solid fa-lock"></i>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        minlength="8"
                        required>

                    <button
                        type="button"
                        class="password-toggle"
                        onclick="togglePassword('password', this)"
                        aria-label="Show or hide password">

                        <i class="fa-solid fa-eye"></i>

                    </button>

                </div>

            </div>

            <div class="form-group">

                <label for="confirm_password">
                    Confirm Password
                </label>

                <div class="input-wrapper password-wrapper">

                    <i class="fa-solid fa-lock"></i>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm your password"
                        minlength="6"
                        required>

                    <button
                        type="button"
                        class="password-toggle"
                        onclick="togglePassword('confirm_password', this)"
                        aria-label="Show or hide password">

                        <i class="fa-solid fa-eye"></i>

                    </button>

                </div>

            </div>

            <button
                type="submit"
                name="register"
                class="create-account-btn">

                <i class="fa-solid fa-user-plus"></i>
                Create Account

            </button>

        </form>

        <div class="auth-divider">
            <span>OR</span>
        </div>

        <p class="auth-login-text">
            Already have an account?
            <a href="login.php">Login here</a>
        </p>

    </section>

</main>

<footer class="public-footer">

    <div class="footer-content">

        <div class="footer-brand">
            <i class="fa-solid fa-box-open"></i>
            Inventory Management System
        </div>

        <p>
            © <?php echo date('Y'); ?>
            Inventory Management and Shopkeeper Stock Restoration System.
            All rights reserved.
        </p>

        <div class="footer-links">
            <a href="index.php">Home</a>
            <a href="login.php">Login</a>
        </div>

    </div>

</footer>

<script src="assets/js/script.js?v=2"></script>

<?php include('includes/footer.php'); ?>