<?php
session_start();

$pageTitle = "About Us - LightUp.TV";
$pageDescription = "Learn about LightUp.TV and our mission to create relaxing ambience videos and soundscapes.";
$pageKeywords = "about LightUp.TV, mission, ambience videos, relaxation, meditation sounds";
$canonicalURL = "https://www.lightup.tv/about.php";
include 'header.php';
include 'menu.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
  <h1 class="text-3xl font-bold text-sunset-yellow">About Us</h1>
  <p class="text-gray-300 mt-4">LightUp.TV is dedicated to providing relaxing ambience videos...</p>
</div>

<?php include 'footer.php'; ?>
