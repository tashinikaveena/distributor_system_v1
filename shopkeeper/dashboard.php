<?php

session_start();

if(
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'shopkeeper'
)
{
    header("Location: ../login.php");
    exit();
}

include '../config/database.php';

$shopkeeper_id = $_SESSION['user_id'];

/* Total available products */

$product_result = $conn->query(
    "SELECT COUNT(*) AS total_products
     FROM products"
);

$total_products = 0;

if($product_result)
{
    $product_row = $product_result->fetch_assoc();
    $total_products = $product_row['total_products'];
}

/* Total requests */

$request_stmt = $conn->prepare(
    "SELECT
        COUNT(*) AS total_requests,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_requests,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS approved_requests
     FROM stock_requests
     WHERE shopkeeper_id = ?"
);

$request_stmt->bind_param("i", $shopkeeper_id);
$request_stmt->execute();

$request_result = $request_stmt->get_result();
$request_data = $request_result->fetch_assoc();

$total_requests = $request_data['total_requests'] ?? 0;
$pending_requests = $request_data['pending_requests'] ?? 0;
$approved_requests = $request_data['approved_requests'] ?? 0;

/* Notifications count */

$notification_stmt = $conn->prepare(
    "SELECT COUNT(*) AS total_notifications
     FROM notifications
     WHERE user_id = ?"
);

$notification_stmt->bind_param("i", $shopkeeper_id);
$notification_stmt->execute();

$notification_result = $notification_stmt->get_result();
$notification_data = $notification_result->fetch_assoc();

$total_notifications = $notification_data['total_notifications'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Shopkeeper Dashboard</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link
        rel="stylesheet"
        href="../assets/css/style.css?v=20260712">

</head>

<body>

<?php include('../includes/shopkeeper_sidebar.php'); ?>

<header>

    <div class="logo">
        Inventory Management System
    </div>

    <div class="header-right">

        <i class="fa-solid fa-user"></i>

        Welcome,
        <?php echo htmlspecialchars($_SESSION['name']); ?>

        <span>| Shopkeeper</span>

    </div>

</header>

<main class="container">

    <section class="shopkeeper-welcome">

        <div>

            <p class="dashboard-label">
                Shopkeeper Portal
            </p>

            <h1 class="dashboard-title">
                Welcome back,
                <?php echo htmlspecialchars($_SESSION['name']); ?>
            </h1>

            <p class="dashboard-subtitle">
                View available products, submit stock requests,
                and track your request status from one place.
            </p>

        </div>

        <a href="request_stock.php" class="dashboard-primary-btn">

            <i class="fa-solid fa-plus"></i>
            Request Stock

        </a>

    </section>

    <section class="dashboard-stats-grid">

        <div class="dashboard-stat-card">

            <div class="stat-icon products-icon">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>

            <div>

                <span>Available Products</span>

                <strong>
                    <?php echo $total_products; ?>
                </strong>

            </div>

        </div>

        <div class="dashboard-stat-card">

            <div class="stat-icon requests-icon">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>

            <div>

                <span>My Requests</span>

                <strong>
                    <?php echo $total_requests; ?>
                </strong>

            </div>

        </div>

        <div class="dashboard-stat-card">

            <div class="stat-icon pending-icon">
                <i class="fa-solid fa-clock"></i>
            </div>

            <div>

                <span>Pending Requests</span>

                <strong>
                    <?php echo $pending_requests; ?>
                </strong>

            </div>

        </div>

        <div class="dashboard-stat-card">

            <div class="stat-icon approved-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>

            <div>

                <span>Approved Requests</span>

                <strong>
                    <?php echo $approved_requests; ?>
                </strong>

            </div>

        </div>

    </section>

    <section class="dashboard-section">

        <div class="dashboard-section-heading">

            <div>

                <p class="dashboard-label">
                    Quick Access
                </p>

                <h2>
                    Manage Your Stock Requests
                </h2>

            </div>

        </div>

        <div class="quick-action-grid">

            <a href="products.php" class="quick-action-card">

                <div class="quick-action-icon">
                    <i class="fa-solid fa-box-open"></i>
                </div>

                <h3>View Products</h3>

                <p>
                    Browse the products currently available in the system.
                </p>

                <span>
                    Open Products
                    <i class="fa-solid fa-arrow-right"></i>
                </span>

            </a>

            <a href="request_stock.php" class="quick-action-card">

                <div class="quick-action-icon">
                    <i class="fa-solid fa-file-circle-plus"></i>
                </div>

                <h3>Request Stock</h3>

                <p>
                    Select a product and submit the quantity you require.
                </p>

                <span>
                    Create Request
                    <i class="fa-solid fa-arrow-right"></i>
                </span>

            </a>

            <a href="my_requests.php" class="quick-action-card">

                <div class="quick-action-icon">
                    <i class="fa-solid fa-list-check"></i>
                </div>

                <h3>My Requests</h3>

                <p>
                    Check pending, approved, and rejected stock requests.
                </p>

                <span>
                    View Requests
                    <i class="fa-solid fa-arrow-right"></i>
                </span>

            </a>

            <a href="notifications.php" class="quick-action-card">

                <div class="quick-action-icon">
                    <i class="fa-solid fa-bell"></i>
                </div>

                <h3>Notifications</h3>

                <p>
                    View updates related to your submitted stock requests.
                </p>

                <span>
                    View Notifications
                    <strong class="notification-badge">
                        <?php echo $total_notifications; ?>
                    </strong>
                </span>

            </a>

        </div>

    </section>

</main>

<script src="../assets/js/script.js?v=2"></script>

</body>
</html>