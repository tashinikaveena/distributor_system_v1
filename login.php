<?php

session_start();
include 'config/database.php';

$message = "";
$message_type = "";

if(isset($_POST['login']))
{
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare(
        "SELECT id, name, email, password, role
         FROM users
         WHERE email = ?"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows === 1)
    {
        $user = $result->fetch_assoc();

        if(password_verify($password, $user['password']))
        {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            if($user['role'] === 'admin')
            {
                header("Location: admin/dashboard.php");
                exit();
            }
            elseif($user['role'] === 'shopkeeper')
            {
                header("Location: shopkeeper/dashboard.php");
                exit();
            }
            else
            {
                $message = "This account role is not supported.";
                $message_type = "error";
            }
        }
        else
        {
            $message = "Invalid email or password.";
            $message_type = "error";
        }
    }
    else
    {
        $message = "Invalid email or password.";
        $message_type = "error";
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
        <a href="login.php" class="active">Login</a>
        <a href="register.php">Register</a>
    </nav>

</header>

<main class="auth-page login-auth-page">

    <div class="auth-background-icon auth-left-icon">
        <i class="fa-solid fa-warehouse"></i>
    </div>

    <div class="auth-background-icon auth-right-icon">
        <i class="fa-solid fa-clipboard-check"></i>
    </div>

    <section class="login-card">

        <div class="register-heading">

            <div class="register-icon">
                <i class="fa-solid fa-right-to-bracket"></i>
            </div>

            <h1>Welcome</h1>

            <p>
                Login to continue to your account.
            </p>

        </div>

        <?php if($message !== "") { ?>

            <div class="form-message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php } ?>

        <form method="POST" class="register-form">

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
                        autocomplete="email"
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
                        autocomplete="current-password"
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

            <button
                type="submit"
                name="login"
                class="create-account-btn">

                <i class="fa-solid fa-right-to-bracket"></i>
                Login

            </button>

        </form>

        <div class="auth-divider">
            <span>OR</span>
        </div>

        <p class="auth-login-text">
            Don’t have an account?
            <a href="register.php">Register here</a>
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
            <a href="register.php">Register</a>
        </div>

    </div>

</footer>

<script src="assets/js/script.js?v=2"></script>

<?php include('includes/footer.php'); ?>