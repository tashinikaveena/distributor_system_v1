<?php

session_start();

if(!isset($_SESSION['user_id']))
{
    header("Location: ../login.php");
    exit();
}

include '../config/database.php';

$id = $_GET['id'];

$product_result = $conn->query(
    "SELECT * FROM products WHERE product_id=$id"
);

$product = $product_result->fetch_assoc();

if(isset($_POST['update_product']))
{
    $category_id = $_POST['category_id'];
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];

    $conn->query(
        "UPDATE products SET
        category_id='$category_id',
        product_name='$product_name',
        description='$description',
        price='$price',
        stock_quantity='$stock_quantity'
        WHERE product_id=$id"
    );

    header("Location: products.php");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>

<title>Edit Product</title>

<link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<?php include('../includes/admin_sidebar.php'); ?>

<header>

    <div class="logo">
        Inventory Management System
    </div>

    <div class="header-right">
        👤 Welcome,
        <?php echo $_SESSION['name']; ?>
        | Admin
    </div>

</header>

<div class="container">

<h1 class="dashboard-title">
Edit Product
</h1>

<div class="form-card">

<h2>Update Product</h2>

<form method="POST">

<label>Category</label>

<select name="category_id" required>

<?php

$categories = $conn->query("SELECT * FROM categories");

while($category = $categories->fetch_assoc())
{
?>

<option value="<?php echo $category['category_id']; ?>"
<?php
if($category['category_id'] == $product['category_id'])
{
    echo "selected";
}
?>>

<?php echo $category['category_name']; ?>

</option>

<?php
}
?>

</select>

<label>Product Name</label>

<input
type="text"
name="product_name"
value="<?php echo $product['product_name']; ?>"
required>

<label>Description</label>

<textarea
name="description"
required><?php echo $product['description']; ?></textarea>

<label>Price</label>

<input
type="number"
name="price"
value="<?php echo $product['price']; ?>"
required>

<label>Stock Quantity</label>

<input
type="number"
name="stock_quantity"
value="<?php echo $product['stock_quantity']; ?>"
required>

<button
type="submit"
name="update_product"
class="btn">
Update Product
</button>

<a href="products.php"
class="btn"
style="background:#6c757d; margin-left:10px;">
Cancel
</a>

</form>

</div>

</div>

</body>
</html>