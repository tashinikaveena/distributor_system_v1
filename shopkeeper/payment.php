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

$message = "";
$message_type = "";
$transaction_id = "";
$payment_completed = false;

/* =========================================
   LOAD ALL APPROVED UNPAID REQUESTS
========================================= */

function loadApprovedItems(
    mysqli $conn,
    int $shopkeeper_id
): array
{
    $stmt = $conn->prepare(
        "SELECT
            sr.request_id,
            sr.product_id,
            sr.quantity,
            sr.request_date,

            p.product_name,
            p.description,
            p.price,
            p.stock_quantity,

            c.category_name,

            (sr.quantity * p.price) AS subtotal

         FROM stock_requests sr

         JOIN products p
            ON sr.product_id = p.product_id

         LEFT JOIN categories c
            ON p.category_id = c.category_id

         LEFT JOIN payment_items pi
            ON sr.request_id = pi.request_id

         WHERE sr.shopkeeper_id = ?
           AND sr.status = 'Approved'
           AND pi.payment_item_id IS NULL

         ORDER BY sr.request_date ASC"
    );

    $stmt->bind_param(
        "i",
        $shopkeeper_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $items = [];

    while($row = $result->fetch_assoc())
    {
        $items[] = $row;
    }

    return $items;
}

$approved_items = loadApprovedItems(
    $conn,
    $shopkeeper_id
);

$grand_total = 0;

foreach($approved_items as $item)
{
    $grand_total += (float) $item['subtotal'];
}

$item_count = count($approved_items);

/* =========================================
   PROCESS BATCH PAYMENT
========================================= */

if(isset($_POST['pay_now']))
{
    $payment_method =
        trim($_POST['payment_method'] ?? '');

    $cardholder_name =
        trim($_POST['cardholder_name'] ?? '');

    $card_number =
        preg_replace(
            '/\D/',
            '',
            $_POST['card_number'] ?? ''
        );

    $expiry_date =
        trim($_POST['expiry_date'] ?? '');

    $cvv =
        preg_replace(
            '/\D/',
            '',
            $_POST['cvv'] ?? ''
        );

    $allowed_methods = [
        'Visa',
        'MasterCard',
        'Debit Card'
    ];

    if($item_count === 0)
    {
        $message =
            "There are no approved products available for payment.";

        $message_type = "error";
    }
    elseif(
        !in_array(
            $payment_method,
            $allowed_methods,
            true
        )
    )
    {
        $message =
            "Please select a valid payment method.";

        $message_type = "error";
    }
    elseif($cardholder_name === '')
    {
        $message =
            "Please enter the cardholder name.";

        $message_type = "error";
    }
    elseif(
        strlen($card_number) < 13 ||
        strlen($card_number) > 19
    )
    {
        $message =
            "Please enter a valid card number.";

        $message_type = "error";
    }
    elseif(
        !preg_match(
            '/^\d{2}\/\d{2}$/',
            $expiry_date
        )
    )
    {
        $message =
            "Expiry date must use MM/YY format.";

        $message_type = "error";
    }
    elseif(
        strlen($cvv) < 3 ||
        strlen($cvv) > 4
    )
    {
        $message =
            "Please enter a valid CVV.";

        $message_type = "error";
    }
    else
    {
        try
        {
            $conn->begin_transaction();

            /*
             * Lock all currently approved unpaid requests.
             */

            $lock_stmt = $conn->prepare(
                "SELECT
                    sr.request_id,
                    sr.product_id,
                    sr.quantity,
                    sr.status,

                    p.product_name,
                    p.price,
                    p.stock_quantity

                 FROM stock_requests sr

                 JOIN products p
                    ON sr.product_id = p.product_id

                 LEFT JOIN payment_items pi
                    ON sr.request_id = pi.request_id

                 WHERE sr.shopkeeper_id = ?
                   AND sr.status = 'Approved'
                   AND pi.payment_item_id IS NULL

                 ORDER BY sr.request_id ASC

                 FOR UPDATE"
            );

            $lock_stmt->bind_param(
                "i",
                $shopkeeper_id
            );

            $lock_stmt->execute();

            $lock_result =
                $lock_stmt->get_result();

            if($lock_result->num_rows === 0)
            {
                throw new Exception(
                    "No approved products are available for payment."
                );
            }

            $locked_items = [];
            $locked_grand_total = 0;

            while(
                $locked_item =
                    $lock_result->fetch_assoc()
            )
            {
                $available_stock =
                    (int) $locked_item['stock_quantity'];

                $requested_quantity =
                    (int) $locked_item['quantity'];

                if(
                    $available_stock <
                    $requested_quantity
                )
                {
                    throw new Exception(
                        "Not enough stock is available for "
                        . $locked_item['product_name']
                        . "."
                    );
                }

                $unit_price =
                    (float) $locked_item['price'];

                $subtotal =
                    $unit_price *
                    $requested_quantity;

                $locked_item['subtotal'] =
                    $subtotal;

                $locked_items[] =
                    $locked_item;

                $locked_grand_total +=
                    $subtotal;
            }

            $transaction_id =
                'TXN' .
                date('YmdHis') .
                random_int(1000, 9999);

            $payment_status = "Success";

            /*
             * One payment record for all approved items.
             * request_id is NULL for a batch payment.
             */

            $payment_stmt = $conn->prepare(
                "INSERT INTO payments
                (
                    request_id,
                    shopkeeper_id,
                    amount,
                    payment_method,
                    payment_status,
                    transaction_id
                )
                VALUES
                (
                    NULL,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )"
            );

            $payment_stmt->bind_param(
                "idsss",
                $shopkeeper_id,
                $locked_grand_total,
                $payment_method,
                $payment_status,
                $transaction_id
            );

            if(!$payment_stmt->execute())
            {
                throw new Exception(
                    "The payment record could not be saved."
                );
            }

            $payment_id =
                (int) $conn->insert_id;

            $order_status = "Completed";

            /*
             * Process every approved product.
             */

            foreach($locked_items as $item)
            {
                $request_id =
                    (int) $item['request_id'];

                $product_id =
                    (int) $item['product_id'];

                $quantity =
                    (int) $item['quantity'];

                $unit_price =
                    (float) $item['price'];

                $subtotal =
                    (float) $item['subtotal'];

                /* Insert payment item */

                $payment_item_stmt =
                    $conn->prepare(
                        "INSERT INTO payment_items
                        (
                            payment_id,
                            request_id,
                            product_id,
                            quantity,
                            unit_price,
                            subtotal
                        )
                        VALUES (?, ?, ?, ?, ?, ?)"
                    );

                $payment_item_stmt->bind_param(
                    "iiiidd",
                    $payment_id,
                    $request_id,
                    $product_id,
                    $quantity,
                    $unit_price,
                    $subtotal
                );

                if(
                    !$payment_item_stmt->execute()
                )
                {
                    throw new Exception(
                        "A payment item could not be saved."
                    );
                }

                /* Reduce product stock */

                $stock_stmt =
                    $conn->prepare(
                        "UPDATE products

                         SET stock_quantity =
                             stock_quantity - ?

                         WHERE product_id = ?
                           AND stock_quantity >= ?"
                    );

                $stock_stmt->bind_param(
                    "iii",
                    $quantity,
                    $product_id,
                    $quantity
                );

                $stock_stmt->execute();

                if(
                    $stock_stmt->affected_rows !== 1
                )
                {
                    throw new Exception(
                        "Stock could not be updated for "
                        . $item['product_name']
                        . "."
                    );
                }

                /* Mark request as completed */

                $request_update_stmt =
                    $conn->prepare(
                        "UPDATE stock_requests

                         SET status = 'Completed'

                         WHERE request_id = ?
                           AND shopkeeper_id = ?
                           AND status = 'Approved'"
                    );

                $request_update_stmt->bind_param(
                    "ii",
                    $request_id,
                    $shopkeeper_id
                );

                $request_update_stmt->execute();

                if(
                    $request_update_stmt
                        ->affected_rows !== 1
                )
                {
                    throw new Exception(
                        "Request #"
                        . $request_id
                        . " could not be completed."
                    );
                }

                /* Create one order for each product */

                $order_stmt =
                    $conn->prepare(
                        "INSERT INTO orders
                        (
                            request_id,
                            payment_id,
                            product_id,
                            shopkeeper_id,
                            quantity,
                            total_amount,
                            order_status
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );

                $order_stmt->bind_param(
                    "iiiiids",
                    $request_id,
                    $payment_id,
                    $product_id,
                    $shopkeeper_id,
                    $quantity,
                    $subtotal,
                    $order_status
                );

                if(!$order_stmt->execute())
                {
                    throw new Exception(
                        "The order for "
                        . $item['product_name']
                        . " could not be created."
                    );
                }
            }

            /* Save one notification */

            $notification_message =
                "Payment successful for "
                . count($locked_items)
                . " approved product(s). "
                . "Total paid: Rs. "
                . number_format(
                    $locked_grand_total,
                    2
                )
                . ". Transaction ID: "
                . $transaction_id
                . ". Your orders have been completed.";

            $notification_stmt =
                $conn->prepare(
                    "INSERT INTO notifications
                    (
                        user_id,
                        message
                    )
                    VALUES (?, ?)"
                );

            $notification_stmt->bind_param(
                "is",
                $shopkeeper_id,
                $notification_message
            );

            if(!$notification_stmt->execute())
            {
                throw new Exception(
                    "The payment notification could not be saved."
                );
            }

            $conn->commit();

            $message =
                "Payment completed successfully.";

            $message_type = "success";

            $payment_completed = true;

            $grand_total =
                $locked_grand_total;

            $item_count =
                count($locked_items);

            $approved_items = [];
        }
        catch(Throwable $error)
        {
            $conn->rollback();

            $message =
                $error->getMessage();

            $message_type = "error";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Make Payment</title>

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

    <section class="page-heading-row">

        <div>

            <p class="dashboard-label">
                Secure Checkout
            </p>

            <h1 class="dashboard-title">
                Pay Approved Products
            </h1>

            <p class="dashboard-subtitle">
                Review all approved products and complete
                one payment for the full amount.
            </p>

        </div>

        <a
            href="my_requests.php"
            class="back-products-btn">

            <i class="fa-solid fa-arrow-left"></i>
            Back to My Requests

        </a>

    </section>

    <?php if($message !== "") { ?>

        <div
            class="form-message <?php
            echo htmlspecialchars($message_type);
            ?>">

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php } ?>

    <?php if($payment_completed) { ?>

        <section class="payment-success-card">

            <div class="payment-success-icon">

                <i class="fa-solid fa-circle-check"></i>

            </div>

            <p class="dashboard-label">
                Payment Completed
            </p>

            <h2>
                Payment Successful
            </h2>

            <p>
                Your payment for
                <?php echo $item_count; ?>
                product(s) was completed successfully.
            </p>

            <div class="batch-payment-success-details">

                <div>

                    <span>Total Paid</span>

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

                <div>

                    <span>Transaction ID</span>

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $transaction_id
                        );
                        ?>

                    </strong>

                </div>

            </div>

            <div class="payment-success-actions">

                <a
                    href="orders.php"
                    class="dashboard-primary-btn">

                    <i class="fa-solid fa-boxes-packing"></i>
                    View My Orders

                </a>

                <a
                    href="notifications.php"
                    class="back-products-btn">

                    <i class="fa-solid fa-bell"></i>
                    View Notification

                </a>

            </div>

        </section>

    <?php } elseif($item_count === 0) { ?>

        <section class="payment-unavailable-card">

            <i class="fa-solid fa-cart-shopping"></i>

            <h2>
                No Approved Products
            </h2>

            <p>
                There are currently no approved unpaid
                products available for checkout.
            </p>

            <a
                href="my_requests.php"
                class="dashboard-primary-btn">

                Return to My Requests

            </a>

        </section>

    <?php } else { ?>

        <section class="batch-payment-layout">

            <!-- Approved Product List -->

            <div class="batch-order-summary-card">

                <div class="request-form-heading">

                    <div>

                        <p class="dashboard-label">
                            Order Summary
                        </p>

                        <h2>
                            Approved Product List
                        </h2>

                    </div>

                    <div class="request-heading-icon">

                        <i class="fa-solid fa-cart-shopping"></i>

                    </div>

                </div>

                <div class="batch-payment-item-list">

                    <?php foreach($approved_items as $item) { ?>

                        <article class="batch-payment-item">

                            <div class="batch-payment-product-icon">

                                <i class="fa-solid fa-box"></i>

                            </div>

                            <div class="batch-payment-product-info">

                                <h3>

                                    <?php
                                    echo htmlspecialchars(
                                        $item['product_name']
                                    );
                                    ?>

                                </h3>

                                <span>

                                    <?php
                                    echo htmlspecialchars(
                                        $item['category_name']
                                        ?? 'Uncategorized'
                                    );
                                    ?>

                                </span>

                                <p>

                                    Quantity:
                                    <strong>
                                        <?php
                                        echo (int)
                                            $item['quantity'];
                                        ?>
                                    </strong>

                                    × Rs.
                                    <?php
                                    echo number_format(
                                        (float)
                                        $item['price'],
                                        2
                                    );
                                    ?>

                                </p>

                            </div>

                            <div class="batch-payment-subtotal">

                                <span>Subtotal</span>

                                <strong>

                                    Rs.
                                    <?php
                                    echo number_format(
                                        (float)
                                        $item['subtotal'],
                                        2
                                    );
                                    ?>

                                </strong>

                            </div>

                        </article>

                    <?php } ?>

                </div>

                <div class="batch-grand-total">

                    <div>

                        <span>Number of Products</span>

                        <strong>
                            <?php echo $item_count; ?>
                        </strong>

                    </div>

                    <div>

                        <span>Grand Total</span>

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

            <!-- Card Payment Form -->

            <div class="payment-form-card">

                <div class="request-form-heading">

                    <div>

                        <p class="dashboard-label">
                            Payment Details
                        </p>

                        <h2>
                            Card Payment
                        </h2>

                    </div>

                    <div class="request-heading-icon">

                        <i class="fa-solid fa-credit-card"></i>

                    </div>

                </div>

                <div class="payment-security-note">

                    <i class="fa-solid fa-shield-halved"></i>

                    <p>
                        This payment page is a simulation created
                        for the project demonstration. Card details
                        are not stored in the database.
                    </p>

                </div>

                <form
                    method="POST"
                    class="payment-form"
                    onsubmit="
                        return confirm(
                            'Confirm payment for all approved products?'
                        );
                    ">

                    <div class="form-group">

                        <label for="payment_method">
                            Payment Method
                        </label>

                        <select
                            name="payment_method"
                            id="payment_method"
                            required>

                            <option value="">
                                Select Payment Method
                            </option>

                            <option value="Visa">
                                Visa
                            </option>

                            <option value="MasterCard">
                                MasterCard
                            </option>

                            <option value="Debit Card">
                                Debit Card
                            </option>

                        </select>

                    </div>

                    <div class="form-group">

                        <label for="cardholder_name">
                            Cardholder Name
                        </label>

                        <div class="request-input-wrapper">

                            <i class="fa-solid fa-user"></i>

                            <input
                                type="text"
                                id="cardholder_name"
                                name="cardholder_name"
                                placeholder="Name shown on the card"
                                autocomplete="cc-name"
                                required>

                        </div>

                    </div>

                    <div class="form-group">

                        <label for="card_number">
                            Card Number
                        </label>

                        <div class="request-input-wrapper">

                            <i class="fa-solid fa-credit-card"></i>

                            <input
                                type="text"
                                id="card_number"
                                name="card_number"
                                placeholder="1234 5678 9012 3456"
                                inputmode="numeric"
                                maxlength="23"
                                autocomplete="cc-number"
                                required>

                        </div>

                    </div>

                    <div class="payment-field-row">

                        <div class="form-group">

                            <label for="expiry_date">
                                Expiry Date
                            </label>

                            <div class="request-input-wrapper">

                                <i class="fa-solid fa-calendar"></i>

                                <input
                                    type="text"
                                    id="expiry_date"
                                    name="expiry_date"
                                    placeholder="MM/YY"
                                    maxlength="5"
                                    autocomplete="cc-exp"
                                    required>

                            </div>

                        </div>

                        <div class="form-group">

                            <label for="cvv">
                                CVV
                            </label>

                            <div class="request-input-wrapper">

                                <i class="fa-solid fa-lock"></i>

                                <input
                                    type="password"
                                    id="cvv"
                                    name="cvv"
                                    placeholder="123"
                                    inputmode="numeric"
                                    maxlength="4"
                                    autocomplete="cc-csc"
                                    required>

                            </div>

                        </div>

                    </div>

                    <button
                        type="submit"
                        name="pay_now"
                        class="pay-order-btn">

                        <i class="fa-solid fa-lock"></i>

                        Pay Rs.
                        <?php
                        echo number_format(
                            $grand_total,
                            2
                        );
                        ?>

                    </button>

                </form>

            </div>

        </section>

    <?php } ?>

</main>

<script src="../assets/js/script.js?v=3"></script>

</body>
</html>