document.addEventListener('DOMContentLoaded', function () {
  var supplierForm = document.getElementById('supplierForm');
  var suppliersList = document.getElementById('suppliersList');
  var alertPlaceholder = document.getElementById('alertPlaceholder');
  var formAlertPlaceholder = document.getElementById('formAlertPlaceholder');
  var formTitle = document.getElementById('formTitle');
  var supplierIdInput = document.getElementById('supplierIdInput');
  var supplierTokenInput = document.getElementById('supplierToken');
  var cancelBtn = document.getElementById('cancelBtn');
  var saveBtn = document.getElementById('saveBtn');
  var refreshBtn = document.getElementById('refreshBtn');

  var confirmOverlay = document.getElementById('confirmOverlay');
  var confirmBody = document.getElementById('confirmBody');
  var confirmCancelBtn = document.getElementById('confirmCancelBtn');
  var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  
  var activeDeleteId = null;
  var allSuppliers = [];
  var searchInput = document.getElementById('searchInput');

  var currentPage = 1;
  var itemsPerPage = 25;
  var tableContainer = document.getElementById('suppliersTable').parentElement;
  var paginationContainer = document.createElement('div');
  paginationContainer.className = 'table-pagination';
  tableContainer.parentNode.insertBefore(paginationContainer, tableContainer.nextSibling);

  function getFilteredSuppliers() {
    var query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    if (!query) return allSuppliers;
    return allSuppliers.filter(function (s) {
      var nameVal = (s.name || '').toLowerCase();
      var typeVal = (s.supplier_type || '').toLowerCase();
      var nifVal = (s.nif || '').toLowerCase();
      var phoneVal = (s.phone || '').toLowerCase();
      var regionVal = (s.region || '').toLowerCase();
      var statusVal = (s.is_active === 1 ? 'active' : 'inactive').toLowerCase();
      return nameVal.indexOf(query) !== -1 ||
             typeVal.indexOf(query) !== -1 ||
             nifVal.indexOf(query) !== -1 ||
             phoneVal.indexOf(query) !== -1 ||
             regionVal.indexOf(query) !== -1 ||
             statusVal.indexOf(query) !== -1;
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      currentPage = 1;
      renderSuppliers();
    });
  }

  function fetchSuppliers() {
    fetch('suppliers_api.php?action=list')
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (result.success) {
          allSuppliers = result.data;
          renderSuppliers();
          updateStats(result.data);
          if (result.token) {
            updateToken(result.token);
          }
        } else {
          showAlert(alertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error fetching suppliers:', error);
        showAlert(alertPlaceholder, 'error', 'An error occurred while loading suppliers.');
      });
  }

  function renderSuppliers() {
    var filtered = getFilteredSuppliers();
    var totalItems = filtered.length;
    var totalPages = Math.ceil(totalItems / itemsPerPage);
    
    if (currentPage > totalPages) {
      currentPage = Math.max(1, totalPages);
    }
    
    if (totalItems === 0) {
      suppliersList.innerHTML = '<tr><td colspan="8" class="table-empty">' + (searchInput && searchInput.value.trim() ? 'No matching suppliers found.' : 'No suppliers configured yet.') + '</td></tr>';
      renderPagination(totalItems, totalPages);
      return;
    }

    var startIndex = (currentPage - 1) * itemsPerPage;
    var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
    var paginated = filtered.slice(startIndex, endIndex);

    var html = '';
    paginated.forEach(function (s, index) {
      var globalIndex = startIndex + index + 1;
      var nameVal = escapeHtml(s.name);
      var typeVal = escapeHtml(s.supplier_type.charAt(0).toUpperCase() + s.supplier_type.slice(1));
      var nifVal = s.nif ? escapeHtml(s.nif) : '—';
      var phoneVal = s.phone ? escapeHtml(s.phone) : '—';
      var regionVal = s.region ? escapeHtml(s.region) : '—';
      
      var statusLabel = s.is_active === 1
        ? '<span class="status-pill pill-green">Active</span>'
        : '<span class="status-pill pill-red">Inactive</span>';

      html += '<tr>' +
        '<td>' + globalIndex + '</td>' +
        '<td><strong>' + nameVal + '</strong></td>' +
        '<td><span class="code-badge">' + typeVal + '</span></td>' +
        '<td>' + nifVal + '</td>' +
        '<td>' + phoneVal + '</td>' +
        '<td>' + regionVal + '</td>' +
        '<td>' + statusLabel + '</td>' +
        '<td style="text-align: right;">' +
          '<div class="action-buttons" style="justify-content: flex-end;">' +
            '<button class="btn-icon-only edit" title="Edit Supplier" data-id="' + s.id + '">' +
              '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' +
            '</button>' +
            '<button class="btn-icon-only delete" title="Delete Supplier" data-id="' + s.id + '">' +
              '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>' +
            '</button>' +
          '</div>' +
        '</td>' +
      '</tr>';
    });

    suppliersList.innerHTML = html;
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
          renderSuppliers();
        }
      });
    });
  }

  function updateStats(suppliers) {
    var total = suppliers.length;
    var active = 0;
    var inactive = 0;
    var coops = 0;

    suppliers.forEach(function (s) {
      if (s.is_active === 1) {
        active++;
      } else {
        inactive++;
      }
      if (s.supplier_type === 'cooperative') {
        coops++;
      }
    });

    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-active').textContent = active;
    document.getElementById('stat-inactive').textContent = inactive;
    document.getElementById('stat-coops').textContent = coops;
  }

  function updateToken(token) {
    if (supplierTokenInput) {
      supplierTokenInput.value = token;
    }
  }

  function attachRowEventListeners(suppliers) {
    var editButtons = document.querySelectorAll('.action-buttons .edit');
    var deleteButtons = document.querySelectorAll('.action-buttons .delete');

    editButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var s = suppliers.find(function (x) { return x.id === id; });
        if (s) {
          setEditMode(s);
        }
      });
    });

    deleteButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var s = suppliers.find(function (x) { return x.id === id; });
        if (s) {
          activeDeleteId = id;
          confirmBody.textContent = 'Are you sure you want to delete the supplier configuration "' + s.name + '"? This will fail if advances are recorded under this supplier.';
          confirmOverlay.style.display = 'flex';
        }
      });
    });
  }

  function setEditMode(s) {
    if (!supplierForm) return;
    
    supplierIdInput.value = s.id;
    document.getElementById('supplierType').value = s.supplier_type;
    document.getElementById('supplierName').value = s.name;
    document.getElementById('supplierNif').value = s.nif || '';
    document.getElementById('supplierVat').value = s.vat_reg_no || '';
    document.getElementById('supplierPhone').value = s.phone || '';
    document.getElementById('supplierEmail').value = s.email || '';
    document.getElementById('supplierAddress').value = s.address || '';
    document.getElementById('supplierRegion').value = s.region || '';
    document.getElementById('supplierNotes').value = s.notes || '';
    document.getElementById('supplierActive').checked = s.is_active === 1;

    formTitle.textContent = 'Edit Supplier: ' + s.name;
    saveBtn.textContent = 'Update Supplier';
    cancelBtn.style.display = 'inline-block';
    
    if (window.innerWidth <= 992) {
      document.getElementById('formCard').scrollIntoView({ behavior: 'smooth' });
    }
  }

  function resetForm() {
    if (!supplierForm) return;
    supplierForm.reset();
    supplierIdInput.value = '';
    
    document.getElementById('supplierType').value = 'individual';
    document.getElementById('supplierActive').checked = true;

    formTitle.textContent = 'Add New Supplier';
    saveBtn.textContent = 'Save Supplier';
    cancelBtn.style.display = 'none';
    formAlertPlaceholder.innerHTML = '';
  }

  if (supplierForm) {
    supplierForm.addEventListener('submit', function (ev) {
      ev.preventDefault();

      var id = supplierIdInput.value;
      var type = document.getElementById('supplierType').value;
      var name = document.getElementById('supplierName').value.trim();
      var nif = document.getElementById('supplierNif').value.trim();
      var vat = document.getElementById('supplierVat').value.trim();
      var phone = document.getElementById('supplierPhone').value.trim();
      var email = document.getElementById('supplierEmail').value.trim();
      var address = document.getElementById('supplierAddress').value.trim();
      var region = document.getElementById('supplierRegion').value.trim();
      var notes = document.getElementById('supplierNotes').value.trim();
      var active = document.getElementById('supplierActive').checked ? '1' : '0';
      var token = supplierTokenInput.value;

      if (!name) {
        showAlert(formAlertPlaceholder, 'error', 'Supplier name is required.');
        return;
      }

      var action = id ? 'update' : 'create';
      
      var formData = new URLSearchParams();
      if (id) formData.append('id', id);
      formData.append('supplier_type', type);
      formData.append('name', name);
      formData.append('nif', nif);
      formData.append('vat_reg_no', vat);
      formData.append('phone', phone);
      formData.append('email', email);
      formData.append('address', address);
      formData.append('region', region);
      formData.append('notes', notes);
      formData.append('is_active', active);
      formData.append('token', token);

      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';

      fetch('suppliers_api.php?action=' + action, {
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
        saveBtn.textContent = id ? 'Update Supplier' : 'Save Supplier';

        if (result.token) {
          updateToken(result.token);
        }

        if (result.success) {
          showAlert(alertPlaceholder, 'success', result.message);
          resetForm();
          fetchSuppliers();
        } else {
          showAlert(formAlertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error saving supplier:', error);
        saveBtn.disabled = false;
        saveBtn.textContent = id ? 'Update Supplier' : 'Save Supplier';
        showAlert(formAlertPlaceholder, 'error', 'An error occurred while saving the supplier.');
      });
    });
  }

  confirmCancelBtn.addEventListener('click', function () {
    confirmOverlay.style.display = 'none';
    activeDeleteId = null;
  });

  confirmDeleteBtn.addEventListener('click', function () {
    if (!activeDeleteId) return;

    var token = supplierTokenInput.value;
    var formData = new URLSearchParams();
    formData.append('id', activeDeleteId);
    formData.append('token', token);

    confirmOverlay.style.display = 'none';
    
    fetch('suppliers_api.php?action=delete', {
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
        fetchSuppliers();
      } else {
        showAlert(alertPlaceholder, 'error', result.message);
      }
      activeDeleteId = null;
    })
    .catch(function (error) {
      console.error('Error deleting supplier:', error);
      showAlert(alertPlaceholder, 'error', 'An error occurred while deleting the supplier.');
      activeDeleteId = null;
    });
  });

  if (cancelBtn) {
    cancelBtn.addEventListener('click', resetForm);
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      fetchSuppliers();
      showAlert(alertPlaceholder, 'success', 'Suppliers list reloaded.');
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

  fetchSuppliers();
});
