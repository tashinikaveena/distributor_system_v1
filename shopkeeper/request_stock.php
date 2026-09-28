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

$message = "";
$message_type = "";

$shopkeeper_id = (int) $_SESSION['user_id'];

/* Selected product from Products page */

$selected_product_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

/* Create Stock Request */

if(isset($_POST['request_stock']))
{
    $product_id = (int) $_POST['product_id'];
    $quantity = (int) $_POST['quantity'];

    if($product_id <= 0)
    {
        $message = "Please select a product.";
        $message_type = "error";
    }
    elseif($quantity <= 0)
    {
        $message = "Quantity must be greater than zero.";
        $message_type = "error";
    }
    else
    {
        /* Check product and available stock */

        $product_check = $conn->prepare(
            "SELECT product_name, stock_quantity
             FROM products
             WHERE product_id = ?"
        );

        $product_check->bind_param("i", $product_id);
        $product_check->execute();

        $product_result = $product_check->get_result();

        if($product_result->num_rows !== 1)
        {
            $message = "Selected product was not found.";
            $message_type = "error";
        }
        else
        {
            $product_data = $product_result->fetch_assoc();
            $available_stock = (int) $product_data['stock_quantity'];

            if($available_stock <= 0)
            {
                $message = "This product is currently out of stock.";
                $message_type = "error";
            }
            elseif($quantity > $available_stock)
            {
                $message =
                    "Requested quantity cannot exceed the available stock of "
                    . $available_stock . ".";
                $message_type = "error";
            }
            else
            {
                $status = "Pending";

                $stmt = $conn->prepare(
                    "INSERT INTO stock_requests
                    (shopkeeper_id, product_id, quantity, status)
                    VALUES (?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "iiis",
                    $shopkeeper_id,
                    $product_id,
                    $quantity,
                    $status
                );

                if($stmt->execute())
                {
                    $message =
                        "Stock request submitted successfully. "
                        . "Please wait for administrator approval.";

                    $message_type = "success";

                    $selected_product_id = 0;
                }
                else
                {
                    $message =
                        "Unable to submit the stock request. Please try again.";

                    $message_type = "error";
                }
            }
        }
    }
}

/* Get all available products */

$products = $conn->query(
    "SELECT
        products.product_id,
        products.product_name,
        products.description,
        products.price,
        products.stock_quantity,
        categories.category_name
     FROM products
     LEFT JOIN categories
        ON products.category_id = categories.category_id
     WHERE products.stock_quantity > 0
     ORDER BY products.product_name ASC"
);

/* Get selected product details */

$selected_product = null;

if($selected_product_id > 0)
{
    $selected_stmt = $conn->prepare(
        "SELECT
            products.product_id,
            products.product_name,
            products.description,
            products.price,
            products.stock_quantity,
            categories.category_name
         FROM products
         LEFT JOIN categories
            ON products.category_id = categories.category_id
         WHERE products.product_id = ?"
    );

    $selected_stmt->bind_param("i", $selected_product_id);
    $selected_stmt->execute();

    $selected_result = $selected_stmt->get_result();

    if($selected_result->num_rows === 1)
    {
        $selected_product = $selected_result->fetch_assoc();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Request Stock</title>

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
                Stock Restoration
            </p>

            <h1 class="dashboard-title">
                Request Stock
            </h1>

            <p class="dashboard-subtitle">
                Select a product and enter the quantity required
                for your shop.
            </p>

        </div>

        <a href="products.php" class="back-products-btn">

            <i class="fa-solid fa-arrow-left"></i>
            Back to Products

        </a>

    </section>

    <?php if($message !== "") { ?>

        <div class="form-message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php } ?>

    <section class="stock-request-layout">

        <?php if($selected_product !== null) { ?>

            <div class="selected-product-card">

                <div class="selected-product-icon">
                    <i class="fa-solid fa-box-open"></i>
                </div>

                <p class="dashboard-label">
                    Selected Product
                </p>

                <h2>
                    <?php
                    echo htmlspecialchars(
                        $selected_product['product_name']
                    );
                    ?>
                </h2>

                <span class="category-badge">

                    <?php
                    echo htmlspecialchars(
                        $selected_product['category_name']
                        ?? 'Uncategorized'
                    );
                    ?>

                </span>

                <p class="selected-product-description">

                    <?php
                    echo htmlspecialchars(
                        $selected_product['description']
                    );
                    ?>

                </p>

                <div class="selected-product-details">

                    <div>

                        <span>Price</span>

                        <strong>
                            Rs.
                            <?php
                            echo number_format(
                                (float) $selected_product['price'],
                                2
                            );
                            ?>
                        </strong>

                    </div>

                    <div>

                        <span>Available Stock</span>

                        <strong>
                            <?php
                            echo (int)
                                $selected_product['stock_quantity'];
                            ?>
                        </strong>

                    </div>

                </div>

            </div>

        <?php } else { ?>

            <div class="selected-product-card empty-product-card">

                <div class="selected-product-icon">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>

                <h2>Select a Product</h2>

                <p>
                    Choose an available product from the request form.
                </p>

            </div>

        <?php } ?>

        <div class="stock-request-form-card">

            <div class="request-form-heading">

                <div>

                    <p class="dashboard-label">
                        New Request
                    </p>

                    <h2>Send Stock Request</h2>

                </div>

                <div class="request-heading-icon">
                    <i class="fa-solid fa-file-circle-plus"></i>
                </div>

            </div>

            <form method="POST" class="stock-request-form">

                <div class="form-group">

                    <label for="product_id">
                        Product
                    </label>

                    <select
                        name="product_id"
                        id="product_id"
                        required>

                        <option value="">
                            Select Product
                        </option>

                        <?php while($product = $products->fetch_assoc()) { ?>

                            <option
                                value="<?php echo $product['product_id']; ?>"
                                <?php
                                $current_product_id =
                                    isset($_POST['product_id'])
                                    ? (int) $_POST['product_id']
                                    : $selected_product_id;

                                if(
                                    $current_product_id ===
                                    (int) $product['product_id']
                                )
                                {
                                    echo "selected";
                                }
                                ?>>

                                <?php
                                echo htmlspecialchars(
                                    $product['product_name']
                                );
                                ?>

                                — Stock:
                                <?php
                                echo (int)
                                    $product['stock_quantity'];
                                ?>

                            </option>

                        <?php } ?>

                    </select>

                </div>

                <div class="form-group">

                    <label for="quantity">
                        Required Quantity
                    </label>

                    <div class="request-input-wrapper">

                        <i class="fa-solid fa-cubes"></i>

                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            placeholder="Enter required quantity"
                            min="1"
                            value="<?php
                            echo isset($_POST['quantity'])
                                ? (int) $_POST['quantity']
                                : '';
                            ?>"
                            required>

                    </div>

                </div>

                <div class="request-information">

                    <i class="fa-solid fa-circle-info"></i>

                    <p>
                        Your request will be submitted with
                        <strong>Pending</strong> status.
                        Payment becomes available only after administrator
                        approval.
                    </p>

                </div>

                <button
                    type="submit"
                    name="request_stock"
                    class="submit-request-btn">

                    <i class="fa-solid fa-paper-plane"></i>
                    Submit Stock Request

                </button>

            </form>

        </div>

    </section>

</main>

<script src="../assets/js/script.js?v=2"></script>

</body>
</html>