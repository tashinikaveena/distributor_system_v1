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

/* Summary */

$summary_result = $conn->query(
    "SELECT
        COUNT(*) AS total_orders,
        COALESCE(SUM(total_amount), 0) AS total_sales
     FROM orders"
);

$summary_data = $summary_result->fetch_assoc();

$total_orders = (int) ($summary_data['total_orders'] ?? 0);
$total_sales = (float) ($summary_data['total_sales'] ?? 0);

/* Orders list */

$sql = "
    SELECT
        orders.order_id,
        orders.request_id,
        orders.quantity,
        orders.total_amount,
        orders.order_status,
        orders.completed_date,

        users.name AS shopkeeper_name,
        users.email AS shopkeeper_email,
        users.shop_name,

        products.product_name,

        categories.category_name,

        payments.transaction_id,
        payments.payment_method

    FROM orders

    JOIN users
        ON orders.shopkeeper_id = users.id

    JOIN products
        ON orders.product_id = products.product_id

    LEFT JOIN categories
        ON products.category_id = categories.category_id

    JOIN payments
        ON orders.payment_id = payments.payment_id

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

$sql .= " ORDER BY orders.completed_date DESC";

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

    <title>Order Management</title>

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
                Completed Orders
            </p>

            <h1 class="dashboard-title">
                Order Management
            </h1>

            <p class="dashboard-subtitle">
                View completed orders and related payment details.
            </p>

        </div>

    </section>

    <section class="orders-summary-grid">

        <div class="order-summary-card">

            <div class="order-summary-icon total-orders-icon">

                <i class="fa-solid fa-boxes-packing"></i>

            </div>

            <div>

                <span>Total Orders</span>

                <strong>
                    <?php echo $total_orders; ?>
                </strong>

            </div>

        </div>

        <div class="order-summary-card">

            <div class="order-summary-icon total-spent-icon">

                <i class="fa-solid fa-money-bill-wave"></i>

            </div>

            <div>

                <span>Total Sales</span>

                <strong>
                    Rs.
                    <?php echo number_format($total_sales, 2); ?>
                </strong>

            </div>

        </div>

    </section>

    <section class="product-filter-card">

        <form
            method="GET"
            class="orders-filter-form">

            <div class="filter-field">

                <label for="search">
                    Search Orders
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

            <button
                type="submit"
                class="filter-btn">

                <i class="fa-solid fa-search"></i>
                Search

            </button>

            <a
                href="orders.php"
                class="reset-filter-btn">

                <i class="fa-solid fa-rotate-left"></i>
                Reset

            </a>

        </form>

    </section>

    <section class="products-table-card">

        <div class="table-card-heading">

            <div>

                <h2>Completed Order List</h2>

                <p>
                    <?php echo $result->num_rows; ?>
                    order(s) found
                </p>

            </div>

        </div>

        <?php if($result->num_rows > 0) { ?>

            <div class="table-responsive">

                <table class="admin-orders-table">

                    <thead>

                        <tr>
                            <th>Order ID</th>
                            <th>Shopkeeper</th>
                            <th>Shop</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Total Amount</th>
                            <th>Payment Method</th>
                            <th>Transaction ID</th>
                            <th>Status</th>
                            <th>Completed Date</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php while($row = $result->fetch_assoc()) { ?>

                            <tr>

                                <td>

                                    <strong>
                                        #<?php echo $row['order_id']; ?>
                                    </strong>

                                    <small class="table-email">
                                        Request #<?php echo $row['request_id']; ?>
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
                                        <?php echo (int) $row['quantity']; ?>
                                    </strong>
                                </td>

                                <td>

                                    <strong class="order-total-amount">

                                        Rs.
                                        <?php
                                        echo number_format(
                                            (float) $row['total_amount'],
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

                                    <span class="transaction-id-text">

                                        <?php
                                        echo htmlspecialchars(
                                            $row['transaction_id']
                                        );
                                        ?>

                                    </span>

                                </td>

                                <td>

                                    <span class="request-status-badge completed-request">

                                        <i class="fa-solid fa-circle-check"></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $row['order_status']
                                        );
                                        ?>

                                    </span>

                                </td>

                                <td>

                                    <?php
                                    echo date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $row['completed_date']
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

                <i class="fa-solid fa-box-open"></i>

                <h3>No Completed Orders</h3>

                <p>
                    Completed paid orders will appear here.
                </p>

            </div>

        <?php } ?>

    </section>

</main>

<script src="../assets/js/script.js?v=3"></script>

</body>
</html>