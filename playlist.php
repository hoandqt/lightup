<?php
session_start();
require_once 'functions.php';

// Get alias or ID from the request
$alias = $_GET['alias'] ?? null;
$id = $_GET['id'] ?? null;

$playlistFile = null;

// Handle alias-based routing
if ($alias) {
  $playlistDir = __DIR__ . '/playlist-data/';

  foreach (glob($playlistDir . '*/') as $dir) {
    $jsonFile = $dir . basename($dir) . '.json';
    if (file_exists($jsonFile)) {
      $playlistData = json_decode(file_get_contents($jsonFile), true);
      if (!empty($playlistData['alias'])) {
        if ($playlistData['alias'] === $alias) {
          $playlistFile = $jsonFile;
          break;
        }
      }
    }
  }
}

// Handle ID-based routing if alias wasn't found
if (!$playlistFile && $id) {
  $playlistFile = __DIR__ . "/playlist-data/$id/$id.json";
  if (!file_exists($playlistFile)) {
    $playlistFile = null;
  }
}

// Error if neither alias nor ID resolves to a valid file
if (!$playlistFile) {
  echo "<div class='site-notification text-red-500 error'>Error: Playlist not found.</div>";
  exit;
}

// Load playlist data
$playlistData = json_decode(file_get_contents($playlistFile), true);

// Load category data
$categoryFile = __DIR__ . '/json/playlist-category.json';
$categories = file_exists($categoryFile) ? json_decode(file_get_contents($categoryFile), true) : [];

// Get category details
$categoryName = $playlistData['category'] ?? 'Unknown';
$categoryPath = '#';

if (isset($categories[$playlistData['category']])) {
  $categoryDetails = $categories[$playlistData['category']];
  $categoryName = $categoryDetails['name'] ?? 'Unknown';
  $categoryPath = '/playlist-category/' . ($categoryDetails['path_alias'] ?? '');
}

// Generate the embed URL
$embedUrl = generateEmbedUrl($playlistData['playlist_link']);

// Page metadata
$pageTitle = htmlspecialchars($playlistData['meta_title'] ?: $playlistData['title']);
$pageDescription = htmlspecialchars($playlistData['meta_description']);
$pageKeywords = htmlspecialchars($playlistData['meta_keywords']);
$ogImageURL = $playlistData['thumbnail'] ? "https://lightup.tv/playlist-data/" . $playlistData['unique_id'] . "/" . $playlistData['thumbnail'] : '';
$ogImageAlt = (isset($playlistData['og_image_alt']) && !empty($playlistData['og_image_alt'])) ? $playlistData['og_image_alt'] : '';
$canonicalURL = $alias
  ? "https://lightup.tv/playlist/{$alias}"
  : "https://lightup.tv/playlist?id={$playlistData['unique_id']}";

// Check playlist visibility
$privatePlaylist = false;
if (isset($playlistData['visibility']) && $playlistData['visibility'] === 'private') {
  $pageTitle = "Private Playlist | " . $siteName;
  $pageDescription = "Private playlist";
  $pageKeywords = "";
  $ogImageURL = "";
  $ogImageAlt = "";
  $privatePlaylist = true;
}

include 'header.php';
include 'menu.php';
include 'sub-heading.php';
?>

<div class="<?php echo $mainContainerClass ?>">
  <div class="flex items-center">
    <?php
    if ($privatePlaylist) {
      $contentTitle = "Private Playlist";
    } else {
      $contentTitle = $playlistData['title'];
      include 'breadcrumb.php';
    }
    ?>

    <?php if ($privatePlaylist): ?>
      <div class='site-notification w-full text-center text-yellow-500 warning'>This playlist is private and cannot be
        viewed at this time.</div>
    <?php endif; ?>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
      <?php
      if ($privatePlaylist) {
        $dropDownClass = "absolute right-10";
      } else {
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
          <a href="/edit-playlist?id=<?= htmlspecialchars($playlistData['unique_id']) ?>"
            class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
            Edit
          </a>
          <button onclick="showDeleteModal('<?= htmlspecialchars($playlistData['unique_id']) ?>')"
            class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
            Delete
          </button>
          <button onclick="addVideoToThisPlaylist('<?= htmlspecialchars($playlistData['unique_id']) ?>')"
            class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
            Add a Video to this Playlist
          </button>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <?php if (!$privatePlaylist): ?>

    <div class="grid grid-cols-1 lg:grid-cols-10 gap-8 mt-6">
      <!-- First Column -->
      <div id="first-column" class="lg:col-span-7">
        <!-- Playlist Player -->
        <div id="playlist-container" class="video-container relative">
          <iframe id="playlist" class="playlist video" width="560" height="315" src="<?= htmlspecialchars($embedUrl) ?>"
            title="YouTube playlist player" frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>
          </iframe>

          <div id="playlist-thumbnail" class="absolute top-0 hidden w-full"><img src="<?= $ogImageURL ?>" alt="<?php !empty($ogImageAlt) ? $ogImageAlt : $pageTitle ." Playlist Image" ?>" width="100%" height="100%" /></div>

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

          <button id="prevButton"
            class="flex justify-start items-center w-10 h-10 absolute top-1/2 left-3 transform -translate-y-1/2 z-20 hidden hover:block">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
              stroke="currentColor" class="w-12 h-12 text-white">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M21 16.811c0 .864-.933 1.406-1.683.977l-7.108-4.061a1.125 1.125 0 0 1 0-1.954l7.108-4.061A1.125 1.125 0 0 1 21 8.689v8.122ZM11.25 16.811c0 .864-.933 1.406-1.683.977l-7.108-4.061a1.125 1.125 0 0 1 0-1.954l7.108-4.061a1.125 1.125 0 0 1 1.683.977v8.122Z" />
            </svg>
          </button>

          <button id="nextButton"
            class="flex justify-end items-center w-10 h-10 absolute top-1/2 right-3 transform -translate-y-1/2 z-20 hidden hover:block">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
              stroke="currentColor" class="w-12 h-12 text-white">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M3 8.689c0-.864.933-1.406 1.683-.977l7.108 4.061a1.125 1.125 0 0 1 0 1.954l-7.108 4.061A1.125 1.125 0 0 1 3 16.811V8.69ZM12.75 8.689c0-.864.933-1.406 1.683-.977l7.108 4.061a1.125 1.125 0 0 1 0 1.954l-7.108 4.061a1.125 1.125 0 0 1-1.683-.977V8.69Z" />
            </svg>
          </button>

          <?php
          // Fetch playlist videos
          $playlistVideos = getPlaylistVideos($playlistData['unique_id']);
          ?>
          <script>
            const playlistContainer = document.getElementById('playlist-container');
            const playButton = document.getElementById('playButton');
            const pauseButton = document.getElementById('pauseButton');
            const prevButton = document.getElementById('prevButton');
            const nextButton = document.getElementById('nextButton');
            const progressBar = document.getElementById('progressBar');
            const playlistIframe = document.getElementById('playlist');
            const playlistThumbnail = document.getElementById('playlist-thumbnail');

            let player;
            let isPlaying = false;
            let currentVideoIndex = 0;

            // Your playlist videos
            const videoLinks = <?php echo json_encode(array_values(array_reduce($playlistVideos, function ($acc, $video) {
              if (preg_match('/v=([^&]+)/', $video['video_link'], $matches)) {
                $videoId = $matches[1];
                $acc[] = "https://www.youtube.com/embed/$videoId?controls=0&showinfo=0&modestbranding=1&rel=0&enablejsapi=1";
              }
              return $acc;
            }, []))); ?>;

            // Load the YouTube Iframe API
            const loadYouTubeAPI = () => {
              const tag = document.createElement('script');
              tag.src = "https://www.youtube.com/iframe_api";
              const firstScriptTag = document.getElementsByTagName('script')[0];
              firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
            };

            // Initialize the YouTube player when the API is ready
            function onYouTubeIframeAPIReady() {
              player = new YT.Player('playlist', {
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
              prevButton.addEventListener('click', playPreviousVideo);
              nextButton.addEventListener('click', playNextVideo);
              progressBar.addEventListener('input', handleSeek);

              // Start playing the first video
              loadVideoByIndex(currentVideoIndex);
            }

            // Player state change event
            function onPlayerStateChange(event) {
              if (event.data === YT.PlayerState.PLAYING) {
                isPlaying = true;
                updateProgressBar();
              } else if (event.data === YT.PlayerState.ENDED) {
                playNextVideo();
              } else {
                isPlaying = false;
              }
            }

            // Function to toggle play and pause
            const togglePlayPause = (action) => {
              if (action === 'play') {
                player.playVideo();
                if (playlistThumbnail) {
                  playlistThumbnail.classList.add('hidden');
                }
                playlistContainer.classList.remove('visible-play-button');
                playButton.classList.add('hidden');
                pauseButton.classList.add('hover-visible');
                prevButton.classList.add('hover-visible');
                nextButton.classList.add('hover-visible');
                pauseButton.classList.remove('hidden');
                // Auto hide the player navigation after 3 seconds
                setTimeout(function(){playlistContainer.classList.add('auto-hide-navigation')}, 3000);
              } else if (action === 'pause') {
                player.pauseVideo();
                if (playlistThumbnail) {
                  playlistThumbnail.classList.remove('hidden');
                }
                playlistContainer.classList.add('visible-play-button');
                playButton.classList.remove('hidden');
                pauseButton.classList.remove('hover-visible');
                prevButton.classList.remove('hover-visible');
                nextButton.classList.remove('hover-visible');
                pauseButton.classList.add('hidden');
              }
            };

            // Remove auto hide class from the player navigation
            playlistContainer.addEventListener('mousemove', function(event) {
              console.log(playlistContainer.classList.contains('auto-hide-navigation'));
              playlistContainer.classList.remove('auto-hide-navigation');
            });

            // Update the progress bar based on the playlist's current time
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

              const wasPlaying = player.getPlayerState() === YT.PlayerState.PLAYING;

              player.seekTo(seekTime, true);

              if (wasPlaying) {
                player.playVideo();
                togglePlayPause('play');
              } else {
                player.pauseVideo();
                togglePlayPause('pause');
              }

              const progress = progressBar.value;
              progressBar.style.setProperty('--progress', `${progress}%`);
            };

            var savedAutoplayState = localStorage.getItem('autoplay_playlist');
            savedAutoplayState = JSON.parse(savedAutoplayState.toLowerCase());

            // Load a video by its index in the playlist
            const loadVideoByIndex = (index) => {
              if (index == 0) {
                // First video will auto play by default or when the autoplay option is checked.
                currentVideoIndex = index;
                if (savedAutoplayState === null || savedAutoplayState === true) {
                  if (playlistThumbnail) {
                    playlistThumbnail.classList.add('hidden');
                  }
                  player.loadVideoByUrl(videoLinks[currentVideoIndex]);
                  playlistContainer.classList.remove('visible-play-button');
                  playButton.classList.add('hidden');
                  pauseButton.classList.add('hover-visible');
                  prevButton.classList.add('hover-visible');
                  nextButton.classList.add('hover-visible');
                  pauseButton.classList.remove('hidden');
                }
                else {
                  if (playlistThumbnail) {
                    playlistThumbnail.classList.remove('hidden');
                  }
                  player.cueVideoByUrl(videoLinks[currentVideoIndex]);
                  playlistContainer.classList.add('visible-play-button');
                  playButton.classList.remove('hidden');
                  pauseButton.classList.remove('hover-visible');
                  prevButton.classList.remove('hover-visible');
                  nextButton.classList.remove('hover-visible');
                  pauseButton.classList.add('hidden');
                }
                addClassToListItem(index, 'highlight');
              }
              else if (index > 0 && index < videoLinks.length) {
                // Next videos will auto play
                currentVideoIndex = index;
                player.loadVideoByUrl(videoLinks[currentVideoIndex]);
                addClassToListItem(index, 'highlight');
              } else {
                //console.log("No more videos in the playlist.");
              }
            };

            // Play the next video in the playlist
            const playNextVideo = () => {
              if (currentVideoIndex + 1 < videoLinks.length) {
                loadVideoByIndex(currentVideoIndex + 1);
              } else {
                // Load first video at the end
                currentVideoIndex = 0;
                player.cueVideoByUrl(videoLinks[0]);
                playlistContainer.classList.add('visible-play-button');
                playButton.classList.remove('hidden');
                pauseButton.classList.remove('hover-visible');
                prevButton.classList.remove('hover-visible');
                nextButton.classList.remove('hover-visible');
                pauseButton.classList.add('hidden');
                addClassToListItem(0, 'highlight');
              }
            };

            // Play the previous video in the playlist
            const playPreviousVideo = () => {
              if (currentVideoIndex - 1 >= 0) {
                loadVideoByIndex(currentVideoIndex - 1);
              } else {
                //console.log("Already at the start of the playlist.");
              }
            };

            const firstVideoLink = Object.values(videoLinks)[0];
            if (firstVideoLink) {
              const iframe = document.getElementById("playlist");
              iframe.src = firstVideoLink;
            } else {
              //console.warn("No videos found in the playlist.");
            }

            // Function to add a class to an <li> by index
            const addClassToListItem = (index, className) => {
              const currentPlaylist = document.getElementById('current-playlist');
              const listItems = currentPlaylist.children; // Get all <li> elements

              // Remove the class from all <li> elements
              Array.from(listItems).forEach((item) => {
                item.classList.remove(className);
              });

              // Add the class to the specific <li> by index
              if (index >= 0 && index < listItems.length) { // Check index bounds
                listItems[index].classList.add(className); // Add the class to the specified <li>
              } else {
                //console.warn("Index out of bounds");
              }
            };

            // Load the YouTube API
            loadYouTubeAPI();
          </script>
        </div>

        <!-- Statics & Interaction -->
        <div id="statics-interaction" class="bg-gray-800 rounded-lg shadow-lg"
          data-id="<?= $playlistData['unique_id'] ?>">
          <div class="wrapper relative flex flex-wrap md:flex-nowrap items-center gap-0 sm:gap-3 right-0 z-20 mt-2 p-2 w-full">
            <button onclick="handleModal('likeModal', this)"
              class="text-smaller flex items-center justify-center gap-2 w-1/4 sm:w-full text-left px-6 py-1 text-sm text-gray-300 hover:bg-gray-700 hover:text-white"
              data-id="<?= $playlistData['unique_id'] ?>"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z">
                </path>
              </svg>
              Like</button>
            <button onclick="handleModal('shareModal', this)"
              class="text-smaller flex items-center justify-center gap-2 w-1/4 sm:w-full text-left px-6 py-1 text-sm text-gray-300 hover:bg-gray-700 hover:text-white"
              data-id="<?= $playlistData['unique_id'] ?>"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z">
                </path>
              </svg>
              Share</button>
            <button onclick="handleModal('commentModal', this)"
              class="text-smaller flex items-center justify-center gap-2 w-1/4 sm:w-full text-left px-6 py-1 text-sm text-gray-300 hover:bg-gray-700 hover:text-white"
              data-id="<?= $playlistData['unique_id'] ?>"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z">
                </path>
              </svg>
              Comment</button>
            <button onclick="handleModal('reportModal', this)"
              class="text-smaller flex items-center justify-center gap-2 w-1/4 sm:w-full text-left px-6 py-1 text-sm text-gray-300 hover:bg-gray-700 hover:text-white"
              data-id="<?= $playlistData['unique_id'] ?>"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5">
                </path>
              </svg>
              Report</button>
          </div>
        </div>
        <!-- Description, Tags, Posted Date -->
        <div id="playlist-description" class="description-container mt-6">
          <?php if (!empty($playlistData['description_content'])): ?>
            <div class="text-gray-400 n-desc">
              <?= $playlistData['description_content'] ?>
            </div>
          <?php else: ?>
            <div class="text-gray-400 desc">
              <?= nl2br(htmlspecialchars($playlistData['description'])) ?>
            </div>
          <?php endif; ?>
          <div class="mt-4">
            <h2 class="text-lg font-bold text-sunset-yellow">Tags:</h2>
            <p class="text-gray-400"><?= htmlspecialchars($playlistData['tags']) ?></p>
          </div>
          <div class="mt-4">
            <h2 class="text-lg font-bold text-sunset-yellow">Posted Date:</h2>
            <p class="text-gray-400"><?= htmlspecialchars($playlistData['posted_date']) ?></p>
          </div>
        </div>
      </div>

      <!-- Second Column -->
      <div id="second-column" class="relative lg:col-span-3 bg-gray-800 rounded-lg shadow-lg">
        <h2 class="text-lg font-bold text-sunset-yellow pt-6 pb-4 px-6">Playlist Songs</h2>
        <div class="absolute top-5 right-4 ml-auto">
          <button id="playlistDropdownButton"
            class="px-2 py-1 bg-gray-700 text-white rounded hover:bg-gray-600 focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
              stroke="currentColor" class="size-6 inline-block h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 5.25 7.5 7.5 7.5-7.5m-15 6 7.5 7.5 7.5-7.5">
              </path>
            </svg>
          </button>
          <div id="playlistDropdownOptions"
            class="hidden absolute right-0 z-20 mt-2 pt-4 pb-4 w-48 bg-gray-600 rounded shadow-lg">
            <div id="playlist-options" class="mb-2">
              <div class="flex items-center px-6">
                <input type="checkbox" id="autoplay-option" class="mr-2" checked="">
                <label for="autoplay-option" class="cursor-pointer select-none">Autoplay</label>
              </div>
            </div>
            <button onclick="handleModal('likeModal', this)"
              class="flex items-center gap-2 w-full text-left px-6 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white"
              data-id="<?= $playlistData['unique_id'] ?>"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z">
                </path>
              </svg>
              Like</button>
            <button onclick="handleModal('shareModal', this)"
              class="flex items-center gap-2 w-full text-left px-6 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white"
              data-id="<?= $playlistData['unique_id'] ?>"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z">
                </path>
              </svg>
              Share</button>
            <button onclick="handleModal('commentModal', this)"
              class="flex items-center gap-2  w-full text-left px-6 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white"
              data-id="<?= $playlistData['unique_id'] ?>"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z">
                </path>
              </svg>
              Comment</button>
            <button onclick="handleModal('reportModal', this)"
              class="flex items-center gap-2  w-full text-left px-6 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white"
              data-id="<?= $playlistData['unique_id'] ?>"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5">
                </path>
              </svg>
              Report</button>
          </div>
        </div>
        <?php if (!empty($playlistVideos)): ?>
          <ul id="current-playlist">
            <?php foreach ($playlistVideos as $video): ?>
              <li class="flex space-x-4 px-6 pt-4 pb-4">
                <img src="<?= $video['thumbnail'] ?>" alt="<?= htmlspecialchars($video['title']) ?>"
                  class="w-16 h-16 object-cover rounded-md">
                <a href="<?= $video['link'] ?>"
                  class="text-sunset-yellow hover:underline text-sm font-medium"><?= htmlspecialchars($video['title']) ?></a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="text-gray-400 pt-0 p-6">No video found.</p>
        <?php endif; ?>
      </div>
    </div>

  <?php endif; ?>

</div>

<!-- Modals -->
<div id="likeModal" class="modal fixed inset-0 hidden z-50 bg-black bg-opacity-50 flex justify-center items-center">
  <div class="modal-content text-center bg-gray-800 text-text-light rounded-lg p-6 mx-4 max-w-md w-full">
    <h2 class="text-lg font-bold mb-4">Registered Users Only</h2>
    <p>Please login to like the playlist!</p>
    <div class="actions flex justify-center mt-4 gap-2">
      <button onclick="closeModal('likeModal')"
        class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Cancel</button>
      <button class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"><a class="btn" href="/login"
          target="_blank">Login</a></button>
    </div>
  </div>
</div>

<div id="shareModal"
  class="modal fixed inset-0 hidden z-50 bg-black bg-opacity-50 flex justify-center items-center fixed inset-0 hidden z-50 bg-black bg-opacity-50 flex justify-center items-center">
  <div class="modal-content text-center bg-gray-800 text-text-light rounded-lg p-6 mx-4 max-w-lg w-full">
    <h2 class="text-lg font-bold mb-4">Share Playlist</h2>
    <p>Please select an option below to share with your friends.</p>
    <div class="block text-center mt-2">
      <!-- Behance -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-[#1769ff] px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 576 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M232 237.2c31.8-15.2 48.4-38.2 48.4-74 0-70.6-52.6-87.8-113.3-87.8H0v354.4h171.8c64.4 0 124.9-30.9 124.9-102.9 0-44.5-21.1-77.4-64.7-89.7zM77.9 135.9H151c28.1 0 53.4 7.9 53.4 40.5 0 30.1-19.7 42.2-47.5 42.2h-79v-82.7zm83.3 233.7H77.9V272h84.9c34.3 0 56 14.3 56 50.6 0 35.8-25.9 47-57.6 47zm358.5-240.7H376V94h143.7v34.9zM576 305.2c0-75.9-44.4-139.2-124.9-139.2-78.2 0-131.3 58.8-131.3 135.8 0 79.9 50.3 134.7 131.3 134.7 61.3 0 101-27.6 120.1-86.3H509c-6.7 21.9-34.3 33.5-55.7 33.5-41.3 0-63-24.2-63-65.3h185.1c.3-4.2 .6-8.7 .6-13.2zM390.4 274c2.3-33.7 24.7-54.8 58.5-54.8 35.4 0 53.2 20.8 56.2 54.8H390.4z" />
          </svg>
        </span>
      </button>

      <!-- Discord -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-[#7289da] px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 640 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M524.5 69.8a1.5 1.5 0 0 0 -.8-.7A485.1 485.1 0 0 0 404.1 32a1.8 1.8 0 0 0 -1.9 .9 337.5 337.5 0 0 0 -14.9 30.6 447.8 447.8 0 0 0 -134.4 0 309.5 309.5 0 0 0 -15.1-30.6 1.9 1.9 0 0 0 -1.9-.9A483.7 483.7 0 0 0 116.1 69.1a1.7 1.7 0 0 0 -.8 .7C39.1 183.7 18.2 294.7 28.4 404.4a2 2 0 0 0 .8 1.4A487.7 487.7 0 0 0 176 479.9a1.9 1.9 0 0 0 2.1-.7A348.2 348.2 0 0 0 208.1 430.4a1.9 1.9 0 0 0 -1-2.6 321.2 321.2 0 0 1 -45.9-21.9 1.9 1.9 0 0 1 -.2-3.1c3.1-2.3 6.2-4.7 9.1-7.1a1.8 1.8 0 0 1 1.9-.3c96.2 43.9 200.4 43.9 295.5 0a1.8 1.8 0 0 1 1.9 .2c2.9 2.4 6 4.9 9.1 7.2a1.9 1.9 0 0 1 -.2 3.1 301.4 301.4 0 0 1 -45.9 21.8 1.9 1.9 0 0 0 -1 2.6 391.1 391.1 0 0 0 30 48.8 1.9 1.9 0 0 0 2.1 .7A486 486 0 0 0 610.7 405.7a1.9 1.9 0 0 0 .8-1.4C623.7 277.6 590.9 167.5 524.5 69.8zM222.5 337.6c-29 0-52.8-26.6-52.8-59.2S193.1 219.1 222.5 219.1c29.7 0 53.3 26.8 52.8 59.2C275.3 311 251.9 337.6 222.5 337.6zm195.4 0c-29 0-52.8-26.6-52.8-59.2S388.4 219.1 417.9 219.1c29.7 0 53.3 26.8 52.8 59.2C470.7 311 447.5 337.6 417.9 337.6z" />
          </svg>
        </span>
      </button>

      <!-- Github -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-[#333] px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 496 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M165.9 397.4c0 2-2.3 3.6-5.2 3.6-3.3 .3-5.6-1.3-5.6-3.6 0-2 2.3-3.6 5.2-3.6 3-.3 5.6 1.3 5.6 3.6zm-31.1-4.5c-.7 2 1.3 4.3 4.3 4.9 2.6 1 5.6 0 6.2-2s-1.3-4.3-4.3-5.2c-2.6-.7-5.5 .3-6.2 2.3zm44.2-1.7c-2.9 .7-4.9 2.6-4.6 4.9 .3 2 2.9 3.3 5.9 2.6 2.9-.7 4.9-2.6 4.6-4.6-.3-1.9-3-3.2-5.9-2.9zM244.8 8C106.1 8 0 113.3 0 252c0 110.9 69.8 205.8 169.5 239.2 12.8 2.3 17.3-5.6 17.3-12.1 0-6.2-.3-40.4-.3-61.4 0 0-70 15-84.7-29.8 0 0-11.4-29.1-27.8-36.6 0 0-22.9-15.7 1.6-15.4 0 0 24.9 2 38.6 25.8 21.9 38.6 58.6 27.5 72.9 20.9 2.3-16 8.8-27.1 16-33.7-55.9-6.2-112.3-14.3-112.3-110.5 0-27.5 7.6-41.3 23.6-58.9-2.6-6.5-11.1-33.3 2.6-67.9 20.9-6.5 69 27 69 27 20-5.6 41.5-8.5 62.8-8.5s42.8 2.9 62.8 8.5c0 0 48.1-33.6 69-27 13.7 34.7 5.2 61.4 2.6 67.9 16 17.7 25.8 31.5 25.8 58.9 0 96.5-58.9 104.2-114.8 110.5 9.2 7.9 17 22.9 17 46.4 0 33.7-.3 75.4-.3 83.6 0 6.5 4.6 14.4 17.3 12.1C428.2 457.8 496 362.9 496 252 496 113.3 383.5 8 244.8 8zM97.2 352.9c-1.3 1-1 3.3 .7 5.2 1.6 1.6 3.9 2.3 5.2 1 1.3-1 1-3.3-.7-5.2-1.6-1.6-3.9-2.3-5.2-1zm-10.8-8.1c-.7 1.3 .3 2.9 2.3 3.9 1.6 1 3.6 .7 4.3-.7 .7-1.3-.3-2.9-2.3-3.9-2-.6-3.6-.3-4.3 .7zm32.4 35.6c-1.6 1.3-1 4.3 1.3 6.2 2.3 2.3 5.2 2.6 6.5 1 1.3-1.3 .7-4.3-1.3-6.2-2.2-2.3-5.2-2.6-6.5-1zm-11.4-14.7c-1.6 1-1.6 3.6 0 5.9 1.6 2.3 4.3 3.3 5.6 2.3 1.6-1.3 1.6-3.9 0-6.2-1.4-2.3-4-3.3-5.6-2z" />
          </svg>
        </span>
      </button>

      <!-- Facebook -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-[#1877f2] px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 320 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M80 299.3V512H196V299.3h86.5l18-97.8H196V166.9c0-51.7 20.3-71.5 72.7-71.5c16.3 0 29.4 .4 37 1.2V7.9C291.4 4 256.4 0 236.2 0C129.3 0 80 50.5 80 159.4v42.1H14v97.8H80z" />
          </svg>
        </span>
      </button>

      <!-- Instagram -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-[#c13584] px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 448 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z" />
          </svg>
        </span>
      </button>

      <!-- Google -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-[#ea4335] px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 488 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M488 261.8C488 403.3 391.1 504 248 504 110.8 504 0 393.2 0 256S110.8 8 248 8c66.8 0 123 24.5 166.3 64.9l-67.5 64.9C258.5 52.6 94.3 116.6 94.3 256c0 86.5 69.1 156.6 153.7 156.6 98.2 0 135-70.4 140.8-106.9H248v-85.3h236.1c2.3 12.7 3.9 24.9 3.9 41.4z" />
          </svg>
        </span>
      </button>

      <!-- Linkedin -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-[#0077b5] px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 448 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M100.3 448H7.4V148.9h92.9zM53.8 108.1C24.1 108.1 0 83.5 0 53.8a53.8 53.8 0 0 1 107.6 0c0 29.7-24.1 54.3-53.8 54.3zM447.9 448h-92.7V302.4c0-34.7-.7-79.2-48.3-79.2-48.3 0-55.7 37.7-55.7 76.7V448h-92.8V148.9h89.1v40.8h1.3c12.4-23.5 42.7-48.3 87.9-48.3 94 0 111.3 61.9 111.3 142.3V448z" />
          </svg>
        </span>
      </button>

      <!-- Pinterest -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-[#e60023] px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 496 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M496 256c0 137-111 248-248 248-25.6 0-50.2-3.9-73.4-11.1 10.1-16.5 25.2-43.5 30.8-65 3-11.6 15.4-59 15.4-59 8.1 15.4 31.7 28.5 56.8 28.5 74.8 0 128.7-68.8 128.7-154.3 0-81.9-66.9-143.2-152.9-143.2-107 0-163.9 71.8-163.9 150.1 0 36.4 19.4 81.7 50.3 96.1 4.7 2.2 7.2 1.2 8.3-3.3 .8-3.4 5-20.3 6.9-28.1 .6-2.5 .3-4.7-1.7-7.1-10.1-12.5-18.3-35.3-18.3-56.6 0-54.7 41.4-107.6 112-107.6 60.9 0 103.6 41.5 103.6 100.9 0 67.1-33.9 113.6-78 113.6-24.3 0-42.6-20.1-36.7-44.8 7-29.5 20.5-61.3 20.5-82.6 0-19-10.2-34.9-31.4-34.9-24.9 0-44.9 25.7-44.9 60.2 0 22 7.4 36.8 7.4 36.8s-24.5 103.8-29 123.2c-5 21.4-3 51.6-.9 71.2C65.4 450.9 0 361.1 0 256 0 119 111 8 248 8s248 111 248 248z" />
          </svg>
        </span>
      </button>

      <!-- Vkontakte -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-[#45668e] px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 448 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M31.5 63.5C0 95 0 145.7 0 247V265C0 366.3 0 417 31.5 448.5C63 480 113.7 480 215 480H233C334.3 480 385 480 416.5 448.5C448 417 448 366.3 448 265V247C448 145.7 448 95 416.5 63.5C385 32 334.3 32 233 32H215C113.7 32 63 32 31.5 63.5zM75.6 168.3H126.7C128.4 253.8 166.1 290 196 297.4V168.3H244.2V242C273.7 238.8 304.6 205.2 315.1 168.3H363.3C359.3 187.4 351.5 205.6 340.2 221.6C328.9 237.6 314.5 251.1 297.7 261.2C316.4 270.5 332.9 283.6 346.1 299.8C359.4 315.9 369 334.6 374.5 354.7H321.4C316.6 337.3 306.6 321.6 292.9 309.8C279.1 297.9 262.2 290.4 244.2 288.1V354.7H238.4C136.3 354.7 78 284.7 75.6 168.3z" />
          </svg>
        </span>
      </button>

      <!-- Stack overflow -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-[#f48024] px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 384 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M290.7 311L95 269.7 86.8 309l195.7 41zm51-87L188.2 95.7l-25.5 30.8 153.5 128.3zm-31.2 39.7L129.2 179l-16.7 36.5L293.7 300zM262 32l-32 24 119.3 160.3 32-24zm20.5 328h-200v39.7h200zm39.7 80H42.7V320h-40v160h359.5V320h-40z" />
          </svg>
        </span>
      </button>

      <!-- Telegram -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-[#0088cc] px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 496 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M248 8C111 8 0 119 0 256S111 504 248 504 496 393 496 256 385 8 248 8zM363 176.7c-3.7 39.2-19.9 134.4-28.1 178.3-3.5 18.6-10.3 24.8-16.9 25.4-14.4 1.3-25.3-9.5-39.3-18.7-21.8-14.3-34.2-23.2-55.3-37.2-24.5-16.1-8.6-25 5.3-39.5 3.7-3.8 67.1-61.5 68.3-66.7 .2-.7 .3-3.1-1.2-4.4s-3.6-.8-5.1-.5q-3.3 .7-104.6 69.1-14.8 10.2-26.9 9.9c-8.9-.2-25.9-5-38.6-9.1-15.5-5-27.9-7.7-26.8-16.3q.8-6.7 18.5-13.7 108.4-47.2 144.6-62.3c68.9-28.6 83.2-33.6 92.5-33.8 2.1 0 6.6 .5 9.6 2.9a10.5 10.5 0 0 1 3.5 6.7A43.8 43.8 0 0 1 363 176.7z" />
          </svg>
        </span>
      </button>

      <!-- Youtube -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-[#ff0000] px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 576 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M549.7 124.1c-6.3-23.7-24.8-42.3-48.3-48.6C458.8 64 288 64 288 64S117.2 64 74.6 75.5c-23.5 6.3-42 24.9-48.3 48.6-11.4 42.9-11.4 132.3-11.4 132.3s0 89.4 11.4 132.3c6.3 23.7 24.8 41.5 48.3 47.8C117.2 448 288 448 288 448s170.8 0 213.4-11.5c23.5-6.3 42-24.2 48.3-47.8 11.4-42.9 11.4-132.3 11.4-132.3s0-89.4-11.4-132.3zm-317.5 213.5V175.2l142.7 81.2-142.7 81.2z" />
          </svg>
        </span>
      </button>

      <!-- TikTok -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-[#6a76ac] px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 448 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M448 209.9a210.1 210.1 0 0 1 -122.8-39.3V349.4A162.6 162.6 0 1 1 185 188.3V278.2a74.6 74.6 0 1 0 52.2 71.2V0l88 0a121.2 121.2 0 0 0 1.9 22.2h0A122.2 122.2 0 0 0 381 102.4a121.4 121.4 0 0 0 67 20.1z" />
          </svg>
        </span>
      </button>

      <!-- Snapchat -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-[#f8cc1b] px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 512 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M496.9 366.6c-3.4-9.2-9.8-14.1-17.1-18.2-1.4-.8-2.6-1.5-3.7-1.9-2.2-1.1-4.4-2.2-6.6-3.4-22.8-12.1-40.6-27.3-53-45.4a102.9 102.9 0 0 1 -9.1-16.1c-1.1-3-1-4.7-.2-6.3a10.2 10.2 0 0 1 2.9-3c3.9-2.6 8-5.2 10.7-7 4.9-3.2 8.8-5.7 11.2-7.4 9.4-6.5 15.9-13.5 20-21.3a42.4 42.4 0 0 0 2.1-35.2c-6.2-16.3-21.6-26.4-40.3-26.4a55.5 55.5 0 0 0 -11.7 1.2c-1 .2-2.1 .5-3.1 .7 .2-11.2-.1-22.9-1.1-34.5-3.5-40.8-17.8-62.1-32.7-79.2A130.2 130.2 0 0 0 332.1 36.4C309.5 23.5 283.9 17 256 17S202.6 23.5 180 36.4a129.7 129.7 0 0 0 -33.3 26.8c-14.9 17-29.2 38.4-32.7 79.2-1 11.6-1.2 23.4-1.1 34.5-1-.3-2-.5-3.1-.7a55.5 55.5 0 0 0 -11.7-1.2c-18.7 0-34.1 10.1-40.3 26.4a42.4 42.4 0 0 0 2 35.2c4.1 7.8 10.7 14.7 20 21.3 2.5 1.7 6.4 4.2 11.2 7.4 2.6 1.7 6.5 4.2 10.3 6.7a11.1 11.1 0 0 1 3.3 3.3c.8 1.6 .8 3.4-.4 6.6a102 102 0 0 1 -8.9 15.8c-12.1 17.7-29.4 32.6-51.4 44.6C32.4 348.6 20.2 352.8 15.1 366.7c-3.9 10.5-1.3 22.5 8.5 32.6a49.1 49.1 0 0 0 12.4 9.4 134.3 134.3 0 0 0 30.3 12.1 20 20 0 0 1 6.1 2.7c3.6 3.1 3.1 7.9 7.8 14.8a34.5 34.5 0 0 0 9 9.1c10 6.9 21.3 7.4 33.2 7.8 10.8 .4 23 .9 36.9 5.5 5.8 1.9 11.8 5.6 18.7 9.9C194.8 481 217.7 495 256 495s61.3-14.1 78.1-24.4c6.9-4.2 12.9-7.9 18.5-9.8 13.9-4.6 26.2-5.1 36.9-5.5 11.9-.5 23.2-.9 33.2-7.8a34.6 34.6 0 0 0 10.2-11.2c3.4-5.8 3.3-9.9 6.6-12.8a19 19 0 0 1 5.8-2.6A134.9 134.9 0 0 0 476 408.7a48.3 48.3 0 0 0 13-10.2l.1-.1C498.4 388.5 500.7 376.9 496.9 366.6zm-34 18.3c-20.7 11.5-34.5 10.2-45.3 17.1-9.1 5.9-3.7 18.5-10.3 23.1-8.1 5.6-32.2-.4-63.2 9.9-25.6 8.5-42 32.8-88 32.8s-62-24.3-88.1-32.9c-31-10.3-55.1-4.2-63.2-9.9-6.6-4.6-1.2-17.2-10.3-23.1-10.7-6.9-24.5-5.7-45.3-17.1-13.2-7.3-5.7-11.8-1.3-13.9 75.1-36.4 87.1-92.6 87.7-96.7 .6-5 1.4-9-4.2-14.1-5.4-5-29.2-19.7-35.8-24.3-10.9-7.6-15.7-15.3-12.2-24.6 2.5-6.5 8.5-8.9 14.9-8.9a27.6 27.6 0 0 1 6 .7c12 2.6 23.7 8.6 30.4 10.2a10.7 10.7 0 0 0 2.5 .3c3.6 0 4.9-1.8 4.6-5.9-.8-13.1-2.6-38.7-.6-62.6 2.8-32.9 13.4-49.2 26-63.6 6.1-6.9 34.5-37 88.9-37s82.9 29.9 88.9 36.8c12.6 14.4 23.2 30.7 26 63.6 2.1 23.9 .3 49.5-.6 62.6-.3 4.3 1 5.9 4.6 5.9a10.6 10.6 0 0 0 2.5-.3c6.7-1.6 18.4-7.6 30.4-10.2a27.6 27.6 0 0 1 6-.7c6.4 0 12.4 2.5 14.9 8.9 3.5 9.4-1.2 17-12.2 24.6-6.6 4.6-30.4 19.3-35.8 24.3-5.6 5.1-4.8 9.1-4.2 14.1 .5 4.2 12.5 60.4 87.7 96.7C468.6 373 476.1 377.5 462.9 384.9z" />
          </svg>
        </span>
      </button>

      <!-- Slack -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-[#3eb991] px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 448 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M94.1 315.1c0 25.9-21.2 47.1-47.1 47.1S0 341 0 315.1c0-25.9 21.2-47.1 47.1-47.1h47.1v47.1zm23.7 0c0-25.9 21.2-47.1 47.1-47.1s47.1 21.2 47.1 47.1v117.8c0 25.9-21.2 47.1-47.1 47.1s-47.1-21.2-47.1-47.1V315.1zm47.1-189c-25.9 0-47.1-21.2-47.1-47.1S139 32 164.9 32s47.1 21.2 47.1 47.1v47.1H164.9zm0 23.7c25.9 0 47.1 21.2 47.1 47.1s-21.2 47.1-47.1 47.1H47.1C21.2 244 0 222.8 0 196.9s21.2-47.1 47.1-47.1H164.9zm189 47.1c0-25.9 21.2-47.1 47.1-47.1 25.9 0 47.1 21.2 47.1 47.1s-21.2 47.1-47.1 47.1h-47.1V196.9zm-23.7 0c0 25.9-21.2 47.1-47.1 47.1-25.9 0-47.1-21.2-47.1-47.1V79.1c0-25.9 21.2-47.1 47.1-47.1 25.9 0 47.1 21.2 47.1 47.1V196.9zM283.1 385.9c25.9 0 47.1 21.2 47.1 47.1 0 25.9-21.2 47.1-47.1 47.1-25.9 0-47.1-21.2-47.1-47.1v-47.1h47.1zm0-23.7c-25.9 0-47.1-21.2-47.1-47.1 0-25.9 21.2-47.1 47.1-47.1h117.8c25.9 0 47.1 21.2 47.1 47.1 0 25.9-21.2 47.1-47.1 47.1H283.1z" />
          </svg>
        </span>
      </button>

      <!-- Messenger -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-[#0084ff] px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 512 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M256.6 8C116.5 8 8 110.3 8 248.6c0 72.3 29.7 134.8 78.1 177.9 8.4 7.5 6.6 11.9 8.1 58.2A19.9 19.9 0 0 0 122 502.3c52.9-23.3 53.6-25.1 62.6-22.7C337.9 521.8 504 423.7 504 248.6 504 110.3 396.6 8 256.6 8zm149.2 185.1l-73 115.6a37.4 37.4 0 0 1 -53.9 9.9l-58.1-43.5a15 15 0 0 0 -18 0l-78.4 59.4c-10.5 7.9-24.2-4.6-17.1-15.7l73-115.6a37.4 37.4 0 0 1 53.9-9.9l58.1 43.5a15 15 0 0 0 18 0l78.4-59.4c10.4-8 24.1 4.5 17.1 15.6z" />
          </svg>
        </span>
      </button>

      <!-- Dribbble -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-[#ea4c89] px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 512 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M256 8C119.3 8 8 119.3 8 256s111.3 248 248 248 248-111.3 248-248S392.7 8 256 8zm164 114.4c29.5 36 47.4 82 47.8 132-7-1.5-77-15.7-147.5-6.8-5.8-14-11.2-26.4-18.6-41.6 78.3-32 113.8-77.5 118.3-83.5zM396.4 97.9c-3.8 5.4-35.7 48.3-111 76.5-34.7-63.8-73.2-116.2-79-124 67.2-16.2 138 1.3 190.1 47.5zm-230.5-33.3c5.6 7.7 43.4 60.1 78.5 122.5-99.1 26.3-186.4 25.9-195.8 25.8C62.4 147.2 106.7 92.6 165.9 64.6zM44.2 256.3c0-2.2 0-4.3 .1-6.5 9.3 .2 111.9 1.5 217.7-30.1 6.1 11.9 11.9 23.9 17.2 35.9-76.6 21.6-146.2 83.5-180.5 142.3C64.8 360.4 44.2 310.7 44.2 256.3zm81.8 167.1c22.1-45.2 82.2-103.6 167.6-132.8 29.7 77.3 42 142.1 45.2 160.6-68.1 29-150 21.1-212.8-27.9zm248.4 8.5c-2.2-12.9-13.4-74.9-41.2-151 66.4-10.6 124.7 6.8 131.9 9.1-9.4 58.9-43.3 109.8-90.8 142z" />
          </svg>
        </span>
      </button>

      <!-- Reddit -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-[#ff4500] px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 512 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M373 138.6c-25.2 0-46.3-17.5-51.9-41l0 0c-30.6 4.3-54.2 30.7-54.2 62.4l0 .2c47.4 1.8 90.6 15.1 124.9 36.3c12.6-9.7 28.4-15.5 45.5-15.5c41.3 0 74.7 33.4 74.7 74.7c0 29.8-17.4 55.5-42.7 67.5c-2.4 86.8-97 156.6-213.2 156.6S45.5 410.1 43 323.4C17.6 311.5 0 285.7 0 255.7c0-41.3 33.4-74.7 74.7-74.7c17.2 0 33 5.8 45.7 15.6c34-21.1 76.8-34.4 123.7-36.4l0-.3c0-44.3 33.7-80.9 76.8-85.5C325.8 50.2 347.2 32 373 32c29.4 0 53.3 23.9 53.3 53.3s-23.9 53.3-53.3 53.3zM157.5 255.3c-20.9 0-38.9 20.8-40.2 47.9s17.1 38.1 38 38.1s36.6-9.8 37.8-36.9s-14.7-49.1-35.7-49.1zM395 303.1c-1.2-27.1-19.2-47.9-40.2-47.9s-36.9 22-35.7 49.1c1.2 27.1 16.9 36.9 37.8 36.9s39.3-11 38-38.1zm-60.1 70.8c1.5-3.6-1-7.7-4.9-8.1c-23-2.3-47.9-3.6-73.8-3.6s-50.8 1.3-73.8 3.6c-3.9 .4-6.4 4.5-4.9 8.1c12.9 30.8 43.3 52.4 78.7 52.4s65.8-21.6 78.7-52.4z" />
          </svg>
        </span>
      </button>

      <!-- X -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-black px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 512 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z" />
          </svg>
        </span>
      </button>

      <!-- Whatsapp -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-[#128c7e] px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 448 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
          </svg>
        </span>
      </button>

      <!-- Twitch -->
      <button type="button" data-twe-ripple-init data-twe-ripple-color="light"
        class="mb-1 inline-block rounded bg-[#9146ff] px-6 py-2.5 text-xs font-medium uppercase leading-normal text-white shadow-md transition duration-150 ease-in-out hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg">
        <span class="[&>svg]:h-4 [&>svg]:w-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 512 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
            <path
              d="M391.2 103.5H352.5v109.7h38.6zM285 103H246.4V212.8H285zM120.8 0 24.3 91.4V420.6H140.1V512l96.5-91.4h77.3L487.7 256V0zM449.1 237.8l-77.2 73.1H294.6l-67.6 64v-64H140.1V36.6H449.1z" />
          </svg>
        </span>
      </button>
    </div>
    <div class="actions flex justify-center mt-4 gap-2">
      <button onclick="closeModal('shareModal')"
        class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Close</button>
    </div>
  </div>
</div>

<div id="commentModal" class="modal fixed inset-0 hidden z-50 bg-black bg-opacity-50 flex justify-center items-center">
  <div class="modal-content text-center bg-gray-800 text-text-light rounded-lg p-6 mx-4 max-w-md w-full">
    <h2 class="text-lg font-bold mb-4">Leave Your Comment</h2>
    <textarea placeholder="Write your comment here..." rows="6"
      class="w-full p-2 bg-gray-700 rounded text-white"></textarea>
    <div class="actions flex justify-center mt-4 gap-2">
      <button onclick="closeModal('commentModal')"
        class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Cancel</button>
      <button class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">Submit</button>
    </div>
  </div>
</div>

<div id="reportModal" class="modal fixed inset-0 hidden z-50 bg-black bg-opacity-50 flex justify-center items-center">
  <div class="modal-content text-center bg-gray-800 text-text-light rounded-lg p-6 mx-4 max-w-md w-full">
    <h2 class="text-lg font-bold mb-4">Report a Problem</h2>
    <p>Are you sure you want to report this item?</p>
    <div class="actions flex justify-center mt-4 gap-2">
      <button onclick="closeModal('reportModal')"
        class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Cancel</button>
      <button class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Report</button>
    </div>
  </div>
</div>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="fixed inset-0 hidden z-50 bg-black bg-opacity-50 flex justify-center items-center">
    <div class="bg-gray-800 text-text-light rounded-lg p-6 max-w-md w-full">
      <h2 class="text-lg font-bold mb-4">Confirm Delete</h2>
      <p>Are you sure you want to delete this playlist?</p>
      <div class="flex justify-end mt-4">
        <button id="cancelDelete" class="mr-2 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Cancel</button>
        <button id="confirmDelete" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
      </div>
    </div>
  </div>

  <!-- Add Video to Playlist Modal -->
  <div id="addVideoToPlaylistModal"
    class="fixed inset-0 hidden z-50 bg-black bg-opacity-50 flex justify-center items-center">
    <div class="bg-gray-800 text-text-light rounded-lg p-6 max-w-md w-full">
      <h2 class="text-lg font-bold mb-4">Search a video to add to this playlist</h2>
      <input id="search-video" type="text" placeholder="Enter keywords to search videos"
        class="w-full p-2 bg-gray-700 text-white rounded mb-4" />
      <ul id="search-video-results" class="bg-gray-700 rounded text-white max-h-96 overflow-y-auto">
        <!-- Dropdown will be populated dynamically -->
      </ul>
      <div class="flex justify-end mt-4">
        <button id="cancelAdd" class="mr-2 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
          Cancel
        </button>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
  <script src="/js/playlist.js?<?= randomVersion() ?>"></script>
<?php else: ?>
  <?php if ($debug): ?>
    <script src="/js/playlist.js?<?= randomVersion() ?>"></script>
  <?php else: ?>
    <script src="/js/playlist.js"></script>
  <?php endif; ?>
<?php endif; ?>

<script type="text/javascript" src="/node_modules/tw-elements/js/tw-elements.umd.min.js"></script>
<?php include 'footer.php'; ?>