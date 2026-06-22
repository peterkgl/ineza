document.addEventListener('DOMContentLoaded', function () {
  var currencyForm = document.getElementById('currencyForm');
  var currenciesList = document.getElementById('currenciesList');
  var alertPlaceholder = document.getElementById('alertPlaceholder');
  var formAlertPlaceholder = document.getElementById('formAlertPlaceholder');
  var formTitle = document.getElementById('formTitle');
  var currencyIdInput = document.getElementById('currencyId');
  var currencyTokenInput = document.getElementById('currencyToken');
  var cancelBtn = document.getElementById('cancelBtn');
  var saveBtn = document.getElementById('saveBtn');
  var refreshBtn = document.getElementById('refreshBtn');

  var confirmOverlay = document.getElementById('confirmOverlay');
  var confirmBody = document.getElementById('confirmBody');
  var confirmCancelBtn = document.getElementById('confirmCancelBtn');
  var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  
  var activeDeleteId = null;
  var allCurrencies = [];
  var searchInput = document.getElementById('searchInput');

  var currentPage = 1;
  var itemsPerPage = 25;
  var tableContainer = document.getElementById('currenciesTable').parentElement;
  var paginationContainer = document.createElement('div');
  paginationContainer.className = 'table-pagination';
  tableContainer.parentNode.insertBefore(paginationContainer, tableContainer.nextSibling);

  function getFilteredCurrencies() {
    var query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    if (!query) return allCurrencies;
    return allCurrencies.filter(function (curr) {
      var codeVal = (curr.code || '').toLowerCase();
      var nameVal = (curr.name || '').toLowerCase();
      var symbolVal = (curr.symbol || '').toLowerCase();
      var baseVal = (curr.is_base_currency === 1 ? 'reporting base' : 'secondary').toLowerCase();
      var statusVal = (curr.is_active === 1 ? 'active' : 'inactive').toLowerCase();
      return codeVal.indexOf(query) !== -1 ||
             nameVal.indexOf(query) !== -1 ||
             symbolVal.indexOf(query) !== -1 ||
             baseVal.indexOf(query) !== -1 ||
             statusVal.indexOf(query) !== -1;
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      currentPage = 1;
      renderCurrencies();
    });
  }

  var isFirstLoad = true;
  function fetchCurrencies() {
    if (isFirstLoad && window.initialCurrenciesData) {
      isFirstLoad = false;
      allCurrencies = window.initialCurrenciesData;
      renderCurrencies();
      updateStats(allCurrencies);
      return;
    }
    isFirstLoad = false;
    fetch('currencies_api.php?action=list')
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (result.success) {
          allCurrencies = result.data;
          renderCurrencies();
          updateStats(result.data);
          if (result.token) {
            updateToken(result.token);
          }
        } else {
          showAlert(alertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error fetching currencies:', error);
        showAlert(alertPlaceholder, 'error', 'An error occurred while loading currencies.');
      });
  }

  function renderCurrencies() {
    var filtered = getFilteredCurrencies();
    var totalItems = filtered.length;
    var totalPages = Math.ceil(totalItems / itemsPerPage);
    
    if (currentPage > totalPages) {
      currentPage = Math.max(1, totalPages);
    }
    
    if (totalItems === 0) {
      currenciesList.innerHTML = '<tr><td colspan="7" class="table-empty">' + (searchInput && searchInput.value.trim() ? 'No matching currencies found.' : 'No currencies registered yet.') + '</td></tr>';
      renderPagination(totalItems, totalPages);
      return;
    }

    var startIndex = (currentPage - 1) * itemsPerPage;
    var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
    var paginated = filtered.slice(startIndex, endIndex);

    var html = '';
    paginated.forEach(function (curr, index) {
      var globalIndex = startIndex + index + 1;
      var baseLabel = curr.is_base_currency === 1
        ? '<span class="status-pill pill-green" style="font-weight:600;">Reporting Base</span>'
        : '<span class="status-pill" style="background:var(--bg); color:var(--text3)">Secondary</span>';
      
      var activeLabel = curr.is_active === 1
        ? '<span class="status-pill pill-green">Active</span>'
        : '<span class="status-pill pill-red">Inactive</span>';

      html += '<tr>' +
        '<td>' + globalIndex + '</td>' +
        '<td><span class="code-badge">' + escapeHtml(curr.code) + '</span></td>' +
        '<td class="td-name">' + escapeHtml(curr.name) + '</td>' +
        '<td>' + (curr.symbol ? escapeHtml(curr.symbol) : '—') + '</td>' +
        '<td>' + baseLabel + '</td>' +
        '<td>' + activeLabel + '</td>' +
        '<td style="text-align: right;">' +
          '<div class="action-buttons" style="justify-content: flex-end;">' +
            '<button class="btn-icon-only edit" title="Edit Currency" data-id="' + curr.id + '">' +
              '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' +
            '</button>' +
            '<button class="btn-icon-only delete" title="Delete Currency" data-id="' + curr.id + '">' +
              '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>' +
            '</button>' +
          '</div>' +
        '</td>' +
      '</tr>';
    });

    currenciesList.innerHTML = html;
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
          renderCurrencies();
        }
      });
    });
  }

  function updateStats(currencies) {
    var total = currencies.length;
    var active = currencies.filter(function (c) { return c.is_active === 1; }).length;
    var baseObj = currencies.find(function (c) { return c.is_base_currency === 1; });
    var baseText = baseObj ? baseObj.code : 'None';
    var inactive = total - active;

    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-active').textContent = active;
    document.getElementById('stat-base').textContent = baseText;
    document.getElementById('stat-inactive').textContent = inactive;
  }

  function updateToken(token) {
    if (currencyTokenInput) {
      currencyTokenInput.value = token;
    }
  }

  function attachRowEventListeners(currencies) {
    var editButtons = document.querySelectorAll('.action-buttons .edit');
    var deleteButtons = document.querySelectorAll('.action-buttons .delete');

    editButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var curr = currencies.find(function (c) { return c.id === id; });
        if (curr) {
          setEditMode(curr);
        }
      });
    });

    deleteButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var curr = currencies.find(function (c) { return c.id === id; });
        if (curr) {
          activeDeleteId = id;
          confirmBody.textContent = 'Are you sure you want to delete the currency "' + curr.name + ' (' + curr.code + ')"? This will fail if the currency is used in advances or exchange rates.';
          confirmOverlay.style.display = 'flex';
        }
      });
    });
  }

  function setEditMode(curr) {
    if (!currencyForm) return;
    
    currencyIdInput.value = curr.id;
    document.getElementById('currencyCode').value = curr.code;
    document.getElementById('currencyCode').setAttribute('readonly', 'true');
    document.getElementById('currencyCode').style.opacity = '0.7';

    document.getElementById('currencyName').value = curr.name;
    document.getElementById('currencySymbol').value = curr.symbol || '';
    document.getElementById('currencyIsBase').checked = curr.is_base_currency === 1;
    document.getElementById('currencyIsActive').checked = curr.is_active === 1;

    formTitle.textContent = 'Edit Currency: ' + curr.code;
    saveBtn.textContent = 'Update Currency';
    cancelBtn.style.display = 'inline-block';
    
    if (window.innerWidth <= 992) {
      document.getElementById('formCard').scrollIntoView({ behavior: 'smooth' });
    }
  }

  function resetForm() {
    if (!currencyForm) return;
    currencyForm.reset();
    currencyIdInput.value = '';
    
    var codeInput = document.getElementById('currencyCode');
    codeInput.removeAttribute('readonly');
    codeInput.style.opacity = '1';

    formTitle.textContent = 'Add New Currency';
    saveBtn.textContent = 'Save Currency';
    cancelBtn.style.display = 'none';
    formAlertPlaceholder.innerHTML = '';
  }

  if (currencyForm) {
    currencyForm.addEventListener('submit', function (e) {
      e.preventDefault();

      var id = currencyIdInput.value;
      var code = document.getElementById('currencyCode').value.trim();
      var name = document.getElementById('currencyName').value.trim();
      var symbol = document.getElementById('currencySymbol').value.trim();
      var isBase = document.getElementById('currencyIsBase').checked ? '1' : '0';
      var isActive = document.getElementById('currencyIsActive').checked ? '1' : '0';
      var token = currencyTokenInput.value;

      if (!code || !name) {
        showAlert(formAlertPlaceholder, 'error', 'Currency code and name are required.');
        return;
      }

      var action = id ? 'update' : 'create';
      
      var formData = new URLSearchParams();
      if (id) formData.append('id', id);
      formData.append('code', code);
      formData.append('name', name);
      formData.append('symbol', symbol);
      formData.append('is_base_currency', isBase);
      formData.append('is_active', isActive);
      formData.append('token', token);

      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';

      fetch('currencies_api.php?action=' + action, {
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
        saveBtn.textContent = id ? 'Update Currency' : 'Save Currency';

        if (result.token) {
          updateToken(result.token);
        }

        if (result.success) {
          showAlert(alertPlaceholder, 'success', result.message);
          resetForm();
          fetchCurrencies();
        } else {
          showAlert(formAlertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error saving currency:', error);
        saveBtn.disabled = false;
        saveBtn.textContent = id ? 'Update Currency' : 'Save Currency';
        showAlert(formAlertPlaceholder, 'error', 'An error occurred while saving the currency.');
      });
    });
  }

  confirmCancelBtn.addEventListener('click', function () {
    confirmOverlay.style.display = 'none';
    activeDeleteId = null;
  });

  confirmDeleteBtn.addEventListener('click', function () {
    if (!activeDeleteId) return;

    var token = currencyTokenInput.value;
    var formData = new URLSearchParams();
    formData.append('id', activeDeleteId);
    formData.append('token', token);

    confirmOverlay.style.display = 'none';
    
    fetch('currencies_api.php?action=delete', {
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
        fetchCurrencies();
      } else {
        showAlert(alertPlaceholder, 'error', result.message);
      }
      activeDeleteId = null;
    })
    .catch(function (error) {
      console.error('Error deleting currency:', error);
      showAlert(alertPlaceholder, 'error', 'An error occurred while deleting the currency.');
      activeDeleteId = null;
    });
  });

  if (cancelBtn) {
    cancelBtn.addEventListener('click', resetForm);
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      fetchCurrencies();
      showAlert(alertPlaceholder, 'success', 'Currencies list reloaded.');
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

  fetchCurrencies();
});
