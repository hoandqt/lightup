<?php
require_once "functions.php";

$debug = true; // Set to false in production
$randomVersion = $debug ? '?v=' . rand(1, 999999) : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? 'Welcome to LightUp.TV'; ?></title>

  <?php if (!empty($pageDescription)): ?>
    <meta name="description" content="<?= $pageDescription; ?>">
    <meta property="og:description" content="<?= $pageDescription; ?>">
  <?php endif; ?>

  <?php if (!empty($pageKeywords)): ?>
    <meta name="keywords" content="<?= $pageKeywords; ?>">
  <?php endif; ?>

  <?php if (!empty($pageTitle)): ?>
    <meta property="og:title" content="<?= $pageTitle; ?>">
  <?php endif; ?>

  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= $canonicalURL ?? 'https://www.lightup.tv/'; ?>">
  <?php if (!empty($ogImageURL)): ?>
    <meta property="og:image" content="<?= htmlspecialchars($ogImageURL); ?>">
  <?php endif; ?>
  <?php if (!empty($ogImageAlt)): ?>
    <meta property="og:image:alt" content="<?= htmlspecialchars($ogImageAlt); ?>">
  <?php endif; ?>
  <meta property="og:site_name" content="LightUp.TV">
  <link rel="canonical" href="<?= $canonicalURL ?? 'https://www.lightup.tv/'; ?>">

  <!-- Google Analytics -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-T5QQL0PXXW"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', 'G-T5QQL0PXXW');
  </script>

  <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <script>
      const removedFiles = [];

      function removeFile(fileName) {
        const fileElement = document.getElementById('file-' + fileName);
        if (fileElement) {
          fileElement.remove();
          removedFiles.push(decodeURIComponent(fileName));
          document.getElementById('removed-files').value = JSON.stringify(removedFiles);
        }
      }

      function deleteThumbnail(uniqueId, type) {
        if (!confirm("Are you sure you want to delete this thumbnail?")) {
          return;
        }

        fetch('/ajax/delete-thumbnail', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ unique_id: uniqueId, type: type })
        })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              alert('Thumbnail deleted successfully!');
              document.getElementById('download-thumbnail-checkbox').checked = false;
              location.reload(); // Reload the page to update the UI
            } else {
              alert('Failed to delete thumbnail: ' + data.message);
            }
          })
          .catch(error => {
            console.error('Error deleting thumbnail:', error);
            alert('An unexpected error occurred.');
          });
      }

      function fetchYouTubeThumbnail() {
        const videoLink = document.getElementById('video_link').value; // Get video link
        const thumbnailImg = document.getElementById('youtube-thumbnail');
        const previewContainer = document.getElementById('thumbnail-preview');

        // Regex to extract YouTube video ID
        const videoIdMatch = videoLink.match(/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:watch\?v=|embed\/|v\/)|youtu\.be\/)([\w-]{11})/);
        if (videoIdMatch && videoIdMatch[1]) {
          const videoId = videoIdMatch[1];
          const thumbnailUrl = `https://img.youtube.com/vi/${videoId}/maxresdefault.jpg`;

          // Display thumbnail preview
          thumbnailImg.src = thumbnailUrl;
          thumbnailImg.classList.remove('hidden');

          // Auto-download thumbnail (optional)
          fetch(thumbnailUrl, { mode: 'no-cors' })
            .then(response => response.blob())
            .then(blob => {
              const file = new File([blob], "thumbnail.jpg", { type: "image/jpeg" });
              const dataTransfer = new DataTransfer();
              dataTransfer.items.add(file);
              document.getElementById('thumbnail').files = dataTransfer.files;
            })
            .catch(err => {
              console.log("Failed to fetch the thumbnail. It may not be available.");
              console.error(err);
            });
        } else {
          alert("Invalid YouTube link. Please enter a valid link.");
        }
      }

      async function fetchYouTubeDetails(index) {
        const videoLink = document.getElementById('video_link').value;
        const videoIdMatch = videoLink.match(/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:.*v=|embed\/|v\/)|youtu\.be\/)([\w-]{11})/);
        const videoId = videoIdMatch[1];
        const cleanVideoLink = 'https://www.youtube.com/watch?v='+videoIdMatch[1];
       
        if (!videoIdMatch || !videoIdMatch[1]) {
          alert("Invalid YouTube URL. Please enter a valid YouTube link.");
          return;
        }

        const apiKey = "<?php echo $apiKey ?>";
        const apiUrl = `https://www.googleapis.com/youtube/v3/videos?part=snippet,contentDetails,statistics&id=${videoId}&key=${apiKey}`;

        try {
          const response = await fetch(apiUrl);
          if (!response.ok) throw new Error("Failed to fetch video details.");

          const data = await response.json();
          if (data.items.length === 0) throw new Error("Video not found.");

          const videoDetails = data.items[0].snippet;
          const videoTags = videoDetails.tags ? videoDetails.tags.join(", ") : "";

          // Populate the fields
          document.getElementById('title').value = videoDetails.title;
          document.getElementById('video_link').value = cleanVideoLink;
          document.getElementById('description').value = videoDetails.description;
          document.getElementById('tags').value = videoTags;

          // Fetch Thumbnail
          const thumbnailUrl = videoDetails.thumbnails.maxres?.url || videoDetails.thumbnails.high?.url;
          if (thumbnailUrl) {
            const thumbnailImage = document.getElementById('youtube-thumbnail');
            const thumbnailLink = document.getElementById('thumbnail-download-link');
            const inputthumbnailUrl = document.getElementById('youtube-thumbnail-url');
            const type = 'video';

            if (index == 0) {
              generateImageAlt(thumbnailUrl);
              generateMetadata(videoDetails.title, videoDetails.description, videoTags, type, true);
            }

            thumbnailImage.src = thumbnailUrl;
            thumbnailLink.href = thumbnailUrl; // Set download link
            inputthumbnailUrl.value = thumbnailUrl; // Set the hidden value of youtube thumbnail url
            thumbnailLink.classList.remove('hidden');
          }

          // Handle Channel Name
          const channelUniqueId = videoDetails.channelId;
          const channelTitle = videoDetails.channelTitle;
          if (channelUniqueId && channelTitle) {
            await updateChannelDropdown(channelUniqueId, channelTitle);
          }

          // Handle the category
          const categoryId = data.items[0].snippet.categoryId; // Get the category ID from the API
          if (categoryId) {
            // Retrieve the category name based on ID using a predefined mapping
            const categoryMap = await fetch('/json/youtube-category.json?v=' + Date.now()).then(res => res.json()); // Load mapping from a local JSON file or endpoint
            const categoryName = categoryMap[categoryId] || "Unknown Category";

            if (categoryName) {
              await updateCategoryDropdown(categoryName);
            }
          }
        } catch (error) {
          alert("Error: " + error.message);
          console.error(error);
        }
      }

      async function updateChannelDropdown(channelUniqueId, channelTitle) {
        try {
          // Fetch current channel.json data
          const response = await fetch('/json/channel.json?v=' + Date.now());
          const channels = await response.json();
          console.log(channels);

          // Check if channel exists
          let channelExists = false;
          let channelId = null;

          for (const id in channels) {
            if (channels[id].channel_unique_id === channelUniqueId) {
              channelExists = true;
              channelId = id;

              // Check and add the handle if not exists
              const handle = channels[id].handle;
              if (handle == '') {
                updateChannelHandle(id, channelUniqueId);
              }
              break;
            }
          }

          if (!channelExists) {
            console.log('Channel not exist, adding an entry for: ' + channelUniqueId);

            // Get the channel handle 
            const channelHandle = await fetchChannelHandle(channelUniqueId);
            console.log('channelHandle', channelHandle);

            // Add new channel to channel.json
            console.log('Channels length: ' + Object.keys(channels).length);

            const newId = Object.keys(channels).length + 1;
            const newChannel = {
              name: channelTitle,
              channel_unique_id: channelUniqueId,
              handle: channelHandle,
              channel_type: "",
              channel_description: ""
            };
            console.log('newChannel', newChannel);

            // Send new data to server for updating
            const response = await fetch('/ajax/update-channel', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ id: newId, add_channel: true, data: newChannel})
            });

            if (!response.ok) {
              throw new Error(`Server error: ${response.status}`);
            }
            const data = await response.json();
            if (data.channel) {
              console.log('Added new channel: ' + data.channel.name);
            } else {
              console.error(`Error: ${data.error}`);
            }

            channelId = newId;
          }
          else {
            console.log('Channel ' + channelUniqueId + ' exists!')
          }

          // Update channel dropdown
          const channelSelect = document.getElementById('channel');
          const optionExists = [...channelSelect.options].some(option => option.value == channelId);

          if (!optionExists) {
            const newOption = document.createElement('option');
            newOption.value = channelId;
            newOption.textContent = channelTitle;
            channelSelect.appendChild(newOption);
          }

          channelSelect.value = channelId;
        } catch (error) {
          console.error("Failed to update channel dropdown:", error);
        }
      }

      async function fetchChannelHandle(channelUniqueId) {
        try {
          const response = await fetch('/ajax/fetch-channel-handle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: channelUniqueId })
          });

          if (!response.ok) {
            throw new Error(`Server error: ${response.status}`);
          }
          const data = await response.json();
          if (data.handle) {
            return data.handle;
          } else {
            console.error(`Error: ${data.error}`);
          }
        } catch (error) {
          console.error('Fetch error:', error.message);
        }
      }

      async function updateChannelHandle(id, channelUniqueId) {
        const channelHandle = await fetchChannelHandle(channelUniqueId);
        console.log('channelHandle', channelHandle);

        // Send new data to server for updating
        const response = await fetch('/ajax/update-channel', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: id, update_handle: true, handle: channelHandle })
        });
        if (!response.ok) {
          throw new Error(`Server error: ${response.status}`);
        }
        const data = await response.json();
        if (data.channel) {
          console.log('Channel handler updated!', data.channel);
        } else {
          console.error(`Error: ${data.error}`);
        }
      }

      // Additional JavaScript logic if needed for categories
      async function updateCategoryDropdown(categoryName) {
        try {
          // Fetch current video-category.json data
          const response = await fetch(`/json/video-category.json?v=${Date.now()}`);
          const categories = await response.json();

          // Check if the category exists
          let categoryExists = false;
          let categoryId = null;

          for (const id in categories) {
            if (categories[id].name === categoryName) {
              categoryExists = true;
              categoryId = id;
              break;
            }
          }

          if (!categoryExists) {
            // Add new category to video-category.json
            const newId = Object.keys(categories).length + 1;
            const newCategory = {
              name: categoryName,
              category_description: `Description for ${categoryName}`,
              path_alias: categoryName.toLowerCase().replace(/[^a-z0-9]/g, '-'),
              subcategories: []
            };

            // Send new data to server for updating
            await fetch('/ajax/update-category', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ id: newId, data: newCategory })
            });

            categoryId = newId;
          }

          // Update category dropdown
          const categorySelect = document.getElementById('category');
          const optionExists = [...categorySelect.options].some(option => option.value == categoryId);

          if (!optionExists) {
            const newOption = document.createElement('option');
            newOption.value = categoryId;
            newOption.textContent = categoryName;
            categorySelect.appendChild(newOption);
          }

          categorySelect.value = categoryId;
        } catch (error) {
          console.error("Failed to update category dropdown:", error);
        }
      }

      function generateMetadata(title, description, tags, type, generate_content) {
          if (type == 'playlist') {
            if (!title || !tags) {
                alert('Please fill out the Title and Tags fields before generating metadata.');
                return;
            }
          }
          else {
            if (!title || !description || !tags) {
                alert('Please fill out the Title, Description, and Tags fields before generating metadata.');
                return;
            }
          }
          
          document.getElementById('metadata-loading').classList.remove('hidden');
          document.getElementById('content-loading-1').textContent = 'Working on metadata...';
          document.getElementById('content-loading-1').classList.remove('hidden');

          fetch('/ajax/generate-metadata', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
              },
              body: JSON.stringify({ title, description, tags, type, generate_content }),
          })
          .then(response => response.json())
          .then(data => {
              console.log(data);
              document.getElementById('metadata-loading').classList.add('hidden');
              document.getElementById('content-loading-1').textContent = '';
              document.getElementById('content-loading-1').classList.add('hidden');
              if (data.success) {
                  document.getElementById('meta_title').value = data.meta_title;
                  document.getElementById('meta_description').value = data.meta_description;
                  document.getElementById('meta_keywords').value = data.meta_keywords;
                  if (generate_content) {
                    document.getElementById('new_description').value = data.new_content;
                  }
                  scrollTo('footer');
              } else {
                  alert('Failed to generate metadata: ' + data.message);
              }
          })
          .catch(error => {
              document.getElementById('metadata-loading').classList.add('hidden');
              document.getElementById('content-loading-1').textContent = '';
              document.getElementById('content-loading-1').classList.add('hidden');
              console.error('Error generating metadata:', error);
              alert('An error occurred while generating metadata.');
          });
      }

      function generateImageAlt(imageUrl) {
        if (!imageUrl) {
            alert('Please provide an image URL.');
            return;
        }

        document.getElementById('content-loading').textContent = 'Working on image alt...';
        document.getElementById('content-loading').classList.remove('hidden');

        // Send a POST request using Fetch
        fetch('/ajax/generate-alt', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({ imageUrl: imageUrl })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(data => {
            document.getElementById('content-loading').textContent = '';
            document.getElementById('content-loading').classList.add('hidden');
            document.getElementById('altText').textContent = data;
            document.getElementById('altText').classList.add('text-green-600');
            if (data !== 'none') {
                document.getElementById('og_image_alt').value = data;
            }
        })
        .catch(error => {
            document.getElementById('content-loading').textContent = '';
            document.getElementById('content-loading').classList.add('hidden');
            document.getElementById('altText').textContent = 'Error generating alt text.';
            console.error('There was a problem with the fetch operation:', error);
        });
      }

      // Generate Description, Tags and Metadata from text
      function generateDetails(index, type, inputSelectorId, requirementSelectorId) { // Generate Description and Tags
        document.getElementById('content-loading').textContent = 'Working on playlist details...';
        document.getElementById('content-loading').classList.remove('hidden');

        const input = document.getElementById(inputSelectorId).value;
        const requirement = document.getElementById(requirementSelectorId).value;

        // Send a POST request using Fetch
        fetch('/ajax/generate-details', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ type: type, requirement: requirement, input: input })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log(data);
            if (data.success) {
              document.getElementById('content-loading').textContent = '';
              document.getElementById('content-loading').classList.add('hidden');

              const generatedContent = data.playlist_content;
              const generatedTags = data.playlist_tags;

              document.getElementById('description_content').value = generatedContent;
              document.getElementById('tags').value = generatedTags;

              generateMetadata(input, '', data.playlist_tags, type, false);
            }
            else {
              alert('Failed to generate details: ' + data.message);
            }
        })
        .catch(error => {
            document.getElementById('content-loading').textContent = '';
            document.getElementById('content-loading').classList.add('hidden');
            document.getElementById('altText').textContent = 'Error generating details.';
            console.error('There was a problem with the fetch operation:', error);
        });
      }

      let deleteUniqueId = '';
      function showDeleteModal(uniqueId) {
        deleteUniqueId = uniqueId;
        document.getElementById('deleteModal').classList.remove('hidden');
      }

      // Function to display the modal
      function addVideoToThisPlaylist(playlistUniqueId) {
        document.getElementById('addVideoToPlaylistModal').classList.remove('hidden');

        const searchInput = document.getElementById('search-video');
        const resultsContainer = document.getElementById('search-video-results');
        const modal = document.getElementById('addVideoToPlaylistModal');
        const cancelButton = document.getElementById('cancelAdd');

        let selectedPlaylistId = playlistUniqueId;

        // Event Listener to search videos on input
        let debounceTimer;
        searchInput.addEventListener("input", function () {
          const query = this.value.trim();

          if (debounceTimer) clearTimeout(debounceTimer);

          if (query.length < 2) {
            resultsContainer.classList.add("hidden");
            resultsContainer.innerHTML = '';
            return;
          }

          // Debounce the input to reduce unnecessary requests
          debounceTimer = setTimeout(() => {
            fetch("/ajax/search-handler", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ query }),
            })
              .then((response) => response.json())
              .then((data) => {
                if (data.length > 0) {
                  resultsContainer.innerHTML = data
                    .slice(0, 10) // Show only the top 5 results
                    .map(
                      (item) => `
                                    <li
                                      class="p-2 hover:bg-gray-600 cursor-pointer"
                                      data-unique-id="${item.id}"
                                    >
                                      ${item.title}
                                    </li>
                                `
                    )
                    .join("");
                  resultsContainer.classList.remove("hidden");
                } else {
                  resultsContainer.innerHTML =
                    '<li class="px-4 py-2 text-gray-400">No results found.</li>';
                  resultsContainer.classList.remove("hidden");
                }
              })
              .catch((error) => {
                console.error("Error fetching search results:", error);
              });
          }, 300); // Debounce delay
        });

        // Event Listener to select a video from the dropdown
        resultsContainer.addEventListener('click', async (e) => {
          if (e.target && e.target.dataset.uniqueId) {
            const selectedVideoId = e.target.dataset.uniqueId;

            // Add video to playlist
            await addVideoToPlaylist(selectedPlaylistId, selectedVideoId);
          }
        });

        // Close modal on cancel
        cancelButton.addEventListener('click', () => {
          modal.classList.add('hidden');
        });
      }

      // Function to add video to the playlist using a backend handler
      async function addVideoToPlaylist(playlistUniqueId, videoUniqueId) {
        try {
          // Prepare the data to send to the backend
          const requestData = {
            playlist_unique_id: playlistUniqueId,
            video_unique_id: videoUniqueId,
          };

          // Make a fetch request to the backend handler
          const response = await fetch('/ajax/modify-playlist-handler', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData),
          });

          // Parse the response
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }

          const result = await response.json();

          // Check for success
          if (result.success) {
            alert('Video added to playlist successfully!');
          } else {
            alert(result.message);
          }
        } catch (error) {
          console.error('Error adding video to playlist:', error);
          alert('An error occurred while adding the video to the playlist.');
        }
      }

      document.addEventListener('DOMContentLoaded', () => {
        
        // Fill the requirement on select 
        const selectRequiremntElement = document.getElementById('select_requiremnt');
          if (selectRequiremntElement) {
            document.getElementById('select_requiremnt').addEventListener('change', function () {
            // Get the selected value
            const selectedValue = this.value;

            // Update the #requirement text box with the selected value
            document.getElementById('requirement').value = selectedValue;
          });
        }

        // Handle Create Metadata button
        const createMetadataButton = document.getElementById('create-metadata');
        if (createMetadataButton) {
          createMetadataButton.addEventListener('click', function() {
              const title = document.getElementById('title').value;
              const description = document.getElementById('description').value;
              const tags = document.getElementById('tags').value;
              const include_content = document.getElementById('include_content').checked;
              const type = document.getElementById('type').value;

              generateMetadata(title, description, tags, type, include_content);
          });
        }

        if (document.getElementById('cancelDelete')) {
          document.getElementById('cancelDelete').addEventListener('click', () => {
            document.getElementById('deleteModal').classList.add('hidden');
          });
        }

        if (document.getElementById('confirmDelete')) {
          document.getElementById('confirmDelete').addEventListener('click', () => {
            fetch('/ajax/delete-video', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({ unique_id: deleteUniqueId })
            })
              .then(response => {
                return response.json();
              })
              .then(data => {
                if (data.success) {
                  alert('Video deleted successfully!');
                  window.location.href = '/videos'; // Redirect to /videos
                } else {
                  alert('Error deleting video: ' + data.message);
                }
              })
              .catch(error => {
                alert('An error occurred. (1)');
                console.error('Detailed error information:', error);
              });

            document.getElementById('deleteModal').classList.add('hidden');
          });
        }

        // Close modal functionality
        const closeModalButton = document.getElementById('closeModalButton');
        if (closeModalButton) {
          document.getElementById('closeModalButton').addEventListener('click', () => {
            document.querySelector('.modal').classList.add('hidden');
          });
        }

        const closeModalTopButton = document.getElementById('closeModalTopButton');
        if (closeModalTopButton) {
          document.getElementById('closeModalTopButton').addEventListener('click', () => {
            document.querySelector('.modal').classList.add('hidden');
          });
        }

        // Js to handle the top search box
        const searchBar = document.getElementById("search-bar");
        const dropdown = document.getElementById("search-dropdown");
        let debounceTimer;
        searchBar.addEventListener("input", function () {
          const top_query = this.value.trim();

          if (debounceTimer) clearTimeout(debounceTimer);

          if (top_query.length < 2) {
            dropdown.classList.add("hidden");
            dropdown.innerHTML = "";
            return;
          }

          // Debounce the input to reduce unnecessary requests
          debounceTimer = setTimeout(() => {
            fetch("/ajax/search-handler", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ top_query }),
            })
              .then((response) => response.json())
              .then((data) => {
                if (data.length > 0) {
                  dropdown.innerHTML = data
                    .slice(0, 5) // Show only the top 5 results
                    .map(
                      (item) => `
                                  <a href="${item.link}" class="result-item block px-4 py-2 hover:bg-gray-700">
                                    ${item.title}
                                  </a>
                                `
                    )
                    .join("");
                  dropdown.classList.remove("hidden");
                } else {
                  dropdown.innerHTML =
                    '<p class="px-4 py-2 text-gray-400">No results found.</p>\
                     <p class="px-4 py-2 text-gray-400"><a href="/search">Advanced Search</a></p>';
                  dropdown.classList.remove("hidden");
                }
              })
              .catch((error) => {
                console.error("Error fetching search results:", error);
              });
          }, 300); // Debounce delay
        });

        // Hide dropdown when clicking outside
        document.addEventListener("click", (event) => {
          if (!searchBar.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.add("hidden");
          }
        });

        // Js to handle the playlist search box
        const searchPlaylist = document.getElementById("playlist");
        const playlistDropdown = document.getElementById("playlist-dropdown");
        const selectedPlaylistWrapper = document.querySelector(".selected-content");

        if (searchPlaylist && playlistDropdown) {
          let playlistDebounceTimer;
          searchPlaylist.addEventListener("input", function () {
            const playlist_query = this.value.trim();

            if (playlistDebounceTimer) clearTimeout(playlistDebounceTimer);

            if (playlist_query.length < 2) {
              if (playlist_query.length == 0) {
                if (selectedPlaylistWrapper) {
                  selectedPlaylistWrapper.innerHTML = ''; // Reset the playlist result
                }
              }
              playlistDropdown.classList.add("hidden");
              playlistDropdown.innerHTML = "";
              return;
            }

            // Debounce the input to reduce unnecessary requests
            playlistDebounceTimer = setTimeout(() => {
              fetch("/ajax/search-handler", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ playlist_query }),
              })
                .then((response) => response.json())
                .then((data) => {
                  if (data.length > 0) {
                    playlistDropdown.innerHTML = data
                      .slice(0, 5) // Show only the top 5 results
                      .map(
                        (item) => `
                                    <div class="result-item px-4 py-2 hover:bg-gray-700" data-id="${item.id}" data-link="${item.link}">
                                      <div class="playlist-name inline-block">${item.title}</div>
                                      <a href="${item.link}" class="inline-block">
                                        View playlist
                                      </a>
                                    </div>
                                  `
                      )
                      .join("");

                    // Add event listener to handle clicks on result-item
                    playlistDropdown.addEventListener("click", (event) => {
                      const resultItem = event.target.closest(".result-item");

                      if (resultItem) {
                        const playlistId = resultItem.dataset.id; // Get data-id from clicked item
                        const playlistLink = resultItem.dataset.link; // Get data-link from clicked item
                        const playlistName = resultItem.querySelector(".playlist-name").textContent; // Get the title from clicked item

                        // Set the value of the #playlist input
                        searchPlaylist.value = playlistId;

                        // Set the text of #selected-playlist
                        if (selectedPlaylistWrapper) {
                          selectedPlaylistWrapper.classList.remove('hidden');
                          selectedPlaylistWrapper.innerHTML = `<label class="selected-playlist-label text-gray-400">
                                                                Selected playlist: </label><span id="selected-playlist">
                                                                <a href="${playlistLink}" target="_blank">${playlistName}</a>
                                                                </span>`;
                        }
                      }
                    });
                    playlistDropdown.classList.remove("hidden");
                    } else {
                    playlistDropdown.innerHTML =
                      '<p class="px-4 py-2 text-gray-400">No results found.</p>';
                      playlistDropdown.classList.remove("hidden");
                  }
                })
                .catch((error) => {
                  console.error("Error fetching search results:", error);
                });
            }, 300); // Debounce delay
          });

          // Hide dropdown when clicking outside
          document.addEventListener("click", (event) => {
            if (!searchPlaylist.contains(event.target) && !dropdown.contains(event.target)) {
              playlistDropdown.classList.add("hidden");
            }
          });
        }

      });

    </script>
  <?php endif; ?>
  <script>
    function scrollTo(id) {
      const targetElement = document.getElementById(id);
      if (targetElement) {
        targetElement.scrollIntoView({
          behavior: 'smooth',
          block: 'start',
        });
      } else {
        console.warn(`Element with id "${id}" not found.`);
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      // Close the dropdown of clicking outside
      const dropdownButton = document.getElementById('dropdownButton');
      const dropdownMenu = document.getElementById('dropdownMenu');

      if (dropdownButton && dropdownMenu) {
        // Toggle dropdown visibility
        dropdownButton.addEventListener('click', (event) => {
          dropdownMenu.classList.toggle('hidden');
          event.stopPropagation(); // Prevent the event from bubbling up to the document
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', () => {
          if (!dropdownMenu.classList.contains('hidden')) {
            dropdownMenu.classList.add('hidden');
          }
        });

        // Optional: Close dropdown when pressing the Escape key
        document.addEventListener('keydown', (event) => {
          if (event.key === 'Escape') {
            dropdownMenu.classList.add('hidden');
          }
        });
      }
    });
  </script>

  <script src="https://cdn.tailwindcss.com"></script>

  <link rel="stylesheet" href="/css/style.css<?= $randomVersion; ?>">
</head>

<body class="bg-dark-gray text-text-light">