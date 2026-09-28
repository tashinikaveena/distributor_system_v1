<?php

session_start();

if(!isset($_SESSION['user_id']))
{
    header("Location: login.php");
    exit();
}

include 'config/database.php';

$user_id = $_SESSION['user_id'];

$user =
$conn->query(
"SELECT * FROM users
WHERE id=$user_id")
->fetch_assoc();

$message = "";

if(isset($_POST['update_profile']))
{
    $name = $_POST['name'];
    $email = $_POST['email'];

    $conn->query(
    "UPDATE users
    SET name='$name',
    email='$email'
    WHERE id=$user_id");

    $_SESSION['name'] = $name;

    $message =
    "Profile Updated Successfully";

    $user =
    $conn->query(
    "SELECT * FROM users
    WHERE id=$user_id")
    ->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>

<head>

<title>My Profile</title>

<link rel="stylesheet"
href="assets/css/style.css">

</head>

<body>

<div class="form-container">

<h2>My Profile</h2>

<?php
if($message != "")
{
    echo "<div class='alert'>$message</div>";
}
?>

<form method="POST">

<input
type="text"
name="name"
value="<?php echo $user['name']; ?>"
required>

<input
type="email"
name="email"
value="<?php echo $user['email']; ?>"
required>

<button
type="submit"
name="update_profile">

Update Profile

</button>

</form>

</div>

</body>
</html>