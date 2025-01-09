<?php
session_start();

require_once 'functions.php';

// Check if the user is logged in and has the admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login');
    exit;
}

$pageTitle = "YouTube Playlist Management";
$pageDescription = "Admin page to manage YouTube playlists.";

include 'header.php';
include 'menu.php';
?>

<script src="/js/jquery-3.7.1.min.js"></script>
<script src="//cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<link rel="stylesheet" href="//cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">

<script>
$(document).ready(function() {
    console.log('jQuery version:', $.fn.jquery);
    console.log('DataTables available:', typeof $.fn.DataTable);

    let playlistData = []; // Variable to store playlist data

    const table = $('#yt-playlist').DataTable({
    pageLength: 10,
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
    columns: [
        { 
            data: "thumbnail", 
            orderable: false, 
            render: function(data) {
                return `<img src="${data}" alt="Thumbnail" class="w-16 h-16 object-cover">`;
            } 
        },
        { 
            data: null, // Access both title and video_link properties
            render: function(data) {
                return `<a href="${data.video_link}" target="_blank" class="text-blue-400 hover:underline">${data.title}</a>`;
            } 
        },
        { 
            data: "description", 
            render: function(data, type, row) {
                const jsonStringContent = JSON.stringify(row.description).slice(1, -1);
                return `
                    <span class="description">
                        <span class="full-content hidden">${jsonStringContent}</span>
                        ${data.length > 100 ? data.substring(0, 100) + '... <a href="#" class="read-more-description text-blue-400 hover:underline">Read More</a>' : data}
                    </span>`;
            } 
        },
        { 
            data: "tags", 
            render: function(data, type, row) {
                return `
                    <span class="tags">
                        ${data.length > 50 ? data.substring(0, 50) + '... <a href="#" class="read-more-tags text-blue-400 hover:underline" data-full-content="'+ JSON.stringify(row.tags).slice(1, -1) +'">Read More</a>' : data}
                    </span>`;
            } 
        },
        { 
            data: "channel", 
            render: function(data) {
                return `<span>${data}</span>`;
            } 
        },
        { 
            data: "category_name", 
            render: function(data) {
                return `<span>${data}</span>`;
            } 
        },
        { 
            data: "action", 
            orderable: false, 
            render: function(data) {
                return `<button class="add-video-btn text-green-400 hover:underline" data-index="${data.index}">Add</button>`;
            } 
        }
    ]
});

    document.getElementById("fetchPlaylistBtn").addEventListener("click", function () {
        const playlistInput = document.getElementById("playlistInput").value;
        const playlistId = extractPlaylistId(playlistInput);

        if (!playlistId) {
            alert("Please enter a valid YouTube playlist link or ID.");
            return;
        }

        $.ajax({
            url: "/ajax/fetch-playlist",
            method: "POST",
            data: { playlistId },
            success: function (response) {
                playlistData = JSON.parse(response); // Store the fetched data in the variable
                
                playlistData.forEach((video, index) => {
                    video.action = { index }; // Add index for action buttons
                });

                table.clear().rows.add(playlistData).draw(); // Populate the DataTable
            },
            error: function () {
                alert("Failed to fetch the playlist. Please try again.");
            }
        });
    });

    // Add event listener for dynamically created "Add" buttons
    $('#yt-playlist').on('click', '.add-video-btn', function() {
        const index = $(this).data('index');
        const video = playlistData[index]; // Retrieve the video details using the index
        addVideo(video);
    });

    // Function to extract playlist ID
    function extractPlaylistId(urlOrId) {
        const match = urlOrId.match(/[?&]list=([^&]+)/);
        return match ? match[1] : urlOrId;
    }

    // AJAX function to add a video
    function addVideo(video) {
        $.ajax({
            url: "/ajax/create-video",
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({
                channel: video.channel, // Channel name
                channel_unique_id: video.channel_unique_id, // Channel id
                video_link: video.video_link,
                video_id: video.video_id,
                title: video.title,
                thumbnail: video.thumbnail,
                description: video.description, // Use full description for video creation
                tags: video.tags,
                category: video.category, // Category Id
                category_name: video.category_name
            }),
            success: function(result) {
                if (result.status === "success") {
                    alert(`Video "${video.title}" added successfully with ID: ${result.id}`);
                } else {
                    alert(`Error adding video: ${result.message}`);
                }
            },
            error: function() {
                alert("An error occurred while adding the video.");
            }
        });
    }

    function parseJsonStringContent(jsonString) {
        try {
            // Parse the JSON string and return its value
            return JSON.parse(`"${jsonString}"`);
        } catch (error) {
            console.error("Invalid JSON string:", error);
            return jsonString; // Return original string if parsing fails
        }
    }

    // Show modal with full content
    function showModal(title, content) {
        $('#modal-title').text(title);
        $('#modal-content').html(parseJsonStringContent(content)); // Replace \n with <br>
        $('#modal').removeClass('hidden');
    }

    // Hide modal
    function hideModal() {
        $('#modal').addClass('hidden');
    }

    $(document).on('click', '.read-more-description', function (e) {
        e.preventDefault();
        const fullDescription = $(this).parent().find('.full-content').html();
        showModal('Full Description', fullDescription);
    });

    $(document).on('click', '.read-more-tags', function (e) {
        e.preventDefault();
        const fullTags = $(this).data('full-content');
        showModal('Full Tags', fullTags);
    });

    // Close modal
    $('#modal-close').on('click', hideModal);
    $('#modal-bg').on('click', hideModal);
});
</script>

<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">YouTube Playlist</h1>

    <div class="mb-6">
        <label for="playlistInput" class="block text-lg font-medium text-gray-300 mb-2">Enter YouTube Playlist Link or ID:</label>
        <input type="text" id="playlistInput" class="w-full p-3 bg-gray-800 text-white rounded" placeholder="Enter playlist link or ID">
    </div>

    <button id="fetchPlaylistBtn" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Fetch Playlist</button>

    <div class="mt-6">
        <table id="yt-playlist" class="min-w-full bg-gray-800 text-gray-300 display">
            <thead>
                <tr class="bg-gray-700">
                    <th class="w-[10%]">Thumbnail</th>
                    <th class="w-[20%]">Title</th>
                    <th class="w-[30%]">Description</th>
                    <th class="w-[20%]">Tags</th>
                    <th class="w-[10%]">Channel</th>
                    <th class="w-[5%]">Category</th>
                    <th class="w-[5%]">Action</th>
                </tr>
            </thead>
            <tbody id="playlist-body">
            <!-- Rows will be dynamically added here via JavaScript -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="modal" class="modal fixed inset-0 bg-gray-900 bg-opacity-80 flex items-center justify-center hidden z-50">
    <div id="modal-bg" class="absolute inset-0"></div>
    <div class="modal-content-wrapper relative bg-gray-800 text-text-light rounded-lg p-6 w-full sm:w-1/2">
      <button id="closeModalTopButton" class="absolute top-5 right-5 bg-gray-600 hover:bg-gray-700 text-white rounded-full w-8 h-8 flex items-center justify-center" aria-label="Close Modal">
        ×
      </button>
      <h2 id="modal-title" class="text-2xl font-bold mb-4"></h2>
      <p id="modal-content"></p>
    </div>
</div>

<?php include 'footer.php'; ?>
