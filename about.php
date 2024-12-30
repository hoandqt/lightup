<?php
session_start();

$pageTitle = "About Us: Learn More About Our Mission and Vision";
$pageDescription = "Learn about LightUp.TV and our mission to create relaxing ambience videos and soundscapes.";
$pageKeywords = "about LightUp.TV, mission, ambience videos, relaxation, meditation sounds";
$canonicalURL = "https://lightup.tv/about";
$ogImageURL = "https://lightup.tv/images/About-Us-LightUpTV.jpg";
include 'header.php';
include 'menu.php';
?>

<div class="<?php echo $mainContainerClass ?>">
  <h1 class="text-3xl font-bold text-sunset-yellow">About Us</h1>
  <p class="text-gray-300 mt-4">Welcome to <strong>LightUp.TV</strong>. Our mission is simple yet profound: to create serene, relaxing ambience videos and soundscapes that bring comfort and peace to your everyday life. Whether you're looking for a moment of tranquility, a backdrop for meditation, or soothing sounds to help you focus or drift off to sleep, LightUp.TV is here for you.</p>

  <h2 class="text-2xl font-bold text-sunset-yellow mt-6">Who We Are</h2>
  <p class="text-gray-300 mt-4">We are passionate creators inspired by the beauty of nature, the allure of gentle soundscapes, and the calming power of ambience. Each video we craft is designed with care to enhance your relaxation, foster meditation, and create the perfect environment for work or leisure.</p>
  <p class="text-gray-300 mt-4">Our journey began with the vision of providing a digital sanctuary where anyone could escape the noise of the world. Over time, we've grown into a trusted source for soothing content, loved by viewers worldwide.</p>

  <h2 class="text-2xl font-bold text-sunset-yellow mt-6">What We Offer</h2>
  <ul class="list-disc list-inside text-gray-300 mt-4">
    <li><strong>Relaxing Ambience Videos:</strong> Immerse yourself in breathtaking visuals paired with serene soundscapes.</li>
    <li><strong>Meditation Soundtracks:</strong> Let our carefully curated audio enhance your mindfulness practices.</li>
    <li><strong>Sleep-Enhancing Sounds:</strong> Drift into peaceful slumber with our calming soundtracks.</li>
    <li><strong>Focus Backgrounds:</strong> Improve your productivity with distraction-free, ambient videos.</li>
  </ul>

  <h2 class="text-2xl font-bold text-sunset-yellow mt-6">Why Choose LightUp.TV?</h2>
  <p class="text-gray-300 mt-4">Our content is not just about visuals and sounds; it's about creating a space where you feel at ease, centered, and inspired. With every piece of content, we aim to provide you with:</p>
  <ul class="list-disc list-inside text-gray-300 mt-4">
    <li><strong>High-Quality Production:</strong> Stunning visuals and crystal-clear audio.</li>
    <li><strong>Authentic Experiences:</strong> Capturing the true essence of peace and tranquility.</li>
    <li><strong>Community Connection:</strong> We love hearing from you and growing together as a community.</li>
  </ul>

  <h2 class="text-2xl font-bold text-sunset-yellow mt-6">Join Our Journey</h2>
  <p class="text-gray-300 mt-4">We invite you to explore our growing library of ambience videos and soundscapes, each crafted to soothe and uplift. Stay connected with us through our website, social media, and YouTube channel. Together, let’s light up your world with peace and serenity.</p>
  <p class="text-gray-300 mt-4"><strong>Discover the art of relaxation. Discover LightUp.TV.</strong></p>
  <p class="text-gray-300 mt-4">To view our videos, visit: <a href="https://lightup.tv/videos" class="text-sunset-yellow underline">https://lightup.tv/videos</a></p>

  <h2 class="text-2xl font-bold text-sunset-yellow mt-6">Thank You</h2>
  <p class="text-gray-300 mt-4">Thank you for being part of the LightUp.TV community. Your support fuels our passion and helps us continue creating content that touches lives.</p>
</div>

<?php include 'footer.php'; ?>
