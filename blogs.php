<?php
session_start();

require_once "functions.php";

$pageTitle = "Blogs: Insights, Stories, and Tips to Inspire and Inform";
$pageDescription = "Explore our blogs for inspiring stories, expert tips, and insightful articles on a variety of topics. Stay informed, entertained, and engaged.";
$pageKeywords = "blogs, LightUp.TV, ambience blog, relaxation blog";
$ogImageURL = "https://lightup.tv/images/Blogs-LightUpTV.jpg";
$canonicalURL = "https://lightup.tv/blogs";
include 'header.php';
include 'menu.php';
include 'sub-heading.php';

// Path to content.json
$contentFile = __DIR__ . '/json/post-content.json';

// Check if content.json exists
if (!file_exists($contentFile)) {
  echo "<p class='p-8'>No posts found.</p>";
} else {
  // Read content.json
  $content = json_decode(file_get_contents($contentFile), true);
  ?>
  <div class="<?php echo $mainContainerClass ?>">
    <h1 class="text-3xl font-bold text-sunset-yellow mb-6">Blogs</h1>
    <p class="text-gray-300 mt-4 leading-relaxed">
      Greetings and welcome to the Blogs page of <strong>LightUp.TV</strong>! We’re thrilled to have you join our vibrant
      and ever-growing community of content enthusiasts, creators, and fans. Here, you'll find a treasure trove of
      stories, insights, and updates designed to ignite your creativity, spark inspiration, and deepen your connection
      with the world of digital entertainment.
    </p>
    <div class="image relative w-full dark-overlay dark-overlay-0.5 mt-4">
      <img class="w-full" src="<?php echo $rootFolderPath; ?>images/Blogs-LightUpTV-2.jpg" width="100%" height="100%" alt="Greetings and welcome to the LightUp.TV Blogs" title="Greetings and welcome to the LightUp.TV Blogs"/>
      <div class="image-label w-full flex items-center justify-center absolute bottom-0">Greetings and welcome to the LightUp.TV Blogs</div>
    </div>
    <h2 class="text-2xl font-bold text-sunset-yellow mt-6">What You’ll Discover</h2>
    <p class="text-gray-300 mt-4 leading-relaxed">
      Our Blogs page is a curated space for all things LightUp.TV. Whether you’re looking to explore behind-the-scenes
      content, discover tips and tricks for enhancing your creative projects, or stay updated on the latest trends in
      digital media, we’ve got you covered.
    </p>
    <ul class="list-disc list-inside text-gray-300 mt-4 leading-relaxed">
      <li><strong>Exclusive Insights:</strong> Get an inside look at how we bring exciting content to life and share tips
        to elevate your own creative endeavors.</li>
      <li><strong>Creator Spotlights:</strong> Learn about the talented individuals who shape the world of LightUp.TV.
      </li>
      <li><strong>Trending Topics:</strong> Stay in the loop with what’s buzzing in the entertainment and digital space.
      </li>
      <li><strong>Community Highlights:</strong> Celebrate the creativity and passion of our viewers and contributors.
      </li>
    </ul>

    <h2 class="text-2xl font-bold text-sunset-yellow mt-6">Why LightUp.TV Blogs?</h2>
    <p class="text-gray-300 mt-4 leading-relaxed">
      At LightUp.TV, we believe that storytelling and shared experiences are at the heart of meaningful connections. Our
      blogs are more than just words on a screen; they’re a celebration of creativity, collaboration, and culture. We aim
      to:
    </p>
    <ol class="list-decimal list-inside text-gray-300 mt-4 leading-relaxed">
      <li>Inspire your creative journey with fresh ideas and actionable advice.</li>
      <li>Educate and inform with content that enriches your understanding of the digital landscape.</li>
      <li>Engage and entertain by sharing compelling stories and diverse perspectives.</li>
    </ol>

    <h2 class="text-2xl font-bold text-sunset-yellow mt-6">Join the Conversation</h2>
    <p class="text-gray-300 mt-4 leading-relaxed">
      Our Blogs page isn’t just about us—it’s about you. We encourage you to join the conversation by sharing your
      thoughts, leaving comments, and suggesting topics you’d love for us to explore. Your voice helps shape this
      community and ensures that our content stays fresh and relevant.
    </p>

    <h2 class="text-2xl font-bold text-sunset-yellow mt-6">Stay Connected</h2>
    <p class="text-gray-300 mt-4 leading-relaxed">
      Don’t miss a beat! Bookmark this page and subscribe to our newsletter to stay updated with the latest posts,
      announcements, and events. Follow us on social media to be part of the LightUp.TV family and get sneak peeks into
      what’s coming next.
    </p>

    <p class="text-gray-300 mt-4 leading-relaxed font-bold">
      Thank you for being part of this journey. Together, let’s LightUp the world with creativity and passion!
    </p>
    <p class="text-gray-300 mt-4 leading-relaxed font-bold">
      To view our videos, visit: <a href="https://lightup.tv/videos">https://lightup.tv/videos</a>
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($content as $post): ?>
        <?php
        // Get post details from its JSON file
        $postFile = __DIR__ . '/post-data/' . $post['unique_id'] . '/' . $post['unique_id'] . '.json';
        if (!file_exists($postFile)) {
          continue;
        }
        $postDetails = json_decode(file_get_contents($postFile), true);
        $description = trimDescription($postDetails['description'], 150);

        // Determine the post image
        if (!empty($postDetails['thumbnail'])) {
          $postImage = "post-data/" . $post['unique_id'] . "/" . $postDetails['thumbnail'];
        } else if (!empty($postDetails['post_thumbnail_url'])) {
          $postImage = $postDetails['post_thumbnail_url'];
        } else {
          $postImage = "images/default-image.jpeg";
        }

        // Determine the post link
        $postLink = !empty($postDetails['alias'])
          ? "/post/" . htmlspecialchars($postDetails['alias'])
          : "/post?id=" . htmlspecialchars($post['unique_id']);
        ?>
        <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
          <a href="<?= $postLink ?>">
            <img src="<?= $postImage ?>" alt="<?= htmlspecialchars($postDetails['title']) ?>"
              class="w-full h-40 object-cover rounded">
          </a>
          <h2 class="font-bold text-sunset-yellow mt-4">
            <a href="<?= $postLink ?>"><?= htmlspecialchars($postDetails['title']) ?></a>
          </h2>
          <p class="text-sm text-gray-400 mt-2"><?= htmlspecialchars($description) ?></p>

          <div class="flex justify-between mt-4">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
              <a href="edit-post?id=<?= $post['unique_id'] ?>"
                class="btn edit-btn px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Edit</a>
              <button onclick="showDeleteModal('<?= $post['unique_id'] ?>')"
                class="btn delete-btn px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="fixed inset-0 hidden z-50 bg-black bg-opacity-50 flex justify-center items-center">
    <div class="bg-gray-800 text-text-light rounded-lg p-6 max-w-md w-full">
      <h2 class="font-bold mb-4">Confirm Delete</h2>
      <p>Are you sure you want to delete this post?</p>
      <div class="flex justify-end mt-4">
        <button id="cancelDelete" class="mr-2 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Cancel</button>
        <button id="confirmDelete" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
      </div>
    </div>
  </div>

<?php } ?>

<?php include 'footer.php'; ?>