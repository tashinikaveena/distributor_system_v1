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
   SUMMARY COUNTS
========================================= */

$count_stmt = $conn->prepare(
    "SELECT
        COUNT(*) AS total_requests,

        SUM(
            CASE
                WHEN status = 'Pending' THEN 1
                ELSE 0
            END
        ) AS pending_requests,

        SUM(
            CASE
                WHEN status = 'Approved' THEN 1
                ELSE 0
            END
        ) AS approved_requests,

        SUM(
            CASE
                WHEN status = 'Rejected' THEN 1
                ELSE 0
            END
        ) AS rejected_requests,

        SUM(
            CASE
                WHEN status = 'Completed' THEN 1
                ELSE 0
            END
        ) AS completed_requests

     FROM stock_requests

     WHERE shopkeeper_id = ?"
);

$count_stmt->bind_param(
    "i",
    $shopkeeper_id
);

$count_stmt->execute();

$count_result = $count_stmt->get_result();
$count_data = $count_result->fetch_assoc();

$total_requests =
    (int) ($count_data['total_requests'] ?? 0);

$pending_requests =
    (int) ($count_data['pending_requests'] ?? 0);

$approved_requests =
    (int) ($count_data['approved_requests'] ?? 0);

$rejected_requests =
    (int) ($count_data['rejected_requests'] ?? 0);

$completed_requests =
    (int) ($count_data['completed_requests'] ?? 0);

/* =========================================
   APPROVED ITEMS READY FOR PAYMENT
========================================= */

$approved_stmt = $conn->prepare(
    "SELECT
        stock_requests.request_id,
        stock_requests.product_id,
        stock_requests.quantity,
        stock_requests.request_date,

        products.product_name,
        products.description,
        products.price,
        products.stock_quantity,

        categories.category_name,

        (
            stock_requests.quantity *
            products.price
        ) AS subtotal

     FROM stock_requests

     JOIN products
        ON stock_requests.product_id =
           products.product_id

     LEFT JOIN categories
        ON products.category_id =
           categories.category_id

     WHERE stock_requests.shopkeeper_id = ?
       AND stock_requests.status = 'Approved'

     ORDER BY stock_requests.request_date ASC"
);

$approved_stmt->bind_param(
    "i",
    $shopkeeper_id
);

$approved_stmt->execute();

$approved_result = $approved_stmt->get_result();

$approved_items = [];
$approved_grand_total = 0;

while($approved_row = $approved_result->fetch_assoc())
{
    $approved_items[] = $approved_row;

    $approved_grand_total +=
        (float) $approved_row['subtotal'];
}

$approved_item_count = count($approved_items);

/* =========================================
   REQUEST HISTORY WITH FILTERS
========================================= */

$sql = "
    SELECT
        stock_requests.request_id,
        stock_requests.quantity,
        stock_requests.status,
        stock_requests.request_date,

        products.product_name,
        products.price,

        categories.category_name

    FROM stock_requests

    JOIN products
        ON stock_requests.product_id =
           products.product_id

    LEFT JOIN categories
        ON products.category_id =
           categories.category_id

    WHERE stock_requests.shopkeeper_id = ?
";

$params = [$shopkeeper_id];
$types = "i";

if($search !== "")
{
    $sql .= "
        AND products.product_name LIKE ?
    ";

    $search_term = "%" . $search . "%";

    $params[] = $search_term;
    $types .= "s";
}

if($status_filter !== "")
{
    $sql .= "
        AND stock_requests.status = ?
    ";

    $params[] = $status_filter;
    $types .= "s";
}

$sql .= "
    ORDER BY stock_requests.request_date DESC
";

$stmt = $conn->prepare($sql);

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

    <title>My Requests</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link
        rel="stylesheet"
        href="../assets/css/style.css?v=20260713">

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

    <!-- Page Heading -->

    <section class="page-heading-row">

        <div>

            <p class="dashboard-label">
                Request Tracking
            </p>

            <h1 class="dashboard-title">
                My Stock Requests
            </h1>

            <p class="dashboard-subtitle">
                Track submitted requests and pay for all
                approved products together.
            </p>

        </div>

        <a
            href="request_stock.php"
            class="dashboard-primary-btn">

            <i class="fa-solid fa-plus"></i>
            New Request

        </a>

    </section>

    <!-- Summary Cards -->

    <section class="request-summary-grid">

        <div class="request-summary-card">

            <div class="request-summary-icon total-summary-icon">

                <i class="fa-solid fa-list"></i>

            </div>

            <div>

                <span>Total Requests</span>

                <strong>
                    <?php echo $total_requests; ?>
                </strong>

            </div>

        </div>

        <div class="request-summary-card">

            <div class="request-summary-icon pending-summary-icon">

                <i class="fa-solid fa-clock"></i>

            </div>

            <div>

                <span>Pending</span>

                <strong>
                    <?php echo $pending_requests; ?>
                </strong>

            </div>

        </div>

        <div class="request-summary-card">

            <div class="request-summary-icon approved-summary-icon">

                <i class="fa-solid fa-circle-check"></i>

            </div>

            <div>

                <span>Ready for Payment</span>

                <strong>
                    <?php echo $approved_requests; ?>
                </strong>

            </div>

        </div>

        <div class="request-summary-card">

            <div class="request-summary-icon rejected-summary-icon">

                <i class="fa-solid fa-circle-xmark"></i>

            </div>

            <div>

                <span>Rejected</span>

                <strong>
                    <?php echo $rejected_requests; ?>
                </strong>

            </div>

        </div>

    </section>

    <!-- Approved Items Checkout Section -->

    <?php if($approved_item_count > 0) { ?>

        <section class="approved-checkout-card">

            <div class="approved-checkout-heading">

                <div>

                    <p class="dashboard-label">
                        Ready for Checkout
                    </p>

                    <h2>
                        Approved Products
                    </h2>

                    <p>
                        These products were approved by the
                        administrator and can be paid together.
                    </p>

                </div>

                <div class="approved-items-count">

                    <i class="fa-solid fa-cart-shopping"></i>

                    <span>
                        <?php echo $approved_item_count; ?>
                        item(s)
                    </span>

                </div>

            </div>

            <div class="table-responsive">

                <table class="approved-items-table">

                    <thead>

                        <tr>
                            <th>Request ID</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach($approved_items as $item) { ?>

                            <tr>

                                <td>

                                    <strong>
                                        #<?php
                                        echo (int)
                                            $item['request_id'];
                                        ?>
                                    </strong>

                                </td>

                                <td>

                                    <div class="product-name-cell">

                                        <div class="product-table-icon">

                                            <i class="fa-solid fa-box"></i>

                                        </div>

                                        <strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $item['product_name']
                                            );
                                            ?>

                                        </strong>

                                    </div>

                                </td>

                                <td>

                                    <span class="category-badge">

                                        <?php
                                        echo htmlspecialchars(
                                            $item['category_name']
                                            ?? 'Uncategorized'
                                        );
                                        ?>

                                    </span>

                                </td>

                                <td>

                                    <strong>
                                        <?php
                                        echo (int)
                                            $item['quantity'];
                                        ?>
                                    </strong>

                                </td>

                                <td>

                                    <span class="approved-unit-price">

                                        Rs.
                                        <?php
                                        echo number_format(
                                            (float)
                                            $item['price'],
                                            2
                                        );
                                        ?>

                                    </span>

                                </td>

                                <td>

                                    <strong class="approved-subtotal">

                                        Rs.
                                        <?php
                                        echo number_format(
                                            (float)
                                            $item['subtotal'],
                                            2
                                        );
                                        ?>

                                    </strong>

                                </td>

                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

            <div class="approved-checkout-footer">

                <div class="approved-payment-note">

                    <i class="fa-solid fa-circle-info"></i>

                    <p>
                        One payment will cover all the approved
                        products shown above.
                    </p>

                </div>

                <div class="approved-total-box">

                    <span>Grand Total</span>

                    <strong>

                        Rs.
                        <?php
                        echo number_format(
                            $approved_grand_total,
                            2
                        );
                        ?>

                    </strong>

                </div>

                <a
                    href="payment.php"
                    class="pay-all-approved-btn">

                    <i class="fa-solid fa-credit-card"></i>

                    Pay All Approved Items

                </a>

            </div>

        </section>

    <?php } else { ?>

        <section class="no-approved-items-card">

            <div class="no-approved-items-icon">

                <i class="fa-solid fa-cart-shopping"></i>

            </div>

            <div>

                <h3>
                    No Products Ready for Payment
                </h3>

                <p>
                    Approved unpaid products will appear here.
                </p>

            </div>

        </section>

    <?php } ?>

    <!-- Search and Status Filters -->

    <section class="product-filter-card">

        <form
            method="GET"
            class="request-filter-form">

            <div class="filter-field">

                <label for="search">
                    Search Product
                </label>

                <div class="filter-input-wrapper">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        placeholder="Search by product name"
                        value="<?php
                        echo htmlspecialchars($search);
                        ?>">

                </div>

            </div>

            <div class="filter-field">

                <label for="status">
                    Request Status
                </label>

                <select
                    id="status"
                    name="status">

                    <option value="">
                        All Statuses
                    </option>

                    <?php foreach($allowed_statuses as $status) { ?>

                        <option
                            value="<?php
                            echo htmlspecialchars($status);
                            ?>"

                            <?php
                            if($status_filter === $status)
                            {
                                echo "selected";
                            }
                            ?>>

                            <?php
                            echo htmlspecialchars($status);
                            ?>

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
                href="my_requests.php"
                class="reset-filter-btn">

                <i class="fa-solid fa-rotate-left"></i>
                Reset

            </a>

        </form>

    </section>

    <!-- Full Request History -->

    <section class="products-table-card">

        <div class="table-card-heading">

            <div>

                <h2>Complete Request History</h2>

                <p>
                    <?php echo $result->num_rows; ?>
                    request(s) found
                </p>

            </div>

        </div>

        <?php if($result->num_rows > 0) { ?>

            <div class="table-responsive">

                <table class="requests-table">

                    <thead>

                        <tr>
                            <th>No</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Estimated Amount</th>
                            <th>Status</th>
                            <th>Request Date</th>
                            <th>Next Action</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php

                        $no = 1;

                        while($row = $result->fetch_assoc())
                        {
                            $estimated_amount =
                                (float) $row['price'] *
                                (int) $row['quantity'];

                        ?>

                            <tr>

                                <td>
                                    <?php echo $no++; ?>
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
                                        echo (int)
                                            $row['quantity'];
                                        ?>
                                    </strong>

                                </td>

                                <td>

                                    <strong class="request-estimated-total">

                                        Rs.
                                        <?php
                                        echo number_format(
                                            $estimated_amount,
                                            2
                                        );
                                        ?>

                                    </strong>

                                </td>

                                <td>

                                    <?php
                                    if(
                                        $row['status'] ===
                                        'Pending'
                                    )
                                    {
                                    ?>

                                        <span class="request-status-badge pending-request">

                                            <i class="fa-solid fa-clock"></i>
                                            Pending

                                        </span>

                                    <?php
                                    }
                                    elseif(
                                        $row['status'] ===
                                        'Approved'
                                    )
                                    {
                                    ?>

                                        <span class="request-status-badge approved-request">

                                            <i class="fa-solid fa-circle-check"></i>
                                            Approved

                                        </span>

                                    <?php
                                    }
                                    elseif(
                                        $row['status'] ===
                                        'Rejected'
                                    )
                                    {
                                    ?>

                                        <span class="request-status-badge rejected-request">

                                            <i class="fa-solid fa-circle-xmark"></i>
                                            Rejected

                                        </span>

                                    <?php
                                    }
                                    else
                                    {
                                    ?>

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

                                <td>

                                    <?php
                                    if(
                                        $row['status'] ===
                                        'Pending'
                                    )
                                    {
                                    ?>

                                        <span class="request-action-text waiting-action">

                                            <i class="fa-solid fa-hourglass-half"></i>
                                            Waiting for approval

                                        </span>

                                    <?php
                                    }
                                    elseif(
                                        $row['status'] ===
                                        'Approved'
                                    )
                                    {
                                    ?>

                                        <span class="request-action-text approved-checkout-action">

                                            <i class="fa-solid fa-cart-shopping"></i>
                                            Included in checkout

                                        </span>

                                    <?php
                                    }
                                    elseif(
                                        $row['status'] ===
                                        'Rejected'
                                    )
                                    {
                                    ?>

                                        <span class="request-action-text rejected-action">

                                            <i class="fa-solid fa-circle-xmark"></i>
                                            Request rejected

                                        </span>

                                    <?php
                                    }
                                    else
                                    {
                                    ?>

                                        <span class="request-action-text completed-action">

                                            <i class="fa-solid fa-circle-check"></i>
                                            Payment completed

                                        </span>

                                    <?php } ?>

                                </td>

                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

        <?php } else { ?>

            <div class="empty-state">

                <i class="fa-solid fa-clipboard-list"></i>

                <h3>No Requests Found</h3>

                <p>
                    Submit a new request or change
                    the selected filters.
                </p>

                <a
                    href="request_stock.php"
                    class="dashboard-primary-btn">

                    <i class="fa-solid fa-plus"></i>
                    Create Request

                </a>

            </div>

        <?php } ?>

    </section>

</main>

<script src="../assets/js/script.js?v=3"></script>

</body>
</html>