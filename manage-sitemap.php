<?php
session_start();

require_once "functions.php";

// Check if the user is logged in and has the admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

// Load existing sitemap entries
$sitemapFile = 'sitemap.xml';
$sitemapEntries = [];
if (file_exists($sitemapFile)) {
    $sitemapEntries = simplexml_load_file($sitemapFile);
}

$pageTitle = "Sitemap Management";
$pageDescription = "Manage Sitemap Entries";
$pageKeywords = "";
$canonicalURL = "https://lightup.tv/sitemap";

include 'header.php';
include 'menu.php';
include 'sub-heading.php';
?>

<div class="<?php echo $mainContainerClass ?>">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-sunset-yellow">Manage Sitemap</h1>
        <button id="openModalButton" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Add
            Page</button>
    </div>

    <div class="overflow-x-auto mt-6">
        <table id="sitemapTable" class="min-w-full bg-gray-800 text-gray-300 border border-gray-700 rounded-lg">
            <thead>
                <tr class="bg-gray-700 text-left text-gray-400 uppercase text-sm font-semibold">
                    <th class="w-[50%] px-6 py-3">Path</th>
                    <th class="w-auto px-6 py-3">Last Modified</th>
                    <th class="w-auto px-6 py-3">Priority</th>
                    <th class="w-auto px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                <?php if ($sitemapEntries): ?>
                    <?php foreach ($sitemapEntries->url as $entry): ?>
                        <tr class="hover:bg-gray-700 transition duration-200">
                            <td class="px-6 py-3"><a href="<?php echo htmlspecialchars($entry->loc); ?>"><?php echo htmlspecialchars($entry->loc); ?></a></td>
                            <td class="px-6 py-3"><?php echo htmlspecialchars($entry->lastmod); ?></td>
                            <td class="px-6 py-3"><?php echo htmlspecialchars($entry->priority); ?></td>
                            <td class="px-6 py-3 flex">
                                <button
                                    class="editSitemapEntryButton bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded shadow"
                                    data-path="<?php echo htmlspecialchars($entry->loc); ?>">Edit</button>
                                <button
                                    class="deleteSitemapEntryButton bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded shadow ml-2"
                                    data-path="<?php echo htmlspecialchars($entry->loc); ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-3 text-center text-gray-400">No entries found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="mt-6">
            <label for="jsonSource" class="block text-gray-400 mb-2">Select JSON Source:</label>
            <select id="jsonSource" class="w-full p-2 bg-gray-700 border border-gray-600 rounded">
                <option value="/json/content.json">Videos (content.json)</option>
                <option value="/json/post-content.json">Blog Posts (post-content.json)</option>
            </select>
            <div class="mt-4">
                <button id="scanJsonButton" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Scan
                    and Update Sitemap</button>
            </div>
        </div>

    </div>

</div>

<div id="editPageModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
    <div class="modal-content bg-gray-800 text-white p-6 rounded-lg mx-auto bg-gray-800 w-full sm:w-1/2 relative">
        <h2 class="text-2xl font-bold mb-4">Edit Sitemap Entry</h2>
        <form id="editPageForm">
            <div class="mb-4">
                <label for="editPath" class="block text-gray-400">Path:</label>
                <input type="text" id="editPath" name="path"
                    class="w-full p-2 bg-gray-700 border border-gray-600 rounded" required>
            </div>
            <div class="mb-4">
                <label for="editPriority" class="block text-gray-400">Priority:</label>
                <input type="number" step="0.1" min="0.1" max="1.0" id="editPriority" name="priority"
                    class="w-full p-2 bg-gray-700 border border-gray-600 rounded" required>
            </div>
            <div class="flex justify-end">
                <button type="button" id="closeEditModalButton"
                    class="mr-4 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Save
                    Changes</button>
            </div>
        </form>
    </div>
</div>

<div id="deletePageModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
    <div class="modal-content bg-gray-800 text-white p-6 rounded-lg mx-auto bg-gray-800 w-full sm:w-1/2 relative">
        <h2 class="text-2xl font-bold mb-4">Delete Sitemap Entry</h2>
        <p class="mb-4">Are you sure you want to delete the page: <span id="deletePath"></span>?</p>
        <div class="flex justify-end">
            <button type="button" id="closeDeleteModalButton"
                class="mr-4 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Cancel</button>
            <button id="confirmDeleteButton"
                class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Delete</button>
        </div>
    </div>
</div>

<!-- Modal for Adding Page -->
<div id="addPageModal" class="modal fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
    <div class="modal-content bg-gray-800 text-white p-6 rounded-lg mx-auto bg-gray-800 w-full sm:w-1/2 relative">
        <h2 class="text-2xl font-bold mb-4">Add New Sitemap Entry</h2>
        <form id="addPageForm">
            <div class="mb-4">
                <label for="newPath" class="block text-gray-400">Path:</label>
                <input type="text" id="newPath" name="path"
                    class="w-full p-2 bg-gray-700 border border-gray-600 rounded" required>
            </div>
            <div class="mb-4">
                <label for="newPriority" class="block text-gray-400">Priority:</label>
                <input type="number" step="0.1" min="0.1" max="1.0" id="newPriority" name="priority"
                    class="w-full p-2 bg-gray-700 border border-gray-600 rounded" required>
            </div>
            <div class="flex justify-end">
                <button type="button" id="closeModalButton"
                    class="mr-4 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Add</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Open modal when the "Add Page" button is clicked
    document.getElementById('openModalButton').addEventListener('click', function () {
        document.getElementById('addPageModal').style.display = 'flex'; // Show the modal
    });

    // Close modal when the "Cancel" button is clicked
    document.getElementById('closeModalButton').addEventListener('click', function () {
        document.getElementById('addPageModal').style.display = 'none'; // Hide the modal
    });

    // Close modal when clicking outside of the modal content
    document.getElementById('addPageModal').addEventListener('click', function (event) {
        if (event.target === this) { // Only close when clicking the background, not the modal content
            this.style.display = 'none';
        }
    });

    // Handle Add Page Form Submission
    document.getElementById('addPageForm').addEventListener('submit', function (event) {
        event.preventDefault();

        const pathInput = document.getElementById('newPath');
        const priorityInput = document.getElementById('newPriority');
        const path = pathInput.value;
        const priority = priorityInput.value;

        const newEntry = { path, priority };

        fetch('add-xml-entry', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(newEntry)
        }).then(response => {
            if (response.ok) {
                // Refresh the table to reflect the new entry
                refreshTable();

                // Clear the form fields
                pathInput.value = '';
                priorityInput.value = '';

                // Hide the modal
                document.getElementById('addPageModal').classList.add('hidden');
            } else {
                alert('Failed to add entry.');
            }
        }).catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });

    // Global variables to track the current editing/deleting path
    let currentEditingPath = null;
    let currentDeletingPath = null;

    // Refresh table function
    function refreshTable() {
        fetch('get-sitemap-entries')
            .then(response => response.text())
            .then(html => {
                document.querySelector('#sitemapTable tbody').innerHTML = html;

                // Re-bind events after table refresh
                bindEditButtons();
                bindDeleteButtons();
            })
            .catch(err => console.error('Error refreshing table:', err));
    }

    // Bind Edit button functionality
    function bindEditButtons() {
        document.querySelectorAll('.editSitemapEntryButton').forEach(button => {
            button.addEventListener('click', function () {
                const row = this.closest('tr');
                currentEditingPath = row.querySelector('td:nth-child(1)').innerText;
                const priority = row.querySelector('td:nth-child(3)').innerText;

                // Populate modal with current values
                document.getElementById('editPath').value = currentEditingPath;
                document.getElementById('editPriority').value = priority;

                // Show the edit modal
                document.getElementById('editPageModal').style.display = 'flex';
            });
        });
    }

    // Bind Delete button functionality
    function bindDeleteButtons() {
        document.querySelectorAll('.deleteSitemapEntryButton').forEach(button => {
            button.addEventListener('click', function () {
                const row = this.closest('tr');
                currentDeletingPath = row.querySelector('td:nth-child(1)').innerText;

                // Display path in the delete modal
                document.getElementById('deletePath').innerText = currentDeletingPath;

                // Show the delete modal
                document.getElementById('deletePageModal').style.display = 'flex';
            });
        });
    }

    // Handle Edit Modal Form Submission
    document.getElementById('editPageForm').addEventListener('submit', function (event) {
        event.preventDefault();

        const newPath = document.getElementById('editPath').value;
        const newPriority = document.getElementById('editPriority').value;

        fetch('edit-xml-entry', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ oldPath: currentEditingPath, newPath, priority: newPriority })
        }).then(response => {
            if (response.ok) {
                alert('Page updated successfully!');
                refreshTable();
            } else {
                alert('Failed to update the page.');
            }
        });

        document.getElementById('editPageModal').style.display = 'none';
    });

    // Handle Delete Confirmation
    document.getElementById('confirmDeleteButton').addEventListener('click', function () {
        fetch('delete-xml-entry', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: currentDeletingPath }) // Match with PHP key
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    //alert('Page deleted successfully!');
                    refreshTable(); // Refresh the table to reflect changes
                } else {
                    alert(`Failed to delete the page: ${data.error || 'Unknown error'}`);
                }
            })
            .catch(error => {
                console.error('Error deleting entry:', error);
                alert('An unexpected error occurred.');
            });

        document.getElementById('deletePageModal').style.display = 'none';
    });

    // Close modals
    document.getElementById('closeEditModalButton').addEventListener('click', function () {
        document.getElementById('editPageModal').style.display = 'none';
    });

    document.getElementById('closeDeleteModalButton').addEventListener('click', function () {
        document.getElementById('deletePageModal').style.display = 'none';
    });

    // Initial binding of edit and delete buttons
    bindEditButtons();
    bindDeleteButtons();

    document.getElementById('scanJsonButton').addEventListener('click', function () {
        const source = document.getElementById('jsonSource').value;

        // Fetch the selected JSON file
        fetch(source)
            .then(response => response.json())
            .then(data => {
                const promises = [];

                // Iterate over each entry in the JSON
                for (const uniqueId in data) {
                    const itemPath = source.includes('content.json')
                        ? `item-data/${uniqueId}/${uniqueId}.json`
                        : `post-data/${uniqueId}/${uniqueId}.json`;

                    // Fetch details for each unique item
                    promises.push(
                        fetch(itemPath)
                            .then(itemResponse => itemResponse.json())
                            .then(itemData => {
                                if (!itemData.alias) {
                                    console.warn(`No alias found for item: ${uniqueId}`);
                                    return;
                                }
                                const aliasPath = source.includes('content.json') ? '/video/' + itemData.alias : '/post/' + itemData.alias; // Use 'alias' as the path for sitemap
                                const priority = source.includes('content.json') ? '0.7' : '0.6';

                                // Check and update sitemap
                                return fetch('add-xml-entry', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        path: aliasPath,
                                        priority: priority,
                                    }),
                                }).then(response => {
                                    if (response.ok) {
                                        console.log(`Path added to sitemap: ${aliasPath}`);
                                    } else {
                                        console.warn(`Failed to add path: ${aliasPath}`);
                                    }
                                });
                            })
                            .catch(err => console.error(`Failed to fetch item data for: ${itemPath}`, err))
                    );
                }

                // Wait for all promises to complete
                Promise.all(promises)
                    .then(() => {
                        alert('Sitemap updated successfully!');
                        refreshTable();
                    })
                    .catch(err => console.error('Error updating sitemap:', err));
            })
            .catch(err => console.error('Error fetching JSON source:', err));
    });

</script>

<?php include 'footer.php'; ?>