<?php

session_start();

if(
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
)
{
    header("Location: ../login.php");
    exit();
}

include '../config/database.php';

/* Dashboard counts */

$product_count = $conn->query(
    "SELECT COUNT(*) AS total
     FROM products"
)->fetch_assoc()['total'];

$category_count = $conn->query(
    "SELECT COUNT(*) AS total
     FROM categories"
)->fetch_assoc()['total'];

$shopkeeper_count = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'shopkeeper'"
)->fetch_assoc()['total'];

$request_count = $conn->query(
    "SELECT COUNT(*) AS total
     FROM stock_requests"
)->fetch_assoc()['total'];

$pending_count = $conn->query(
    "SELECT COUNT(*) AS total
     FROM stock_requests
     WHERE status = 'Pending'"
)->fetch_assoc()['total'];

$approved_count = $conn->query(
    "SELECT COUNT(*) AS total
     FROM stock_requests
     WHERE status = 'Approved'"
)->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link
        rel="stylesheet"
        href="../assets/css/style.css?v=20260712">

</head>

<body>

<?php include('../includes/admin_sidebar.php'); ?>

<header>

    <div class="logo">
        Inventory Management System
    </div>

    <div class="header-right">

        <i class="fa-solid fa-user-shield"></i>

        Welcome,
        <?php echo htmlspecialchars($_SESSION['name']); ?>

        <span>| Admin</span>

    </div>

</header>

<main class="container">

    <section class="admin-welcome">

        <div>

            <p class="dashboard-label">
                Administrator Portal
            </p>

            <h1 class="dashboard-title">
                Admin Dashboard
            </h1>

            <p class="dashboard-subtitle">
                Manage inventory, shopkeepers, stock requests,
                payments, and system reports.
            </p>

        </div>

        <a href="stock_requests.php" class="dashboard-primary-btn">

            <i class="fa-solid fa-clipboard-check"></i>
            Review Requests

        </a>

    </section>

    <section class="dashboard-stats-grid admin-stats-grid">

        <div class="dashboard-stat-card">

            <div class="stat-icon products-icon">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>

            <div>
                <span>Total Products</span>
                <strong><?php echo $product_count; ?></strong>
            </div>

        </div>

        <div class="dashboard-stat-card">

            <div class="stat-icon category-stat-icon">
                <i class="fa-solid fa-layer-group"></i>
            </div>

            <div>
                <span>Total Categories</span>
                <strong><?php echo $category_count; ?></strong>
            </div>

        </div>

        <div class="dashboard-stat-card">

            <div class="stat-icon shopkeeper-stat-icon">
                <i class="fa-solid fa-store"></i>
            </div>

            <div>
                <span>Total Shopkeepers</span>
                <strong><?php echo $shopkeeper_count; ?></strong>
            </div>

        </div>

        <div class="dashboard-stat-card">

            <div class="stat-icon requests-icon">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>

            <div>
                <span>Total Requests</span>
                <strong><?php echo $request_count; ?></strong>
            </div>

        </div>

        <div class="dashboard-stat-card">

            <div class="stat-icon pending-icon">
                <i class="fa-solid fa-clock"></i>
            </div>

            <div>
                <span>Pending Requests</span>
                <strong><?php echo $pending_count; ?></strong>
            </div>

        </div>

        <div class="dashboard-stat-card">

            <div class="stat-icon approved-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>

            <div>
                <span>Approved Requests</span>
                <strong><?php echo $approved_count; ?></strong>
            </div>

        </div>

    </section>

    <section class="dashboard-section">

        <div class="dashboard-section-heading">

            <p class="dashboard-label">
                Quick Access
            </p>

            <h2>
                System Management
            </h2>

        </div>

        <div class="quick-action-grid admin-action-grid">

            <a href="categories.php" class="quick-action-card">

                <div class="quick-action-icon">
                    <i class="fa-solid fa-layer-group"></i>
                </div>

                <h3>Manage Categories</h3>

                <p>
                    Add, edit, and organize product categories.
                </p>

                <span>
                    Open Categories
                    <i class="fa-solid fa-arrow-right"></i>
                </span>

            </a>

            <a href="products.php" class="quick-action-card">

                <div class="quick-action-icon">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>

                <h3>Manage Products</h3>

                <p>
                    Add products and manage prices and stock quantities.
                </p>

                <span>
                    Open Products
                    <i class="fa-solid fa-arrow-right"></i>
                </span>

            </a>

            <a href="shopkeepers.php" class="quick-action-card">

                <div class="quick-action-icon">
                    <i class="fa-solid fa-store"></i>
                </div>

                <h3>Manage Shopkeepers</h3>

                <p>
                    View registered shopkeepers and their account details.
                </p>

                <span>
                    Open Shopkeepers
                    <i class="fa-solid fa-arrow-right"></i>
                </span>

            </a>

            <a href="stock_requests.php" class="quick-action-card">

                <div class="quick-action-icon">
                    <i class="fa-solid fa-clipboard-check"></i>
                </div>

                <h3>Stock Requests</h3>

                <p>
                    Review pending requests and approve or reject them.
                </p>

                <span>
                    Review Requests
                    <strong class="notification-badge">
                        <?php echo $pending_count; ?>
                    </strong>
                </span>

            </a>

            <a href="reports.php" class="quick-action-card">

                <div class="quick-action-icon">
                    <i class="fa-solid fa-chart-column"></i>
                </div>

                <h3>Reports</h3>

                <p>
                    Monitor stock requests and system activity.
                </p>

                <span>
                    View Reports
                    <i class="fa-solid fa-arrow-right"></i>
                </span>

            </a>

        </div>

    </section>

</main>

<script src="../assets/js/script.js?v=2"></script>

</body>
</html>