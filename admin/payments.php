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

$method_filter = isset($_GET['method'])
    ? trim($_GET['method'])
    : '';

$status_filter = isset($_GET['status'])
    ? trim($_GET['status'])
    : '';

$allowed_methods = [
    'Visa',
    'MasterCard',
    'Debit Card'
];

$allowed_statuses = [
    'Pending',
    'Success',
    'Failed'
];

if(
    $method_filter !== '' &&
    !in_array($method_filter, $allowed_methods, true)
)
{
    $method_filter = '';
}

if(
    $status_filter !== '' &&
    !in_array($status_filter, $allowed_statuses, true)
)
{
    $status_filter = '';
}

/* Summary */

$summary_result = $conn->query(
    "SELECT
        COUNT(*) AS total_payments,

        COALESCE(
            SUM(
                CASE
                    WHEN payment_status = 'Success'
                    THEN amount
                    ELSE 0
                END
            ),
            0
        ) AS total_revenue,

        SUM(
            CASE
                WHEN DATE(payment_date) = CURDATE()
                THEN 1
                ELSE 0
            END
        ) AS today_payments,

        SUM(
            CASE
                WHEN payment_status = 'Success'
                THEN 1
                ELSE 0
            END
        ) AS successful_payments

     FROM payments"
);

$summary_data = $summary_result->fetch_assoc();

$total_payments =
    (int) ($summary_data['total_payments'] ?? 0);

$total_revenue =
    (float) ($summary_data['total_revenue'] ?? 0);

$today_payments =
    (int) ($summary_data['today_payments'] ?? 0);

$successful_payments =
    (int) ($summary_data['successful_payments'] ?? 0);

/* Payment list */

$sql = "
    SELECT
        payments.payment_id,
        payments.request_id,
        payments.amount,
        payments.payment_method,
        payments.payment_status,
        payments.transaction_id,
        payments.payment_date,

        users.name AS shopkeeper_name,
        users.email AS shopkeeper_email,
        users.shop_name,

        stock_requests.quantity,

        products.product_name,

        categories.category_name

    FROM payments

    JOIN users
        ON payments.shopkeeper_id = users.id

    JOIN stock_requests
        ON payments.request_id = stock_requests.request_id

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
            OR users.email LIKE ?
            OR products.product_name LIKE ?
            OR payments.transaction_id LIKE ?
        )
    ";

    $search_term = "%" . $search . "%";

    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;

    $types .= "sssss";
}

if($method_filter !== "")
{
    $sql .= " AND payments.payment_method = ?";

    $params[] = $method_filter;
    $types .= "s";
}

if($status_filter !== "")
{
    $sql .= " AND payments.payment_status = ?";

    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY payments.payment_date DESC";

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

    <title>Payment Management</title>

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
                Payment Monitoring
            </p>

            <h1 class="dashboard-title">
                Payment Management
            </h1>

            <p class="dashboard-subtitle">
                View payment history, revenue, methods,
                and transaction details.
            </p>

        </div>

    </section>

    <section class="payment-summary-grid">

        <div class="payment-admin-summary-card">

            <div class="payment-admin-icon total-payment-icon">

                <i class="fa-solid fa-receipt"></i>

            </div>

            <div>

                <span>Total Payments</span>

                <strong>
                    <?php echo $total_payments; ?>
                </strong>

            </div>

        </div>

        <div class="payment-admin-summary-card">

            <div class="payment-admin-icon revenue-icon">

                <i class="fa-solid fa-money-bill-trend-up"></i>

            </div>

            <div>

                <span>Total Revenue</span>

                <strong>
                    Rs.
                    <?php echo number_format($total_revenue, 2); ?>
                </strong>

            </div>

        </div>

        <div class="payment-admin-summary-card">

            <div class="payment-admin-icon today-payment-icon">

                <i class="fa-solid fa-calendar-day"></i>

            </div>

            <div>

                <span>Today’s Payments</span>

                <strong>
                    <?php echo $today_payments; ?>
                </strong>

            </div>

        </div>

        <div class="payment-admin-summary-card">

            <div class="payment-admin-icon success-payment-icon">

                <i class="fa-solid fa-circle-check"></i>

            </div>

            <div>

                <span>Successful Payments</span>

                <strong>
                    <?php echo $successful_payments; ?>
                </strong>

            </div>

        </div>

    </section>

    <section class="product-filter-card">

        <form
            method="GET"
            class="admin-payment-filter-form">

            <div class="filter-field">

                <label for="search">
                    Search Payments
                </label>

                <div class="filter-input-wrapper">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        placeholder="Shopkeeper, product, or transaction ID"
                        value="<?php echo htmlspecialchars($search); ?>">

                </div>

            </div>

            <div class="filter-field">

                <label for="method">
                    Payment Method
                </label>

                <select id="method" name="method">

                    <option value="">
                        All Methods
                    </option>

                    <?php foreach($allowed_methods as $method) { ?>

                        <option
                            value="<?php echo $method; ?>"
                            <?php
                            if($method_filter === $method)
                            {
                                echo "selected";
                            }
                            ?>>

                            <?php echo $method; ?>

                        </option>

                    <?php } ?>

                </select>

            </div>

            <div class="filter-field">

                <label for="status">
                    Payment Status
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

            <button
                type="submit"
                class="filter-btn">

                <i class="fa-solid fa-filter"></i>
                Filter

            </button>

            <a
                href="payments.php"
                class="reset-filter-btn">

                <i class="fa-solid fa-rotate-left"></i>
                Reset

            </a>

        </form>

    </section>

    <section class="products-table-card">

        <div class="table-card-heading">

            <div>

                <h2>Payment History</h2>

                <p>
                    <?php echo $result->num_rows; ?>
                    payment(s) found
                </p>

            </div>

        </div>

        <?php if($result->num_rows > 0) { ?>

            <div class="table-responsive">

                <table class="admin-payments-table">

                    <thead>

                        <tr>
                            <th>Payment ID</th>
                            <th>Shopkeeper</th>
                            <th>Shop</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Transaction ID</th>
                            <th>Payment Date</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php while($row = $result->fetch_assoc()) { ?>

                            <tr>

                                <td>

                                    <strong>
                                        #<?php echo $row['payment_id']; ?>
                                    </strong>

                                    <small class="table-email">

                                        Request #
                                        <?php echo $row['request_id']; ?>

                                    </small>

                                </td>

                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $row['shopkeeper_name']
                                        );
                                        ?>

                                    </strong>

                                    <small class="table-email">

                                        <?php
                                        echo htmlspecialchars(
                                            $row['shopkeeper_email']
                                        );
                                        ?>

                                    </small>

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

                                        <div>

                                            <strong>

                                                <?php
                                                echo htmlspecialchars(
                                                    $row['product_name']
                                                );
                                                ?>

                                            </strong>

                                            <small class="order-request-id">

                                                <?php
                                                echo htmlspecialchars(
                                                    $row['category_name']
                                                    ?? 'Uncategorized'
                                                );
                                                ?>

                                            </small>

                                        </div>

                                    </div>

                                </td>

                                <td>

                                    <strong>
                                        <?php echo (int) $row['quantity']; ?>
                                    </strong>

                                </td>

                                <td>

                                    <strong class="payment-amount-text">

                                        Rs.
                                        <?php
                                        echo number_format(
                                            (float) $row['amount'],
                                            2
                                        );
                                        ?>

                                    </strong>

                                </td>

                                <td>

                                    <span class="payment-method-badge">

                                        <i class="fa-solid fa-credit-card"></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $row['payment_method']
                                        );
                                        ?>

                                    </span>

                                </td>

                                <td>

                                    <?php if($row['payment_status'] === 'Success') { ?>

                                        <span class="payment-status-badge payment-success-status">

                                            <i class="fa-solid fa-circle-check"></i>
                                            Success

                                        </span>

                                    <?php } elseif($row['payment_status'] === 'Failed') { ?>

                                        <span class="payment-status-badge payment-failed-status">

                                            <i class="fa-solid fa-circle-xmark"></i>
                                            Failed

                                        </span>

                                    <?php } else { ?>

                                        <span class="payment-status-badge payment-pending-status">

                                            <i class="fa-solid fa-clock"></i>
                                            Pending

                                        </span>

                                    <?php } ?>

                                </td>

                                <td>

                                    <span class="transaction-id-text">

                                        <?php
                                        echo htmlspecialchars(
                                            $row['transaction_id']
                                            ?: '-'
                                        );
                                        ?>

                                    </span>

                                </td>

                                <td>

                                    <?php
                                    echo date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $row['payment_date']
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

                <i class="fa-solid fa-receipt"></i>

                <h3>No Payments Found</h3>

                <p>
                    No payment records match the current filters.
                </p>

            </div>

        <?php } ?>

    </section>

</main>

<script src="../assets/js/script.js?v=3"></script>

</body>
</html>