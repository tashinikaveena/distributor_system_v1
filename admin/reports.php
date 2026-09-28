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

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$status_filter = isset($_GET['status'])
    ? trim($_GET['status'])
    : '';

$allowed_statuses = [
    'Pending',
    'Approved',
    'Rejected',
    'Completed'
];

if(
    $status_filter !== '' &&
    !in_array($status_filter, $allowed_statuses, true)
)
{
    $status_filter = '';
}

/* =========================================
   REPORT SUMMARY
========================================= */

$request_count = $conn->query(
    "SELECT COUNT(*) AS total
     FROM stock_requests"
)->fetch_assoc()['total'];

$order_count = $conn->query(
    "SELECT COUNT(*) AS total
     FROM orders"
)->fetch_assoc()['total'];

$revenue_result = $conn->query(
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM payments
     WHERE payment_status = 'Success'"
);

$total_revenue = $revenue_result
    ->fetch_assoc()['total'];

$low_stock_count = $conn->query(
    "SELECT COUNT(*) AS total
     FROM products
     WHERE stock_quantity < 10"
)->fetch_assoc()['total'];

/* =========================================
   REQUEST REPORT
========================================= */

$sql = "
    SELECT
        stock_requests.request_id,
        stock_requests.quantity,
        stock_requests.status,
        stock_requests.request_date,

        users.name AS shopkeeper_name,
        users.shop_name,

        products.product_name,

        categories.category_name

    FROM stock_requests

    JOIN users
        ON stock_requests.shopkeeper_id = users.id

    JOIN products
        ON stock_requests.product_id = products.product_id

    LEFT JOIN categories
        ON products.category_id = categories.category_id

    WHERE 1
";

$params = [];
$types = "";

if($search !== "")
{
    $sql .= "
        AND (
            users.name LIKE ?
            OR users.shop_name LIKE ?
            OR products.product_name LIKE ?
        )
    ";

    $search_term = "%" . $search . "%";

    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;

    $types .= "sss";
}

if($status_filter !== "")
{
    $sql .= " AND stock_requests.status = ?";

    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY stock_requests.request_date DESC";

$stmt = $conn->prepare($sql);

if(!empty($params))
{
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>System Reports</title>

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

    <section class="page-heading-row">

        <div>

            <p class="dashboard-label">
                System Information
            </p>

            <h1 class="dashboard-title">
                System Reports
            </h1>

            <p class="dashboard-subtitle">
                View stock-request activity, completed orders,
                revenue, and low-stock information.
            </p>

        </div>

    </section>

    <!-- Summary Cards -->

    <section class="report-summary-grid">

        <div class="report-summary-card">

            <div class="report-summary-icon report-request-icon">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>

            <div>

                <span>Total Requests</span>

                <strong>
                    <?php echo (int) $request_count; ?>
                </strong>

            </div>

        </div>

        <div class="report-summary-card">

            <div class="report-summary-icon report-order-icon">
                <i class="fa-solid fa-boxes-packing"></i>
            </div>

            <div>

                <span>Completed Orders</span>

                <strong>
                    <?php echo (int) $order_count; ?>
                </strong>

            </div>

        </div>

        <div class="report-summary-card">

            <div class="report-summary-icon report-revenue-icon">
                <i class="fa-solid fa-money-bill-trend-up"></i>
            </div>

            <div>

                <span>Total Revenue</span>

                <strong>
                    Rs.
                    <?php
                    echo number_format(
                        (float) $total_revenue,
                        2
                    );
                    ?>
                </strong>

            </div>

        </div>

        <div class="report-summary-card">

            <div class="report-summary-icon report-stock-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>

            <div>

                <span>Low Stock Products</span>

                <strong>
                    <?php echo (int) $low_stock_count; ?>
                </strong>

            </div>

        </div>

    </section>

    <!-- Filters -->

    <section class="product-filter-card">

        <form method="GET" class="report-filter-form">

            <div class="filter-field">

                <label for="search">
                    Search Report
                </label>

                <div class="filter-input-wrapper">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        placeholder="Shopkeeper, shop, or product"
                        value="<?php
                        echo htmlspecialchars($search);
                        ?>">

                </div>

            </div>

            <div class="filter-field">

                <label for="status">
                    Request Status
                </label>

                <select id="status" name="status">

                    <option value="">
                        All Statuses
                    </option>

                    <?php foreach($allowed_statuses as $status) { ?>

                        <option
                            value="<?php echo $status; ?>"

                            <?php
                            if($status_filter === $status)
                            {
                                echo "selected";
                            }
                            ?>>

                            <?php echo $status; ?>

                        </option>

                    <?php } ?>

                </select>

            </div>

            <button type="submit" class="filter-btn">

                <i class="fa-solid fa-filter"></i>
                Filter

            </button>

            <a href="reports.php" class="reset-filter-btn">

                <i class="fa-solid fa-rotate-left"></i>
                Reset

            </a>

        </form>

    </section>

    <!-- Report Table -->

    <section class="products-table-card">

        <div class="table-card-heading">

            <div>

                <h2>Stock Restoration Report</h2>

                <p>
                    <?php echo $result->num_rows; ?>
                    record(s) found
                </p>

            </div>

        </div>

        <?php if($result->num_rows > 0) { ?>

            <div class="table-responsive">

                <table class="reports-table">

                    <thead>

                        <tr>
                            <th>Request ID</th>
                            <th>Shopkeeper</th>
                            <th>Shop</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Request Date</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php while($row = $result->fetch_assoc()) { ?>

                            <tr>

                                <td>

                                    <strong>
                                        #<?php
                                        echo $row['request_id'];
                                        ?>
                                    </strong>

                                </td>

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $row['shopkeeper_name']
                                    );
                                    ?>

                                </td>

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $row['shop_name'] ?: '-'
                                    );
                                    ?>

                                </td>

                                <td>

                                    <div class="product-name-cell">

                                        <div class="product-table-icon">

                                            <i class="fa-solid fa-box"></i>

                                        </div>

                                        <strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $row['product_name']
                                            );
                                            ?>

                                        </strong>

                                    </div>

                                </td>

                                <td>

                                    <span class="category-badge">

                                        <?php
                                        echo htmlspecialchars(
                                            $row['category_name']
                                            ?? 'Uncategorized'
                                        );
                                        ?>

                                    </span>

                                </td>

                                <td>

                                    <strong>
                                        <?php
                                        echo (int) $row['quantity'];
                                        ?>
                                    </strong>

                                </td>

                                <td>

                                    <?php if($row['status'] === 'Pending') { ?>

                                        <span class="request-status-badge pending-request">

                                            <i class="fa-solid fa-clock"></i>
                                            Pending

                                        </span>

                                    <?php } elseif($row['status'] === 'Approved') { ?>

                                        <span class="request-status-badge approved-request">

                                            <i class="fa-solid fa-circle-check"></i>
                                            Approved

                                        </span>

                                    <?php } elseif($row['status'] === 'Rejected') { ?>

                                        <span class="request-status-badge rejected-request">

                                            <i class="fa-solid fa-circle-xmark"></i>
                                            Rejected

                                        </span>

                                    <?php } else { ?>

                                        <span class="request-status-badge completed-request">

                                            <i class="fa-solid fa-circle-check"></i>
                                            Completed

                                        </span>

                                    <?php } ?>

                                </td>

                                <td>

                                    <?php
                                    echo date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $row['request_date']
                                        )
                                    );
                                    ?>

                                </td>

                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

        <?php } else { ?>

            <div class="empty-state">

                <i class="fa-solid fa-chart-column"></i>

                <h3>No Report Records Found</h3>

                <p>
                    No stock requests match the selected filters.
                </p>

            </div>

        <?php } ?>

    </section>

</main>

<script src="../assets/js/script.js?v=3"></script>

</body>
</html>