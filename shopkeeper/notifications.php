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

$user_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT id, message, created_at
     FROM notifications
     WHERE user_id = ?
     ORDER BY created_at DESC"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$total_notifications = $result->num_rows;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Notifications</title>

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

    <section class="page-heading-row">

        <div>

            <p class="dashboard-label">
                Updates
            </p>

            <h1 class="dashboard-title">
                My Notifications
            </h1>

            <p class="dashboard-subtitle">
                View updates about stock requests, payments,
                and completed orders.
            </p>

        </div>

        <div class="notification-count-card">

            <div class="notification-count-icon">
                <i class="fa-solid fa-bell"></i>
            </div>

            <div>

                <span>Total Notifications</span>

                <strong>
                    <?php echo $total_notifications; ?>
                </strong>

            </div>

        </div>

    </section>

    <section class="notifications-card">

        <div class="notifications-card-heading">

            <div>

                <h2>Latest Notifications</h2>

                <p>
                    Notifications are ordered from newest to oldest.
                </p>

            </div>

        </div>

        <?php if($total_notifications > 0) { ?>

            <div class="notification-list">

                <?php while($row = $result->fetch_assoc()) { ?>

                    <?php

                    $message = $row['message'];
                    $message_lower = strtolower($message);

                    $notification_class = "default-notification";
                    $icon_class = "fa-bell";

                    if(str_contains($message_lower, "approved"))
                    {
                        $notification_class = "approved-notification";
                        $icon_class = "fa-circle-check";
                    }
                    elseif(str_contains($message_lower, "rejected"))
                    {
                        $notification_class = "rejected-notification";
                        $icon_class = "fa-circle-xmark";
                    }
                    elseif(
                        str_contains($message_lower, "payment") ||
                        str_contains($message_lower, "paid")
                    )
                    {
                        $notification_class = "payment-notification";
                        $icon_class = "fa-credit-card";
                    }
                    elseif(
                        str_contains($message_lower, "completed") ||
                        str_contains($message_lower, "order")
                    )
                    {
                        $notification_class = "completed-notification";
                        $icon_class = "fa-box-open";
                    }

                    ?>

                    <article class="notification-item <?php echo $notification_class; ?>">

                        <div class="notification-item-icon">

                            <i class="fa-solid <?php echo $icon_class; ?>"></i>

                        </div>

                        <div class="notification-item-content">

                            <p>
                                <?php echo htmlspecialchars($message); ?>
                            </p>

                            <span>

                                <i class="fa-regular fa-clock"></i>

                                <?php
                                echo date(
                                    'd M Y, h:i A',
                                    strtotime($row['created_at'])
                                );
                                ?>

                            </span>

                        </div>

                    </article>

                <?php } ?>

            </div>

        <?php } else { ?>

            <div class="empty-state">

                <i class="fa-regular fa-bell-slash"></i>

                <h3>No Notifications Yet</h3>

                <p>
                    Updates about your stock requests will appear here.
                </p>

                <a href="products.php" class="dashboard-primary-btn">

                    <i class="fa-solid fa-box-open"></i>
                    View Products

                </a>

            </div>

        <?php } ?>

    </section>

</main>

<script src="../assets/js/script.js?v=2"></script>

</body>
</html>