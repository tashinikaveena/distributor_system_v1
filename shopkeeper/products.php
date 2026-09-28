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

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$category_id = isset($_GET['category'])
    ? (int) $_GET['category']
    : 0;

/* Categories for filter */

$categories = $conn->query(
    "SELECT category_id, category_name
     FROM categories
     ORDER BY category_name ASC"
);

/* Product query */

$sql = "
    SELECT
        products.product_id,
        products.product_name,
        products.description,
        products.price,
        products.stock_quantity,
        categories.category_name
    FROM products
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
            products.product_name LIKE ?
            OR products.description LIKE ?
        )
    ";

    $search_term = "%" . $search . "%";

    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

if($category_id > 0)
{
    $sql .= " AND products.category_id = ?";

    $params[] = $category_id;
    $types .= "i";
}

$sql .= " ORDER BY products.product_name ASC";

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

    <title>Available Products</title>

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
                Product Catalogue
            </p>

            <h1 class="dashboard-title">
                Available Products
            </h1>

            <p class="dashboard-subtitle">
                Search available products and submit a stock request
                for the quantity you need.
            </p>

        </div>

    </section>

    <section class="product-filter-card">

        <form method="GET" class="product-filter-form">

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
                        value="<?php echo htmlspecialchars($search); ?>">

                </div>

            </div>

            <div class="filter-field">

                <label for="category">
                    Category
                </label>

                <select id="category" name="category">

                    <option value="0">
                        All Categories
                    </option>

                    <?php while($category = $categories->fetch_assoc()) { ?>

                        <option
                            value="<?php echo $category['category_id']; ?>"
                            <?php
                            if(
                                $category_id ==
                                $category['category_id']
                            )
                            {
                                echo "selected";
                            }
                            ?>>

                            <?php
                            echo htmlspecialchars(
                                $category['category_name']
                            );
                            ?>

                        </option>

                    <?php } ?>

                </select>

            </div>

            <button type="submit" class="filter-btn">

                <i class="fa-solid fa-filter"></i>
                Search

            </button>

            <a href="products.php" class="reset-filter-btn">

                <i class="fa-solid fa-rotate-left"></i>
                Reset

            </a>

        </form>

    </section>

    <section class="products-table-card">

        <div class="table-card-heading">

            <div>

                <h2>Product List</h2>

                <p>
                    <?php echo $result->num_rows; ?>
                    product(s) found
                </p>

            </div>

        </div>

        <?php if($result->num_rows > 0) { ?>

            <div class="table-responsive">

                <table class="products-table">

                    <thead>

                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Available Stock</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php while($row = $result->fetch_assoc()) { ?>

                            <tr>

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

                                <td class="product-description-cell">

                                    <?php
                                    echo htmlspecialchars(
                                        $row['description']
                                    );
                                    ?>

                                </td>

                                <td>

                                    <strong class="product-price">

                                        Rs.
                                        <?php
                                        echo number_format(
                                            (float) $row['price'],
                                            2
                                        );
                                        ?>

                                    </strong>

                                </td>

                                <td>

                                    <?php
                                    $stock_quantity =
                                        (int) $row['stock_quantity'];
                                    ?>

                                    <?php if($stock_quantity <= 0) { ?>

                                        <span class="stock-badge out-stock">
                                            Out of Stock
                                        </span>

                                    <?php } elseif($stock_quantity < 10) { ?>

                                        <span class="stock-badge low-stock-badge">

                                            <?php
                                            echo $stock_quantity;
                                            ?>

                                            Low Stock

                                        </span>

                                    <?php } else { ?>

                                        <span class="stock-badge in-stock">

                                            <?php
                                            echo $stock_quantity;
                                            ?>

                                            Available

                                        </span>

                                    <?php } ?>

                                </td>

                                <td>

                                    <?php if($stock_quantity > 0) { ?>

                                        <a
                                            href="request_stock.php?id=<?php
                                            echo $row['product_id'];
                                            ?>"
                                            class="request-stock-btn">

                                            <i class="fa-solid fa-cart-plus"></i>
                                            Request Stock

                                        </a>

                                    <?php } else { ?>

                                        <button
                                            type="button"
                                            class="request-stock-btn disabled"
                                            disabled>

                                            Unavailable

                                        </button>

                                    <?php } ?>

                                </td>

                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

        <?php } else { ?>

            <div class="empty-state">

                <i class="fa-solid fa-box-open"></i>

                <h3>No Products Found</h3>

                <p>
                    Try changing the search keyword or category filter.
                </p>

            </div>

        <?php } ?>

    </section>

</main>

<script src="../assets/js/script.js?v=2"></script>

</body>
</html>