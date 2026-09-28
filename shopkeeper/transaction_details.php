<?php

session_start();

/* Shopkeeper access protection */

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'shopkeeper'
) {
    header("Location: ../login.php");
    exit();
}

include '../config/database.php';

$shopkeeper_id = (int) $_SESSION['user_id'];


/* Check payment ID */

if (
    !isset($_GET['payment_id']) ||
    !ctype_digit($_GET['payment_id'])
) {
    header("Location: orders.php");
    exit();
}

$payment_id = (int) $_GET['payment_id'];


/* Get transaction summary */

$payment_sql = "
    SELECT
        payments.payment_id,
        payments.transaction_id,
        payments.payment_method,
        payments.payment_status,
        payments.payment_date,

        COUNT(orders.order_id) AS item_count,
        COALESCE(SUM(orders.total_amount), 0) AS grand_total,
        MAX(orders.order_status) AS order_status,
        MAX(orders.completed_date) AS completed_date

    FROM payments

    INNER JOIN orders
        ON payments.payment_id = orders.payment_id

    WHERE
        payments.payment_id = ?
        AND orders.shopkeeper_id = ?

    GROUP BY
        payments.payment_id,
        payments.transaction_id,
        payments.payment_method,
        payments.payment_status,
        payments.payment_date
";

$payment_stmt = $conn->prepare($payment_sql);

if (!$payment_stmt) {
    die("Payment query error: " . htmlspecialchars($conn->error));
}

$payment_stmt->bind_param(
    "ii",
    $payment_id,
    $shopkeeper_id
);

$payment_stmt->execute();

$payment_result = $payment_stmt->get_result();


/* Prevent access to another shopkeeper's transaction */

if ($payment_result->num_rows === 0) {
    header("Location: orders.php");
    exit();
}

$payment = $payment_result->fetch_assoc();


/* Get purchased items */

$items_sql = "
    SELECT
        orders.order_id,
        orders.request_id,
        orders.product_id,
        orders.quantity,
        orders.total_amount,
        orders.order_status,
        orders.completed_date,

        products.product_name,
        products.price AS unit_price,

        categories.category_name

    FROM orders

    INNER JOIN products
        ON orders.product_id = products.product_id

    LEFT JOIN categories
        ON products.category_id = categories.category_id

    WHERE
        orders.payment_id = ?
        AND orders.shopkeeper_id = ?

    ORDER BY
        orders.order_id ASC
";

$items_stmt = $conn->prepare($items_sql);

if (!$items_stmt) {
    die("Items query error: " . htmlspecialchars($conn->error));
}

$items_stmt->bind_param(
    "ii",
    $payment_id,
    $shopkeeper_id
);

$items_stmt->execute();

$items_result = $items_stmt->get_result();


/* Prepare display values */

$transaction_id = $payment['transaction_id'] ?? 'Not available';

$payment_method = $payment['payment_method'] ?? 'Not available';

$payment_status = $payment['payment_status'] ?? 'Paid';

$order_status = $payment['order_status'] ?? 'Completed';

$item_count = (int) ($payment['item_count'] ?? 0);

$grand_total = (float) ($payment['grand_total'] ?? 0);

$display_date =
    $payment['completed_date']
    ?? $payment['payment_date']
    ?? null;


/* Create invoice number */

$invoice_date =
    $payment['payment_date']
    ?? $payment['completed_date']
    ?? date('Y-m-d');

$invoice_year = date(
    'Y',
    strtotime($invoice_date)
);

$invoice_number =
    'INV-' .
    $invoice_year .
    '-' .
    str_pad(
        (string) $payment_id,
        6,
        '0',
        STR_PAD_LEFT
    );

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Purchase Details</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/style.css?v=20260722"
    >

</head>

<body>

<?php include '../includes/shopkeeper_sidebar.php'; ?>


<header>

    <div class="logo">

        Inventory Management System

    </div>

    <div class="header-right">

        <i class="fa-solid fa-user"></i>

        Welcome,

        <?php
        echo htmlspecialchars(
            $_SESSION['name'] ?? 'Shopkeeper'
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
                Purchase Details
            </h1>

            <p class="dashboard-subtitle">

                Review the purchased items and payment
                information related to this transaction.

            </p>

        </div>

        <a
            href="orders.php"
            class="back-to-purchases-btn"
        >

            <i class="fa-solid fa-arrow-left"></i>

            Back to Purchases

        </a>

    </section>


    <!-- Purchase confirmation -->

    <section class="purchase-success-card">

        <div class="purchase-success-content">

            <div class="purchase-success-icon">

                <i class="fa-solid fa-check"></i>

            </div>

            <div>

                <h2>
                    Purchase Completed
                </h2>

                <p>

                    Your payment was successfully processed
                    and this purchase has been completed.

                </p>

            </div>

        </div>

        <a
            href="invoice.php?payment_id=<?php echo $payment_id; ?>"
            class="invoice-download-btn"
        >

            <i class="fa-solid fa-file-arrow-down"></i>

            Download Invoice

        </a>

    </section>


    <div class="purchase-details-wrapper">

        <div class="purchase-main-column">

            <!-- Transaction information -->

            <section class="details-card">

                <div class="details-card-header">

                    <div>

                        <h2>
                            Transaction Information
                        </h2>

                        <p>

                            Payment and purchase reference details

                        </p>

                    </div>

                    <span class="transaction-status-badge">

                        <i class="fa-solid fa-circle-check"></i>

                        <?php
                        echo htmlspecialchars($order_status);
                        ?>

                    </span>

                </div>


                <div class="purchase-info-grid">

                    <div class="purchase-info-item">

                        <span>
                            Invoice Number
                        </span>

                        <strong>

                            <?php
                            echo htmlspecialchars(
                                $invoice_number
                            );
                            ?>

                        </strong>

                    </div>


                    <div class="purchase-info-item">

                        <span>
                            Transaction ID
                        </span>

                        <strong class="transaction-full-id">

                            <?php
                            echo htmlspecialchars(
                                $transaction_id
                            );
                            ?>

                        </strong>

                    </div>


                    <div class="purchase-info-item">

                        <span>
                            Payment Method
                        </span>

                        <strong>

                            <?php
                            echo htmlspecialchars(
                                $payment_method
                            );
                            ?>

                        </strong>

                    </div>


                    <div class="purchase-info-item">

                        <span>
                            Payment Status
                        </span>

                        <strong>

                            <?php
                            echo htmlspecialchars(
                                $payment_status
                            );
                            ?>

                        </strong>

                    </div>


                    <div class="purchase-info-item">

                        <span>
                            Number of Items
                        </span>

                        <strong>

                            <?php echo $item_count; ?>

                            <?php
                            echo $item_count === 1
                                ? 'Item'
                                : 'Items';
                            ?>

                        </strong>

                    </div>


                    <div class="purchase-info-item">

                        <span>
                            Completed Date
                        </span>

                        <strong>

                            <?php

                            if (!empty($display_date)) {

                                echo date(
                                    'd M Y, h:i A',
                                    strtotime($display_date)
                                );

                            } else {

                                echo 'Not available';

                            }

                            ?>

                        </strong>

                    </div>

                </div>

            </section>


            <!-- Purchased items -->

            <section class="details-card">

                <div class="details-card-header">

                    <div>

                        <h2>
                            Purchased Items
                        </h2>

                        <p>

                            Products included in this transaction

                        </p>

                    </div>

                </div>


                <div class="table-responsive">

                    <table class="purchased-items-table">

                        <thead>

                            <tr>

                                <th>Product</th>

                                <th>Quantity</th>

                                <th>Unit Price</th>

                                <th>Total</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php if ($items_result->num_rows > 0) { ?>

                            <?php while ($item = $items_result->fetch_assoc()) { ?>

                                <tr>

                                    <td>

                                        <div class="purchase-product-cell">

                                            <div class="purchase-product-icon">

                                                <i class="fa-solid fa-box"></i>

                                            </div>

                                            <div>

                                                <strong>

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $item['product_name']
                                                    );
                                                    ?>

                                                </strong>

                                                <small>

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $item['category_name']
                                                        ?? 'Uncategorized'
                                                    );
                                                    ?>

                                                    · Request #

                                                    <?php
                                                    echo (int)
                                                        $item['request_id'];
                                                    ?>

                                                </small>

                                            </div>

                                        </div>

                                    </td>


                                    <td>

                                        <span class="purchase-quantity-badge">

                                            <?php
                                            echo (int)
                                                $item['quantity'];
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        Rs.

                                        <?php
                                        echo number_format(
                                            (float)
                                            $item['unit_price'],
                                            2
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <strong>

                                            Rs.

                                            <?php
                                            echo number_format(
                                                (float)
                                                $item['total_amount'],
                                                2
                                            );
                                            ?>

                                        </strong>

                                    </td>

                                </tr>

                            <?php } ?>

                        <?php } else { ?>

                            <tr>

                                <td
                                    colspan="4"
                                    class="empty-table-message"
                                >

                                    No purchased items found.

                                </td>

                            </tr>

                        <?php } ?>

                        </tbody>

                    </table>

                </div>


                <div class="items-table-total">

                    <div class="items-grand-total">

                        <div class="items-grand-total-row">

                            <span>
                                Grand Total
                            </span>

                            <strong>

                                Rs.

                                <?php
                                echo number_format(
                                    $grand_total,
                                    2
                                );
                                ?>

                            </strong>

                        </div>

                    </div>

                </div>

            </section>

        </div>


        <!-- Right-side summary -->

        <aside class="purchase-sidebar">

            <section class="sidebar-summary-card">

                <h3>
                    Purchase Summary
                </h3>

                <div class="sidebar-summary-row">

                    <span>
                        Items
                    </span>

                    <strong>
                        <?php echo $item_count; ?>
                    </strong>

                </div>


                <div class="sidebar-summary-row">

                    <span>
                        Payment Method
                    </span>

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $payment_method
                        );
                        ?>

                    </strong>

                </div>


                <div class="sidebar-summary-row">

                    <span>
                        Payment Status
                    </span>

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $payment_status
                        );
                        ?>

                    </strong>

                </div>


                <div class="sidebar-total-box">

                    <span>
                        Total Paid
                    </span>

                    <strong>

                        Rs.

                        <?php
                        echo number_format(
                            $grand_total,
                            2
                        );
                        ?>

                    </strong>

                </div>


                <a
                    href="invoice.php?payment_id=<?php echo $payment_id; ?>"
                    class="invoice-download-btn sidebar-invoice-btn"
                >

                    <i class="fa-solid fa-file-pdf"></i>

                    Download Invoice

                </a>

            </section>


            <section class="sidebar-summary-card">

                <h3>
                    Shopkeeper Information
                </h3>

                <div class="sidebar-summary-row">

                    <span>
                        Name
                    </span>

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $_SESSION['name']
                            ?? 'Shopkeeper'
                        );
                        ?>

                    </strong>

                </div>


                <div class="sidebar-summary-row">

                    <span>
                        Customer ID
                    </span>

                    <strong>

                        #<?php echo $shopkeeper_id; ?>

                    </strong>

                </div>

            </section>

        </aside>

    </div>

</main>


<script src="../assets/js/script.js?v=3"></script>

</body>

</html>