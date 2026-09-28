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

$shopkeeper_id = (int) $_SESSION['user_id'];

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

/*
|--------------------------------------------------------------------------
| Purchase summary
|--------------------------------------------------------------------------
| One payment_id is treated as one purchase.
*/

$summary_stmt = $conn->prepare(
    "SELECT
        COUNT(DISTINCT payment_id) AS total_purchases,
        COALESCE(SUM(total_amount), 0) AS total_spent
     FROM orders
     WHERE shopkeeper_id = ?"
);

$summary_stmt->bind_param(
    "i",
    $shopkeeper_id
);

$summary_stmt->execute();

$summary_result = $summary_stmt->get_result();
$summary_data = $summary_result->fetch_assoc();

$total_purchases = (int) (
    $summary_data['total_purchases'] ?? 0
);

$total_spent = (float) (
    $summary_data['total_spent'] ?? 0
);

/*
|--------------------------------------------------------------------------
| Load purchases grouped by payment
|--------------------------------------------------------------------------
| Each payment/transaction appears as one table row.
*/

$sql = "
    SELECT
        payments.payment_id,
        payments.transaction_id,
        payments.payment_method,
        payments.payment_status,
        payments.payment_date,

        COUNT(orders.order_id) AS item_count,

        COALESCE(
            SUM(orders.total_amount),
            0
        ) AS purchase_total,

        MAX(orders.order_status) AS order_status,

        MAX(orders.completed_date) AS completed_date

    FROM payments

    JOIN orders
        ON payments.payment_id = orders.payment_id

    WHERE orders.shopkeeper_id = ?
";

$params = [$shopkeeper_id];
$types = "i";

if($search !== "")
{
    $sql .= "
        AND payments.transaction_id LIKE ?
    ";

    $search_term = "%" . $search . "%";

    $params[] = $search_term;
    $types .= "s";
}

$sql .= "
    GROUP BY
        payments.payment_id,
        payments.transaction_id,
        payments.payment_method,
        payments.payment_status,
        payments.payment_date

    ORDER BY
        MAX(orders.completed_date) DESC
";

$stmt = $conn->prepare($sql);

if(!$stmt)
{
    die(
        "Database query preparation failed: "
        . htmlspecialchars($conn->error)
    );
}

$stmt->bind_param(
    $types,
    ...$params
);

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

    <title>My Purchases</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link
        rel="stylesheet"
        href="../assets/css/style.css?v=20260722">

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
        <?php
        echo htmlspecialchars(
            $_SESSION['name']
        );
        ?>

        <span>| Shopkeeper</span>

    </div>

</header>

<main class="container">

    <!-- Page heading -->

    <section class="page-heading-row">

        <div>

            <p class="dashboard-label">
                Purchase History
            </p>

            <h1 class="dashboard-title">
                My Purchases
            </h1>

            <p class="dashboard-subtitle">
                View completed purchases, payment information,
                transaction details and invoices.
            </p>

        </div>

        <a
            href="products.php"
            class="dashboard-primary-btn">

            <i class="fa-solid fa-box-open"></i>
            View Products

        </a>

    </section>

    <!-- Summary cards -->

    <section class="orders-summary-grid">

        <div class="order-summary-card">

            <div class="order-summary-icon total-orders-icon">

                <i class="fa-solid fa-bag-shopping"></i>

            </div>

            <div>

                <span>Total Purchases</span>

                <strong>
                    <?php echo $total_purchases; ?>
                </strong>

            </div>

        </div>

        <div class="order-summary-card">

            <div class="order-summary-icon total-spent-icon">

                <i class="fa-solid fa-money-bill-wave"></i>

            </div>

            <div>

                <span>Total Spent</span>

                <strong>
                    Rs.
                    <?php
                    echo number_format(
                        $total_spent,
                        2
                    );
                    ?>
                </strong>

            </div>

        </div>

    </section>

    <!-- Search area -->

    <section class="product-filter-card">

        <form
            method="GET"
            class="orders-filter-form">

            <div class="filter-field">

                <label for="search">
                    Search Purchases
                </label>

                <div class="filter-input-wrapper">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        placeholder="Enter transaction ID"
                        value="<?php
                        echo htmlspecialchars($search);
                        ?>">

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

    <!-- Purchases table -->

    <section class="products-table-card">

        <div class="table-card-heading">

            <div>

                <h2>Completed Purchases</h2>

                <p>
                    <?php echo $result->num_rows; ?>
                    purchase(s) found
                </p>

            </div>

        </div>

        <?php if($result->num_rows > 0) { ?>

            <div class="table-responsive">

                <table class="orders-table purchase-history-table">

                    <thead>

                        <tr>

                            <th>Transaction ID</th>

                            <th>Items</th>

                            <th>Total Amount</th>

                            <th>Payment Method</th>

                            <th>Status</th>

                            <th>Completed Date</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php while($row = $result->fetch_assoc()) { ?>

                            <?php

                            $transaction_id =
                                (string) $row['transaction_id'];

                            /*
                            |--------------------------------------------------
                            | Short transaction ID for table display
                            |--------------------------------------------------
                            */

                            if(strlen($transaction_id) > 14)
                            {
                                $short_transaction_id =
                                    substr(
                                        $transaction_id,
                                        0,
                                        7
                                    )
                                    . "..."
                                    . substr(
                                        $transaction_id,
                                        -5
                                    );
                            }
                            else
                            {
                                $short_transaction_id =
                                    $transaction_id;
                            }

                            ?>

                            <tr>

                                <!-- Transaction ID -->

                                <td>

                                    <a
                                        href="transaction_details.php?payment_id=<?php
                                        echo (int) $row['payment_id'];
                                        ?>"
                                        class="transaction-details-link"
                                        title="<?php
                                        echo htmlspecialchars(
                                            $transaction_id
                                        );
                                        ?>">

                                        <div class="transaction-link-content">

                                            <span class="transaction-link-icon">

                                                <i class="fa-solid fa-receipt"></i>

                                            </span>

                                            <div>

                                                <strong>

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $short_transaction_id
                                                    );
                                                    ?>

                                                </strong>

                                                <small>
                                                    View purchase details
                                                </small>

                                            </div>

                                        </div>

                                    </a>

                                </td>

                                <!-- Item count -->

                                <td>

                                    <span class="purchase-item-count">

                                        <i class="fa-solid fa-boxes-stacked"></i>

                                        <?php
                                        echo (int) $row['item_count'];
                                        ?>

                                        <?php
                                        echo (
                                            (int) $row['item_count'] === 1
                                        )
                                            ? 'Item'
                                            : 'Items';
                                        ?>

                                    </span>

                                </td>

                                <!-- Purchase total -->

                                <td>

                                    <strong class="order-total-amount">

                                        Rs.
                                        <?php
                                        echo number_format(
                                            (float) $row['purchase_total'],
                                            2
                                        );
                                        ?>

                                    </strong>

                                </td>

                                <!-- Payment method -->

                                <td>

                                    <span class="payment-method-badge">

                                        <i class="fa-solid fa-credit-card"></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $row['payment_method']
                                            ?? 'Not available'
                                        );
                                        ?>

                                    </span>

                                </td>

                                <!-- Status -->

                                <td>

                                    <span class="request-status-badge completed-request">

                                        <i class="fa-solid fa-circle-check"></i>

                                        <?php

                                        $status =
                                            $row['order_status']
                                            ?? 'Completed';

                                        echo htmlspecialchars($status);

                                        ?>

                                    </span>

                                </td>

                                <!-- Completed date -->

                                <td>

                                    <div class="purchase-date-cell">

                                        <i class="fa-regular fa-calendar-check"></i>

                                        <span>

                                            <?php

                                            if(
                                                !empty(
                                                    $row['completed_date']
                                                )
                                            )
                                            {
                                                echo date(
                                                    'd M Y, h:i A',
                                                    strtotime(
                                                        $row['completed_date']
                                                    )
                                                );
                                            }
                                            else
                                            {
                                                echo 'Not available';
                                            }

                                            ?>

                                        </span>

                                    </div>

                                </td>

                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

            <div class="purchase-table-note">

                <i class="fa-solid fa-circle-info"></i>

                Click a transaction ID to view product details
                and download the invoice.

            </div>

        <?php } else { ?>

            <div class="empty-state">

                <i class="fa-solid fa-bag-shopping"></i>

                <h3>No Completed Purchases</h3>

                <p>

                    <?php if($search !== "") { ?>

                        No purchase was found for the entered
                        transaction ID.

                    <?php } else { ?>

                        Completed paid purchases will appear here.

                    <?php } ?>

                </p>

                <?php if($search !== "") { ?>

                    <a
                        href="orders.php"
                        class="dashboard-primary-btn">

                        <i class="fa-solid fa-rotate-left"></i>
                        Clear Search

                    </a>

                <?php } else { ?>

                    <a
                        href="my_requests.php"
                        class="dashboard-primary-btn">

                        <i class="fa-solid fa-list-check"></i>
                        View My Requests

                    </a>

                <?php } ?>

            </div>

        <?php } ?>

    </section>

</main>

<script src="../assets/js/script.js?v=3"></script>

</body>

</html>