<?php include('includes/header.php'); ?>

<header class="public-header">

    <div class="logo">
        <i class="fa-solid fa-box-open"></i>
        Inventory Management System
    </div>

    <nav>
        <a href="index.php" class="active">Home</a>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    </nav>

</header>

<main>

    <!-- Hero Section -->

    <section class="home-hero">

        <div class="hero-content">

            <div class="hero-text">

                <span class="hero-label">
                    Smart Inventory Solution
                </span>

                <h1>
                    Prime Stock Lanka
                   <span>Shopkeeper Stock Restoration System</span>
                </h1>

                <p>
                    An inventory management system designed to help shopkeepers
                    request stock online while enabling administrators to manage
                    products, stock requests, payments, orders, and inventory
                    efficiently.
                </p>

                <div class="hero-buttons">

                    <a href="login.php" class="primary-btn">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        Login
                    </a>

                    <a href="register.php" class="secondary-btn">
                        <i class="fa-solid fa-user-plus"></i>
                        Register
                    </a>

                </div>

            </div>

            <div class="hero-visual">

                <div class="warehouse-visual">

                    <div class="warehouse-icon">
                      <i class="fa-solid fa-warehouse"></i>
                    </div>

                    <div class="visual-box box-a">
                        <i class="fa-solid fa-box"></i>
                    </div>

                    <div class="visual-box box-b">
                        <i class="fa-solid fa-box"></i>
                    </div>

                    <div class="visual-box box-c">
                        <i class="fa-solid fa-box-open"></i>
                    </div>

                    <div class="visual-checklist">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>

                </div>

            </div>

        </div>

    </section>

    <!-- Features Section -->

    <section class="features-section">

        <div class="section-heading">

            <span>Core Features</span>

            <h2>System Features</h2>

            <p>
                Everything needed to manage inventory and stock requests
                efficiently in one system.
            </p>

        </div>

        <div class="feature-grid">

            <div class="feature-card">

                <div class="feature-icon">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>

                <h3>Product Management</h3>

                <p>
                    Add, edit, organize, and monitor products and stock
                    quantities efficiently.
                </p>

            </div>

            <div class="feature-card">

                <div class="feature-icon">
                    <i class="fa-solid fa-file-circle-plus"></i>
                </div>

                <h3>Stock Requests</h3>

                <p>
                    Shopkeepers can request stock online and track the
                    current request status.
                </p>

            </div>

            <div class="feature-card">

                <div class="feature-icon">
                    <i class="fa-solid fa-chart-column"></i>
                </div>

                <h3>Reports and Analytics</h3>

                <p>
                    Administrators can monitor inventory activity,
                    requests, payments, and completed orders.
                </p>

            </div>

        </div>

    </section>

    <!-- Workflow Section -->

    <section class="workflow-section">

        <div class="section-heading">

            <span>Simple Process</span>

            <h2>How It Works</h2>

        </div>

        <div class="workflow-grid">

            <div class="workflow-step">

                <div class="workflow-icon">
                    <i class="fa-solid fa-store"></i>
                </div>

                <h3>Shopkeeper</h3>

                <p>Browses available products.</p>

            </div>

            <div class="workflow-arrow">
                <i class="fa-solid fa-arrow-right"></i>
            </div>

            <div class="workflow-step">

                <div class="workflow-icon">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>

                <h3>Request Stock</h3>

                <p>Submits the required quantity.</p>

            </div>

            <div class="workflow-arrow">
                <i class="fa-solid fa-arrow-right"></i>
            </div>

            <div class="workflow-step">

                <div class="workflow-icon">
                    <i class="fa-solid fa-user-shield"></i>
                </div>

                <h3>Administrator</h3>

                <p>Reviews and approves the request.</p>

            </div>

            <div class="workflow-arrow">
                <i class="fa-solid fa-arrow-right"></i>
            </div>

            <div class="workflow-step">

                <div class="workflow-icon">
                    <i class="fa-solid fa-credit-card"></i>
                </div>

                <h3>Online Payment</h3>

                <p>Shopkeeper pays after approval.</p>

            </div>

            <div class="workflow-arrow">
                <i class="fa-solid fa-arrow-right"></i>
            </div>

            <div class="workflow-step">

                <div class="workflow-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <h3>Order Completed</h3>

                <p>Stock is reduced automatically.</p>

            </div>

        </div>

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
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        </div>

    </div>

</footer>

<?php include('includes/footer.php'); ?>