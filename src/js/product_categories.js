document.addEventListener('DOMContentLoaded', function () {
  var categoryForm = document.getElementById('categoryForm');
  var categoriesList = document.getElementById('categoriesList');
  var alertPlaceholder = document.getElementById('alertPlaceholder');
  var formAlertPlaceholder = document.getElementById('formAlertPlaceholder');
  var formTitle = document.getElementById('formTitle');
  var categoryIdInput = document.getElementById('categoryIdInput');
  var categoryTokenInput = document.getElementById('categoryToken');
  var cancelBtn = document.getElementById('cancelBtn');
  var saveBtn = document.getElementById('saveBtn');
  var refreshBtn = document.getElementById('refreshBtn');

  var confirmOverlay = document.getElementById('confirmOverlay');
  var confirmBody = document.getElementById('confirmBody');
  var confirmCancelBtn = document.getElementById('confirmCancelBtn');
  var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  
  var activeDeleteId = null;
  var allCategories = [];
  var searchInput = document.getElementById('searchInput');

  var currentPage = 1;
  var itemsPerPage = 25;
  var tableContainer = document.getElementById('categoriesTable').parentElement;
  var paginationContainer = document.createElement('div');
  paginationContainer.className = 'table-pagination';
  tableContainer.parentNode.insertBefore(paginationContainer, tableContainer.nextSibling);

  function getFilteredCategories() {
    var query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    if (!query) return allCategories;
    return allCategories.filter(function (c) {
      var codeVal = (c.category_code || '').toLowerCase();
      var nameVal = (c.category_name || '').toLowerCase();
      var descVal = (c.description || '').toLowerCase();
      var statusVal = (c.is_active === 1 ? 'active' : 'inactive').toLowerCase();
      return codeVal.indexOf(query) !== -1 ||
             nameVal.indexOf(query) !== -1 ||
             descVal.indexOf(query) !== -1 ||
             statusVal.indexOf(query) !== -1;
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      currentPage = 1;
      renderCategories();
    });
  }

  var isFirstLoad = true;
  function fetchCategories() {
    if (isFirstLoad && window.initialCategoriesData) {
      isFirstLoad = false;
      allCategories = window.initialCategoriesData;
      renderCategories();
      updateStats(allCategories);
      return;
    }
    isFirstLoad = false;
    fetch('categories_api.php?action=list')
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (result.success) {
          allCategories = result.data;
          renderCategories();
          updateStats(result.data);
          if (result.token) {
            updateToken(result.token);
          }
        } else {
          showAlert(alertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error fetching categories:', error);
        showAlert(alertPlaceholder, 'error', 'An error occurred while loading product categories.');
      });
  }

  function renderCategories() {
    var filtered = getFilteredCategories();
    var totalItems = filtered.length;
    var totalPages = Math.ceil(totalItems / itemsPerPage);
    
    if (currentPage > totalPages) {
      currentPage = Math.max(1, totalPages);
    }
    
    if (totalItems === 0) {
      categoriesList.innerHTML = '<tr><td colspan="6" class="table-empty">' + (searchInput && searchInput.value.trim() ? 'No matching categories found.' : 'No categories configured yet.') + '</td></tr>';
      renderPagination(totalItems, totalPages);
      return;
    }

    var startIndex = (currentPage - 1) * itemsPerPage;
    var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
    var paginated = filtered.slice(startIndex, endIndex);

    var html = '';
    paginated.forEach(function (c, index) {
      var globalIndex = startIndex + index + 1;
      var codeVal = escapeHtml(c.category_code);
      var nameVal = escapeHtml(c.category_name);
      var descVal = c.description ? escapeHtml(c.description) : '—';
      
      var statusLabel = c.is_active === 1
        ? '<span class="status-pill pill-green">Active</span>'
        : '<span class="status-pill pill-red">Inactive</span>';

      html += '<tr>' +
        '<td>' + globalIndex + '</td>' +
        '<td><span class="code-badge">' + codeVal + '</span></td>' +
        '<td><strong>' + nameVal + '</strong></td>' +
        '<td>' + descVal + '</td>' +
        '<td>' + statusLabel + '</td>' +
        '<td style="text-align: right;">' +
          '<div class="action-buttons" style="justify-content: flex-end;">' +
            '<button class="btn-icon-only edit" title="Edit Category" data-id="' + c.id + '">' +
              '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' +
            '</button>' +
            '<button class="btn-icon-only delete" title="Delete Category" data-id="' + c.id + '">' +
              '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>' +
            '</button>' +
          '</div>' +
        '</td>' +
      '</tr>';
    });

    categoriesList.innerHTML = html;
    attachRowEventListeners(paginated);
    renderPagination(totalItems, totalPages);
  }

  function renderPagination(totalItems, totalPages) {
    if (totalPages <= 1) {
      paginationContainer.style.display = 'none';
      return;
    }
    paginationContainer.style.display = 'flex';

    var startIndex = (currentPage - 1) * itemsPerPage + 1;
    var endIndex = Math.min(startIndex + itemsPerPage - 1, totalItems);

    var infoHtml = 'Showing ' + startIndex + ' to ' + endIndex + ' of ' + totalItems + ' entries';
    
    var buttonsHtml = '';
    buttonsHtml += '<button class="pagination-btn" ' + (currentPage === 1 ? 'disabled' : '') + ' data-page="' + (currentPage - 1) + '">&laquo; Prev</button>';

    var startPage = Math.max(1, currentPage - 2);
    var endPage = Math.min(totalPages, startPage + 4);
    if (endPage - startPage < 4) {
      startPage = Math.max(1, endPage - 4);
    }

    if (startPage > 1) {
      buttonsHtml += '<button class="pagination-btn" data-page="1">1</button>';
      if (startPage > 2) {
        buttonsHtml += '<span style="padding: 0 4px; color: var(--text3);">...</span>';
      }
    }

    for (var p = startPage; p <= endPage; p++) {
      buttonsHtml += '<button class="pagination-btn ' + (p === currentPage ? 'active' : '') + '" data-page="' + p + '">' + p + '</button>';
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        buttonsHtml += '<span style="padding: 0 4px; color: var(--text3);">...</span>';
      }
      buttonsHtml += '<button class="pagination-btn" data-page="' + totalPages + '">' + totalPages + '</button>';
    }

    buttonsHtml += '<button class="pagination-btn" ' + (currentPage === totalPages ? 'disabled' : '') + ' data-page="' + (currentPage + 1) + '">Next &raquo;</button>';

    paginationContainer.innerHTML = 
      '<div class="pagination-info">' + infoHtml + '</div>' +
      '<div class="pagination-buttons">' + buttonsHtml + '</div>';

    var buttons = paginationContainer.querySelectorAll('.pagination-btn');
    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var page = parseInt(btn.getAttribute('data-page'), 10);
        if (page && page !== currentPage) {
          currentPage = page;
          renderCategories();
        }
      });
    });
  }

  function updateStats(categories) {
    var total = categories.length;
    var active = 0;
    var inactive = 0;

    categories.forEach(function (c) {
      if (c.is_active === 1) {
        active++;
      } else {
        inactive++;
      }
    });

    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-active').textContent = active;
    document.getElementById('stat-inactive').textContent = inactive;
  }

  function updateToken(token) {
    if (categoryTokenInput) {
      categoryTokenInput.value = token;
    }
  }

  function attachRowEventListeners(categories) {
    var editButtons = document.querySelectorAll('.action-buttons .edit');
    var deleteButtons = document.querySelectorAll('.action-buttons .delete');

    editButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var c = categories.find(function (x) { return x.id === id; });
        if (c) {
          setEditMode(c);
        }
      });
    });

    deleteButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var c = categories.find(function (x) { return x.id === id; });
        if (c) {
          activeDeleteId = id;
          confirmBody.textContent = 'Are you sure you want to delete the product category "' + c.category_name + ' (' + c.category_code + ')"? This will fail if products are configured under this category.';
          confirmOverlay.style.display = 'flex';
        }
      });
    });
  }

  function setEditMode(c) {
    if (!categoryForm) return;
    
    categoryIdInput.value = c.id;
    var codeInput = document.getElementById('categoryCode');
    codeInput.value = c.category_code;
    codeInput.setAttribute('readonly', 'true');
    codeInput.style.opacity = '0.7';

    document.getElementById('categoryName').value = c.category_name;
    document.getElementById('categoryDescription').value = c.description || '';
    document.getElementById('categoryActive').checked = c.is_active === 1;

    formTitle.textContent = 'Edit Category: ' + c.category_code;
    saveBtn.textContent = 'Update Category';
    cancelBtn.style.display = 'inline-block';
    
    if (window.innerWidth <= 992) {
      document.getElementById('formCard').scrollIntoView({ behavior: 'smooth' });
    }
  }

  function resetForm() {
    if (!categoryForm) return;
    categoryForm.reset();
    categoryIdInput.value = '';
    
    var codeInput = document.getElementById('categoryCode');
    codeInput.removeAttribute('readonly');
    codeInput.style.opacity = '1';

    formTitle.textContent = 'Add New Category';
    saveBtn.textContent = 'Save Category';
    cancelBtn.style.display = 'none';
    formAlertPlaceholder.innerHTML = '';
  }

  if (categoryForm) {
    categoryForm.addEventListener('submit', function (e) {
      e.preventDefault();

      var id = categoryIdInput.value;
      var code = document.getElementById('categoryCode').value.trim();
      var name = document.getElementById('categoryName').value.trim();
      var description = document.getElementById('categoryDescription').value.trim();
      var active = document.getElementById('categoryActive').checked ? '1' : '0';
      var token = categoryTokenInput.value;

      if (!code || !name) {
        showAlert(formAlertPlaceholder, 'error', 'Category code and name are required.');
        return;
      }

      var action = id ? 'update' : 'create';
      
      var formData = new URLSearchParams();
      if (id) formData.append('id', id);
      formData.append('category_code', code);
      formData.append('category_name', name);
      formData.append('description', description);
      formData.append('is_active', active);
      formData.append('token', token);

      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';

      fetch('categories_api.php?action=' + action, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: formData.toString()
      })
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        saveBtn.disabled = false;
        saveBtn.textContent = id ? 'Update Category' : 'Save Category';

        if (result.token) {
          updateToken(result.token);
        }

        if (result.success) {
          showAlert(alertPlaceholder, 'success', result.message);
          resetForm();
          fetchCategories();
        } else {
          showAlert(formAlertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error saving category:', error);
        saveBtn.disabled = false;
        saveBtn.textContent = id ? 'Update Category' : 'Save Category';
        showAlert(formAlertPlaceholder, 'error', 'An error occurred while saving the category.');
      });
    });
  }

  confirmCancelBtn.addEventListener('click', function () {
    confirmOverlay.style.display = 'none';
    activeDeleteId = null;
  });

  confirmDeleteBtn.addEventListener('click', function () {
    if (!activeDeleteId) return;

    var token = categoryTokenInput.value;
    var formData = new URLSearchParams();
    formData.append('id', activeDeleteId);
    formData.append('token', token);

    confirmOverlay.style.display = 'none';
    
    fetch('categories_api.php?action=delete', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: formData.toString()
    })
    .then(function (response) {
      return response.json();
    })
    .then(function (result) {
      if (result.token) {
        updateToken(result.token);
      }

      if (result.success) {
        showAlert(alertPlaceholder, 'success', result.message);
        resetForm();
        fetchCategories();
      } else {
        showAlert(alertPlaceholder, 'error', result.message);
      }
      activeDeleteId = null;
    })
    .catch(function (error) {
      console.error('Error deleting category:', error);
      showAlert(alertPlaceholder, 'error', 'An error occurred while deleting the category.');
      activeDeleteId = null;
    });
  });

  if (cancelBtn) {
    cancelBtn.addEventListener('click', resetForm);
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      fetchCategories();
      showAlert(alertPlaceholder, 'success', 'Product categories list reloaded.');
    });
  }

  function showAlert(container, type, message) {
    var icon = type === 'success' 
      ? '<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>'
      : '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    
    container.innerHTML = '<div class="alert-banner ' + type + '">' + icon + '<span>' + escapeHtml(message) + '</span></div>';
    
    if (type === 'success') {
      setTimeout(function () {
        container.innerHTML = '';
      }, 4000);
    }
  }

  function escapeHtml(str) {
    if (!str) return '';
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  fetchCategories();
});
