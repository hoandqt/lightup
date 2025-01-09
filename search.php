<?php
session_start();

require_once "functions.php";

$pageTitle = "Search - LightUp.TV";
$pageDescription = "Search videos and posts on LightUp.TV";
$pageKeywords = "search, videos, posts, LightUp.TV, ambience, relaxation";
$canonicalURL = "https://lightup.tv/search";
include 'header.php';
include 'menu.php';
include 'sub-heading.php';
?>

<div class="<?php echo $mainContainerClass ?>">
    <div class="flex items-center mb-6">
        <h1 class="text-3xl font-bold text-sunset-yellow">Search</h1>
        <div id="filtering" class="flex items-center gap-3 ml-auto">
            <div class="view grid-view active flex items-center justify-center w-8 h-8 bg-gray-700 rounded cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 inline-block h-5 text-white">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 1.5v-1.5m0 0c0-.621.504-1.125 1.125-1.125m0 0h7.5" />
                </svg>
            </div>

            <div class="view list-view flex items-center justify-center w-8 h-8 bg-gray-700 rounded cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 inline-block h-5 text-white">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Search Box -->
    <div class="relative mb-8">
      <form id="searchForm">
            <input
                type="text"
                id="searchInput"
                name="keywords"
                value="<?= htmlspecialchars($_GET['keywords'] ?? '') ?>"
                class="w-full p-4 rounded bg-gray-700 text-white placeholder-gray-400 focus:outline-none"
                placeholder="Search videos or posts..."
            />
            <button type="submit" class="hidden">Search</button>
      </form>
    </div>

    <div id="searchResults" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Results will be appended here -->
    </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('input', function() {
    const query = this.value.trim();
    const resultsContainer = document.getElementById('searchResults');

    if (query.length < 3) {
        resultsContainer.innerHTML = '';
        return;
    }

    fetch('/ajax/search-handler', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ query })
    })
    .then(response => response.json())
    .then(data => {
        resultsContainer.innerHTML = data.map(result => `
            <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
                <a href="${result.link}">
                    <img src="${result.thumbnail}" alt="${result.title}" class="w-full h-40 object-cover rounded">
                </a>
                <div class="mt-4">
                    <h2 class="text-lg font-bold text-sunset-yellow">
                        <a href="${result.link}">${result.title}</a>
                    </h2>
                    <p class="text-sm text-gray-400 mt-2">${result.description}</p>
                </div>
            </div>
        `).join('');
    })
    .catch(error => console.error('Error fetching search results:', error));
});

document.getElementById('searchForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent form submission

        const query = document.getElementById('searchInput').value.trim();
        if (!query) return;

        // Update URL with keywords
        const url = new URL(window.location.href);
        url.searchParams.set('keywords', query);
        history.pushState(null, '', url);

        // Fetch search results
        fetch('/ajax/search-handler', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ query })
        })
        .then(response => response.json())
        .then(data => {
            const resultsContainer = document.getElementById('searchResults');
            resultsContainer.innerHTML = data.map(result => `
                <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
                    <a href="${result.link}">
                        <img src="${result.thumbnail}" alt="${result.title}" class="w-full h-40 object-cover rounded">
                    </a>
                    <div class="mt-4">
                        <h2 class="text-lg font-bold text-sunset-yellow">
                            <a href="${result.link}">${result.title}</a>
                        </h2>
                        <p class="text-sm text-gray-400 mt-2">${result.description}</p>
                    </div>
                </div>
            `).join('');
        })
        .catch(error => console.error('Error fetching search results:', error));
    });

    // On page load, fetch results if keywords are present in the URL
    window.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const query = urlParams.get('keywords');
        if (query) {
            document.getElementById('searchInput').value = query;

            // Trigger the search request
            fetch('/ajax/search-handler', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ query })
            })
            .then(response => response.json())
            .then(data => {
                const resultsContainer = document.getElementById('searchResults');
                resultsContainer.innerHTML = data.map(result => `
                    <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
                        <a href="${result.link}">
                            <img src="${result.thumbnail}" alt="${result.title}" class="w-full h-40 object-cover rounded">
                        </a>
                        <div class="mt-4">
                            <h2 class="text-lg font-bold text-sunset-yellow">
                                <a href="${result.link}">${result.title}</a>
                            </h2>
                            <p class="text-sm text-gray-400 mt-2">${result.description}</p>
                        </div>
                    </div>
                `).join('');
            })
            .catch(error => console.error('Error fetching search results:', error));
        }
    });

</script>

<?php include 'footer.php'; ?>
