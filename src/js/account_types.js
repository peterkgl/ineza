document.addEventListener('DOMContentLoaded', function () {
  var accountTypeForm = document.getElementById('accountTypeForm');
  var accountTypesList = document.getElementById('accountTypesList');
  var alertPlaceholder = document.getElementById('alertPlaceholder');
  var formAlertPlaceholder = document.getElementById('formAlertPlaceholder');
  var formTitle = document.getElementById('formTitle');
  var accountTypeIdInput = document.getElementById('accountTypeId');
  var accountTypeTokenInput = document.getElementById('accountTypeToken');
  var parentSelect = document.getElementById('accountTypeParent');
  var codeInput = document.getElementById('accountTypeCode');
  var nameInput = document.getElementById('accountTypeName');
  var cancelBtn = document.getElementById('cancelBtn');
  var saveBtn = document.getElementById('saveBtn');
  var refreshBtn = document.getElementById('refreshBtn');

  var confirmOverlay = document.getElementById('confirmOverlay');
  var confirmBody = document.getElementById('confirmBody');
  var confirmCancelBtn = document.getElementById('confirmCancelBtn');
  var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  
  var activeDeleteId = null;
  var allTypes = [];
  var searchInput = document.getElementById('searchInput');
  var codeTimeout = null;
  var isCodeValid = true;

  var currentPage = 1;
  var itemsPerPage = 25;
  var tableContainer = document.getElementById('accountTypesTable').parentElement;
  var paginationContainer = document.createElement('div');
  paginationContainer.className = 'table-pagination';
  tableContainer.parentNode.insertBefore(paginationContainer, tableContainer.nextSibling);

  function getFilteredAccountTypes() {
    var query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    if (!query) return allTypes;
    return allTypes.filter(function (t) {
      var codeVal = (t.code || '').toLowerCase();
      var nameVal = (t.name || '').toLowerCase();
      var parentVal = t.parent_id 
        ? (t.parent_name + ' (' + t.parent_code + ')').toLowerCase()
        : 'root category';
      var systemVal = (t.is_editable === 0 && t.is_deletable === 0 ? 'system lock' : 'user defined').toLowerCase();
      return codeVal.indexOf(query) !== -1 ||
             nameVal.indexOf(query) !== -1 ||
             parentVal.indexOf(query) !== -1 ||
             systemVal.indexOf(query) !== -1;
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      currentPage = 1;
      renderAccountTypes();
    });
  }

  var isFirstLoad = true;
  function fetchAccountTypes() {
    if (isFirstLoad && window.initialAccountTypesData) {
      isFirstLoad = false;
      allTypes = window.initialAccountTypesData;
      renderAccountTypes();
      populateParentDropdown(allTypes);
      return;
    }
    isFirstLoad = false;
    fetch('account_types_api.php?action=list')
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (result.success) {
          allTypes = result.data;
          renderAccountTypes();
          populateParentDropdown(result.data);
          if (result.token) {
            updateToken(result.token);
          }
        } else {
          showAlert(alertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error fetching account types:', error);
        showAlert(alertPlaceholder, 'error', 'An error occurred while loading account types.');
      });
  }

  function renderAccountTypes() {
    var filtered = getFilteredAccountTypes();
    var totalItems = filtered.length;
    var totalPages = Math.ceil(totalItems / itemsPerPage);
    
    if (currentPage > totalPages) {
      currentPage = Math.max(1, totalPages);
    }
    
    if (totalItems === 0) {
      accountTypesList.innerHTML = '<tr><td colspan="6" class="table-empty">' + (searchInput && searchInput.value.trim() ? 'No matching account types found.' : 'No account types configured yet.') + '</td></tr>';
      renderPagination(totalItems, totalPages);
      return;
    }

    var startIndex = (currentPage - 1) * itemsPerPage;
    var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
    var paginated = filtered.slice(startIndex, endIndex);

    var html = '';
    paginated.forEach(function (t, index) {
      var globalIndex = startIndex + index + 1;
      var systemLabel = t.is_editable === 0 && t.is_deletable === 0
        ? '<span class="status-pill" style="background:var(--blue-bg); color:var(--blue)">System Lock</span>'
        : '<span class="status-pill" style="background:var(--bg); color:var(--text3)">User Defined</span>';
      
      var parentLabel = t.parent_id 
        ? '<span class="parent-type-badge">' + escapeHtml(t.parent_name) + ' (' + escapeHtml(t.parent_code) + ')</span>'
        : '<em style="color:var(--text3); font-size:11.5px;">(Root Category)</em>';

      var editDisabled = t.is_editable === 0 ? 'disabled style="opacity:0.3; cursor:not-allowed;"' : '';
      var deleteDisabled = t.is_deletable === 0 ? 'disabled style="opacity:0.3; cursor:not-allowed;"' : '';

      html += '<tr>' +
        '<td>' + globalIndex + '</td>' +
        '<td><span class="code-badge" style="font-weight:600; font-family:monospace;">' + escapeHtml(t.code) + '</span></td>' +
        '<td class="td-name" style="font-weight:500;">' + escapeHtml(t.name) + '</td>' +
        '<td>' + parentLabel + '</td>' +
        '<td>' + systemLabel + '</td>' +
        '<td style="text-align: right;">' +
          '<div class="action-buttons" style="justify-content: flex-end;">' +
            '<button class="btn-icon-only edit" title="Edit Category" data-id="' + t.id + '" ' + editDisabled + '>' +
              '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' +
            '</button>' +
            '<button class="btn-icon-only delete" title="Delete Category" data-id="' + t.id + '" ' + deleteDisabled + '>' +
              '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>' +
            '</button>' +
          '</div>' +
        '</td>' +
      '</tr>';
    });

    accountTypesList.innerHTML = html;
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
          renderAccountTypes();
        }
      });
    });
  }

  function populateParentDropdown(types, excludeId) {
    if (!parentSelect) return;
    var currentSelected = parentSelect.value;
    var html = '<option value="">(None — Root Category)</option>';
    
    types.forEach(function (t) {
      if (excludeId && t.id === parseInt(excludeId, 10)) {
        return; // skip self
      }
      html += '<option value="' + t.id + '">' + escapeHtml(t.name) + ' (' + escapeHtml(t.code) + ')</option>';
    });
    
    parentSelect.innerHTML = html;
    parentSelect.value = currentSelected;
  }

  function updateToken(token) {
    if (accountTypeTokenInput) {
      accountTypeTokenInput.value = token;
    }
  }

  function attachRowEventListeners(types) {
    var editButtons = document.querySelectorAll('.action-buttons .edit');
    var deleteButtons = document.querySelectorAll('.action-buttons .delete');

    editButtons.forEach(function (btn) {
      if (btn.hasAttribute('disabled')) return;
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var t = types.find(function (item) { return item.id === id; });
        if (t) {
          setEditMode(t);
        }
      });
    });

    deleteButtons.forEach(function (btn) {
      if (btn.hasAttribute('disabled')) return;
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var t = types.find(function (item) { return item.id === id; });
        if (t) {
          activeDeleteId = id;
          confirmBody.textContent = 'Are you sure you want to delete the account type "' + t.name + ' (' + t.code + ')"? This will fail if there are accounts or sub-types belonging to it.';
          confirmOverlay.style.display = 'flex';
        }
      });
    });
  }

  function setEditMode(t) {
    if (!accountTypeForm) return;
    
    accountTypeIdInput.value = t.id;
    codeInput.value = t.code;
    nameInput.value = t.name;
    
    // Repopulate parents dropdown excluding current type to prevent cycle
    populateParentDropdown(allTypes, t.id);
    parentSelect.value = t.parent_id || '';
    
    clearValidationState();

    formTitle.textContent = 'Edit Account Type: ' + t.code;
    saveBtn.textContent = 'Update Account Type';
    cancelBtn.style.display = 'inline-block';
    
    if (window.innerWidth <= 992) {
      document.getElementById('formCard').scrollIntoView({ behavior: 'smooth' });
    }
  }

  function resetForm() {
    if (!accountTypeForm) return;
    accountTypeForm.reset();
    accountTypeIdInput.value = '';
    
    populateParentDropdown(allTypes);
    clearValidationState();

    formTitle.textContent = 'Add New Account Type';
    saveBtn.textContent = 'Save Account Type';
    cancelBtn.style.display = 'none';
    formAlertPlaceholder.innerHTML = '';
  }

  // Live code uniqueness validation
  if (codeInput) {
    codeInput.addEventListener('input', function () {
      clearTimeout(codeTimeout);
      var code = codeInput.value.trim();
      var id = accountTypeIdInput.value;
      
      if (!code) {
        clearValidationState();
        return;
      }

      codeTimeout = setTimeout(function () {
        fetch('account_types_api.php?action=check_code&code=' + encodeURIComponent(code) + '&id=' + id)
          .then(function (response) { return response.json(); })
          .then(function (res) {
            var feedback = document.getElementById('codeValidationFeedback');
            if (res.exists) {
              isCodeValid = false;
              codeInput.className = 'form-control is-invalid';
              feedback.className = 'invalid-feedback';
              feedback.textContent = 'This account type code is already in use.';
              saveBtn.disabled = true;
            } else {
              isCodeValid = true;
              codeInput.className = 'form-control is-valid';
              feedback.className = 'valid-feedback';
              feedback.textContent = 'Code is available.';
              saveBtn.disabled = false;
            }
          })
          .catch(function (err) {
            console.error('Error checking code uniqueness:', err);
          });
      }, 350);
    });
  }

  function clearValidationState() {
    isCodeValid = true;
    if (codeInput) {
      codeInput.className = 'form-control';
    }
    var feedback = document.getElementById('codeValidationFeedback');
    if (feedback) {
      feedback.className = '';
      feedback.textContent = '';
    }
    if (saveBtn) {
      saveBtn.disabled = false;
    }
  }

  if (accountTypeForm) {
    accountTypeForm.addEventListener('submit', function (e) {
      e.preventDefault();

      if (!isCodeValid) {
        showAlert(formAlertPlaceholder, 'error', 'Please resolve the invalid fields before saving.');
        return;
      }

      var id = accountTypeIdInput.value;
      var code = codeInput.value.trim();
      var name = nameInput.value.trim();
      var parent_id = parentSelect.value;
      var token = accountTypeTokenInput.value;

      if (!code || !name) {
        showAlert(formAlertPlaceholder, 'error', 'Code and Name are required fields.');
        return;
      }

      var action = id ? 'update' : 'create';
      
      var formData = new URLSearchParams();
      if (id) formData.append('id', id);
      formData.append('code', code);
      formData.append('name', name);
      formData.append('parent_id', parent_id);
      formData.append('token', token);

      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';

      fetch('account_types_api.php?action=' + action, {
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
        saveBtn.textContent = id ? 'Update Account Type' : 'Save Account Type';

        if (result.token) {
          updateToken(result.token);
        }

        if (result.success) {
          showAlert(alertPlaceholder, 'success', result.message);
          resetForm();
          fetchAccountTypes();
        } else {
          showAlert(formAlertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error saving account type:', error);
        saveBtn.disabled = false;
        saveBtn.textContent = id ? 'Update Account Type' : 'Save Account Type';
        showAlert(formAlertPlaceholder, 'error', 'An error occurred while saving.');
      });
    });
  }

  confirmCancelBtn.addEventListener('click', function () {
    confirmOverlay.style.display = 'none';
    activeDeleteId = null;
  });

  confirmDeleteBtn.addEventListener('click', function () {
    if (!activeDeleteId) return;

    var token = accountTypeTokenInput.value;
    var formData = new URLSearchParams();
    formData.append('id', activeDeleteId);
    formData.append('token', token);

    confirmOverlay.style.display = 'none';
    
    fetch('account_types_api.php?action=delete', {
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
        fetchAccountTypes();
      } else {
        showAlert(alertPlaceholder, 'error', result.message);
      }
      activeDeleteId = null;
    })
    .catch(function (error) {
      console.error('Error deleting account type:', error);
      showAlert(alertPlaceholder, 'error', 'An error occurred while deleting.');
      activeDeleteId = null;
    });
  });

  if (cancelBtn) {
    cancelBtn.addEventListener('click', resetForm);
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      fetchAccountTypes();
      showAlert(alertPlaceholder, 'success', 'Account types list reloaded.');
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

  fetchAccountTypes();
});
