<nav class="bg-dark-gray border-b border-gray-700">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">
      <a href="/" class="text-sunset-yellow text-xl font-bold">
        <img src="/images/white-logo.png" width="" height="38" class="logo" />
      </a>
      <!-- Desktop Menu -->
      <div class="hidden md:flex space-x-4">
        <!-- <a href="index.php" class="text-text-light hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium">Home</a>
        <a href="about.php" class="text-text-light hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium">About</a>
        <a href="videos.php" class="text-text-light hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium">Videos</a>
        <a href="contact.php" class="text-text-light hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium">Contact</a> -->

        <a href="/" class="text-text-light hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium">Home</a>
        <a href="/about" class="text-text-light hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium">About</a>
        <a href="/videos" class="text-text-light hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium">Videos</a>
        <a href="/contact" class="text-text-light hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium">Contact</a>
        
        <!-- Conditionally Add "Add Video" and Auth Buttons -->
        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
          <a href="/add-video" class="text-text-light hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium">Add Video</a>
          <a href="/logout" class="text-red-500 hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium">Log Out</a>
        <?php else: ?>
          <a href="/login" class="text-text-light hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium">Log In</a>
        <?php endif; ?>
      </div>

      <!-- Mobile Menu Button -->
      <div class="md:hidden">
        <button id="mobile-menu-button" class="text-text-light focus:outline-none focus:ring-2 focus:ring-sunset-yellow">
          <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
          </svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile Menu -->
  <div id="mobile-menu" class="hidden md:hidden space-y-1 px-3 py-2">

    <!-- <a href="index.php" class="block text-text-light hover:bg-sunset-yellow px-3 py-2 rounded-md">Home</a>
    <a href="about.php" class="block text-text-light hover:bg-sunset-yellow px-3 py-2 rounded-md">About</a>
    <a href="videos.php" class="block text-text-light hover:bg-sunset-yellow px-3 py-2 rounded-md">Videos</a>
    <a href="contact.php" class="block text-text-light hover:bg-sunset-yellow px-3 py-2 rounded-md">Contact</a> -->

    <a href="/" class="block text-text-light hover:bg-sunset-yellow px-3 py-2 rounded-md">Home</a>
    <a href="/about" class="block text-text-light hover:bg-sunset-yellow px-3 py-2 rounded-md">About</a>
    <a href="/videos" class="block text-text-light hover:bg-sunset-yellow px-3 py-2 rounded-md">Videos</a>
    <a href="/contact" class="block text-text-light hover:bg-sunset-yellow px-3 py-2 rounded-md">Contact</a>
    
    <!-- Conditionally Add "Add Video" and Auth Buttons -->
    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
      <a href="/add-video" class="block text-text-light hover:bg-sunset-yellow px-3 py-2 rounded-md">Add Video</a>
      <a href="/logout" class="block text-red-500 hover:bg-sunset-yellow px-3 py-2 rounded-md">Log Out</a>
    <?php else: ?>
      <a href="/login" class="block text-text-light hover:bg-sunset-yellow px-3 py-2 rounded-md">Log In</a>
    <?php endif; ?>
  </div>
</nav>

<script>
  const menuButton = document.getElementById('mobile-menu-button');
  const mobileMenu = document.getElementById('mobile-menu');
  menuButton.addEventListener('click', () => {
    mobileMenu.classList.toggle('hidden');
  });
</script>