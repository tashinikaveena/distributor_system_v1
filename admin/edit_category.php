<?php

session_start();

if(!isset($_SESSION['user_id']))
{
    header("Location: ../login.php");
    exit();
}

include '../config/database.php';

$id = $_GET['id'];

$result = $conn->query(
    "SELECT * FROM categories WHERE category_id=$id"
);

$category = $result->fetch_assoc();

if(isset($_POST['update_category']))
{
    $category_name = $_POST['category_name'];

    $conn->query(
        "UPDATE categories
         SET category_name='$category_name'
         WHERE category_id=$id"
    );

    header("Location: categories.php");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>

<title>Edit Category</title>

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
Edit Category
</h1>

<div class="form-card">

<h2>Update Category</h2>

<form method="POST">

<label>Category Name</label>

<input
type="text"
name="category_name"
value="<?php echo $category['category_name']; ?>"
required>

<button
type="submit"
name="update_category"
class="btn">
Update Category
</button>

<a href="categories.php" class="btn"
style="background:#6c757d; margin-left:10px;">
    Cancel
</a>

</form>

</div>

</div>

</body>
</html>