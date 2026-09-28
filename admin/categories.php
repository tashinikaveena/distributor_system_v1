<?php

session_start();

if(!isset($_SESSION['user_id']))
{
    header("Location: ../login.php");
    exit();
}

include '../config/database.php';

$message = "";

// Add Category

if(isset($_POST['add_category']))
{
    $category_name = $_POST['category_name'];

    $sql = "INSERT INTO categories(category_name)
            VALUES('$category_name')";

    if($conn->query($sql))
    {
        $message = "Category Added Successfully";
    }
}

// Delete Category

if(isset($_GET['delete']))
{
    $id = $_GET['delete'];

    $conn->query(
        "DELETE FROM categories
         WHERE category_id=$id"
    );
}
?>

<!DOCTYPE html>
<html>

<head>

<title>Categories</title>

<link rel="stylesheet"
href="../assets/css/style.css">

</head>

<body>

<?php include('../includes/admin_sidebar.php'); ?>

<header>

<div class="logo">
Category Management
</div>

<nav>
<a href="dashboard.php">Dashboard</a>
</nav>

</header>

<div class="container">

<h1 class="dashboard-title">
Manage Categories
</h1>

<div class="dashboard">

<?php
if($message != "")
{
    echo "<div class='alert'>$message</div>";
}
?>

<div class="form-card">

<h2>Add New Category</h2>

<form method="POST">

<label>Category Name</label>

<input
type="text"
name="category_name"
placeholder="Enter Category Name"
required>

<button
type="submit"
name="add_category"
class="btn">
+ Add Category
</button>

</form>

</div>

<br>

<table border="1"
width="100%"
cellpadding="10">

<tr>

<th>ID</th>
<th>Category Name</th>
<th>Action</th>

</tr>

<?php

$result =
$conn->query(
"SELECT * FROM categories");

while($row =
$result->fetch_assoc())
{
?>

<tr>

<td>
<?php echo $row['category_id']; ?>
</td>

<td>
<?php echo $row['category_name']; ?>
</td>

<td>

<a href="edit_category.php?id=<?php echo $row['category_id']; ?>">
✏️ Edit
</a>

&nbsp; | &nbsp;

<a href="?delete=<?php echo $row['category_id']; ?>"
onclick="return confirmDelete();">

🗑️ Delete

</a>

</td>

</tr>

<?php
}
?>

</table>

</div>

<script src="../assets/js/script.js"></script>

</div> <!-- container -->

</body>
</html>