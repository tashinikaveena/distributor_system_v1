<?php

session_start();

if(!isset($_SESSION['user_id']))
{
    header("Location: ../login.php");
    exit();
}

include '../config/database.php';

$message = "";

/* Add Product */

if(isset($_POST['add_product']))
{
    $category_id = $_POST['category_id'];
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];

    $sql = "INSERT INTO products
    (category_id, product_name, description, price, stock_quantity)
    VALUES
    ('$category_id','$product_name','$description','$price','$stock_quantity')";

    if($conn->query($sql))
    {
        $message = "Product Added Successfully";
    }
}

/* Delete Product */

if(isset($_GET['delete']))
{
    $id = $_GET['delete'];

    $conn->query(
    "DELETE FROM products WHERE product_id=$id");
}
?>

<!DOCTYPE html>
<html>

<head>

<title>Product Management</title>

<link rel="stylesheet"
href="../assets/css/style.css">

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
Manage Products
</h1>

<div class="dashboard">

<form method="GET">

<input
type="text"
name="search"
placeholder="Search Product">

<select name="category">

<option value="">
All Categories
</option>

<?php

$category_result =
$conn->query(
"SELECT * FROM categories");

while($cat =
$category_result->fetch_assoc())
{
?>

<option
value="<?php echo $cat['category_id']; ?>">

<?php echo $cat['category_name']; ?>

</option>

<?php
}
?>

</select>

<button type="submit">

Search

</button>

</form>

<br>

<?php
if($message != "")
{
    echo "<div class='alert'>$message</div>";
}
?>

<div class="form-card">

<h2>Add New Product</h2>

<form method="POST">

<select name="category_id" required>

<option value="">
Select Category
</option>

<?php

$categories =
$conn->query(
"SELECT * FROM categories");

while($category =
$categories->fetch_assoc())
{
?>

<option
value="<?php echo $category['category_id']; ?>">

<?php echo $category['category_name']; ?>

</option>

<?php
}
?>

</select>

<input
type="text"
name="product_name"
placeholder="Product Name"
required>

<textarea
name="description"
placeholder="Product Description"
required></textarea>

<input
type="number"
name="price"
placeholder="Price"
required>

<input
type="number"
name="stock_quantity"
placeholder="Stock Quantity"
required>

<button
type="submit"
name="add_product">

Add Product

</button>

</form>

</div>

<br>

<table border="1" width="100%">

<tr>

<th>ID</th>
<th>Category</th>
<th>Product Name</th>
<th>Description</th>
<th>Price</th>
<th>Stock</th>
<th>Alert</th>
<th>Action</th>

</tr>

<?php

$search =
isset($_GET['search'])
? $_GET['search']
: '';

$category =
isset($_GET['category'])
? $_GET['category']
: '';

$sql =

"SELECT products.*,
categories.category_name

FROM products

LEFT JOIN categories

ON products.category_id =
categories.category_id

WHERE 1";

if($search != '')
{
    $sql .=
    " AND product_name
    LIKE '%$search%'";
}

if($category != '')
{
    $sql .=
    " AND products.category_id =
    '$category'";
}

$result = $conn->query($sql);

while($row = $result->fetch_assoc())
{
?>

<tr>

<td>
<?php echo $row['product_id']; ?>
</td>

<td>
<?php echo $row['category_name']; ?>
</td>

<td>
<?php echo $row['product_name']; ?>
</td>

<td>
<?php echo $row['description']; ?>
</td>

<td>
<?php echo $row['price']; ?>
</td>

<td>
<?php echo $row['stock_quantity']; ?>
</td>

<td>

<?php

if($row['stock_quantity'] < 10)
{
    echo "<span class='low-stock'>
    Low Stock
    </span>";
}
else
{
    echo "Normal";
}

?>

</td>

<td>

<a href="edit_product.php?id=<?php echo $row['product_id']; ?>">
✏️ Edit
</a>

&nbsp; | &nbsp;

<a
href="?delete=<?php echo $row['product_id']; ?>"
onclick="return confirmDelete();">

🗑️ Delete

</a>

</td>

</tr>

<?php
}
?>

</table>

</div> <!-- dashboard -->

</div> <!-- container -->

<script src="../assets/js/script.js"></script>

</body>
</html>