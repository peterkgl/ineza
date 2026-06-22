document.addEventListener('DOMContentLoaded', function () {
  var accountForm = document.getElementById('accountForm');
  var accountsList = document.getElementById('accountsList');
  var alertPlaceholder = document.getElementById('alertPlaceholder');
  var formAlertPlaceholder = document.getElementById('formAlertPlaceholder');
  var formTitle = document.getElementById('formTitle');
  var accountIdInput = document.getElementById('accountId');
  var accountTokenInput = document.getElementById('accountToken');
  var typeSelect = document.getElementById('accountType');
  var codeInput = document.getElementById('accountCode');
  var nameInput = document.getElementById('accountName');
  var balanceInput = document.getElementById('openingBalance');
  var descriptionInput = document.getElementById('description');
  var activeCheckbox = document.getElementById('accountIsActive');
  var cancelBtn = document.getElementById('cancelBtn');
  var saveBtn = document.getElementById('saveBtn');
  var refreshBtn = document.getElementById('refreshBtn');

  var confirmOverlay = document.getElementById('confirmOverlay');
  var confirmBody = document.getElementById('confirmBody');
  var confirmCancelBtn = document.getElementById('confirmCancelBtn');
  var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  
  var activeDeleteId = null;
  var allAccounts = [];
  var searchInput = document.getElementById('searchInput');
  var codeTimeout = null;
  
  var isRangeValid = true;
  var isCodeUnique = true;

  var currentPage = 1;
  var itemsPerPage = 25;
  var tableContainer = document.getElementById('accountsTable').parentElement;
  var paginationContainer = document.createElement('div');
  paginationContainer.className = 'table-pagination';
  tableContainer.parentNode.insertBefore(paginationContainer, tableContainer.nextSibling);

  function getFilteredAccounts() {
    var query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    if (!query) return allAccounts;
    return allAccounts.filter(function (a) {
      var codeVal = (a.account_code || '').toLowerCase();
      var nameVal = (a.account_name || '').toLowerCase();
      var typeVal = (a.account_type_name + ' (' + a.account_type_code + ')').toLowerCase();
      var opBal = String(a.opening_balance || '').toLowerCase();
      var statusVal = (a.is_active === 1 ? 'active' : 'inactive').toLowerCase();
      return codeVal.indexOf(query) !== -1 ||
             nameVal.indexOf(query) !== -1 ||
             typeVal.indexOf(query) !== -1 ||
             opBal.indexOf(query) !== -1 ||
             statusVal.indexOf(query) !== -1;
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      currentPage = 1;
      renderAccounts();
    });
  }

  var isFirstLoad = true;
  function fetchAccounts() {
    if (isFirstLoad && window.initialAccountsData) {
      isFirstLoad = false;
      allAccounts = window.initialAccountsData;
      renderAccounts();
      updateStats(allAccounts);
      return;
    }
    isFirstLoad = false;
    fetch('accounts_api.php?action=list')
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (result.success) {
          allAccounts = result.data;
          renderAccounts();
          updateStats(result.data);
          if (result.token) {
            updateToken(result.token);
          }
        } else {
          showAlert(alertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error fetching accounts:', error);
        showAlert(alertPlaceholder, 'error', 'An error occurred while loading accounts.');
      });
  }

  function renderAccounts() {
    if (!allAccounts || allAccounts.length === 0) {
      accountsList.innerHTML = '<tr><td colspan="8" class="table-empty">No accounts registered yet.</td></tr>';
      renderPagination(0, 0);
      return;
    }

    var filtered = getFilteredAccounts();
    var totalItems = filtered.length;
    var totalPages = Math.ceil(totalItems / itemsPerPage);
    
    if (currentPage > totalPages) {
      currentPage = Math.max(1, totalPages);
    }
    
    if (totalItems === 0) {
      accountsList.innerHTML = '<tr><td colspan="7" class="table-empty">' + (searchInput && searchInput.value.trim() ? 'No matching accounts found.' : 'No accounts registered yet.') + '</td></tr>';
      renderPagination(totalItems, totalPages);
      return;
    }

    var startIndex = (currentPage - 1) * itemsPerPage;
    var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
    var paginated = filtered.slice(startIndex, endIndex);

    var html = '';
    paginated.forEach(function (a, index) {
      var globalIndex = startIndex + index + 1;
      var activeLabel = a.is_active === 1
        ? '<span class="status-pill pill-green">Active</span>'
        : '<span class="status-pill pill-red">Inactive</span>';

      html += '<tr>' +
        '<td>' + globalIndex + '</td>' +
        '<td><span class="code-badge" style="font-weight:600; font-family:monospace;">' + escapeHtml(a.account_code) + '</span></td>' +
        '<td class="td-name" style="font-weight:500;">' + escapeHtml(a.account_name) + '</td>' +
        '<td><span class="parent-type-badge">' + escapeHtml(a.account_type_name) + ' (' + escapeHtml(a.account_type_code) + ')</span></td>' +
        '<td style="text-align: right; font-family:monospace;">' + formatMoney(a.opening_balance) + '</td>' +
        '<td>' + activeLabel + '</td>' +
        '<td style="text-align: right;">' +
          '<div class="action-buttons" style="justify-content: flex-end;">' +
            '<button class="btn-icon-only edit" title="Edit Account" data-id="' + a.id + '">' +
              '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' +
            '</button>' +
            '<button class="btn-icon-only delete" title="Delete Account" data-id="' + a.id + '">' +
              '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>' +
            '</button>' +
          '</div>' +
        '</td>' +
      '</tr>';
    });

    accountsList.innerHTML = html;
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
          renderAccounts();
        }
      });
    });
  }

  function updateStats(accounts) {
    var total = accounts.length;
    var active = accounts.filter(function (a) { return a.is_active === 1; }).length;
    var inactive = total - active;
    
    // Sum balances based on opening balance since current_balance is removed
    var balanceSum = accounts.reduce(function (sum, a) { return sum + (a.opening_balance || 0); }, 0);

    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-active').textContent = active;
    document.getElementById('stat-inactive').textContent = inactive;
    document.getElementById('stat-balance').textContent = formatMoney(balanceSum);
  }

  function updateToken(token) {
    if (accountTokenInput) {
      accountTokenInput.value = token;
    }
  }

  function attachRowEventListeners(accounts) {
    var editButtons = document.querySelectorAll('.action-buttons .edit');
    var deleteButtons = document.querySelectorAll('.action-buttons .delete');

    editButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var a = accounts.find(function (item) { return item.id === id; });
        if (a) {
          setEditMode(a);
        }
      });
    });

    deleteButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var a = accounts.find(function (item) { return item.id === id; });
        if (a) {
          activeDeleteId = id;
          confirmBody.textContent = 'Are you sure you want to delete the account "' + a.account_name + ' (' + a.account_code + ')"? This action cannot be undone.';
          confirmOverlay.style.display = 'flex';
        }
      });
    });
  }

  function setEditMode(a) {
    if (!accountForm) return;
    
    accountIdInput.value = a.id;
    typeSelect.value = a.account_type_id;
    codeInput.value = a.account_code;
    nameInput.value = a.account_name;
    balanceInput.value = a.opening_balance.toFixed(2);
    descriptionInput.value = a.description || '';
    activeCheckbox.checked = a.is_active === 1;

    clearValidationState();

    formTitle.textContent = 'Edit Account: ' + a.account_code;
    saveBtn.textContent = 'Update Account';
    cancelBtn.style.display = 'inline-block';
    
    if (window.innerWidth <= 992) {
      document.getElementById('formCard').scrollIntoView({ behavior: 'smooth' });
    }
  }

  function resetForm() {
    if (!accountForm) return;
    accountForm.reset();
    accountIdInput.value = '';
    balanceInput.value = '0.00';
    clearValidationState();

    formTitle.textContent = 'Add New Account';
    saveBtn.textContent = 'Save Account';
    cancelBtn.style.display = 'none';
    formAlertPlaceholder.innerHTML = '';
  }

  // Auto generate code based on selected type category
  function autoGenerateCode() {
    var typeVal = typeSelect.value;
    var id = accountIdInput.value;
    var feedback = document.getElementById('codeValidationFeedback');
    
    if (id) {
      // In edit mode, code is already set and is read-only.
      return;
    }

    if (!typeVal) {
      codeInput.value = '';
      clearValidationState();
      return;
    }

    codeInput.value = 'Generating...';
    saveBtn.disabled = true;

    fetch('accounts_api.php?action=generate_code&account_type_id=' + typeVal)
      .then(function (response) { return response.json(); })
      .then(function (res) {
        if (res.success) {
          codeInput.value = res.data.code;
          isRangeValid = true;
          isCodeUnique = true;
          codeInput.className = 'form-control is-valid';
          feedback.className = 'valid-feedback';
          feedback.textContent = 'Code generated successfully.';
          saveBtn.disabled = false;
        } else {
          codeInput.value = '';
          isRangeValid = false;
          codeInput.className = 'form-control is-invalid';
          feedback.className = 'invalid-feedback';
          feedback.textContent = res.message;
          saveBtn.disabled = true;
        }
      })
      .catch(function (err) {
        console.error('Error generating code:', err);
        codeInput.value = '';
        isRangeValid = false;
        codeInput.className = 'form-control is-invalid';
        feedback.className = 'invalid-feedback';
        feedback.textContent = 'An error occurred while generating account code.';
        saveBtn.disabled = true;
      });
  }

  if (typeSelect) {
    typeSelect.addEventListener('change', autoGenerateCode);
  }

  function clearValidationState() {
    isRangeValid = true;
    isCodeUnique = true;
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

  if (accountForm) {
    accountForm.addEventListener('submit', function (e) {
      e.preventDefault();

      if (!isRangeValid || !isCodeUnique) {
        showAlert(formAlertPlaceholder, 'error', 'Please resolve the invalid fields before saving.');
        return;
      }

      var id = accountIdInput.value;
      var account_type_id = typeSelect.value;
      var account_code = codeInput.value.trim();
      var account_name = nameInput.value.trim();
      var opening_balance = balanceInput.value.trim();
      var description = descriptionInput.value.trim();
      var is_active = activeCheckbox.checked ? '1' : '0';
      var token = accountTokenInput.value;

      if (!account_type_id || !account_code || !account_name) {
        showAlert(formAlertPlaceholder, 'error', 'Account Type, Code, and Name are required fields.');
        return;
      }

      var action = id ? 'update' : 'create';
      
      var formData = new URLSearchParams();
      if (id) formData.append('id', id);
      formData.append('account_type_id', account_type_id);
      formData.append('account_code', account_code);
      formData.append('account_name', account_name);
      formData.append('opening_balance', opening_balance);
      formData.append('description', description);
      formData.append('is_active', is_active);
      formData.append('token', token);

      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';

      fetch('accounts_api.php?action=' + action, {
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
        saveBtn.textContent = id ? 'Update Account' : 'Save Account';

        if (result.token) {
          updateToken(result.token);
        }

        if (result.success) {
          showAlert(alertPlaceholder, 'success', result.message);
          resetForm();
          fetchAccounts();
        } else {
          showAlert(formAlertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error saving account:', error);
        saveBtn.disabled = false;
        saveBtn.textContent = id ? 'Update Account' : 'Save Account';
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

    var token = accountTokenInput.value;
    var formData = new URLSearchParams();
    formData.append('id', activeDeleteId);
    formData.append('token', token);

    confirmOverlay.style.display = 'none';
    
    fetch('accounts_api.php?action=delete', {
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
        fetchAccounts();
      } else {
        showAlert(alertPlaceholder, 'error', result.message);
      }
      activeDeleteId = null;
    })
    .catch(function (error) {
      console.error('Error deleting account:', error);
      showAlert(alertPlaceholder, 'error', 'An error occurred while deleting.');
      activeDeleteId = null;
    });
  });

  if (cancelBtn) {
    cancelBtn.addEventListener('click', resetForm);
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      fetchAccounts();
      showAlert(alertPlaceholder, 'success', 'Accounts list reloaded.');
    });
  }

  function formatMoney(amount) {
    return '$ ' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
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

  fetchAccounts();
});
