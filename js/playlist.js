
document.addEventListener('DOMContentLoaded', () => {
  // Close the dropdown if clicking outside
  const playlistDropdownButton = document.getElementById('playlistDropdownButton');
  const playlistDropdownOptions = document.getElementById('playlistDropdownOptions');

  if (playlistDropdownButton && playlistDropdownOptions) {
    // Toggle dropdown visibility
    playlistDropdownButton.addEventListener('click', (event) => {
      playlistDropdownOptions.classList.toggle('hidden');
      event.stopPropagation(); // Prevent the event from bubbling up to the document
    });

    // Prevent closing dropdown when clicking inside the options
    playlistDropdownOptions.addEventListener('click', (event) => {
      event.stopPropagation(); // Stop propagation for clicks inside the dropdown
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', () => {
      if (!playlistDropdownOptions.classList.contains('hidden')) {
        playlistDropdownOptions.classList.add('hidden');
      }
    });

    // Optional: Close dropdown when pressing the Escape key
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        playlistDropdownOptions.classList.add('hidden');
      }
    });
  }
});

// Function to handle opening modals
function handleModal(modalId, button) {
  const modal = document.getElementById(modalId);
  modal.classList.remove('hidden');
  modal.classList.add('active');
  console.log(`Action triggered for ID: ${button.getAttribute('data-id')}`);
}

// Function to close modals
function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  modal.classList.add('hidden');
  modal.classList.remove('active');
}

// User playlist autoplay option handler
// Function to save autoplay state to localStorage
function saveAutoplayState() {
  const autoplayCheckbox = document.getElementById('autoplay-option');
  localStorage.setItem('autoplay_playlist', autoplayCheckbox.checked ? 'true' : 'false');
}

// Function to load autoplay state from localStorage
function loadAutoplayState() {
  const autoplayCheckbox = document.getElementById('autoplay-option');
  const savedState = localStorage.getItem('autoplay_playlist');
  // Set default to true if no saved state exists
  if (savedState === null) {
    autoplayCheckbox.checked = true;
    // Save the default state to localStorage
    localStorage.setItem('autoplay_playlist', 'true');
  } else {
    autoplayCheckbox.checked = savedState === 'true';
    document.getElementById('playlist-thumbnail').classList.remove('hidden');
  }
}

// Event listener to save state when checkbox is clicked
document.getElementById('autoplay-option').addEventListener('change', saveAutoplayState);

// Load the saved state when the page is loaded
document.addEventListener('DOMContentLoaded', loadAutoplayState);
