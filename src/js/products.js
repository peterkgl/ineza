document.addEventListener('DOMContentLoaded', function () {
  var productForm = document.getElementById('productForm');
  var productsList = document.getElementById('productsList');
  var alertPlaceholder = document.getElementById('alertPlaceholder');
  var formAlertPlaceholder = document.getElementById('formAlertPlaceholder');
  var formTitle = document.getElementById('formTitle');
  var productIdInput = document.getElementById('productIdInput');
  var productTokenInput = document.getElementById('productToken');
  var cancelBtn = document.getElementById('cancelBtn');
  var saveBtn = document.getElementById('saveBtn');
  var refreshBtn = document.getElementById('refreshBtn');

  var confirmOverlay = document.getElementById('confirmOverlay');
  var confirmBody = document.getElementById('confirmBody');
  var confirmCancelBtn = document.getElementById('confirmCancelBtn');
  var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  
  var activeDeleteId = null;
  var allProducts = [];
  var searchInput = document.getElementById('searchInput');

  var currentPage = 1;
  var itemsPerPage = 25;
  var tableContainer = document.getElementById('productsTable').parentElement;
  var paginationContainer = document.createElement('div');
  paginationContainer.className = 'table-pagination';
  tableContainer.parentNode.insertBefore(paginationContainer, tableContainer.nextSibling);

  function getFilteredProducts() {
    var query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    if (!query) return allProducts;
    return allProducts.filter(function (p) {
      var codeVal = (p.product_code || '').toLowerCase();
      var nameVal = (p.product_name || '').toLowerCase();
      var uomCodeVal = (p.uom_code || '').toLowerCase();
      var uomNameVal = (p.uom_name || '').toLowerCase();
      var invCodeVal = (p.inventory_account_code || '').toLowerCase();
      var invNameVal = (p.inventory_account_name || '').toLowerCase();
      var salCodeVal = (p.sales_account_code || '').toLowerCase();
      var salNameVal = (p.sales_account_name || '').toLowerCase();
      var cogCodeVal = (p.cogs_account_code || '').toLowerCase();
      var cogNameVal = (p.cogs_account_name || '').toLowerCase();
      var statusVal = (p.is_active === 1 ? 'active' : 'inactive').toLowerCase();
      return codeVal.indexOf(query) !== -1 ||
             nameVal.indexOf(query) !== -1 ||
             uomCodeVal.indexOf(query) !== -1 ||
             uomNameVal.indexOf(query) !== -1 ||
             invCodeVal.indexOf(query) !== -1 ||
             invNameVal.indexOf(query) !== -1 ||
             salCodeVal.indexOf(query) !== -1 ||
             salNameVal.indexOf(query) !== -1 ||
             cogCodeVal.indexOf(query) !== -1 ||
             cogNameVal.indexOf(query) !== -1 ||
             statusVal.indexOf(query) !== -1;
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      currentPage = 1;
      renderProducts();
    });
  }

  var isFirstLoad = true;
  function fetchProducts() {
    if (isFirstLoad && window.initialProductsData) {
      isFirstLoad = false;
      allProducts = window.initialProductsData;
      renderProducts();
      updateStats(allProducts);
      return;
    }
    isFirstLoad = false;
    fetch('products_api.php?action=list')
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (result.success) {
          allProducts = result.data;
          renderProducts();
          updateStats(result.data);
          if (result.token) {
            updateToken(result.token);
          }
        } else {
          showAlert(alertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error fetching products:', error);
        showAlert(alertPlaceholder, 'error', 'An error occurred while loading products.');
      });
  }

  function renderProducts() {
    var filtered = getFilteredProducts();
    var totalItems = filtered.length;
    var totalPages = Math.ceil(totalItems / itemsPerPage);
    
    if (currentPage > totalPages) {
      currentPage = Math.max(1, totalPages);
    }
    
    if (totalItems === 0) {
      productsList.innerHTML = '<tr><td colspan="9" class="table-empty">' + (searchInput && searchInput.value.trim() ? 'No matching products found.' : 'No products configured yet.') + '</td></tr>';
      renderPagination(totalItems, totalPages);
      return;
    }

    var startIndex = (currentPage - 1) * itemsPerPage;
    var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
    var paginated = filtered.slice(startIndex, endIndex);

    var html = '';
    paginated.forEach(function (p, index) {
      var globalIndex = startIndex + index + 1;
      var codeVal = escapeHtml(p.product_code);
      var nameVal = escapeHtml(p.product_name);
      var uomVal = escapeHtml(p.uom_code);
      var invVal = p.inventory_account_name ? '<span class="code-badge">' + escapeHtml(p.inventory_account_name) + '</span>' : '—';
      var salVal = p.sales_account_name ? '<span class="code-badge">' + escapeHtml(p.sales_account_name) + '</span>' : '—';
      var cogVal = p.cogs_account_name ? '<span class="code-badge">' + escapeHtml(p.cogs_account_name) + '</span>' : '—';
      
      var statusLabel = p.is_active === 1
        ? '<span class="status-pill pill-green">Active</span>'
        : '<span class="status-pill pill-red">Inactive</span>';

      html += '<tr>' +
        '<td>' + globalIndex + '</td>' +
        '<td><span class="code-badge">' + codeVal + '</span></td>' +
        '<td><strong>' + nameVal + '</strong></td>' +
        '<td>' + uomVal + '</td>' +
        '<td>' + invVal + '</td>' +
        '<td>' + salVal + '</td>' +
        '<td>' + cogVal + '</td>' +
        '<td>' + statusLabel + '</td>' +
        '<td style="text-align: right;">' +
          '<div class="action-buttons" style="justify-content: flex-end;">' +
            '<button class="btn-icon-only edit" title="Edit Product" data-id="' + p.id + '">' +
              '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' +
            '</button>' +
            '<button class="btn-icon-only delete" title="Delete Product" data-id="' + p.id + '">' +
              '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>' +
            '</button>' +
          '</div>' +
        '</td>' +
      '</tr>';
    });

    productsList.innerHTML = html;
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
          renderProducts();
        }
      });
    });
  }

  function updateStats(products) {
    var total = products.length;
    var active = 0;
    var inactive = 0;

    products.forEach(function (p) {
      if (p.is_active === 1) {
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
    if (productTokenInput) {
      productTokenInput.value = token;
    }
  }

  function attachRowEventListeners(products) {
    var editButtons = document.querySelectorAll('.action-buttons .edit');
    var deleteButtons = document.querySelectorAll('.action-buttons .delete');

    editButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var p = products.find(function (x) { return x.id === id; });
        if (p) {
          setEditMode(p);
        }
      });
    });

    deleteButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var p = products.find(function (x) { return x.id === id; });
        if (p) {
          activeDeleteId = id;
          confirmBody.textContent = 'Are you sure you want to delete the product configuration "' + p.product_name + ' (' + p.product_code + ')"? This will fail if elements are configured under this product.';
          confirmOverlay.style.display = 'flex';
        }
      });
    });
  }

  function setEditMode(p) {
    if (!productForm) return;
    
    productIdInput.value = p.id;
    var codeInput = document.getElementById('productCode');
    codeInput.value = p.product_code;
    codeInput.setAttribute('readonly', 'true');
    codeInput.style.opacity = '0.7';

    document.getElementById('productName').value = p.product_name;
    document.getElementById('uomId').value = p.uom_id;
    document.getElementById('inventoryAccountId').value = p.inventory_account_id || '';
    document.getElementById('salesAccountId').value = p.sales_account_id || '';
    document.getElementById('cogsAccountId').value = p.cogs_account_id || '';
    document.getElementById('description').value = p.description || '';
    document.getElementById('productActive').checked = p.is_active === 1;

    formTitle.textContent = 'Edit Product: ' + p.product_code;
    saveBtn.textContent = 'Update Product';
    cancelBtn.style.display = 'inline-block';
    
    if (window.innerWidth <= 992) {
      document.getElementById('formCard').scrollIntoView({ behavior: 'smooth' });
    }
  }

  function resetForm() {
    if (!productForm) return;
    productForm.reset();
    productIdInput.value = '';
    
    var codeInput = document.getElementById('productCode');
    codeInput.removeAttribute('readonly');
    codeInput.style.opacity = '1';
    
    document.getElementById('uomId').value = '';
    document.getElementById('inventoryAccountId').value = '';
    document.getElementById('salesAccountId').value = '';
    document.getElementById('cogsAccountId').value = '';

    formTitle.textContent = 'Add New Product';
    saveBtn.textContent = 'Save Product';
    cancelBtn.style.display = 'none';
    formAlertPlaceholder.innerHTML = '';
  }

  if (productForm) {
    productForm.addEventListener('submit', function (e) {
      e.preventDefault();

      var id = productIdInput.value;
      var product_code = document.getElementById('productCode').value.trim();
      var product_name = document.getElementById('productName').value.trim();
      var uom_id = document.getElementById('uomId').value;
      var inventory_account_id = document.getElementById('inventoryAccountId').value;
      var sales_account_id = document.getElementById('salesAccountId').value;
      var cogs_account_id = document.getElementById('cogsAccountId').value;
      var description = document.getElementById('description').value.trim();
      var active = document.getElementById('productActive').checked ? '1' : '0';
      var token = productTokenInput.value;

      if (!product_code || !product_name || !uom_id) {
        showAlert(formAlertPlaceholder, 'error', 'Product code, product name, and unit of measure are required.');
        return;
      }

      var action = id ? 'update' : 'create';
      
      var formData = new URLSearchParams();
      if (id) formData.append('id', id);
      formData.append('product_code', product_code);
      formData.append('product_name', product_name);
      formData.append('uom_id', uom_id);
      formData.append('inventory_account_id', inventory_account_id);
      formData.append('sales_account_id', sales_account_id);
      formData.append('cogs_account_id', cogs_account_id);
      formData.append('description', description);
      formData.append('is_active', active);
      formData.append('token', token);

      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';

      fetch('products_api.php?action=' + action, {
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
        saveBtn.textContent = id ? 'Update Product' : 'Save Product';

        if (result.token) {
          updateToken(result.token);
        }

        if (result.success) {
          showAlert(alertPlaceholder, 'success', result.message);
          resetForm();
          fetchProducts();
        } else {
          showAlert(formAlertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error saving product:', error);
        saveBtn.disabled = false;
        saveBtn.textContent = id ? 'Update Product' : 'Save Product';
        showAlert(formAlertPlaceholder, 'error', 'An error occurred while saving the product.');
      });
    });
  }

  confirmCancelBtn.addEventListener('click', function () {
    confirmOverlay.style.display = 'none';
    activeDeleteId = null;
  });

  confirmDeleteBtn.addEventListener('click', function () {
    if (!activeDeleteId) return;

    var token = productTokenInput.value;
    var formData = new URLSearchParams();
    formData.append('id', activeDeleteId);
    formData.append('token', token);

    confirmOverlay.style.display = 'none';
    
    fetch('products_api.php?action=delete', {
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
        fetchProducts();
      } else {
        showAlert(alertPlaceholder, 'error', result.message);
      }
      activeDeleteId = null;
    })
    .catch(function (error) {
      console.error('Error deleting product:', error);
      showAlert(alertPlaceholder, 'error', 'An error occurred while deleting the product.');
      activeDeleteId = null;
    });
  });

  if (cancelBtn) {
    cancelBtn.addEventListener('click', resetForm);
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      fetchProducts();
      showAlert(alertPlaceholder, 'success', 'Products list reloaded.');
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

  fetchProducts();
});
