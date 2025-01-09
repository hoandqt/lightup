<?php
session_start();

$pageTitle = "About Us: Learn More About Our Mission and Vision - LightUp.TV";
$pageDescription = "Discover curated videos across platforms and interests with LightUp.TV. Relax, learn, and explore with handpicked content tailored to you.";
$pageKeywords = "about LightUp.TV, mission, curated videos, ambience videos, relaxation, meditation, sounds, relax, learn, explore";
$canonicalURL = "https://lightup.tv/about";
$ogImageURL = "https://lightup.tv/images/About-Us-LightUpTV.jpg";
include 'header.php';
include 'menu.php';
?>

<div class="<?php echo $mainContainerClass ?>">
  <h1 class="text-3xl font-bold text-sunset-yellow">About Us</h1>
  <p class="text-gray-300 mt-4">Welcome to <strong>LightUp.TV</strong>. Our mission is to gather, curate, and present the best videos from various subjects and platforms, making it easier for users to find content that aligns with their interests. From relaxation and ambience videos to educational and entertaining clips, we aim to be your go-to source for discovering outstanding videos.</p>

  <h2 class="text-2xl font-bold text-sunset-yellow mt-6">Who We Are</h2>
  <p class="text-gray-300 mt-4">At LightUp.TV, we are a passionate team dedicated to collecting and showcasing high-quality videos from diverse networks. Our focus is on making video discovery seamless and enjoyable, catering to a wide range of interests and preferences.</p>
  <p class="text-gray-300 mt-4">We believe in the power of storytelling and the ability of videos to educate, inspire, and entertain. Our platform is designed to connect users with exceptional content that resonates with them.</p>

  <h2 class="text-2xl font-bold text-sunset-yellow mt-6">What We Offer</h2>
  <ul class="list-disc list-inside text-gray-300 mt-4">
    <li><strong>Curated Playlists:</strong> Discover handpicked collections of videos across various genres and topics.</li>
    <li><strong>Cross-Platform Discovery:</strong> Access the best content from YouTube, Facebook, and other video networks in one place.</li>
    <li><strong>Interest-Based Search:</strong> Easily find videos tailored to your preferences, whether it's for relaxation, learning, or fun.</li>
    <li><strong>Continuous Updates:</strong> Stay ahead with fresh content curated regularly to keep you engaged.</li>
  </ul>

  <h2 class="text-2xl font-bold text-sunset-yellow mt-6">Why Choose LightUp.TV?</h2>
  <p class="text-gray-300 mt-4">Our platform stands out by offering:</p>
  <ul class="list-disc list-inside text-gray-300 mt-4">
    <li><strong>Broad Video Categories:</strong> Explore videos spanning relaxation, education, entertainment, and more.</li>
    <li><strong>User-Focused Curation:</strong> We prioritize content that adds value to your viewing experience.</li>
    <li><strong>Effortless Navigation:</strong> Quickly find what you're looking for with our intuitive search and filters.</li>
    <li><strong>Community-Driven Growth:</strong> Share your favorite videos and help us grow a vibrant, collaborative community.</li>
  </ul>

  <h2 class="text-2xl font-bold text-sunset-yellow mt-6">Join Our Journey</h2>
  <p class="text-gray-300 mt-4">Dive into a world of exceptional videos with LightUp.TV. Whether you’re here to relax, learn, or explore, we’re committed to providing you with a rich video library tailored to your interests. Follow us across social platforms, contribute your suggestions, and be part of our ever-growing family.</p>
  <p class="text-gray-300 mt-4"><strong>Discover, explore, and enjoy with LightUp.TV.</strong></p>
  <p class="text-gray-300 mt-4">Start exploring today: <a href="https://lightup.tv/videos" class="text-sunset-yellow underline">https://lightup.tv/videos</a></p>

  <h2 class="text-2xl font-bold text-sunset-yellow mt-6">Thank You</h2>
  <p class="text-gray-300 mt-4">We appreciate your support and interest in LightUp.TV. Together, let’s continue to explore and enjoy the world of amazing videos.</p>
</div>


<?php include 'footer.php'; ?>
