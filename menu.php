<nav class="bg-dark-gray border-b border-gray-700">
  <div id="top-navigation" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">
      <a href="/" class="text-sunset-yellow text-xl font-bold">
        <img src="/images/white-logo.png" width="" height="38" class="logo" />
      </a>
      <!-- Search box -->
      <div id="search" class="relative ml-12">
          <input
              type="text"
              id="search-bar"
              class="w-full lg:w-72 md:w-40 px-4 py-2 rounded bg-gray-700 text-white placeholder-gray-400 focus:outline-none"
              placeholder="Search..."
          />
          <div
              id="search-dropdown"
              class="absolute w-96 bg-gray-800 text-white rounded shadow-lg z-20 top-11"
          >
              <!-- Dropdown results will be appended here -->
          </div>
      </div>

      <!-- Desktop Menu -->
      <div id="top-menu" class="menu ml-auto hidden md:flex items-center space-x-4">
        <?php
          $currentPage = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // Get the current page path
        ?>
        <a href="/" class="text-text-light hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium <?php echo $currentPage === '/' ? 'active' : ''; ?>">Home</a>
        <a href="/about" class="text-text-light hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium <?php echo $currentPage === '/about' ? 'active' : ''; ?>">About</a>
        <a href="/videos" class="text-text-light hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium <?php echo $currentPage === '/videos' ? 'active' : ''; ?>">Videos</a>
        <a href="/blogs" class="text-text-light hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium <?php echo $currentPage === '/blogs' ? 'active' : ''; ?>">Blogs</a>
        <a href="/contact" class="text-text-light hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium <?php echo $currentPage === '/contact' ? 'active' : ''; ?>">Contact</a>

        <!-- Conditionally Add "Create" Dropdown and Auth Buttons -->
        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
          <div class="dropdown relative group">
            <a href="#" class="text-text-light hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium inline-block">Create</a>
            <div class="menu-item-wrapper absolute hidden bg-dark-gray border border-gray-700 mt-2 shadow-lg z-40">
              <a href="/add-video" class="block px-4 py-2 text-text-light hover:bg-sunset-yellow <?php echo $currentPage === '/add-video' ? 'active' : ''; ?>">Add Video</a>
              <a href="/add-post" class="block px-4 py-2 text-text-light hover:bg-sunset-yellow <?php echo $currentPage === '/add-post' ? 'active' : ''; ?>">Add Blog Post</a>
              <a href="/add-playlist" class="block px-4 py-2 text-text-light hover:bg-sunset-yellow <?php echo $currentPage === '/add-playlist' ? 'active' : ''; ?>">Add Playlist</a>
              <a href="/yt-playlist" class="block px-4 py-2 text-text-light hover:bg-sunset-yellow rounded-b-md <?php echo $currentPage === '/yt-playlist' ? 'active' : ''; ?>">YT Playlist</a>
              <a href="/helper/extract-video-image" class="block px-4 py-2 text-text-light hover:bg-sunset-yellow <?php echo $currentPage === '/helper/extract-video-image' ? 'active' : ''; ?>">Extract Video Image</a>
              <a href="/manage-sitemap" class="block px-4 py-2 text-text-light hover:bg-sunset-yellow rounded-b-md <?php echo $currentPage === '/manage-sitemap' ? 'active' : ''; ?>">Sitemap</a>
            </div>
          </div>
          <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <a href="/manage-content" class="text-text-light hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium <?php echo $currentPage === '/manage-content' ? 'active' : ''; ?>">Content</a>
            <a href="/manage-category" class="text-text-light hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium <?php echo $currentPage === '/manage-category' ? 'active' : ''; ?>">Category</a>
          <?php endif; ?>
          <a href="/logout" class="text-red-500 hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium">Log Out</a>
        <?php else: ?>
          <a href="/login" class="text-text-light hover:text-sunset-yellow px-3 py-2 rounded-md text-sm font-medium <?php echo $currentPage === '/login' ? 'active' : ''; ?>">Log In</a>
        <?php endif; ?>
      </div>

      <!-- Mobile Menu Button -->
      <div class="md:hidden">
        <button id="mobile-menu-button" class="ml-auto text-text-light focus:outline-none focus:ring-2 focus:ring-sunset-yellow">
          <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
          </svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile Menu -->
  <div id="mobile-menu" class="hidden md:hidden space-y-1 px-3 py-2">
    <a href="/" class="block text-text-light hover:bg-sunset-yellow px-3 py-2 rounded-md <?php echo $currentPage === '/' ? 'active' : ''; ?>">Home</a>
    <a href="/about" class="block text-text-light hover:bg-sunset-yellow px-3 py-2 rounded-md <?php echo $currentPage === '/about' ? 'active' : ''; ?>">About</a>
    <a href="/videos" class="block text-text-light hover:bg-sunset-yellow px-3 py-2 rounded-md <?php echo $currentPage === '/videos' ? 'active' : ''; ?>">Videos</a>
    <a href="/blogs" class="block text-text-light hover:bg-sunset-yellow px-3 py-2 rounded-md <?php echo $currentPage === '/blogs' ? 'active' : ''; ?>">Blogs</a>
    <a href="/contact" class="block text-text-light hover:bg-sunset-yellow px-3 py-2 rounded-md <?php echo $currentPage === '/contact' ? 'active' : ''; ?>">Contact</a>

    <!-- Conditionally Add "Create" Dropdown -->
    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
      <div class="block">
        <a href="/add-video" class="block px-3 py-2 text-text-light hover:bg-sunset-yellow rounded-md <?php echo $currentPage === '/add-video' ? 'active' : ''; ?>">Add Video</a>
        <a href="/add-post" class="block px-3 py-2 text-text-light hover:bg-sunset-yellow rounded-md <?php echo $currentPage === '/add-post' ? 'active' : ''; ?>">Add Blog Post</a>
        <a href="/add-playlist" class="block px-3 py-2 text-text-light hover:bg-sunset-yellow rounded-md <?php echo $currentPage === '/add-playlist' ? 'active' : ''; ?>">Add Playlist</a>
        <a href="/helper/extract-video-image" class="block px-3 py-2 text-text-light hover:bg-sunset-yellow rounded-md <?php echo $currentPage === '/helper/extract-video-image' ? 'active' : ''; ?>">Extract Video Image</a>
        <a href="/manage-sitemap" class="block px-3 py-2 text-text-light hover:bg-sunset-yellow rounded-md <?php echo $currentPage === '/manage-sitemap' ? 'active' : ''; ?>">Sitemap</a>
      </div>
      <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
        <a href="/content" class="block text-text-light hover:bg-sunset-yellow px-3 py-2 rounded-md">Content</a>
      <?php endif; ?>
      <a href="/logout" class="block text-red-500 hover:bg-sunset-yellow px-3 py-2 rounded-md">Log Out</a>
    <?php else: ?>
      <a href="/login" class="block text-text-light hover:bg-sunset-yellow px-3 py-2 rounded-md <?php echo $currentPage === '/login' ? 'active' : ''; ?>">Log In</a>
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
