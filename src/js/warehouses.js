document.addEventListener('DOMContentLoaded', function () {
  var warehouseForm = document.getElementById('warehouseForm');
  var warehousesList = document.getElementById('warehousesList');
  var alertPlaceholder = document.getElementById('alertPlaceholder');
  var formAlertPlaceholder = document.getElementById('formAlertPlaceholder');
  var formTitle = document.getElementById('formTitle');
  var warehouseIdInput = document.getElementById('warehouseIdInput');
  var warehouseTokenInput = document.getElementById('warehouseToken');
  var cancelBtn = document.getElementById('cancelBtn');
  var saveBtn = document.getElementById('saveBtn');
  var refreshBtn = document.getElementById('refreshBtn');

  var confirmOverlay = document.getElementById('confirmOverlay');
  var confirmBody = document.getElementById('confirmBody');
  var confirmCancelBtn = document.getElementById('confirmCancelBtn');
  var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  
  var activeDeleteId = null;
  var allWarehouses = [];
  var searchInput = document.getElementById('searchInput');

  var currentPage = 1;
  var itemsPerPage = 25;
  var tableContainer = document.getElementById('warehousesTable').parentElement;
  var paginationContainer = document.createElement('div');
  paginationContainer.className = 'table-pagination';
  tableContainer.parentNode.insertBefore(paginationContainer, tableContainer.nextSibling);

  function getFilteredWarehouses() {
    var query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    if (!query) return allWarehouses;
    return allWarehouses.filter(function (w) {
      var codeVal = (w.warehouse_code || '').toLowerCase();
      var nameVal = (w.warehouse_name || '').toLowerCase();
      var addrVal = (w.address || '').toLowerCase();
      var statusVal = (w.is_active === 1 ? 'active' : 'inactive').toLowerCase();
      return codeVal.indexOf(query) !== -1 ||
             nameVal.indexOf(query) !== -1 ||
             addrVal.indexOf(query) !== -1 ||
             statusVal.indexOf(query) !== -1;
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      currentPage = 1;
      renderWarehouses();
    });
  }

  var isFirstLoad = true;
  function fetchWarehouses() {
    if (isFirstLoad && window.initialWarehousesData) {
      isFirstLoad = false;
      allWarehouses = window.initialWarehousesData;
      renderWarehouses();
      updateStats(allWarehouses);
      return;
    }
    isFirstLoad = false;
    fetch('warehouses_api.php?action=list')
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (result.success) {
          allWarehouses = result.data;
          renderWarehouses();
          updateStats(result.data);
          if (result.token) {
            updateToken(result.token);
          }
        } else {
          showAlert(alertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error fetching warehouses:', error);
        showAlert(alertPlaceholder, 'error', 'An error occurred while loading warehouses.');
      });
  }

  function renderWarehouses() {
    var filtered = getFilteredWarehouses();
    var totalItems = filtered.length;
    var totalPages = Math.ceil(totalItems / itemsPerPage);
    
    if (currentPage > totalPages) {
      currentPage = Math.max(1, totalPages);
    }
    
    if (totalItems === 0) {
      warehousesList.innerHTML = '<tr><td colspan="6" class="table-empty">No warehouses found.</td></tr>';
      renderPagination(0, 0, 0);
      return;
    }

    var startIndex = (currentPage - 1) * itemsPerPage;
    var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
    var pageData = filtered.slice(startIndex, endIndex);

    var html = '';
    pageData.forEach(function (w, index) {
      var rowNum = startIndex + index + 1;
      var statusBadge = w.is_active === 1 
        ? '<span class="status-pill pill-green">Active</span>' 
        : '<span class="status-pill pill-red">Inactive</span>';

      var actions = '';
      actions += '<div class="action-buttons" style="justify-content: flex-end;">';
      if (warehouseForm) {
        actions += '<button class="btn-icon-only edit" data-id="' + w.id + '" title="Edit">';
        actions += '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>';
        actions += '</button>';
      }
      if (confirmOverlay) {
        actions += '<button class="btn-icon-only delete" data-id="' + w.id + '" title="Delete">';
        actions += '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>';
        actions += '</button>';
      }
      actions += '</div>';

      html += '<tr>';
      html += '<td>' + rowNum + '</td>';
      html += '<td class="td-bold">' + escapeHtml(w.warehouse_code) + '</td>';
      html += '<td class="td-name">' + escapeHtml(w.warehouse_name) + '</td>';
      html += '<td>' + (w.address ? escapeHtml(w.address) : '—') + '</td>';
      html += '<td>' + statusBadge + '</td>';
      html += '<td style="text-align: right;">' + actions + '</td>';
      html += '</tr>';
    });

    warehousesList.innerHTML = html;
    renderPagination(totalItems, startIndex, endIndex);
    attachActionListeners();
  }

  function renderPagination(totalItems, startIndex, endIndex) {
    if (totalItems === 0) {
      paginationContainer.innerHTML = '';
      return;
    }
    
    var totalPages = Math.ceil(totalItems / itemsPerPage);
    var html = '';
    
    html += '<div class="pagination-info">Showing ' + (startIndex + 1) + ' to ' + endIndex + ' of ' + totalItems + ' entries</div>';
    html += '<div class="pagination-buttons">';
    
    html += '<button class="pagination-btn" id="prevPage" ' + (currentPage === 1 ? 'disabled' : '') + '>&larr;</button>';
    
    for (var i = 1; i <= totalPages; i++) {
      if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
        html += '<button class="pagination-btn ' + (i === currentPage ? 'active' : '') + '" data-page="' + i + '">' + i + '</button>';
      } else if (i === currentPage - 2 || i === currentPage + 2) {
        html += '<span style="padding: 0 4px; align-self: center;">...</span>';
      }
    }
    
    html += '<button class="pagination-btn" id="nextPage" ' + (currentPage === totalPages ? 'disabled' : '') + '>&rarr;</button>';
    html += '</div>';
    
    paginationContainer.innerHTML = html;

    var btns = paginationContainer.querySelectorAll('.pagination-btn[data-page]');
    btns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        currentPage = parseInt(btn.getAttribute('data-page'));
        renderWarehouses();
      });
    });

    var prevBtn = paginationContainer.querySelector('#prevPage');
    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        if (currentPage > 1) {
          currentPage--;
          renderWarehouses();
        }
      });
    }

    var nextBtn = paginationContainer.querySelector('#nextPage');
    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        if (currentPage < totalPages) {
          currentPage++;
          renderWarehouses();
        }
      });
    }
  }

  function attachActionListeners() {
    var editBtns = warehousesList.querySelectorAll('.btn-icon-only.edit');
    editBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'));
        var warehouse = allWarehouses.find(function (w) { return w.id === id; });
        if (warehouse) {
          populateForm(warehouse);
        }
      });
    });

    var deleteBtns = warehousesList.querySelectorAll('.btn-icon-only.delete');
    deleteBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'));
        var warehouse = allWarehouses.find(function (w) { return w.id === id; });
        if (warehouse) {
          activeDeleteId = id;
          confirmBody.textContent = 'Are you sure you want to delete the warehouse "' + warehouse.warehouse_name + '" (' + warehouse.warehouse_code + ')? This action is permanent and cannot be undone.';
          confirmOverlay.style.display = 'flex';
        }
      });
    });
  }

  function populateForm(w) {
    if (!warehouseForm) return;
    
    warehouseIdInput.value = w.id;
    document.getElementById('warehouseCode').value = w.warehouse_code;
    document.getElementById('warehouseName').value = w.warehouse_name;
    document.getElementById('warehouseAddress').value = w.address || '';
    document.getElementById('warehouseActive').checked = (w.is_active === 1);
    
    formTitle.textContent = 'Edit Warehouse: ' + w.warehouse_code;
    saveBtn.textContent = 'Update Warehouse';
    if (cancelBtn) cancelBtn.style.display = 'inline-block';
    
    clearAlerts();
    document.getElementById('warehouseCode').scrollIntoView({ behavior: 'smooth' });
  }

  function resetForm() {
    if (!warehouseForm) return;
    
    warehouseForm.reset();
    warehouseIdInput.value = '';
    formTitle.textContent = 'Add New Warehouse';
    saveBtn.textContent = 'Save Warehouse';
    if (cancelBtn) cancelBtn.style.display = 'none';
    clearAlerts();
  }

  if (cancelBtn) {
    cancelBtn.addEventListener('click', function () {
      resetForm();
    });
  }

  if (warehouseForm) {
    warehouseForm.addEventListener('submit', function (e) {
      e.preventDefault();
      clearAlerts();

      var code = document.getElementById('warehouseCode').value.trim();
      var name = document.getElementById('warehouseName').value.trim();
      var address = document.getElementById('warehouseAddress').value.trim();
      var isActive = document.getElementById('warehouseActive').checked ? '1' : '0';
      var id = warehouseIdInput.value;
      var token = warehouseTokenInput.value;

      if (!code || !name) {
        showAlert(formAlertPlaceholder, 'error', 'Warehouse code and name are required.');
        return;
      }

      var action = id ? 'update' : 'create';
      var formData = new FormData();
      if (id) formData.append('id', id);
      formData.append('warehouse_code', code);
      formData.append('warehouse_name', name);
      formData.append('address', address);
      formData.append('is_active', isActive);
      formData.append('token', token);

      saveBtn.disabled = true;
      saveBtn.textContent = id ? 'Updating...' : 'Saving...';

      fetch('warehouses_api.php?action=' + action, {
        method: 'POST',
        body: formData
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (result) {
          saveBtn.disabled = false;
          saveBtn.textContent = id ? 'Update Warehouse' : 'Save Warehouse';
          
          if (result.token) {
            updateToken(result.token);
          }

          if (result.success) {
            showAlert(alertPlaceholder, 'success', result.message);
            resetForm();
            fetchWarehouses();
          } else {
            showAlert(formAlertPlaceholder, 'error', result.message);
          }
        })
        .catch(function (error) {
          console.error('Error saving warehouse:', error);
          saveBtn.disabled = false;
          saveBtn.textContent = id ? 'Update Warehouse' : 'Save Warehouse';
          showAlert(formAlertPlaceholder, 'error', 'An error occurred while saving.');
        });
    });
  }

  if (confirmCancelBtn) {
    confirmCancelBtn.addEventListener('click', function () {
      confirmOverlay.style.display = 'none';
      activeDeleteId = null;
    });
  }

  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener('click', function () {
      if (!activeDeleteId) return;

      var token = warehouseTokenInput.value;
      var formData = new FormData();
      formData.append('id', activeDeleteId);
      formData.append('token', token);

      confirmDeleteBtn.disabled = true;
      confirmDeleteBtn.textContent = 'Deleting...';

      fetch('warehouses_api.php?action=delete', {
        method: 'POST',
        body: formData
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (result) {
          confirmDeleteBtn.disabled = false;
          confirmDeleteBtn.textContent = 'Delete';
          confirmOverlay.style.display = 'none';
          
          if (result.token) {
            updateToken(result.token);
          }

          if (result.success) {
            showAlert(alertPlaceholder, 'success', result.message);
            activeDeleteId = null;
            resetForm();
            fetchWarehouses();
          } else {
            showAlert(alertPlaceholder, 'error', result.message);
            activeDeleteId = null;
          }
        })
        .catch(function (error) {
          console.error('Error deleting warehouse:', error);
          confirmDeleteBtn.disabled = false;
          confirmDeleteBtn.textContent = 'Delete';
          confirmOverlay.style.display = 'none';
          showAlert(alertPlaceholder, 'error', 'An error occurred while deleting.');
          activeDeleteId = null;
        });
    });
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      fetchWarehouses();
    });
  }

  function updateStats(data) {
    var total = data.length;
    var active = data.filter(function (w) { return w.is_active === 1; }).length;
    var inactive = total - active;

    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-active').textContent = active;
    document.getElementById('stat-inactive').textContent = inactive;
  }

  function updateToken(token) {
    if (warehouseTokenInput) warehouseTokenInput.value = token;
  }

  function clearAlerts() {
    if (alertPlaceholder) alertPlaceholder.innerHTML = '';
    if (formAlertPlaceholder) formAlertPlaceholder.innerHTML = '';
  }

  function showAlert(placeholder, type, message) {
    if (!placeholder) return;
    var icon = type === 'success' 
      ? '<svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
      : '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    
    placeholder.innerHTML = '<div class="alert-banner ' + type + '">' + icon + '<span>' + escapeHtml(message) + '</span></div>';
    setTimeout(function () {
      placeholder.innerHTML = '';
    }, 5000);
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

  fetchWarehouses();
});
