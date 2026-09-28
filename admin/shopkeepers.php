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

/* =========================================
   DELETE SHOPKEEPER
========================================= */

if(isset($_GET['delete']))
{
    $shopkeeper_id = (int) $_GET['delete'];

    /* Check that the selected user is a shopkeeper */

    $user_check = $conn->prepare(
        "SELECT id, name
         FROM users
         WHERE id = ?
           AND role = 'shopkeeper'"
    );

    $user_check->bind_param("i", $shopkeeper_id);
    $user_check->execute();

    $user_result = $user_check->get_result();

    if($user_result->num_rows !== 1)
    {
        $message = "Shopkeeper account was not found.";
        $message_type = "error";
    }
    else
    {
        $shopkeeper = $user_result->fetch_assoc();

        /*
         * Check whether this shopkeeper has request,
         * payment, or order history.
         */

        $history_check = $conn->prepare(
            "SELECT
                (SELECT COUNT(*)
                 FROM stock_requests
                 WHERE shopkeeper_id = ?) AS request_count,

                (SELECT COUNT(*)
                 FROM payments
                 WHERE shopkeeper_id = ?) AS payment_count,

                (SELECT COUNT(*)
                 FROM orders
                 WHERE shopkeeper_id = ?) AS order_count"
        );

        $history_check->bind_param(
            "iii",
            $shopkeeper_id,
            $shopkeeper_id,
            $shopkeeper_id
        );

        $history_check->execute();

        $history =
            $history_check->get_result()->fetch_assoc();

        $request_count = (int) $history['request_count'];
        $payment_count = (int) $history['payment_count'];
        $order_count = (int) $history['order_count'];

        if(
            $request_count > 0 ||
            $payment_count > 0 ||
            $order_count > 0
        )
        {
            $message =
                "Cannot delete "
                . $shopkeeper['name']
                . " because this account has request, payment, "
                . "or order history.";

            $message_type = "error";
        }
        else
        {
            /* Remove notifications first */

            $notification_delete = $conn->prepare(
                "DELETE FROM notifications
                 WHERE user_id = ?"
            );

            $notification_delete->bind_param(
                "i",
                $shopkeeper_id
            );

            $notification_delete->execute();

            /* Delete shopkeeper */

            $delete_stmt = $conn->prepare(
                "DELETE FROM users
                 WHERE id = ?
                   AND role = 'shopkeeper'"
            );

            $delete_stmt->bind_param(
                "i",
                $shopkeeper_id
            );

            if($delete_stmt->execute())
            {
                $message =
                    "Shopkeeper deleted successfully.";

                $message_type = "success";
            }
            else
            {
                $message =
                    "Unable to delete the shopkeeper.";

                $message_type = "error";
            }
        }
    }
}

/* =========================================
   SEARCH SHOPKEEPERS
========================================= */

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$sql = "
    SELECT
        id,
        name,
        email,
        phone,
        shop_name
    FROM users
    WHERE role = 'shopkeeper'
";

$params = [];
$types = "";

if($search !== "")
{
    $sql .= "
        AND (
            name LIKE ?
            OR email LIKE ?
            OR phone LIKE ?
            OR shop_name LIKE ?
        )
    ";

    $search_term = "%" . $search . "%";

    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;

    $types = "ssss";
}

$sql .= " ORDER BY name ASC";

$stmt = $conn->prepare($sql);

if(!empty($params))
{
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();

$total_shopkeepers = $result->num_rows;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Shopkeeper Management</title>

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
                User Management
            </p>

            <h1 class="dashboard-title">
                Registered Shopkeepers
            </h1>

            <p class="dashboard-subtitle">
                View and remove registered shopkeeper accounts.
            </p>

        </div>

        <div class="notification-count-card">

            <div class="notification-count-icon">
                <i class="fa-solid fa-store"></i>
            </div>

            <div>

                <span>Total Shopkeepers</span>

                <strong>
                    <?php echo $total_shopkeepers; ?>
                </strong>

            </div>

        </div>

    </section>

    <?php if($message !== "") { ?>

        <div class="form-message <?php echo $message_type; ?>">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php } ?>

    <section class="product-filter-card">

        <form
            method="GET"
            class="orders-filter-form">

            <div class="filter-field">

                <label for="search">
                    Search Shopkeepers
                </label>

                <div class="filter-input-wrapper">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        placeholder="Name, email, phone, or shop"
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
                href="shopkeepers.php"
                class="reset-filter-btn">

                <i class="fa-solid fa-rotate-left"></i>
                Reset

            </a>

        </form>

    </section>

    <section class="products-table-card">

        <div class="table-card-heading">

            <div>

                <h2>Shopkeeper List</h2>

                <p>
                    <?php echo $result->num_rows; ?>
                    shopkeeper(s) found
                </p>

            </div>

        </div>

        <?php if($result->num_rows > 0) { ?>

            <div class="table-responsive">

                <table class="shopkeepers-table">

                    <thead>

                        <tr>
                            <th>No</th>
                            <th>Shopkeeper</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Shop Name</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php
                        $no = 1;

                        while($row = $result->fetch_assoc())
                        {
                        ?>

                            <tr>

                                <td>
                                    <?php echo $no++; ?>
                                </td>

                                <td>

                                    <div class="product-name-cell">

                                        <div class="product-table-icon">
                                            <i class="fa-solid fa-user"></i>
                                        </div>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $row['name']
                                            );
                                            ?>
                                        </strong>

                                    </div>

                                </td>

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $row['email']
                                    );
                                    ?>

                                </td>

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $row['phone'] ?: '-'
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

                                    <a
                                        href="shopkeepers.php?delete=<?php
                                        echo $row['id'];
                                        ?>"
                                        class="shopkeeper-delete-btn"
                                        onclick="return confirm(
                                            'Are you sure you want to delete this shopkeeper?'
                                        );">

                                        <i class="fa-solid fa-trash"></i>
                                        Delete

                                    </a>

                                </td>

                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

        <?php } else { ?>

            <div class="empty-state">

                <i class="fa-solid fa-store"></i>

                <h3>No Shopkeepers Found</h3>

                <p>
                    No shopkeepers match the current search.
                </p>

            </div>

        <?php } ?>

    </section>

</main>

<script src="../assets/js/script.js?v=3"></script>

</body>
</html>