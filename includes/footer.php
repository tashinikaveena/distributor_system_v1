<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Inventory Management System</title>

    <!-- Bootstrap CSS -->

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->

    <?php
$cssPath = "assets/css/style.css";

if (strpos($_SERVER['PHP_SELF'], '/shopkeeper/') !== false ||
    strpos($_SERVER['PHP_SELF'], '/reports/') !== false) {

    $cssPath = "../assets/css/style.css";
}
?>

<link rel="stylesheet" href="<?php echo $cssPath; ?>">

</head>

<body>