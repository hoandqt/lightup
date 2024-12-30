document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('categoryModal');
  const modalTitle = document.getElementById('modalTitle');
  const modalAction = document.getElementById('modalAction');
  const categoryId = document.getElementById('categoryId');
  const oldCategoryId = document.getElementById('oldCategoryId');
  const modalName = document.getElementById('modalName');
  const modalMetaTitle = document.getElementById('modalMetaTitle');
  const modalDescription = document.getElementById('modalDescription');
  const modalPathAlias = document.getElementById('modalPathAlias');
  const previewImgDiv = document.getElementById('previewImg');
  const categoryImagePreview = document.getElementById('categoryImagePreview');
  const openModalButton = document.getElementById('openModalButton');
  const closeModalButton = document.getElementById('closeModalButton');

  // Initialize DataTables
  $('#categoriesTable').DataTable({
    // Set the default number of rows
    pageLength: 25, // Default to 10 rows per page
    // Customize the "rows per page" dropdown options
    lengthMenu: [
      [10, 25, 50, 100, -1], // Values
      [10, 25, 50, 100, "All"] // Display labels
    ],
    columns: [
      {
        data: null, orderable: false, render: function (data, type, row) {
          return `<input type="checkbox" class="select-item" value="${row.id}">`;
        }
      },
      { data: "id" },
      { data: "name" },
      { data: "description" },
      { data: "alias" },
      { data: "subcategory" },
      { data: "actions" },
    ],
    order: [[1, "asc"]],
    columnDefs: [
      { orderable: false, targets: [0, -1] }
    ]
  });

  // Open modal to add a new category
  openModalButton.addEventListener('click', () => {
    modalTitle.textContent = 'Add Category';
    modalAction.value = 'add';
    categoryId.value = '';
    modalName.value = '';
    modalMetaTitle.value = '';
    modalDescription.value = '';
    modalPathAlias.value = '';
    categoryImagePreview.src = '';
    categoryImagePreview.classList.add('hidden');
    modal.classList.remove('hidden');
  });

  // Close modal
  closeModalButton.addEventListener('click', () => {
    modal.classList.add('hidden');
  });

  // Submit form via AJAX
  document.getElementById('categoryForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('category-action', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          location.reload();
        } else {
          alert(data.message);
        }
      })
      .catch(error => console.error('Error:', error));
  });

  // Handle edit buttons for dynamic image preview
  const tableContainer = document.getElementById('categoriesTable');
  tableContainer.addEventListener('click', (event) => {
    if (event.target && event.target.classList.contains('editCategoryButton')) {
      const id = event.target.getAttribute('data-id');
      const name = event.target.getAttribute('data-name');
      const metaTitle = event.target.getAttribute('data-meta-title');
      const description = event.target.getAttribute('data-description');
      const pathAlias = event.target.getAttribute('data-path-alias');
      const imagePath = event.target.getAttribute('data-image');

      modalTitle.textContent = 'Edit Category';
      modalAction.value = 'edit';
      categoryId.value = id;
      oldCategoryId.value = id;
      modalName.value = name;
      modalMetaTitle.value = metaTitle;
      modalDescription.value = description;
      modalPathAlias.value = pathAlias;

      if (imagePath) {
        previewImgDiv.classList.remove('hidden');
        categoryImagePreview.src = imagePath;
        categoryImagePreview.classList.remove('hidden');
      } else {
        previewImgDiv.classList.add('hidden');
        categoryImagePreview.src = '';
        categoryImagePreview.classList.add('hidden');
      }

      modal.classList.remove('hidden');
    }
  });

  const deleteModal = document.getElementById('deleteModal');
  const cancelDeleteCategoryButton = document.getElementById('cancelDeleteCategory');
  const confirmDeleteCategoryButton = document.getElementById('confirmDeleteCategory');

  let categoryIdToDelete = null; // Store the ID of the category to delete

  // Show modal and set up the delete action
  tableContainer.addEventListener('click', (event) => {
    if (event.target && event.target.classList.contains('deleteCategoryButton')) {
      categoryIdToDelete = event.target.getAttribute('data-id');
      deleteModal.classList.remove('hidden');
    }
  });

  // Cancel delete action
  cancelDeleteCategoryButton.addEventListener('click', () => {
    categoryIdToDelete = null;
    deleteModal.classList.add('hidden');
  });

  // Confirm delete action
  confirmDeleteCategoryButton.addEventListener('click', () => {
    if (categoryIdToDelete) {
      fetch(`category-action?action=delete&id=${categoryIdToDelete}`, {
        method: 'POST'
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            location.reload();
          } else {
            alert(data.message);
          }
        })
        .catch(error => console.error('Error:', error))
        .finally(() => {
          categoryIdToDelete = null;
          deleteModal.classList.add('hidden');
        });
    }
  });

  // Handle Bulk Update
  $('#bulkUpdateButton').on('click', function () {
    const selectedItems = Array.from(document.querySelectorAll('.select-item:checked')).map(el => el.value);
    document.querySelector('#selectedItemsInput').value = JSON.stringify(selectedItems);
    document.querySelector('#bulkForm').submit();
  });

  // Select All / Deselect All functionality
  const selectAllButton = document.getElementById("selectAllButton");
  const deselectAllButton = document.getElementById("deselectAllButton");

  selectAllButton.addEventListener("click", function () {
    document.querySelectorAll('.select-item').forEach(checkbox => {
      checkbox.checked = true;
    });
  });

  deselectAllButton.addEventListener("click", function () {
    document.querySelectorAll('.select-item').forEach(checkbox => {
      checkbox.checked = false;
    });
  });

  // Handle the "Select All" checkbox functionality
  const selectAllCheckbox = document.getElementById("selectAll");

  // Use event delegation to handle dynamically added checkboxes
  document.addEventListener("change", function (event) {
    if (event.target && event.target.classList.contains("select-item")) {
      // Update "Select All" checkbox based on individual checkboxes
      const allCheckboxes = document.querySelectorAll(".select-item");
      const allChecked = [...allCheckboxes].every(checkbox => checkbox.checked);
      const someChecked = [...allCheckboxes].some(checkbox => checkbox.checked);

      selectAllCheckbox.checked = allChecked;
      selectAllCheckbox.indeterminate = !allChecked && someChecked;
    }
  });

  // Handle "Select All" checkbox
  selectAllCheckbox.addEventListener("change", function () {
    const rowCheckboxes = document.querySelectorAll(".select-item");
    rowCheckboxes.forEach(checkbox => {
      checkbox.checked = selectAllCheckbox.checked;
    });
  });

});