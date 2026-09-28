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

$message = "";
$message_type = "";

/* Approve Request */

if(isset($_GET['approve']))
{
    $request_id = (int) $_GET['approve'];

    $request_stmt = $conn->prepare(
        "SELECT
            stock_requests.request_id,
            stock_requests.status,
            stock_requests.quantity,
            users.id AS shopkeeper_id,
            users.name AS shopkeeper_name,
            users.email AS shopkeeper_email,
            products.product_name
         FROM stock_requests
         JOIN users
            ON stock_requests.shopkeeper_id = users.id
         JOIN products
            ON stock_requests.product_id = products.product_id
         WHERE stock_requests.request_id = ?"
    );

    $request_stmt->bind_param("i", $request_id);
    $request_stmt->execute();

    $request_result = $request_stmt->get_result();

    if($request_result->num_rows === 1)
    {
        $request = $request_result->fetch_assoc();

        if($request['status'] === 'Pending')
        {
            $update_stmt = $conn->prepare(
                "UPDATE stock_requests
                 SET status = 'Approved'
                 WHERE request_id = ?"
            );

            $update_stmt->bind_param("i", $request_id);

            if($update_stmt->execute())
            {
                $notification_message =
                    "Your stock request for "
                    . $request['product_name']
                    . " has been approved. "
                    . "Please complete the payment to proceed with your order.";

                $notification_stmt = $conn->prepare(
                    "INSERT INTO notifications
                    (user_id, message)
                    VALUES (?, ?)"
                );

                $notification_stmt->bind_param(
                    "is",
                    $request['shopkeeper_id'],
                    $notification_message
                );

                $notification_stmt->execute();

                /*
                   Email sending code will be added here
                   using PHPMailer.
                */

                $message = "Request approved successfully.";
                $message_type = "success";
            }
            else
            {
                $message = "Unable to approve the request.";
                $message_type = "error";
            }
        }
        else
        {
            $message = "Only pending requests can be approved.";
            $message_type = "error";
        }
    }
    else
    {
        $message = "Request not found.";
        $message_type = "error";
    }
}

/* Reject Request */

if(isset($_GET['reject']))
{
    $request_id = (int) $_GET['reject'];

    $request_stmt = $conn->prepare(
        "SELECT
            stock_requests.request_id,
            stock_requests.status,
            users.id AS shopkeeper_id,
            users.name AS shopkeeper_name,
            users.email AS shopkeeper_email,
            products.product_name
         FROM stock_requests
         JOIN users
            ON stock_requests.shopkeeper_id = users.id
         JOIN products
            ON stock_requests.product_id = products.product_id
         WHERE stock_requests.request_id = ?"
    );

    $request_stmt->bind_param("i", $request_id);
    $request_stmt->execute();

    $request_result = $request_stmt->get_result();

    if($request_result->num_rows === 1)
    {
        $request = $request_result->fetch_assoc();

        if($request['status'] === 'Pending')
        {
            $update_stmt = $conn->prepare(
                "UPDATE stock_requests
                 SET status = 'Rejected'
                 WHERE request_id = ?"
            );

            $update_stmt->bind_param("i", $request_id);

            if($update_stmt->execute())
            {
                $notification_message =
                    "Your stock request for "
                    . $request['product_name']
                    . " has been rejected.";

                $notification_stmt = $conn->prepare(
                    "INSERT INTO notifications
                    (user_id, message)
                    VALUES (?, ?)"
                );

                $notification_stmt->bind_param(
                    "is",
                    $request['shopkeeper_id'],
                    $notification_message
                );

                $notification_stmt->execute();

                /*
                   Rejection email sending code
                   will be added here using PHPMailer.
                */

                $message = "Request rejected successfully.";
                $message_type = "success";
            }
            else
            {
                $message = "Unable to reject the request.";
                $message_type = "error";
            }
        }
        else
        {
            $message = "Only pending requests can be rejected.";
            $message_type = "error";
        }
    }
    else
    {
        $message = "Request not found.";
        $message_type = "error";
    }
}

/* Filters */

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

/* Summary counts */

$count_result = $conn->query(
    "SELECT
        COUNT(*) AS total_requests,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END)
            AS pending_requests,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END)
            AS approved_requests,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END)
            AS rejected_requests
     FROM stock_requests"
);

$count_data = $count_result->fetch_assoc();

$total_requests = (int) ($count_data['total_requests'] ?? 0);
$pending_requests = (int) ($count_data['pending_requests'] ?? 0);
$approved_requests = (int) ($count_data['approved_requests'] ?? 0);
$rejected_requests = (int) ($count_data['rejected_requests'] ?? 0);

/* Request list */

$sql = "
    SELECT
        stock_requests.request_id,
        stock_requests.quantity,
        stock_requests.status,
        stock_requests.request_date,
        users.name AS shopkeeper_name,
        users.email AS shopkeeper_email,
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Stock Requests</title>

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
                Request Management
            </p>

            <h1 class="dashboard-title">
                Stock Requests
            </h1>

            <p class="dashboard-subtitle">
                Review submitted requests and approve or reject them.
            </p>

        </div>

    </section>

    <?php if($message !== "") { ?>

        <div class="form-message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php } ?>

    <section class="request-summary-grid">

        <div class="request-summary-card">
            <div class="request-summary-icon total-summary-icon">
                <i class="fa-solid fa-list"></i>
            </div>
            <div>
                <span>Total Requests</span>
                <strong><?php echo $total_requests; ?></strong>
            </div>
        </div>

        <div class="request-summary-card">
            <div class="request-summary-icon pending-summary-icon">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div>
                <span>Pending</span>
                <strong><?php echo $pending_requests; ?></strong>
            </div>
        </div>

        <div class="request-summary-card">
            <div class="request-summary-icon approved-summary-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <span>Approved</span>
                <strong><?php echo $approved_requests; ?></strong>
            </div>
        </div>

        <div class="request-summary-card">
            <div class="request-summary-icon rejected-summary-icon">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div>
                <span>Rejected</span>
                <strong><?php echo $rejected_requests; ?></strong>
            </div>
        </div>

    </section>

    <section class="product-filter-card">

        <form method="GET" class="request-filter-form">

            <div class="filter-field">

                <label for="search">
                    Search
                </label>

                <div class="filter-input-wrapper">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        placeholder="Shopkeeper, shop, or product"
                        value="<?php echo htmlspecialchars($search); ?>">

                </div>

            </div>

            <div class="filter-field">

                <label for="status">
                    Status
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

            <a href="stock_requests.php" class="reset-filter-btn">

                <i class="fa-solid fa-rotate-left"></i>
                Reset

            </a>

        </form>

    </section>

    <section class="products-table-card">

        <div class="table-card-heading">

            <div>

                <h2>Request List</h2>

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
                            <th>ID</th>
                            <th>Shopkeeper</th>
                            <th>Shop</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php while($row = $result->fetch_assoc()) { ?>

                            <tr>

                                <td>
                                    #<?php echo $row['request_id']; ?>
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

                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $row['product_name']
                                        );
                                        ?>
                                    </strong>

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
                                        strtotime($row['request_date'])
                                    );
                                    ?>

                                </td>

                                <td>

                                    <?php if($row['status'] === 'Pending') { ?>

                                        <a
                                            href="?approve=<?php
                                            echo $row['request_id'];
                                            ?>"
                                            class="approve-btn"
                                            onclick="return confirm(
                                                'Approve this request?'
                                            );">

                                            <i class="fa-solid fa-check"></i>
                                            Approve

                                        </a>

                                        <a
                                            href="?reject=<?php
                                            echo $row['request_id'];
                                            ?>"
                                            class="reject-btn"
                                            onclick="return confirm(
                                                'Reject this request?'
                                            );">

                                            <i class="fa-solid fa-xmark"></i>
                                            Reject

                                        </a>

                                    <?php } else { ?>

                                        <span class="request-action-text">

                                            <i class="fa-solid fa-lock"></i>
                                            Processed

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
                    No stock requests match the current filters.
                </p>

            </div>

        <?php } ?>

    </section>

</main>

<script src="../assets/js/script.js?v=2"></script>

</body>
</html>