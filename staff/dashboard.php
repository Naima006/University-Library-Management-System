<?php

include("../config/auth.php");
include("../config/db.php");

if($_SESSION['role'] != 'staff')
{
    header("Location: ../auth/login.php");
    exit;
}

include("../layouts/header.php");
?>

<div class="flex">

<?php include("../layouts/sidebar.php"); ?>

<div class="ml-64 p-8">

<h1 class="text-3xl font-bold">
Staff Dashboard
</h1>

<p class="mt-4 text-gray-600">
Welcome <?= $_SESSION['full_name']; ?>
</p>

</div>

</div>

<?php include("../layouts/footer.php"); ?>