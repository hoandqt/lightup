<?php
require_once "functions.php";

$debug = true; // Set to false in production
$cssVersion = $debug ? '?v=' . rand(1000, 9999) : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? 'Welcome to LightUp.TV'; ?></title>
  <meta name="description"
    content="<?= $pageDescription ?? 'LightUp.TV - Relaxing ambience videos and immersive soundscapes for your peace and creativity.'; ?>">
  <meta name="keywords"
    content="<?= $pageKeywords ?? 'ambience videos, relaxing music, meditation sounds, mindfulness, LightUp.TV'; ?>">
  <link rel="canonical" href="<?= $canonicalURL ?? 'https://www.lightup.tv/'; ?>">
  <!-- Google Analytics -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-T5QQL0PXXW"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', 'G-T5QQL0PXXW');
  </script>
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

    function deleteThumbnail(uniqueId) {
      if (!confirm("Are you sure you want to delete this thumbnail?")) {
        return;
      }

      fetch('delete-thumbnail', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ unique_id: uniqueId })
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
            const file = new File([blob], "thumbnail_youtube.jpg", { type: "image/jpeg" });
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

    async function fetchYouTubeDetails() {
      const videoLink = document.getElementById('video_link').value;
      const videoIdMatch = videoLink.match(/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:watch\?v=|embed\/|v\/)|youtu\.be\/)([\w-]{11})/);

      if (!videoIdMatch || !videoIdMatch[1]) {
        alert("Invalid YouTube URL. Please enter a valid YouTube link.");
        return;
      }

      const videoId = videoIdMatch[1];
      const apiKey = "<?php echo $apiKey ?>";
      const apiUrl = `https://www.googleapis.com/youtube/v3/videos?part=snippet&id=${videoId}&key=${apiKey}`;

      try {
        const response = await fetch(apiUrl);
        if (!response.ok) throw new Error("Failed to fetch video details.");

        const data = await response.json();
        if (data.items.length === 0) throw new Error("Video not found.");

        const videoDetails = data.items[0].snippet;

        // Populate the fields
        document.getElementById('title').value = videoDetails.title;
        document.getElementById('description').value = videoDetails.description;
        document.getElementById('tags').value = videoDetails.tags ? videoDetails.tags.join(", ") : "";

        // Fetch Thumbnail
        const thumbnailUrl = videoDetails.thumbnails.maxres?.url || videoDetails.thumbnails.high?.url;
        if (thumbnailUrl) {
          const thumbnailImage = document.getElementById('youtube-thumbnail');
          const thumbnailLink = document.getElementById('thumbnail-download-link');
          const inputthumbnailUrl = document.getElementById('youtube-thumbnail-url');

          thumbnailImage.src = thumbnailUrl;
          thumbnailLink.href = thumbnailUrl; // Set download link
          inputthumbnailUrl.value = thumbnailUrl; // Set the hidden value of youtube thumbnail url
          thumbnailLink.classList.remove('hidden');
        }

        // Handle Channel Name
        const channelTitle = videoDetails.channelTitle;
        if (channelTitle) {
          await updateChannelDropdown(channelTitle);
        }

      } catch (error) {
        alert("Error: " + error.message);
        console.error(error);
      }
    }

    async function updateChannelDropdown(channelTitle) {
      try {
        // Fetch current channel.json data
        const response = await fetch('/json/channel.json');
        const channels = await response.json();

        // Check if channel exists
        let channelExists = false;
        let channelId = null;

        for (const id in channels) {
          if (channels[id].name === channelTitle) {
            channelExists = true;
            channelId = id;
            break;
          }
        }

        if (!channelExists) {
          // Add new channel to channel.json
          const newId = Object.keys(channels).length + 1;
          const newChannel = {
            name: channelTitle,
            channel_type: "",
            handle: channelTitle.toLowerCase().replace(/[^a-z0-9]/g, ''),
            channel_description: ""
          };

          // Send new data to server for updating
          await fetch('update-channel', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: newId, data: newChannel })
          });

          channelId = newId;
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

    let deleteUniqueId = '';

    function showDeleteModal(uniqueId) {
      deleteUniqueId = uniqueId;
      document.getElementById('deleteModal').classList.remove('hidden');
    }

    document.addEventListener('DOMContentLoaded', () => {

      if (document.getElementById('cancelDelete')) {
        document.getElementById('cancelDelete').addEventListener('click', () => {
            document.getElementById('deleteModal').classList.add('hidden');
        });
      }

      if (document.getElementById('confirmDelete')) {
        document.getElementById('confirmDelete').addEventListener('click', () => {
            fetch('delete-video', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ unique_id: deleteUniqueId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Video deleted successfully!');
                    location.reload();
                } else {
                    alert('Error deleting video: ' + data.message);
                }
            })
            .catch(error => {
                alert('An error occurred.');
                console.error(error);
            });

            document.getElementById('deleteModal').classList.add('hidden');
        });
      }

    });

  </script>

  <script src="https://cdn.tailwindcss.com"></script>

  <link rel="stylesheet" href="/css/style.css<?= $cssVersion; ?>">
</head>

<body class="bg-dark-gray text-text-light">