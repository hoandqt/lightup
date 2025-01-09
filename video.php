<?php
session_start();
require_once 'functions.php';

// Get alias or ID from the request
$alias = $_GET['alias'] ?? null;
$id = $_GET['id'] ?? null;

$videoFile = null;

// Handle alias-based routing
if ($alias) {
  $videoDir = __DIR__ . '/item-data/';

  foreach (glob($videoDir . '*/') as $dir) {
    $jsonFile = $dir . basename($dir) . '.json';
    if (file_exists($jsonFile)) {
      $videoData = json_decode(file_get_contents($jsonFile), true);
      if (!empty($videoData['alias'])) {
        if ($videoData['alias'] === $alias) {
          $videoFile = $jsonFile;
          break;
        }
      }
    }
  }
}

// Handle ID-based routing if alias wasn't found
if (!$videoFile && $id) {
  $videoFile = __DIR__ . "/item-data/$id/$id.json";
  if (!file_exists($videoFile)) {
    $videoFile = null;
  }
}

// Error if neither alias nor ID resolves to a valid file
if (!$videoFile) {
  echo "<div class='site-notification text-red-500 error'>Error: Video not found.</div>";
  exit;
}

// Load video data
$videoData = json_decode(file_get_contents($videoFile), true);

// Load category data
$categoryFile = __DIR__ . '/json/video-category.json';
$categories = file_exists($categoryFile) ? json_decode(file_get_contents($categoryFile), true) : [];

// Get category details
$categoryName = $videoData['category'] ?? 'Unknown';
$categoryPath = '#';

if (isset($categories[$videoData['category']])) {
  $categoryDetails = $categories[$videoData['category']];
  $categoryName = $categoryDetails['name'] ?? 'Unknown';
  $categoryPath = '/video-category/' . ($categoryDetails['path_alias'] ?? '');
}

// Generate the embed URL
$embedUrl = generateEmbedUrl($videoData['video_link']);

// Fetch related videos 
$relatedVideos = getRelatedVideos($videoData['category'], $videoData['unique_id']);

// Page metadata
$pageTitle = htmlspecialchars($videoData['meta_title'] ?: $videoData['title']);
$pageDescription = htmlspecialchars($videoData['meta_description']);
$pageKeywords = htmlspecialchars($videoData['meta_keywords']);
$ogImageURL = $videoData['thumbnail'] ? "https://lightup.tv/item-data/" . $videoData['unique_id'] . "/" . $videoData['thumbnail'] : '';
$ogImageAlt = (isset($videoData['og_image_alt']) && !empty($videoData['og_image_alt'])) ? $videoData['og_image_alt']: '';
$canonicalURL = $alias
  ? "https://lightup.tv/video/{$alias}"
  : "https://lightup.tv/video?id={$videoData['unique_id']}";

// Check video visibility
$privateVideo = false;
if (isset($videoData['visibility']) && $videoData['visibility'] === 'private') {
  $pageTitle = "Private Video | " . $siteName;
  $pageDescription = "Private video";
  $pageKeywords = "";
  $ogImageURL = "";
  $ogImageAlt = "";
  $privateVideo = true;
}

include 'header.php';
include 'menu.php';
include 'sub-heading.php';
?>

<div class="<?php echo $mainContainerClass ?>">
  <div class="flex items-center">
    <?php 
      if ($privateVideo) {
        $contentTitle = "Private Video";
      }
      else {
        $contentTitle = $videoData['title'];
        include 'breadcrumb.php';
      }
    ?>

    <?php if ($privateVideo): ?>
      <div class='site-notification w-full text-center text-yellow-500 warning'>This video is private and cannot be viewed at this time.</div>
    <?php endif; ?>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
      <?php 
        if ($privateVideo) {
          $dropDownClass = "absolute right-10";
        }
        else {
          $dropDownClass = "relative top-0 right-0 ml-auto";
        }
      ?>
      <div class="<?= $dropDownClass ?>">
        <button id="dropdownButton" class="px-4 py-1 bg-gray-700 text-white rounded hover:bg-gray-600 focus:outline-none">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
            class="size-6 inline-block h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 5.25 7.5 7.5 7.5-7.5m-15 6 7.5 7.5 7.5-7.5" />
          </svg>
        </button>
        <div id="dropdownMenu" class="hidden absolute right-0 z-20 mt-2 w-48 bg-gray-800 rounded shadow-lg">
          <a href="/edit-video?id=<?= htmlspecialchars($videoData['unique_id']) ?>"
            class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
            Edit
          </a>
          <button onclick="showDeleteModal('<?= htmlspecialchars($videoData['unique_id']) ?>')"
            class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
            Delete
          </button>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <?php if (!$privateVideo) :?>

  <div class="grid grid-cols-1 lg:grid-cols-10 gap-8 mt-6">
    <!-- First Column -->
    <div class="lg:col-span-7">
      <!-- Video Player -->
      <div id="video-container" class="video-container relative">
        <iframe id="video" class="video" width="560" height="315" src="<?= htmlspecialchars($embedUrl) ?>"
          title="YouTube video player" frameborder="0"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
          referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>
        </iframe>

        <!-- Progress Bar -->
        <div id="progress-bar" class="w-full absolute bottom-0 z-20">
          <input id="progressBar" type="range" min="0" max="100" value="0"
            class="w-full h-2 bg-gray-700 rounded appearance-none cursor-pointer">
          <style>
            #progressBar::-webkit-slider-runnable-track {
              background: linear-gradient(to right, red var(--progress, 0%), gray var(--progress, 0%));
            }

            #progressBar::-moz-range-track {
              background: linear-gradient(to right, red var(--progress, 0%), gray var(--progress, 0%));
            }

            #progressBar::-webkit-slider-thumb {
              appearance: none;
              width: 14px;
              height: 14px;
              background: white;
              border-radius: 50%;
              cursor: pointer;
            }

            #progressBar::-moz-range-thumb {
              width: 14px;
              height: 14px;
              background: white;
              border-radius: 50%;
              cursor: pointer;
            }
          </style>
        </div>
        <!-- Play Button -->
        <button id="playButton"
          class="flex justify-center items-center w-full h-full absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 z-10">
          <svg xmlns="http://www.w3.org/2000/svg" fill="#ef4444" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" class="w-12 h-12 text-white">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
          </svg>
        </button>

        <!-- Pause Button -->
        <button id="pauseButton"
          class="flex justify-center items-center w-full h-full  absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 z-10 hidden hover:block">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" class="w-12 h-12 text-white">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
          </svg>
        </button>

        <script>
          const videoContainer = document.getElementById('video-container');
          const playButton = document.getElementById('playButton');
          const pauseButton = document.getElementById('pauseButton');
          const progressBar = document.getElementById('progressBar');
          const videoIframe = document.getElementById('video');
          let player;
          let isPlaying = false;

          // Load the YouTube Iframe API
          const loadYouTubeAPI = () => {
            const tag = document.createElement('script');
            tag.src = "https://www.youtube.com/iframe_api";
            const firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
          };

          // Initialize the YouTube player when the API is ready
          function onYouTubeIframeAPIReady() {
            player = new YT.Player('video', {
              events: {
                onReady: onPlayerReady,
                onStateChange: onPlayerStateChange,
              }
            });
          }

          // Player ready event
          function onPlayerReady() {
            playButton.addEventListener('click', () => togglePlayPause('play'));
            pauseButton.addEventListener('click', () => togglePlayPause('pause'));
            progressBar.addEventListener('input', handleSeek);
          }

          // Player state change event
          function onPlayerStateChange(event) {
            if (event.data === YT.PlayerState.PLAYING) {
              isPlaying = true;
              updateProgressBar();
            } else {
              isPlaying = false;
            }
          }

          // Function to toggle play and pause
          const togglePlayPause = (action) => {
            if (action === 'play') {
              player.playVideo();
              videoContainer.classList.remove('visible-play-button');
              playButton.classList.add('hidden');
              pauseButton.classList.add('hover-visible');
              pauseButton.classList.remove('hidden');
            } else if (action === 'pause') {
              player.pauseVideo();
              videoContainer.classList.add('visible-play-button');
              playButton.classList.remove('hidden');
              pauseButton.classList.remove('hover-visible');
              pauseButton.classList.add('hidden');
            }
          };

          // Update the progress bar based on the video's current time
          const updateProgressBar = () => {
            const duration = player.getDuration();
            const currentTime = player.getCurrentTime();
            const progress = (currentTime / duration) * 100;
            progressBar.value = progress;
            progressBar.style.setProperty('--progress', `${progress}%`);

            if (isPlaying) {
              requestAnimationFrame(updateProgressBar);
            }
          };

          // Handle seeking when user interacts with the progress bar
          const handleSeek = () => {
            const duration = player.getDuration();
            const seekTime = (progressBar.value / 100) * duration;

            // Check if the video is currently playing
            const wasPlaying = player.getPlayerState() === YT.PlayerState.PLAYING;

            // Seek to the new position
            player.seekTo(seekTime, true);

            // Continue playing only if the video was playing before seeking
            if (wasPlaying) {
              player.playVideo();
              togglePlayPause('play');
            } else {
              player.pauseVideo();
              togglePlayPause('pause');
            }

            // Update the progress bar's red section
            const progress = progressBar.value;
            progressBar.style.setProperty('--progress', `${progress}%`);
          };
          // Sync the video position immediately when dragging stops
          progressBar.addEventListener('change', handleSeek);

          // Load the YouTube API
          loadYouTubeAPI();
        </script>
      </div>


      <!-- Description, Tags, Posted Date -->
      <div class="mt-6">
          <?php if (!empty($videoData['new_description'])): ?>
            <div class="text-gray-400 n-desc">
              <?= $videoData['new_description'] ?>
            </div>
          <?php else: ?>
            <div class="text-gray-400 desc">
              <?= nl2br(htmlspecialchars($videoData['description'])) ?>
            </div>
          <?php endif; ?>
        <div class="mt-4">
          <h2 class="text-lg font-bold text-sunset-yellow">Tags:</h2>
          <p class="text-gray-400"><?= htmlspecialchars($videoData['tags']) ?></p>
        </div>
        <div class="mt-4">
          <h2 class="text-lg font-bold text-sunset-yellow">Posted Date:</h2>
          <p class="text-gray-400"><?= htmlspecialchars($videoData['posted_date']) ?></p>
        </div>
      </div>
    </div>

    <!-- Second Column -->
    <div class="lg:col-span-3 bg-gray-800 p-6 rounded-lg shadow-lg">
      <h2 class="text-lg font-bold text-sunset-yellow mb-4">Related Videos</h2>
      <?php if (!empty($relatedVideos)): ?>
        <ul class="space-y-4">
          <?php foreach ($relatedVideos as $related): ?>
            <li class="flex space-x-4">
              <img src="<?= $related['thumbnail'] ?>" alt="<?= htmlspecialchars($related['title']) ?>"
                class="w-16 h-16 object-cover rounded-md">
              <a href="<?= $related['link'] ?>"
                class="text-sunset-yellow hover:underline text-sm font-medium"><?= htmlspecialchars($related['title']) ?></a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="text-gray-400">No related videos found.</p>
      <?php endif; ?>
    </div>
  </div>

  <?php endif; ?>

</div>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="fixed inset-0 hidden z-50 bg-black bg-opacity-50 flex justify-center items-center">
    <div class="bg-gray-800 text-text-light rounded-lg p-6 max-w-md w-full">
      <h2 class="text-lg font-bold mb-4">Confirm Delete</h2>
      <p>Are you sure you want to delete this video?</p>
      <div class="flex justify-end mt-4">
        <button id="cancelDelete" class="mr-2 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Cancel</button>
        <button id="confirmDelete" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php include 'footer.php'; ?>