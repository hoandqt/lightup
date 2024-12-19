<?php
session_start();

$pageTitle = "Welcome to LightUp.TV";
$pageDescription = "Explore the homepage of LightUp.TV, featuring relaxing videos, soothing sounds, and creative content for your mindfulness journey.";
$pageKeywords = "LightUp.TV, ambience videos, relaxing music, mindfulness, meditation, creative sounds";
$canonicalURL = "https://www.lightup.tv/";
include 'header.php';
include 'menu.php';
?>

<!-- Hero Section -->
<div class="relative bg-dark-gray overflow-hidden z-0">
  <div class="max-w-7xl mx-auto">
    <div class="relative z-10 bg-dark-gray pb-8 sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
      <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8">
        <div class="sm:text-center lg:text-left">
          <h1 class="text-4xl tracking-tight font-extrabold sm:text-5xl md:text-6xl">
            <span class="block xl:inline">Welcome to</span>
            <span class="block text-sunset-yellow xl:inline">LightUp.TV</span>
          </h1>
          <p class="mt-3 text-base text-gray-300 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
            Discover a world of captivating visuals and enchanting sounds, crafted to inspire relaxation, mindfulness,
            and creativity. Whether you're here to escape the daily grind, find peace in serene soundscapes, or fuel
            your imagination with stunning video content, we've got you covered.
          </p>
          <p class="mt-3 text-base text-gray-300 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
            Dive into our collection of mesmerizing ambience videos, soothing melodies, and immersive experiences.
            Perfect for meditation, focus, sleep, or simply unwinding, our creations are designed to help you reconnect
            with yourself and the world around you.
          </p>
          <p class="mt-3 text-base text-gray-300 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
            Let the journey begin—press play, and let the magic of sound and video transform your day.
          </p>
          <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
            <div class="rounded-md shadow">
              <a href="https://www.youtube.com/channel/UCq7ctxkWd26rYXN4yv7mNTQ/"
                class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-dark-gray bg-sunset-orange md:py-4 md:text-lg md:px-10">
                Get Started
              </a>
            </div>
            <div class="mt-3 sm:mt-0 sm:ml-3">
              <a href="#"
                class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-sunset-yellow bg-transparent border-sunset-yellow hover:bg-sunset-orange hover:text-dark-gray md:py-4 md:text-lg md:px-10">
                Learn More
              </a>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</div>

<!-- Background Image -->
<div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2 z-10 mt-40">
  <img class="h-56 w-full object-cover sm:h-72 md:h-96 lg:w-full lg:h-full" src="images/background.webp"
    alt="Welcome Image">
</div>

<?php include 'footer.php'; ?>