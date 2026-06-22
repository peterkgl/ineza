document.addEventListener('DOMContentLoaded', function () {
  var elementForm = document.getElementById('elementForm');
  var elementsList = document.getElementById('elementsList');
  var alertPlaceholder = document.getElementById('alertPlaceholder');
  var formAlertPlaceholder = document.getElementById('formAlertPlaceholder');
  var formTitle = document.getElementById('formTitle');
  var elementIdInput = document.getElementById('elementIdInput');
  var elementTokenInput = document.getElementById('elementToken');
  var cancelBtn = document.getElementById('cancelBtn');
  var saveBtn = document.getElementById('saveBtn');
  var refreshBtn = document.getElementById('refreshBtn');

  var confirmOverlay = document.getElementById('confirmOverlay');
  var confirmBody = document.getElementById('confirmBody');
  var confirmCancelBtn = document.getElementById('confirmCancelBtn');
  var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  
  var activeDeleteId = null;
  var allElements = [];
  var searchInput = document.getElementById('searchInput');

  var currentPage = 1;
  var itemsPerPage = 25;
  var tableContainer = document.getElementById('elementsTable').parentElement;
  var paginationContainer = document.createElement('div');
  paginationContainer.className = 'table-pagination';
  tableContainer.parentNode.insertBefore(paginationContainer, tableContainer.nextSibling);

  function getFilteredElements() {
    var query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    if (!query) return allElements;
    return allElements.filter(function (e) {
      var prodVal = (e.product_name + ' (' + e.product_code + ')').toLowerCase();
      var codeVal = (e.element_code || '').toLowerCase();
      var nameVal = (e.element_name || '').toLowerCase();
      var unitVal = (e.unit || '').toLowerCase();
      var orderVal = String(e.display_order || '').toLowerCase();
      return prodVal.indexOf(query) !== -1 ||
             codeVal.indexOf(query) !== -1 ||
             nameVal.indexOf(query) !== -1 ||
             unitVal.indexOf(query) !== -1 ||
             orderVal.indexOf(query) !== -1;
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      currentPage = 1;
      renderElements();
    });
  }

  var isFirstLoad = true;
  function fetchElements() {
    if (isFirstLoad && window.initialElementsData) {
      isFirstLoad = false;
      allElements = window.initialElementsData;
      renderElements();
      updateStats(allElements);
      return;
    }
    isFirstLoad = false;
    fetch('elements_api.php?action=list')
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (result.success) {
          allElements = result.data;
          renderElements();
          updateStats(result.data);
          if (result.token) {
            updateToken(result.token);
          }
        } else {
          showAlert(alertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error fetching elements:', error);
        showAlert(alertPlaceholder, 'error', 'An error occurred while loading product elements.');
      });
  }

  function renderElements() {
    var filtered = getFilteredElements();
    var totalItems = filtered.length;
    var totalPages = Math.ceil(totalItems / itemsPerPage);
    
    if (currentPage > totalPages) {
      currentPage = Math.max(1, totalPages);
    }
    
    if (totalItems === 0) {
      elementsList.innerHTML = '<tr><td colspan="7" class="table-empty">' + (searchInput && searchInput.value.trim() ? 'No matching elements found.' : 'No product elements configured yet.') + '</td></tr>';
      renderPagination(totalItems, totalPages);
      return;
    }

    var startIndex = (currentPage - 1) * itemsPerPage;
    var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
    var paginated = filtered.slice(startIndex, endIndex);

    var html = '';
    paginated.forEach(function (e, index) {
      var globalIndex = startIndex + index + 1;
      var prodVal = escapeHtml(e.product_name + ' (' + e.product_code + ')');
      var codeVal = escapeHtml(e.element_code);
      var nameVal = escapeHtml(e.element_name);
      var unitVal = escapeHtml(e.unit);
      var orderVal = parseInt(e.display_order, 10);

      html += '<tr>' +
        '<td>' + globalIndex + '</td>' +
        '<td><strong>' + prodVal + '</strong></td>' +
        '<td><span class="code-badge">' + codeVal + '</span></td>' +
        '<td>' + nameVal + '</td>' +
        '<td>' + unitVal + '</td>' +
        '<td>' + orderVal + '</td>' +
        '<td style="text-align: right;">' +
          '<div class="action-buttons" style="justify-content: flex-end;">' +
            '<button class="btn-icon-only edit" title="Edit Element" data-id="' + e.id + '">' +
              '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' +
            '</button>' +
            '<button class="btn-icon-only delete" title="Delete Element" data-id="' + e.id + '">' +
              '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>' +
            '</button>' +
          '</div>' +
        '</td>' +
      '</tr>';
    });

    elementsList.innerHTML = html;
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
          renderElements();
        }
      });
    });
  }

  function updateStats(elements) {
    var total = elements.length;
    var uniqueProducts = {};
    var uniqueUnits = {};
    var maxOrder = 0;

    elements.forEach(function (e) {
      if (e.product_id) {
        uniqueProducts[e.product_id] = true;
      }
      if (e.unit && e.unit.trim() !== '') {
        uniqueUnits[e.unit.trim().toLowerCase()] = true;
      }
      if (e.display_order > maxOrder) {
        maxOrder = e.display_order;
      }
    });

    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-products').textContent = Object.keys(uniqueProducts).length;
    document.getElementById('stat-units').textContent = Object.keys(uniqueUnits).length;
    document.getElementById('stat-max-order').textContent = maxOrder;
  }

  function updateToken(token) {
    if (elementTokenInput) {
      elementTokenInput.value = token;
    }
  }

  function attachRowEventListeners(elements) {
    var editButtons = document.querySelectorAll('.action-buttons .edit');
    var deleteButtons = document.querySelectorAll('.action-buttons .delete');

    editButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var e = elements.find(function (x) { return x.id === id; });
        if (e) {
          setEditMode(e);
        }
      });
    });

    deleteButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var e = elements.find(function (x) { return x.id === id; });
        if (e) {
          activeDeleteId = id;
          confirmBody.textContent = 'Are you sure you want to delete the product element configuration "' + e.element_name + ' (' + e.element_code + ')"? This action cannot be undone.';
          confirmOverlay.style.display = 'flex';
        }
      });
    });
  }

  function setEditMode(e) {
    if (!elementForm) return;
    
    elementIdInput.value = e.id;
    document.getElementById('productSelect').value = e.product_id;
    document.getElementById('elementCode').value = e.element_code;
    document.getElementById('elementName').value = e.element_name;
    document.getElementById('elementUnit').value = e.unit;
    document.getElementById('displayOrder').value = e.display_order;

    formTitle.textContent = 'Edit Element: ' + e.element_code;
    saveBtn.textContent = 'Update Element';
    cancelBtn.style.display = 'inline-block';
    
    if (window.innerWidth <= 992) {
      document.getElementById('formCard').scrollIntoView({ behavior: 'smooth' });
    }
  }

  function resetForm() {
    if (!elementForm) return;
    elementForm.reset();
    elementIdInput.value = '';
    
    document.getElementById('elementUnit').value = '%';
    document.getElementById('displayOrder').value = '0';

    formTitle.textContent = 'Add Product Element';
    saveBtn.textContent = 'Save Element';
    cancelBtn.style.display = 'none';
    formAlertPlaceholder.innerHTML = '';
  }

  if (elementForm) {
    elementForm.addEventListener('submit', function (ev) {
      ev.preventDefault();

      var id = elementIdInput.value;
      var productId = document.getElementById('productSelect').value;
      var elementCode = document.getElementById('elementCode').value.trim();
      var elementName = document.getElementById('elementName').value.trim();
      var unit = document.getElementById('elementUnit').value.trim();
      var displayOrder = document.getElementById('displayOrder').value.trim();
      var token = elementTokenInput.value;

      if (!productId || !elementCode || !elementName || !unit || displayOrder === '') {
        showAlert(formAlertPlaceholder, 'error', 'All fields are required.');
        return;
      }

      var action = id ? 'update' : 'create';
      
      var formData = new URLSearchParams();
      if (id) formData.append('id', id);
      formData.append('product_id', productId);
      formData.append('element_code', elementCode);
      formData.append('element_name', elementName);
      formData.append('unit', unit);
      formData.append('display_order', displayOrder);
      formData.append('token', token);

      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';

      fetch('elements_api.php?action=' + action, {
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
        saveBtn.textContent = id ? 'Update Element' : 'Save Element';

        if (result.token) {
          updateToken(result.token);
        }

        if (result.success) {
          showAlert(alertPlaceholder, 'success', result.message);
          resetForm();
          fetchElements();
        } else {
          showAlert(formAlertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error saving element:', error);
        saveBtn.disabled = false;
        saveBtn.textContent = id ? 'Update Element' : 'Save Element';
        showAlert(formAlertPlaceholder, 'error', 'An error occurred while saving the product element.');
      });
    });
  }

  confirmCancelBtn.addEventListener('click', function () {
    confirmOverlay.style.display = 'none';
    activeDeleteId = null;
  });

  confirmDeleteBtn.addEventListener('click', function () {
    if (!activeDeleteId) return;

    var token = elementTokenInput.value;
    var formData = new URLSearchParams();
    formData.append('id', activeDeleteId);
    formData.append('token', token);

    confirmOverlay.style.display = 'none';
    
    fetch('elements_api.php?action=delete', {
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
        fetchElements();
      } else {
        showAlert(alertPlaceholder, 'error', result.message);
      }
      activeDeleteId = null;
    })
    .catch(function (error) {
      console.error('Error deleting element:', error);
      showAlert(alertPlaceholder, 'error', 'An error occurred while deleting the product element.');
      activeDeleteId = null;
    });
  });

  if (cancelBtn) {
    cancelBtn.addEventListener('click', resetForm);
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      fetchElements();
      showAlert(alertPlaceholder, 'success', 'Product elements list reloaded.');
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

  fetchElements();
});
