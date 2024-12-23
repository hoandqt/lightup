document.addEventListener('DOMContentLoaded', () => {
  const categorySelect = document.getElementById('category');
  const subcategoryContainer = document.getElementById('subcategory-container');
  const subcategorySelect = document.getElementById('subcategory');

  categorySelect.addEventListener('change', function () {
    const categoryId = this.value;

    if (categoryId) {
      // Show the subcategory dropdown
      subcategoryContainer.classList.remove('hidden');

      // Fetch subcategories via AJAX
      fetch(`/fetch-subcategories?category_id=${encodeURIComponent(categoryId)}`)
        .then(response => {
          if (!response.ok) {
            throw new Error('Failed to fetch subcategories');
          }
          return response.json();
        })
        .then(data => {
          // Populate the subcategory dropdown
          subcategorySelect.innerHTML = '<option value="">Select a Subcategory</option>';
          data.subcategories.forEach(subcategory => {
            const option = document.createElement('option');
            option.value = subcategory.id;
            option.textContent = subcategory.name;
            subcategorySelect.appendChild(option);
          });
        })
        .catch(error => {
          console.error('Error fetching subcategories:', error);
          alert('Failed to load subcategories. Please try again.');
        });
    } else {
      // Hide the subcategory dropdown if no category is selected
      subcategoryContainer.classList.add('hidden');
      subcategorySelect.innerHTML = '<option value="">Select a Subcategory</option>';
    }
  });
});